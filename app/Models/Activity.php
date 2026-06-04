<?php

namespace App\Models;

use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'default_duration_minutes',
        'tags',
        'plugin_key',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'default_duration_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function intents(): BelongsToMany
    {
        return $this->belongsToMany(Intent::class, 'intent_activity')
            ->withPivot('weight')
            ->withTimestamps();
    }

    public function isSystem(): bool
    {
        return $this->user_id === null;
    }

    /** System library (user_id null) plus the given user's custom activities. */
    public function scopeLibraryFor(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('user_id')->orWhere('user_id', $user->id));
    }
}
