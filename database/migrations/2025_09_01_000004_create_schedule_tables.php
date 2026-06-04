<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_plan_id')->constrained('daily_plans')->cascadeOnDelete();
            $table->integer('version')->default(1);
            $table->dateTime('generated_at');
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->index(['daily_plan_id', 'is_current']);
        });

        Schema::create('schedule_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('source_block_id')->nullable()->constrained('daily_plan_blocks')->nullOnDelete();
            $table->string('type'); // EventType enum
            $table->string('label');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('order')->default(0);
            $table->json('metadata')->nullable(); // intents, energy, flags (restInserted, overflow, ...)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_events');
        Schema::dropIfExists('schedules');
    }
};
