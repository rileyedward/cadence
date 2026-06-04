<?php

use App\Models\Intent;
use App\Models\User;

it('shows system and own intents but not other users customs', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Intent::factory()->system()->create(['name' => 'Recovery', 'slug' => 'recovery']);
    Intent::factory()->for($user)->create(['name' => 'Mine', 'slug' => 'mine']);
    Intent::factory()->for($other)->create(['name' => 'Theirs', 'slug' => 'theirs']);

    $this->actingAs($user)
        ->get(route('intents.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Intents/Index')->has('intents', 2));
});

it('creates a custom intent with a unique slug', function () {
    $user = User::factory()->create();
    Intent::factory()->for($user)->create(['name' => 'Focus', 'slug' => 'focus']);

    $this->actingAs($user)->post(route('intents.store'), ['name' => 'Focus'])->assertRedirect();

    expect(Intent::where('user_id', $user->id)->pluck('slug')->all())
        ->toContain('focus', 'focus-2');
});

it('forbids editing a system intent but allows cloning it', function () {
    $user = User::factory()->create();
    $system = Intent::factory()->system()->create(['name' => 'Deep Work', 'slug' => 'deep-work']);

    $this->actingAs($user)
        ->put(route('intents.update', $system), ['name' => 'Hacked'])
        ->assertForbidden();

    $this->actingAs($user)->post(route('intents.clone', $system))->assertRedirect();

    expect(Intent::where('user_id', $user->id)->where('name', 'Deep Work')->exists())->toBeTrue();
});

it('lets a user update and delete their own intent', function () {
    $user = User::factory()->create();
    $intent = Intent::factory()->for($user)->create(['name' => 'Old']);

    $this->actingAs($user)->put(route('intents.update', $intent), ['name' => 'New'])->assertRedirect();
    expect($intent->fresh()->name)->toBe('New');

    $this->actingAs($user)->delete(route('intents.destroy', $intent))->assertRedirect();
    expect(Intent::find($intent->id))->toBeNull();
});
