<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('google');
            $table->text('access_token')->nullable();  // encrypted
            $table->text('refresh_token')->nullable();  // encrypted
            $table->dateTime('expires_at')->nullable();
            $table->string('calendar_id')->default('primary');
            $table->timestamps();

            $table->unique(['user_id', 'provider']);
        });

        // Imported busy events the compiler treats as strict-like anchors (doc 15).
        Schema::create('calendar_busy_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('source_uid');
            $table->string('title')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->unique(['user_id', 'source_uid', 'date']);
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_busy_events');
        Schema::dropIfExists('calendar_connections');
    }
};
