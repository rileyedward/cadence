<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\DailyPlanController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\IntentController;
use App\Http\Controllers\RuntimeController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TemplateAssignmentController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TodayController;
use App\Http\Controllers\WeekController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Cadence primary destinations.
    Route::get('today', [TodayController::class, 'index'])->name('today');
    Route::get('week', [WeekController::class, 'index'])->name('week');
    Route::get('insights', [HistoryController::class, 'index'])->name('insights');

    // Daily plans / instantiation (doc 06). `generate` is wired in Phase 3.
    Route::get('plans', [DailyPlanController::class, 'index'])->name('plans.index');
    Route::post('plans', [DailyPlanController::class, 'store'])->name('plans.store');
    Route::get('plans/{plan}', [DailyPlanController::class, 'show'])->name('plans.show');
    Route::put('plans/{plan}', [DailyPlanController::class, 'update'])->name('plans.update');
    Route::delete('plans/{plan}', [DailyPlanController::class, 'destroy'])->name('plans.destroy');

    // Compiler (doc 07): authoritative generate + non-persisting live preview.
    Route::post('plans/{plan}/generate', [ScheduleController::class, 'generate'])->name('plans.generate');
    Route::post('plans/{plan}/preview', [ScheduleController::class, 'preview'])->name('plans.preview');

    // Check-ins (doc 09).
    Route::post('plan-blocks/{block}/check-in', [CheckInController::class, 'store'])->name('check-ins.store');
    Route::delete('check-ins/{checkIn}', [CheckInController::class, 'destroy'])->name('check-ins.destroy');

    // Runtime adjustments (doc 08): one op per request, server-authoritative reflow.
    Route::post('plans/{plan}/runtime/{op}', [RuntimeController::class, 'handle'])->name('plans.runtime');

    // Intelligence (doc 10): local, deterministic suggestions over the user's history.
    Route::get('plans/{plan}/insights', [InsightController::class, 'forPlan'])->name('plans.insights');
    Route::get('insights/data', [InsightController::class, 'range'])->name('insights.data');

    // Google Calendar (doc 15). Works with no provider configured (null driver).
    Route::get('settings/calendar', [CalendarController::class, 'settings'])->name('calendar.settings');
    Route::get('calendar/connect', [CalendarController::class, 'connect'])->name('calendar.connect');
    Route::get('calendar/callback', [CalendarController::class, 'callback'])->name('calendar.callback');
    Route::post('calendar/import', [CalendarController::class, 'import'])->name('calendar.import');
    Route::delete('calendar/disconnect', [CalendarController::class, 'disconnect'])->name('calendar.disconnect');
    Route::post('plans/{plan}/calendar/push', [CalendarController::class, 'push'])->name('calendar.push');

    // Intents & activities library (doc 05).
    Route::get('intents', [IntentController::class, 'index'])->name('intents.index');
    Route::post('intents', [IntentController::class, 'store'])->name('intents.store');
    Route::put('intents/{intent}', [IntentController::class, 'update'])->name('intents.update');
    Route::delete('intents/{intent}', [IntentController::class, 'destroy'])->name('intents.destroy');
    Route::post('intents/{intent}/clone', [IntentController::class, 'clone'])->name('intents.clone');

    Route::get('activities', [ActivityController::class, 'index'])->name('activities.index');
    Route::post('activities', [ActivityController::class, 'store'])->name('activities.store');
    Route::put('activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
    Route::delete('activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

    // Templates, blocks, assignments (doc 04).
    Route::get('templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::post('templates', [TemplateController::class, 'store'])->name('templates.store');
    Route::get('templates/{template}/edit', [TemplateController::class, 'edit'])->name('templates.edit');
    Route::put('templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
    Route::delete('templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');
    Route::post('templates/{template}/fork', [TemplateController::class, 'fork'])->name('templates.fork');

    Route::post('templates/{template}/blocks', [BlockController::class, 'store'])->name('blocks.store');
    Route::post('templates/{template}/blocks/reorder', [BlockController::class, 'reorder'])->name('blocks.reorder');
    Route::put('blocks/{block}', [BlockController::class, 'update'])->name('blocks.update');
    Route::delete('blocks/{block}', [BlockController::class, 'destroy'])->name('blocks.destroy');

    Route::post('templates/{template}/assignments', [TemplateAssignmentController::class, 'store'])->name('assignments.store');
    Route::put('templates/{template}/assignments/{assignment}', [TemplateAssignmentController::class, 'update'])->name('assignments.update');
    Route::delete('templates/{template}/assignments/{assignment}', [TemplateAssignmentController::class, 'destroy'])->name('assignments.destroy');
});

require __DIR__.'/settings.php';
