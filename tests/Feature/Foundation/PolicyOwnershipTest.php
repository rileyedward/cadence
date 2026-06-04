<?php

use App\Models\Activity;
use App\Models\Intent;
use App\Models\Template;
use App\Models\User;

it('denies viewing another users template', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $template = Template::factory()->for($owner)->create();

    expect($other->can('view', $template))->toBeFalse()
        ->and($owner->can('view', $template))->toBeTrue();
});

it('denies updating another users template', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $template = Template::factory()->for($owner)->create();

    expect($other->can('update', $template))->toBeFalse()
        ->and($other->can('delete', $template))->toBeFalse();
});

it('treats system intents as read-only and customs as owner-only', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();

    $system = Intent::factory()->system()->create();
    $custom = Intent::factory()->for($a)->create();

    // Everyone can view system intents; nobody can edit them.
    expect($a->can('view', $system))->toBeTrue()
        ->and($a->can('update', $system))->toBeFalse()
        ->and($b->can('update', $system))->toBeFalse();

    // Customs are owner-only for writes and views.
    expect($a->can('update', $custom))->toBeTrue()
        ->and($b->can('update', $custom))->toBeFalse()
        ->and($b->can('view', $custom))->toBeFalse();
});

it('treats system activities as read-only and customs as owner-only', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();

    $system = Activity::factory()->create(['user_id' => null]);
    $custom = Activity::factory()->for($a)->create();

    expect($a->can('update', $system))->toBeFalse()
        ->and($a->can('update', $custom))->toBeTrue()
        ->and($b->can('update', $custom))->toBeFalse();
});
