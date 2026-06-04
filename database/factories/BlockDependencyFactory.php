<?php

namespace Database\Factories;

use App\Models\Block;
use App\Models\BlockDependency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockDependency>
 */
class BlockDependencyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'block_id' => Block::factory(),
            'depends_on_block_id' => Block::factory(),
            'type' => 'after',
        ];
    }
}
