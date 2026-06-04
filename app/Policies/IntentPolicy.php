<?php

namespace App\Policies;

use App\Models\Intent;
use App\Models\User;

class IntentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** System intents (user_id null) are visible to everyone; customs only to their owner. */
    public function view(User $user, Intent $intent): bool
    {
        return $intent->user_id === null || $intent->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /** Only custom (owned) intents may be edited; system rows are clone-on-edit. */
    public function update(User $user, Intent $intent): bool
    {
        return $intent->user_id === $user->id;
    }

    public function delete(User $user, Intent $intent): bool
    {
        return $intent->user_id === $user->id;
    }
}
