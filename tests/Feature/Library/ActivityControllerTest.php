<?php

use App\Models\Activity;
use App\Models\Intent;
use App\Models\User;

it('creates a custom activity linked to intents', function () {
    $user = User::factory()->create();
    $intent = Intent::factory()->for($user)->create();

    $this->actingAs($user)->post(route('activities.store'), [
        'name' => 'Coding',
        'default_duration_minutes' => 120,
        'tags' => ['focus'],
        'intent_ids' => [$intent->id],
    ])->assertRedirect();

    $activity = Activity::where('user_id', $user->id)->firstOrFail();
    expect($activity->default_duration_minutes)->toBe(120)
        ->and($activity->intents()->pluck('intents.id')->all())->toContain($intent->id);
});

it('forbids editing a system activity', function () {
    $user = User::factory()->create();
    $system = Activity::factory()->create(['user_id' => null]);

    $this->actingAs($user)->put(route('activities.update', $system), [
        'name' => 'x', 'default_duration_minutes' => 10,
    ])->assertForbidden();
});

it('lets a user delete their own activity', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->create();

    $this->actingAs($user)->delete(route('activities.destroy', $activity))->assertRedirect();
    expect(Activity::find($activity->id))->toBeNull();
});
