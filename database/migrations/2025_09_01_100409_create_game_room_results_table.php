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
        Schema::create('game_room_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_room_id')->constrained()->onDelete('cascade');
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->text('user_answer');
            $table->boolean('is_correct');
            $table->integer('time_taken')->nullable(); // in seconds
            $table->integer('score_earned')->default(0);
            $table->integer('round_number');
            $table->timestamp('answered_at');
            $table->timestamps();

            $table->index(['game_room_id', 'round_number']);
            $table->index(['member_id', 'game_room_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_room_results');
    }
};
