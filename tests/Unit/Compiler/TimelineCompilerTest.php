<?php

use App\Services\Compiler\BlockInput;
use App\Services\Compiler\CompileConfig;
use App\Services\Compiler\EventDraft;
use App\Services\Compiler\TimelineCompiler;

/**
 * @param  array<string,mixed>  $opts
 */
function bi(int $id, int $start, int $end, string $mode, array $opts = []): BlockInput
{
    return new BlockInput(
        id: $id,
        label: $opts['label'] ?? "B{$id}",
        startMin: $start,
        endMin: $end,
        mode: $mode,
        energy: $opts['energy'] ?? null,
        focus: $opts['focus'] ?? null,
        priority: $opts['priority'] ?? 0,
        order: $opts['order'] ?? $id,
        constraints: $opts['constraints'] ?? [],
        activityMinutes: $opts['activityMinutes'] ?? [],
        mobility: $opts['mobility'] ?? null,
        meta: $opts['meta'] ?? [],
    );
}

function compile(array $blocks, array $cfg = []): array
{
    $config = new CompileConfig(
        bufferMinutes: $cfg['buffer'] ?? 5,
        minRestMinutes: $cfg['minRest'] ?? 15,
        softToleranceMinutes: $cfg['softTol'] ?? 30,
        restAfterHighDeep: $cfg['restAfterHighDeep'] ?? true,
    );

    return (new TimelineCompiler)->compile($blocks, $config)->events;
}

/** @return array<int, EventDraft> */
function blocksOnly(array $events): array
{
    return array_values(array_filter($events, fn ($e) => $e->type === 'block'));
}

it('produces an empty schedule for no blocks', function () {
    expect(compile([]))->toBe([]);
});

it('inserts a buffer between two adjacent blocks', function () {
    $events = compile([
        bi(1, 0, 60, 'adaptive'),
        bi(2, 60, 120, 'adaptive'),
    ]);

    expect($events)->toHaveCount(3)
        ->and($events[0]->type)->toBe('block')
        ->and($events[1]->type)->toBe('buffer')
        ->and($events[1]->startMin)->toBe(60)
        ->and($events[1]->endMin)->toBe(65)
        ->and($events[2]->startMin)->toBe(65);
});

it('leaves large gaps as free time without a buffer', function () {
    $events = compile([
        bi(1, 0, 60, 'adaptive'),
        bi(2, 200, 260, 'adaptive'),
    ]);

    expect($events)->toHaveCount(2)
        ->and($events[1]->startMin)->toBe(200); // no buffer event, free time preserved
});

it('never moves a strict anchor and compresses an adaptive block before it', function () {
    $events = compile([
        bi(1, 0, 120, 'adaptive', ['activityMinutes' => [120]]),
        bi(2, 100, 160, 'strict'),
    ]);

    $strict = collect($events)->firstWhere('sourceBlockId', 2);
    $adaptive = collect($events)->firstWhere('sourceBlockId', 1);

    expect($strict->startMin)->toBe(100)
        ->and($strict->endMin)->toBe(160)
        ->and($adaptive->endMin)->toBeLessThanOrEqual(95); // compressed to leave a buffer
});

it('flags overflow when minDuration cannot fit before a strict anchor', function () {
    $events = compile([
        bi(1, 0, 200, 'adaptive', ['activityMinutes' => [200], 'constraints' => ['minDuration' => 80]]),
        bi(2, 50, 100, 'strict'),
    ]);

    $adaptive = collect($events)->firstWhere('sourceBlockId', 1);
    $strict = collect($events)->firstWhere('sourceBlockId', 2);

    expect($adaptive->metadata['overflow'] ?? false)->toBeTrue()
        ->and($adaptive->endMin)->toBe(50)        // truncated at the anchor
        ->and($strict->startMin)->toBe(50);       // strict never moves
});

it('inserts rest between two high+deep blocks', function () {
    $events = compile([
        bi(1, 0, 60, 'adaptive', ['energy' => 'high', 'focus' => 'deep']),
        bi(2, 60, 120, 'adaptive', ['energy' => 'high', 'focus' => 'deep']),
    ], ['minRest' => 15]);

    $rest = collect($events)->first(fn ($e) => ($e->metadata['restInserted'] ?? false) === true);

    expect($rest)->not->toBeNull()
        ->and($rest->endMin - $rest->startMin)->toBe(15)
        ->and($rest->startMin)->toBe(60);
});

it('shifts a soft block forward only within tolerance', function () {
    // Upstream adaptive overruns to minute 200; soft window starts at 80, tol 30 => clamps to 110.
    $events = compile([
        bi(1, 0, 80, 'adaptive', ['activityMinutes' => [200]]),
        bi(2, 80, 140, 'soft'),
    ], ['softTol' => 30, 'buffer' => 5]);

    $soft = collect($events)->firstWhere('sourceBlockId', 2);

    expect($soft->startMin)->toBeLessThanOrEqual(110)
        ->and($soft->startMin)->toBeGreaterThanOrEqual(80);
});

it('compiles the sample day with strict Work unmoved and all blocks present', function () {
    // Minutes-from-day_start (day_start 04:00) per the doc-07 worked example.
    $events = compile([
        bi(1, 180, 240, 'adaptive', ['label' => 'Morning']),
        bi(2, 240, 270, 'strict', ['label' => 'Commute Prep']),
        bi(3, 270, 720, 'strict', ['label' => 'Work']),
        bi(4, 720, 750, 'strict', ['label' => 'Commute Home']),
        bi(5, 750, 840, 'adaptive', ['label' => 'Recovery', 'activityMinutes' => [120]]),
        bi(6, 840, 960, 'soft', ['label' => 'Activation']),
        bi(7, 960, 1290, 'adaptive', ['label' => 'Freedom']),
        bi(8, 1290, 1320, 'adaptive', ['label' => 'Wind Down']),
    ]);

    $work = collect($events)->firstWhere('sourceBlockId', 3);
    expect($work->startMin)->toBe(270)
        ->and($work->endMin)->toBe(720)
        ->and(blocksOnly($events))->toHaveCount(8);

    // Events are emitted in ascending start order and never exceed the day length.
    $starts = array_map(fn ($e) => $e->startMin, $events);
    $sorted = $starts;
    sort($sorted);
    expect($starts)->toBe($sorted)
        ->and(max(array_map(fn ($e) => $e->endMin, $events)))->toBeLessThanOrEqual(1440);
});
