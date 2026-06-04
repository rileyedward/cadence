<?php

namespace App\Services\Compiler;

/**
 * Deterministic timeline compiler (doc 07). Pure: integer-minute math only, no
 * clock reads, stable sort with explicit tie-breakers. Input is a list of
 * BlockInput (minutes-from-day_start); output is ordered EventDraft[].
 *
 * Placement rules:
 *  - strict   : fixed to its window; a hard anchor that never moves.
 *  - soft     : starts at its window start; may shift later up to soft tolerance.
 *  - adaptive : starts at max(cursor, windowStart); reflows and compresses first.
 * Buffers/rest gaps are inserted between close blocks; overflow into a strict
 * anchor compresses upstream flexible blocks (adaptive then soft), never strict.
 */
class TimelineCompiler
{
    /**
     * @param  array<int, BlockInput>  $blocks
     */
    public function compile(array $blocks, CompileConfig $cfg, int $cursorStart = 0): CompileResult
    {
        if ($blocks === []) {
            return new CompileResult([]);
        }

        // 1. Normalize & order: startMin asc, priority desc, order asc, id asc.
        usort($blocks, fn (BlockInput $a, BlockInput $b) => [$a->startMin, -$a->priority, $a->order, $a->id]
            <=> [$b->startMin, -$b->priority, $b->order, $b->id]);

        $strictStarts = array_map(
            fn (BlockInput $b) => $b->startMin,
            array_values(array_filter($blocks, fn (BlockInput $b) => $b->isStrict())),
        );

        /** @var array<int, array<string,mixed>> $placed */
        $placed = [];
        $warnings = [];

        foreach ($blocks as $i => $b) {
            if ($b->isStrict()) {
                $prev = $placed === [] ? null : $placed[count($placed) - 1];
                $placed[] = $this->placeStrict($b, $prev, $placed, $cfg);
            } else {
                $nextStrictStart = $this->nextStrictStartAfter($strictStarts, $b->startMin, $blocks, $i);
                $placed[] = $this->placeFlexible($b, $placed, $cursorStart, $nextStrictStart, $cfg, $warnings);
            }
        }

        return new CompileResult($this->emit($placed), $warnings);
    }

    /**
     * @param  array<string,mixed>|null  $prev
     * @param  array<int, array<string,mixed>>  $placed
     * @return array<string,mixed>
     */
    private function placeStrict(BlockInput $b, ?array $prev, array &$placed, CompileConfig $cfg): array
    {
        $gapBefore = 0;
        $gapType = 'buffer';

        if ($prev !== null) {
            // Reclaim time from upstream flexible blocks so the anchor keeps a buffer.
            $target = $b->startMin - $cfg->bufferMinutes; // desired previous end
            if ($prev['end'] > $target) {
                $need = $prev['end'] - $target;
                $this->reclaim($placed, $need, ['adaptive']);
                $this->reclaim($placed, $need, ['soft']);
            }

            $prevEnd = $placed[count($placed) - 1]['end'];
            $gap = $b->startMin - $prevEnd;

            if ($gap < 0) {
                // Couldn't fully reclaim: previous block truncates at the anchor.
                $idx = count($placed) - 1;
                $placed[$idx]['end'] = $b->startMin;
                $placed[$idx]['overflow'] = true;
                $gapBefore = 0;
            } elseif ($gap > 0 && $gap <= $cfg->bufferMinutes) {
                $gapBefore = $gap;
                $gapType = $this->contextChanged($prevInput = $this->inputOf($placed[count($placed) - 1]), $b) ? 'transition' : 'buffer';
            }
            // gap > buffer => free time, no event.
        }

        return [
            'input' => $b,
            'start' => $b->startMin,
            'end' => $b->endMin,
            'overflow' => false,
            'overran' => $b->estimatedMinutes() > $b->windowLength(),
            'gapBefore' => $gapBefore,
            'gapType' => $gapType,
            'restInserted' => false,
        ];
    }

