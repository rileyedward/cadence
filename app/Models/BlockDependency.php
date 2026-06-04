<?php

namespace App\Models;

use Database\Factories\BlockDependencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockDependency extends Model
{
    /** @use HasFactory<BlockDependencyFactory> */
    use HasFactory;

    protected $fillable = [
        'block_id',
        'depends_on_block_id',
        'type',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(Block::class, 'depends_on_block_id');
    }
}
