<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\TemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    /** @use HasFactory<TemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_active',
        'forked_from_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function forkedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'forked_from_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class)->orderBy('order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TemplateAssignment::class);
    }

    /**
     * Resolve the highest-priority active template assigned to the given date for a user.
     * Matching order: explicit date-range custom/scope > weekday/weekend, then `priority` desc.
     */
    public static function resolveForDate(User $user, CarbonInterface $date): ?self
    {
        $dow = (int) $date->dayOfWeek; // 0 (Sun) .. 6 (Sat)
        $isWeekend = in_array($dow, [0, 6], true);
        $dateStr = $date->toDateString();

        $assignments = TemplateAssignment::query()
            ->whereHas('template', fn ($q) => $q->where('user_id', $user->id)->where('is_active', true))
            ->with('template')
            ->get();

        $matches = $assignments->filter(function (TemplateAssignment $a) use ($dow, $isWeekend, $dateStr): bool {
            if ($a->starts_on !== null && $dateStr < $a->starts_on->toDateString()) {
                return false;
            }
            if ($a->ends_on !== null && $dateStr > $a->ends_on->toDateString()) {
                return false;
            }

            return match ($a->scope->value) {
                'weekday' => ! $isWeekend,
                'weekend' => $isWeekend,
                'custom' => in_array($dow, array_map('intval', $a->days_of_week ?? []), true),
                default => false,
            };
        });

        $best = $matches->sortByDesc(fn (TemplateAssignment $a) => $a->priority)->first();

        return $best?->template;
    }
}