    /**
     * @param  array<int, array<string,mixed>>  $placed
     * @param  array<int, string>  $warnings
     * @return array<string,mixed>
     */
    private function placeFlexible(
        BlockInput $b,
        array &$placed,
        int $cursorStart,
        ?int $nextStrictStart,
        CompileConfig $cfg,
        array &$warnings,
    ): array {
        $minDur = (int) ($b->constraints['minDuration'] ?? 0);
        $maxDur = (int) ($b->constraints['maxDuration'] ?? PHP_INT_MAX);
        $dur = max($minDur, min($maxDur, $b->estimatedMinutes()));
        $overran = $b->estimatedMinutes() > $b->windowLength();

        // Compute the start + leading gap from the current tail of $placed. Wrapped
        // in a closure so we can recompute after reclaiming time upstream.
        $computeStart = function () use (&$placed, $b, $cursorStart, $cfg): array {
            $prev = $placed === [] ? null : $placed[count($placed) - 1];
            $cursor = $prev === null ? $cursorStart : $prev['end'];

            if ($b->mode === 'soft') {
                $desired = $b->startMin;
                if ($cursor > $b->startMin) {
                    $desired = min($cursor, $b->startMin + $cfg->softToleranceMinutes);
                }
            } else { // adaptive
                $desired = max($cursor, $b->startMin);
            }

            $gapBefore = 0;
            $gapType = 'buffer';
            $restInserted = false;

            if ($prev !== null) {
                $prevInput = $this->inputOf($prev);
                $naturalGap = $desired - $prev['end'];
                $base = $cfg->bufferMinutes;
                $rest = $cfg->minRestMinutes;
                $minRestAfter = (int) ($prevInput->constraints['minRestAfter'] ?? 0);

                $required = 0;
                if ($cfg->restAfterHighDeep && $prevInput->isHighDeep() && $b->isHighDeep() && $naturalGap < $rest) {
                    $required = $rest;
                    $restInserted = true;
                } elseif ($naturalGap < $base) {
                    $required = $base;
                }
                if ($minRestAfter > $required && $naturalGap < $minRestAfter) {
                    $required = $minRestAfter;
                    $restInserted = $restInserted || $minRestAfter >= $rest;
                }

                if ($required > 0 && $naturalGap < $required) {
                    $desired = $prev['end'] + $required;
                    $gapBefore = $required;
                    $gapType = $this->contextChanged($prevInput, $b) ? 'transition' : 'buffer';
                }
            }

            return ['start' => $desired, 'gapBefore' => $gapBefore, 'gapType' => $gapType, 'restInserted' => $restInserted];
        };

        $r = $computeStart();

        // A soft block may only shift forward within tolerance. If buffers push it
        // past that, reclaim time from upstream adaptive (then soft) blocks (doc 07).
        if ($b->mode === 'soft') {
            $maxStart = $b->startMin + $cfg->softToleranceMinutes;
            if ($r['start'] > $maxStart) {
                $need = $r['start'] - $maxStart;
                $got = $this->reclaim($placed, $need, ['adaptive']);
                if ($got < $need) {
                    $this->reclaim($placed, $need - $got, ['soft']);
                }
                $r = $computeStart();
                if ($r['start'] > $maxStart) {
                    $r['start'] = $maxStart;
                    $warnings[] = "Soft block {$b->id} exceeded its tolerance.";
                }
            }
        }

        $start = $r['start'];
        $gapBefore = $r['gapBefore'];
        $gapType = $r['gapType'];
        $restInserted = $r['restInserted'];
        $end = $start + $dur;
        $overflow = false;

        // Compress / truncate if we'd run into the next strict anchor.
        if ($nextStrictStart !== null) {
            $effectiveLimit = $nextStrictStart - $cfg->bufferMinutes; // leave room for a buffer
            if ($end > $effectiveLimit) {
                $allowed = max($minDur, $effectiveLimit - $start);
                $end = $start + $allowed;
                if ($end > $nextStrictStart) {
                    $end = $nextStrictStart;
                    $overflow = true;
                    $warnings[] = "Block {$b->id} overflows into a strict anchor.";
                }
            }
        }

        // Never run past the end of the logical day.
        if ($end > $cfg->dayLengthMinutes) {
            $end = $cfg->dayLengthMinutes;
            $overflow = true;
        }

        return [
            'input' => $b,
            'start' => $start,
            'end' => max($start, $end),
            'overflow' => $overflow,
            'overran' => $overran,
            'gapBefore' => $gapBefore,
            'gapType' => $gapType,
            'restInserted' => $restInserted,
        ];
    }

