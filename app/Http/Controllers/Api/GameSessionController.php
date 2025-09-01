<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\GameRoomResult;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameSessionController extends Controller
{
    /**
     * Get current game state for a room
     */
    public function getGameState(Request $request, $roomId)
    {
        $member = $request->user();
        $gameRoom = GameRoom::with(['currentQuestion', 'players.member'])->findOrFail($roomId);
        
        $player = $gameRoom->getPlayer($member);
        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in this game room.',
            ], 400);
        }

        $gameState = [
            'room' => [
                'id' => $gameRoom->id,
                'name' => $gameRoom->name,
                'status' => $gameRoom->status,
                'current_round' => $gameRoom->current_round,
                'total_rounds' => $gameRoom->total_rounds,
                'time_per_question' => $gameRoom->time_per_question,
                'started_at' => $gameRoom->started_at,
            ],
            'current_question' => $gameRoom->currentQuestion ? [
                'id' => $gameRoom->currentQuestion->id,
                'question_text' => $gameRoom->currentQuestion->question_text,
                'options' => $gameRoom->currentQuestion->options,
                'difficulty' => $gameRoom->currentQuestion->difficulty,
                // Don't send correct answer or explanation during active gameplay
            ] : null,
            'players' => $gameRoom->players->whereNull('left_at')->map(function ($player) {
                return [
                    'id' => $player->member->id,
                    'name' => $player->member->name,
                    'current_score' => $player->current_score,
                    'answers_correct' => $player->answers_correct,
                    'answers_incorrect' => $player->answers_incorrect,
                    'is_ready' => $player->is_ready,
                    'accuracy_rate' => $player->getAccuracyRate(),
                ];
            }),
            'my_stats' => [
                'current_score' => $player->current_score,
                'answers_correct' => $player->answers_correct,
                'answers_incorrect' => $player->answers_incorrect,
                'is_ready' => $player->is_ready,
                'accuracy_rate' => $player->getAccuracyRate(),
            ],
        ];

        // Add answer status for current question if game is active
        if ($gameRoom->status === GameRoom::STATUS_PLAYING && $gameRoom->currentQuestion) {
            $hasAnswered = GameRoomResult::where([
                'game_room_id' => $gameRoom->id,
                'member_id' => $member->id,
                'question_id' => $gameRoom->current_question_id,
                'round_number' => $gameRoom->current_round,
            ])->exists();

            $gameState['has_answered_current_question'] = $hasAnswered;
        }

        return response()->json([
            'success' => true,
            'data' => $gameState,
        ]);
    }

    /**
     * Get next question for the game room (only room owner)
     */
    public function nextQuestion(Request $request, $roomId)
    {
        $member = $request->user();
        $gameRoom = GameRoom::findOrFail($roomId);

        if (!$gameRoom->isOwner($member)) {
            return response()->json([
                'success' => false,
                'message' => 'Only the room owner can advance to the next question.',
            ], 403);
        }

        if ($gameRoom->status !== GameRoom::STATUS_PLAYING) {
            return response()->json([
                'success' => false,
                'message' => 'Game is not currently active.',
            ], 400);
        }

        // Check if we've reached the total rounds
        if ($gameRoom->current_round >= $gameRoom->total_rounds) {
            return $this->finishGame($gameRoom);
        }

        DB::beginTransaction();
        try {
            // Get next question (excluding already used questions in this game)
            $usedQuestionIds = GameRoomResult::where('game_room_id', $gameRoom->id)
                ->pluck('question_id')
                ->toArray();

            $nextQuestion = Question::where('category_id', $gameRoom->category_id)
                ->whereNotIn('id', $usedQuestionIds)
                ->inRandomOrder()
                ->first();

            if (!$nextQuestion) {
                // No more questions available, finish the game
                return $this->finishGame($gameRoom);
            }

            // Update game room with next question and round
            $gameRoom->update([
                'current_question_id' => $nextQuestion->id,
                'current_round' => $gameRoom->current_round + 1,
            ]);

            DB::commit();

            // Broadcast next question event
            $this->broadcastNextQuestion($gameRoom, $nextQuestion);

            return response()->json([
                'success' => true,
                'data' => [
                    'question' => [
                        'id' => $nextQuestion->id,
                        'question_text' => $nextQuestion->question_text,
                        'options' => $nextQuestion->options,
                        'difficulty' => $nextQuestion->difficulty,
                    ],
                    'current_round' => $gameRoom->current_round,
                    'total_rounds' => $gameRoom->total_rounds,
                ],
                'message' => 'Next question loaded.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to load next question.',
            ], 500);
        }
    }

    /**
     * Pause the game (only room owner)
     */
    public function pauseGame(Request $request, $roomId)
    {
        $member = $request->user();
        $gameRoom = GameRoom::findOrFail($roomId);

        if (!$gameRoom->isOwner($member)) {
            return response()->json([
                'success' => false,
                'message' => 'Only the room owner can pause the game.',
            ], 403);
        }

        if ($gameRoom->status !== GameRoom::STATUS_PLAYING) {
            return response()->json([
                'success' => false,
                'message' => 'Game is not currently active.',
            ], 400);
        }

        $gameRoom->update(['status' => GameRoom::STATUS_PAUSED]);

        // Broadcast game paused event
        $this->broadcastGamePaused($gameRoom);

        return response()->json([
            'success' => true,
            'message' => 'Game paused successfully.',
        ]);
    }

    /**
     * Resume the game (only room owner)
     */
    public function resumeGame(Request $request, $roomId)
    {
        $member = $request->user();
        $gameRoom = GameRoom::findOrFail($roomId);

        if (!$gameRoom->isOwner($member)) {
            return response()->json([
                'success' => false,
                'message' => 'Only the room owner can resume the game.',
            ], 403);
        }

        if ($gameRoom->status !== GameRoom::STATUS_PAUSED) {
            return response()->json([
                'success' => false,
                'message' => 'Game is not currently paused.',
            ], 400);
        }

        $gameRoom->update(['status' => GameRoom::STATUS_PLAYING]);

        // Broadcast game resumed event
        $this->broadcastGameResumed($gameRoom);

        return response()->json([
            'success' => true,
            'message' => 'Game resumed successfully.',
        ]);
    }

    /**
     * Get question results for current round
     */
    public function getQuestionResults(Request $request, $roomId)
    {
        $member = $request->user();
        $gameRoom = GameRoom::with('currentQuestion')->findOrFail($roomId);

        $player = $gameRoom->getPlayer($member);
        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in this game room.',
            ], 400);
        }

        $results = GameRoomResult::where([
            'game_room_id' => $gameRoom->id,
            'question_id' => $gameRoom->current_question_id,
            'round_number' => $gameRoom->current_round,
        ])->with('member')->get();

        $questionResults = [
            'question' => [
                'id' => $gameRoom->currentQuestion->id,
                'question_text' => $gameRoom->currentQuestion->question_text,
                'correct_answer' => $gameRoom->currentQuestion->correct_answer,
                'explanation' => $gameRoom->currentQuestion->explanation,
            ],
            'results' => $results->map(function ($result) {
                return [
                    'member_name' => $result->member->name,
                    'user_answer' => $result->user_answer,
                    'is_correct' => $result->is_correct,
                    'time_taken' => $result->time_taken,
                    'score_earned' => $result->score_earned,
                ];
            }),
            'statistics' => [
                'total_answers' => $results->count(),
                'correct_answers' => $results->where('is_correct', true)->count(),
                'average_time' => $results->avg('time_taken'),
                'fastest_time' => $results->min('time_taken'),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $questionResults,
        ]);
    }

    /**
     * Get game summary/final results
     */
    public function getGameSummary(Request $request, $roomId)
    {
        $member = $request->user();
        $gameRoom = GameRoom::findOrFail($roomId);

        $player = $gameRoom->getPlayer($member);
        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in this game room.',
            ], 400);
        }

        // Get final rankings
        $finalRankings = GameRoomPlayer::where('game_room_id', $gameRoom->id)
            ->with('member')
            ->whereNull('left_at')
            ->orderBy('current_score', 'desc')
            ->orderBy('answers_correct', 'desc')
            ->get()
            ->map(function ($player, $index) {
                return [
                    'rank' => $index + 1,
                    'member' => [
                        'id' => $player->member->id,
                        'name' => $player->member->name,
                    ],
                    'final_score' => $player->current_score,
                    'answers_correct' => $player->answers_correct,
                    'answers_incorrect' => $player->answers_incorrect,
                    'accuracy_rate' => $player->getAccuracyRate(),
                ];
            });

        // Get my performance details
        $myResults = GameRoomResult::where([
            'game_room_id' => $gameRoom->id,
            'member_id' => $member->id,
        ])->with('question')->get();

        $myPerformance = [
            'final_score' => $player->current_score,
            'total_questions' => $myResults->count(),
            'correct_answers' => $myResults->where('is_correct', true)->count(),
            'accuracy_rate' => $player->getAccuracyRate(),
            'average_time' => $myResults->avg('time_taken'),
            'fastest_answer' => $myResults->min('time_taken'),
            'slowest_answer' => $myResults->max('time_taken'),
            'my_rank' => $finalRankings->search(function ($ranking) use ($member) {
                return $ranking['member']['id'] === $member->id;
            }) + 1,
        ];

        // Game statistics
        $gameStats = [
            'total_players' => $finalRankings->count(),
            'total_questions' => $gameRoom->current_round,
            'game_duration' => $gameRoom->started_at && $gameRoom->ended_at 
                ? $gameRoom->ended_at->diffInMinutes($gameRoom->started_at) 
                : null,
            'category' => $gameRoom->category->name ?? 'Unknown',
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'game_room' => [
                    'id' => $gameRoom->id,
                    'name' => $gameRoom->name,
                    'status' => $gameRoom->status,
                ],
                'final_rankings' => $finalRankings,
                'my_performance' => $myPerformance,
                'game_statistics' => $gameStats,
            ],
        ]);
    }

    /**
     * Skip current question (only room owner)
     */
    public function skipQuestion(Request $request, $roomId)
    {
        $member = $request->user();
        $gameRoom = GameRoom::findOrFail($roomId);

        if (!$gameRoom->isOwner($member)) {
            return response()->json([
                'success' => false,
                'message' => 'Only the room owner can skip questions.',
            ], 403);
        }

        if ($gameRoom->status !== GameRoom::STATUS_PLAYING) {
            return response()->json([
                'success' => false,
                'message' => 'Game is not currently active.',
            ], 400);
        }

        return $this->nextQuestion($request, $roomId);
    }

    /**
     * Finish the game
     */
    private function finishGame(GameRoom $gameRoom)
    {
        $gameRoom->update([
            'status' => GameRoom::STATUS_FINISHED,
            'ended_at' => now(),
        ]);

        // Broadcast game finished event
        $this->broadcastGameFinished($gameRoom);

        return response()->json([
            'success' => true,
            'message' => 'Game finished successfully.',
            'data' => [
                'status' => GameRoom::STATUS_FINISHED,
                'ended_at' => $gameRoom->ended_at,
            ],
        ]);
    }

    /**
     * Broadcast next question event
     */
    private function broadcastNextQuestion(GameRoom $gameRoom, Question $question): void
    {
        // TODO: Implement WebSocket broadcasting for next question
    }

    /**
     * Broadcast game paused event
     */
    private function broadcastGamePaused(GameRoom $gameRoom): void
    {
        // TODO: Implement WebSocket broadcasting for game pause
    }

    /**
     * Broadcast game resumed event
     */
    private function broadcastGameResumed(GameRoom $gameRoom): void
    {
        // TODO: Implement WebSocket broadcasting for game resume
    }

    /**
     * Broadcast game finished event
     */
    private function broadcastGameFinished(GameRoom $gameRoom): void
    {
        // TODO: Implement WebSocket broadcasting for game finish
    }
}
