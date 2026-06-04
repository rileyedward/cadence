<?php

namespace App\Models;

use App\Enums\FlexibilityMode;
use Database\Factories\BlockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Block extends Model
{
    /** @use HasFactory<BlockFactory> */
    use HasFactory;

    protected $fillable = [
        'template_id',
        'name',
        'start_time',
        'end_time',
        'flexibility_mode',
        'category',
        'priority',
        'constraints',
        'context',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'flexibility_mode' => FlexibilityMode::class,
            'constraints' => 'array',
            'context' => 'array',
            'priority' => 'integer',
            'order' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /** Default intent options for this block, weighted. */
    public function defaultIntents(): BelongsToMany
    {
        return $this->belongsToMany(Intent::class, 'block_default_intents')
            ->withPivot('weight')
            ->withTimestamps()
            ->orderByPivot('weight', 'desc');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(BlockDependency::class);
    }
}
