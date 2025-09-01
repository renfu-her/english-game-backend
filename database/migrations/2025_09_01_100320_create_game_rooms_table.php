<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 6)->unique();
            $table->enum('status', ['waiting', 'playing', 'finished', 'paused'])->default('waiting');
            $table->integer('max_players')->default(6);
            $table->integer('current_players')->default(0);
            $table->foreignId('owner_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('current_question_id')->nullable()->constrained('questions')->onDelete('set null');
            $table->integer('current_round')->default(0);
            $table->integer('total_rounds')->default(10);
            $table->integer('time_per_question')->default(30); // seconds
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_rooms');
    }
};
