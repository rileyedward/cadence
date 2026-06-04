<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->date('date');
            $table->string('status')->default('draft'); // PlanStatus enum
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });

        Schema::create('daily_plan_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_plan_id')->constrained('daily_plans')->cascadeOnDelete();
            $table->foreignId('block_id')->nullable()->constrained('blocks')->nullOnDelete();
            // Snapshot of the source block so later template edits never mutate this day.
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('flexibility_mode'); // FlexibilityMode enum
            $table->foreignId('intent_id')->nullable()->constrained('intents')->nullOnDelete();
            $table->foreignId('secondary_intent_id')->nullable()->constrained('intents')->nullOnDelete();
            $table->string('energy_level')->nullable();        // EnergyLevel enum
            $table->string('focus_intensity')->nullable();     // FocusIntensity enum
            $table->string('social_context')->nullable();      // SocialContext enum
            $table->string('mobility_preference')->nullable(); // MobilityPref enum
            $table->json('constraints')->nullable();
            $table->json('context_tags')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('daily_plan_block_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_plan_block_id')->constrained('daily_plan_blocks')->cascadeOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->integer('order')->default(0);
            $table->integer('estimated_minutes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_plan_block_activities');
        Schema::dropIfExists('daily_plan_blocks');
        Schema::dropIfExists('daily_plans');
    }
};
