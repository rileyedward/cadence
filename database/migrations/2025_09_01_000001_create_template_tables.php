<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('forked_from_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('template_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->string('scope'); // TemplateScope enum
            $table->json('days_of_week')->nullable(); // array 0-6 for custom
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->integer('priority')->default(0);
            $table->timestamps();
        });

        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('flexibility_mode'); // FlexibilityMode enum
            $table->string('category')->nullable();
            $table->integer('priority')->default(0);
            $table->json('constraints')->nullable(); // { minDuration, maxDuration, minRestAfter, noOverlap }
            $table->json('context')->nullable();     // { location, mobility, social }
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('block_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained('blocks')->cascadeOnDelete();
            $table->foreignId('depends_on_block_id')->constrained('blocks')->cascadeOnDelete();
            $table->string('type')->default('after'); // after | requires
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_dependencies');
        Schema::dropIfExists('blocks');
        Schema::dropIfExists('template_assignments');
        Schema::dropIfExists('templates');
    }
};
