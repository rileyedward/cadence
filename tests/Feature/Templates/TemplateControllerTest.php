<?php

use App\Models\Block;
use App\Models\Intent;
use App\Models\Template;
use App\Models\User;

it('lists only the current users templates', function () {
    $user = User::factory()->create();
    Template::factory()->for($user)->create(['name' => 'Mine']);
    Template::factory()->create(['name' => 'Theirs']);

    $this->actingAs($user)
        ->get(route('templates.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Templates/Index')
            ->has('templates', 1)
        );
});

it('creates a template and redirects to the editor', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('templates.store'), ['name' => 'Weekday', 'description' => 'desc'])
        ->assertRedirect();

    $this->assertDatabaseHas('templates', ['user_id' => $user->id, 'name' => 'Weekday']);
});

it('forks a template into an independent deep copy', function () {
    $user = User::factory()->create();
    $intent = Intent::factory()->for($user)->create();
    $template = Template::factory()->for($user)->create(['name' => 'Origin']);
    $block = Block::factory()->for($template)->create(['name' => 'Work']);
    $block->defaultIntents()->attach($intent->id, ['weight' => 3]);

    $this->actingAs($user)->post(route('templates.fork', $template))->assertRedirect();

    $copy = Template::where('name', 'Origin (copy)')->firstOrFail();
    expect($copy->forked_from_id)->toBe($template->id)
        ->and($copy->blocks)->toHaveCount(1);

    // Editing the copy must not touch the original.
    $copy->blocks()->first()->update(['name' => 'Changed']);
    expect($template->blocks()->first()->name)->toBe('Work');

    // Default intents carried over with weight.
    expect($copy->blocks()->first()->defaultIntents()->first()->pivot->weight)->toBe(3);
});

it('blocks cross-user access with 403', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $template = Template::factory()->for($owner)->create();

    $this->actingAs($other)->get(route('templates.edit', $template))->assertForbidden();
    $this->actingAs($other)->put(route('templates.update', $template), ['name' => 'x'])->assertForbidden();
    $this->actingAs($other)->delete(route('templates.destroy', $template))->assertForbidden();
    $this->actingAs($other)->post(route('templates.fork', $template))->assertForbidden();
});
