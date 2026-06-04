<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); // null = system library
            $table->string('name');
            $table->string('slug');
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->string('plugin_key')->nullable(); // provenance for plugin-provided intents (doc 14)
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); // null = system library
            $table->string('name');
            $table->integer('default_duration_minutes')->default(30);
            $table->json('tags')->nullable();
            $table->string('plugin_key')->nullable();
            $table->timestamps();
        });

        Schema::create('intent_activity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intent_id')->constrained('intents')->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->integer('weight')->default(1);
            $table->timestamps();

            $table->unique(['intent_id', 'activity_id']);
        });

        Schema::create('block_default_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained('blocks')->cascadeOnDelete();
            $table->foreignId('intent_id')->constrained('intents')->cascadeOnDelete();
            $table->integer('weight')->default(1);
            $table->timestamps();

            $table->unique(['block_id', 'intent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_default_intents');
        Schema::dropIfExists('intent_activity');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('intents');
    }
};
