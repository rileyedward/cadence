<?php

use App\Models\Block;
use App\Models\Intent;
use App\Models\Template;
use App\Models\User;

it('creates a block with default intents', function () {
    $user = User::factory()->create();
    $template = Template::factory()->for($user)->create();
    $intent = Intent::factory()->for($user)->create();

    $this->actingAs($user)->post(route('blocks.store', $template), [
        'name' => 'Deep Work',
        'start_time' => '08:30',
        'end_time' => '16:00',
        'flexibility_mode' => 'strict',
        'default_intents' => [['intent_id' => $intent->id, 'weight' => 2]],
    ])->assertRedirect();

    $block = $template->blocks()->firstOrFail();
    expect($block->name)->toBe('Deep Work')
        ->and($block->defaultIntents()->first()->pivot->weight)->toBe(2);
});

it('allows blocks that wrap past midnight', function () {
    $user = User::factory()->create();
    $template = Template::factory()->for($user)->create();

    $this->actingAs($user)->post(route('blocks.store', $template), [
        'name' => 'Freedom',
        'start_time' => '20:00',
        'end_time' => '01:30',
        'flexibility_mode' => 'adaptive',
    ])->assertRedirect()->assertSessionHasNoErrors();
});

it('reorders blocks', function () {
    $user = User::factory()->create();
    $template = Template::factory()->for($user)->create();
    $a = Block::factory()->for($template)->create(['order' => 0]);
    $b = Block::factory()->for($template)->create(['order' => 1]);
    $c = Block::factory()->for($template)->create(['order' => 2]);

    $this->actingAs($user)->post(route('blocks.reorder', $template), [
        'block_ids' => [$c->id, $a->id, $b->id],
    ])->assertRedirect();

    expect($c->fresh()->order)->toBe(0)
        ->and($a->fresh()->order)->toBe(1)
        ->and($b->fresh()->order)->toBe(2);
});

it('blocks cross-user block writes', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $template = Template::factory()->for($owner)->create();
    $block = Block::factory()->for($template)->create();

    $this->actingAs($other)->put(route('blocks.update', $block), [
        'name' => 'x', 'start_time' => '08:00', 'end_time' => '09:00', 'flexibility_mode' => 'soft',
    ])->assertForbidden();

    $this->actingAs($other)->delete(route('blocks.destroy', $block))->assertForbidden();
});
