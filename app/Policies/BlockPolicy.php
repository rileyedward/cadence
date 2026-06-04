<?php

namespace App\Policies;

use App\Models\Block;
use App\Models\User;

/**
 * Blocks are owned transitively through their template.
 */
class BlockPolicy
{
    public function view(User $user, Block $block): bool
    {
        return $block->template->user_id === $user->id;
    }

    public function update(User $user, Block $block): bool
    {
        return $block->template->user_id === $user->id;
    }

    public function delete(User $user, Block $block): bool
    {
        return $block->template->user_id === $user->id;
    }
}