    /**
     * Reclaim minutes from the most-recent placed blocks of the given modes,
     * shrinking toward minDuration and shifting later blocks earlier. Stops at a
     * strict anchor (never shrinks strict).
     *
     * @param  array<int, array<string,mixed>>  $placed
     * @param  array<int, string>  $modes
     */
    private function reclaim(array &$placed, int $amount, array $modes): int
    {
        $reclaimed = 0;
        for ($i = count($placed) - 1; $i >= 0 && $reclaimed < $amount; $i--) {
            $input = $this->inputOf($placed[$i]);
            if ($input->isStrict()) {
                break;
            }
            if (! in_array($input->mode, $modes, true)) {
                continue;
            }
            $minDur = (int) ($input->constraints['minDuration'] ?? 0);
            $curDur = $placed[$i]['end'] - $placed[$i]['start'];
            $shrink = min($curDur - $minDur, $amount - $reclaimed);
            if ($shrink <= 0) {
                continue;
            }
            $placed[$i]['end'] -= $shrink;
            for ($j = $i + 1; $j < count($placed); $j++) {
                $placed[$j]['start'] -= $shrink;
                $placed[$j]['end'] -= $shrink;
            }
            $reclaimed += $shrink;
        }

        return $reclaimed;
    }

    /**
     * @param  array<int, int>  $strictStarts
     * @param  array<int, BlockInput>  $blocks
     */
    private function nextStrictStartAfter(array $strictStarts, int $fromStart, array $blocks, int $index): ?int
    {
        $next = null;
        for ($j = $index + 1, $n = count($blocks); $j < $n; $j++) {
            if ($blocks[$j]->isStrict()) {
                $next = $blocks[$j]->startMin;
                break;
            }
        }

        return $next;
    }

    /**
     * @param  array<int, array<string,mixed>>  $placed
     * @return array<int, EventDraft>
     */
    private function emit(array $placed): array
    {
        $events = [];

        foreach ($placed as $p) {
            /** @var BlockInput $input */
            $input = $p['input'];

            if ($p['gapBefore'] > 0) {
                $events[] = new EventDraft(
                    type: $p['gapType'],
                    label: $p['restInserted'] ? 'Rest' : ($p['gapType'] === 'transition' ? 'Transition' : 'Buffer'),
                    startMin: $p['start'] - $p['gapBefore'],
                    endMin: $p['start'],
                    metadata: array_filter(['restInserted' => $p['restInserted']]),
                );
            }

            $imported = (bool) ($input->meta['imported'] ?? false);

            $events[] = new EventDraft(
                type: 'block',
                label: $input->label,
                startMin: $p['start'],
                endMin: $p['end'],
                // Imported calendar anchors aren't real plan blocks (no FK target).
                sourceBlockId: $imported ? null : $input->id,
                metadata: array_filter([
                    'intent_id' => $input->meta['intent_id'] ?? null,
                    'energy' => $input->energy,
                    'focus' => $input->focus,
                    'imported' => $imported ?: null,
                    'overflow' => $p['overflow'] ?: null,
                    'overran' => $p['overran'] ?: null,
                ], fn ($v) => $v !== null),
            );
        }

        // Stable order by start, then blocks after their preceding gap.
        usort($events, fn (EventDraft $a, EventDraft $b) => [$a->startMin, $a->type === 'block' ? 1 : 0]
            <=> [$b->startMin, $b->type === 'block' ? 1 : 0]);

        return $events;
    }

    /**
     * @param  array<string,mixed>  $placed
     */
    private function inputOf(array $placed): BlockInput
    {
        return $placed['input'];
    }

    private function contextChanged(BlockInput $a, BlockInput $b): bool
    {
        return $a->mobility !== null && $b->mobility !== null && $a->mobility !== $b->mobility;
    }
}
