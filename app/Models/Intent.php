<?php

namespace App\Models;

use Database\Factories\IntentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Intent extends Model
{
    /** @use HasFactory<IntentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'color',
        'icon',
        'description',
        'plugin_key',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Suggested activities for this intent, weighted. */
    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'intent_activity')
            ->withPivot('weight')
            ->withTimestamps()
            ->orderByPivot('weight', 'desc');
    }

    public function isSystem(): bool
    {
        return $this->user_id === null;
    }

    /** System library (user_id null) plus the given user's custom intents. */
    public function scopeLibraryFor(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('user_id')->orWhere('user_id', $user->id));
    }
}
