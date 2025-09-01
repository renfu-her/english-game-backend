<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\GameRoomResult;
use App\Models\Question;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GameRoomController extends Controller
{
    /**
     * Get list of available game rooms
     */
    public function index(Request $request)
    {
        $request->validate([
            'status' => Rule::in(['waiting', 'playing', 'finished']),
            'category_id' => 'exists:categories,id',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:50',
        ]);

        $query = GameRoom::with(['owner', 'category'])
            ->withCount('players');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Only show non-full rooms if status is waiting
        if ($request->status === 'waiting' || !$request->has('status')) {
            $query->whereRaw('current_players < max_players');
        }

        $rooms = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $rooms,
        ]);
    }

    /**
     * Create a new game room
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'max_players' => 'integer|min:2|max:6',
            'total_rounds' => 'integer|min:5|max:20',
            'time_per_question' => 'integer|min:10|max:120',
            'settings' => 'array',
        ]);

        $member = $request->user();

        // Check if member already owns an active room
        $existingRoom = $member->ownedGameRooms()
            ->whereIn('status', ['waiting', 'playing', 'paused'])
            ->first();

        if ($existingRoom) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active game room.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Create the game room
            $gameRoom = GameRoom::create([
                'name' => $request->name,
                'code' => $this->generateUniqueCode(),
                'status' => GameRoom::STATUS_WAITING,
                'max_players' => $request->get('max_players', 6),
                'current_players' => 1,
                'owner_id' => $member->id,
                'category_id' => $request->category_id,
                'total_rounds' => $request->get('total_rounds', 10),
                'time_per_question' => $request->get('time_per_question', 30),
                'settings' => $request->get('settings', []),
            ]);

            // Add owner as first player
            GameRoomPlayer::create([
                'game_room_id' => $gameRoom->id,
                'member_id' => $member->id,
                'is_ready' => true,
                'joined_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $gameRoom->load(['owner', 'category', 'players.member']),
                'message' => 'Game room created successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create game room.',
            ], 500);
        }
    }

    /**
     * Show a specific game room
     */
    public function show($id)
    {
        $gameRoom = GameRoom::with(['owner', 'category', 'players.member', 'currentQuestion'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $gameRoom,
        ]);
    }

    /**
     * Join a game room
     */
    public function join(Request $request, $id)
    {
        $member = $request->user();
        $gameRoom = GameRoom::findOrFail($id);

        // Check if room is available for joining
        if ($gameRoom->status !== GameRoom::STATUS_WAITING) {
            return response()->json([
                'success' => false,
                'message' => 'This game room is not available for joining.',
            ], 400);
        }

        if ($gameRoom->isFull()) {
            return response()->json([
                'success' => false,
                'message' => 'This game room is full.',
            ], 400);
        }

        // Check if member is already in the room
        if ($gameRoom->hasPlayer($member)) {
            return response()->json([
                'success' => false,
                'message' => 'You are already in this game room.',
            ], 400);
        }

        // Check if member is in another active room
        $existingPlayer = GameRoomPlayer::whereHas('gameRoom', function ($query) {
            $query->whereIn('status', ['waiting', 'playing', 'paused']);
        })->where('member_id', $member->id)
          ->whereNull('left_at')
          ->first();

        if ($existingPlayer) {
            return response()->json([
                'success' => false,
                'message' => 'You are already in another active game room.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Add player to room
            GameRoomPlayer::create([
                'game_room_id' => $gameRoom->id,
                'member_id' => $member->id,
                'is_ready' => false,
                'joined_at' => now(),
            ]);

            // Update room player count
            $gameRoom->increment('current_players');

            DB::commit();

            // Broadcast player joined event
            $this->broadcastRoomUpdate($gameRoom);

            return response()->json([
                'success' => true,
                'data' => $gameRoom->load(['owner', 'category', 'players.member']),
                'message' => 'Successfully joined the game room.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to join game room.',
            ], 500);
        }
    }

    /**
     * Leave a game room
     */
    public function leave(Request $request, $id)
    {
        $member = $request->user();
        $gameRoom = GameRoom::findOrFail($id);

        $player = $gameRoom->getPlayer($member);
        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in this game room.',
            ], 400);
        }

        if ($player->left_at) {
            return response()->json([
                'success' => false,
                'message' => 'You have already left this game room.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Mark player as left
            $player->update(['left_at' => now()]);

            // Update room player count
            $gameRoom->decrement('current_players');

            // If owner leaves, transfer ownership or close room
            if ($gameRoom->isOwner($member)) {
                $newOwner = $gameRoom->players()
                    ->whereNull('left_at')
                    ->where('member_id', '!=', $member->id)
                    ->first();

                if ($newOwner) {
                    $gameRoom->update(['owner_id' => $newOwner->member_id]);
                } else {
                    // No other players, close the room
                    $gameRoom->update(['status' => GameRoom::STATUS_FINISHED]);
                }
            }

            DB::commit();

            // Broadcast player left event
            $this->broadcastRoomUpdate($gameRoom);

            return response()->json([
                'success' => true,
                'message' => 'Successfully left the game room.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to leave game room.',
            ], 500);
        }
    }

    /**
     * Toggle player ready status
     */
    public function toggleReady(Request $request, $id)
    {
        $member = $request->user();
        $gameRoom = GameRoom::findOrFail($id);

        if ($gameRoom->status !== GameRoom::STATUS_WAITING) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot change ready status once game has started.',
            ], 400);
        }

        $player = $gameRoom->getPlayer($member);
        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in this game room.',
            ], 400);
        }

        $player->update(['is_ready' => !$player->is_ready]);

        // Broadcast ready status change
        $this->broadcastRoomUpdate($gameRoom);

        return response()->json([
            'success' => true,
            'data' => ['is_ready' => $player->fresh()->is_ready],
            'message' => 'Ready status updated.',
        ]);
    }

    /**
     * Start the game (only room owner)
     */
    public function start(Request $request, $id)
    {
        $member = $request->user();
        $gameRoom = GameRoom::findOrFail($id);

        if (!$gameRoom->isOwner($member)) {
            return response()->json([
                'success' => false,
                'message' => 'Only the room owner can start the game.',
            ], 403);
        }

        if (!$gameRoom->canStart()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot start the game. Need at least 2 players and all players must be ready.',
            ], 400);
        }

        // Check if all players are ready
        $notReadyPlayers = $gameRoom->players()
            ->whereNull('left_at')
            ->where('is_ready', false)
            ->count();

        if ($notReadyPlayers > 0) {
            return response()->json([
                'success' => false,
                'message' => 'All players must be ready before starting the game.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Get first question
            $firstQuestion = Question::where('category_id', $gameRoom->category_id)
                ->inRandomOrder()
                ->first();

            if (!$firstQuestion) {
                return response()->json([
                    'success' => false,
                    'message' => 'No questions available for this category.',
                ], 400);
            }

            // Update game room status
            $gameRoom->update([
                'status' => GameRoom::STATUS_PLAYING,
                'current_round' => 1,
                'current_question_id' => $firstQuestion->id,
                'started_at' => now(),
            ]);

            DB::commit();

            // Broadcast game started event
            $this->broadcastGameStarted($gameRoom, $firstQuestion);

            return response()->json([
                'success' => true,
                'data' => $gameRoom->load(['currentQuestion']),
                'message' => 'Game started successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to start game.',
            ], 500);
        }
    }

    /**
     * Submit answer for current question
     */
    public function submitAnswer(Request $request, $id)
    {
        $request->validate([
            'user_answer' => 'required|string',
            'time_taken' => 'integer|min:0',
        ]);

        $member = $request->user();
        $gameRoom = GameRoom::with('currentQuestion')->findOrFail($id);

        if ($gameRoom->status !== GameRoom::STATUS_PLAYING) {
            return response()->json([
                'success' => false,
                'message' => 'Game is not currently active.',
            ], 400);
        }

        $player = $gameRoom->getPlayer($member);
        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'You are not in this game room.',
            ], 400);
        }

        // Check if player has already answered this question
        $existingAnswer = GameRoomResult::where([
            'game_room_id' => $gameRoom->id,
            'member_id' => $member->id,
            'question_id' => $gameRoom->current_question_id,
            'round_number' => $gameRoom->current_round,
        ])->first();

        if ($existingAnswer) {
            return response()->json([
                'success' => false,
                'message' => 'You have already answered this question.',
            ], 400);
        }

        $question = $gameRoom->currentQuestion;
        $isCorrect = strtolower(trim($request->user_answer)) === strtolower(trim($question->correct_answer));
        $scoreEarned = $isCorrect ? 10 : 0;

        // Bonus points for quick answers
        if ($isCorrect && $request->time_taken && $request->time_taken < 15) {
            $scoreEarned += 5;
        }

        DB::beginTransaction();
        try {
            // Save answer result
            GameRoomResult::create([
                'game_room_id' => $gameRoom->id,
                'member_id' => $member->id,
                'question_id' => $question->id,
                'user_answer' => $request->user_answer,
                'is_correct' => $isCorrect,
                'time_taken' => $request->time_taken,
                'score_earned' => $scoreEarned,
                'round_number' => $gameRoom->current_round,
                'answered_at' => now(),
            ]);

            // Update player stats
            $player->increment('current_score', $scoreEarned);
            if ($isCorrect) {
                $player->increment('answers_correct');
            } else {
                $player->increment('answers_incorrect');
            }

            DB::commit();

            // Broadcast answer submitted
            $this->broadcastAnswerSubmitted($gameRoom, $member, $isCorrect, $scoreEarned);

            return response()->json([
                'success' => true,
                'data' => [
                    'is_correct' => $isCorrect,
                    'score_earned' => $scoreEarned,
                    'correct_answer' => $question->correct_answer,
                    'explanation' => $question->explanation,
                    'current_score' => $player->fresh()->current_score,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit answer.',
            ], 500);
        }
    }

    /**
     * Get game room by code
     */
    public function findByCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $gameRoom = GameRoom::where('code', strtoupper($request->code))
            ->with(['owner', 'category', 'players.member'])
            ->first();

        if (!$gameRoom) {
            return response()->json([
                'success' => false,
                'message' => 'Game room not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $gameRoom,
        ]);
    }

    /**
     * Get game room leaderboard
     */
    public function leaderboard($id)
    {
        $gameRoom = GameRoom::findOrFail($id);

        $leaderboard = GameRoomPlayer::where('game_room_id', $gameRoom->id)
            ->with('member')
            ->whereNull('left_at')
            ->orderBy('current_score', 'desc')
            ->orderBy('answers_correct', 'desc')
            ->get()
            ->map(function ($player) {
                return [
                    'member' => $player->member,
                    'current_score' => $player->current_score,
                    'answers_correct' => $player->answers_correct,
                    'answers_incorrect' => $player->answers_incorrect,
                    'accuracy_rate' => $player->getAccuracyRate(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $leaderboard,
        ]);
    }

    /**
     * End the game (only room owner)
     */
    public function end(Request $request, $id)
    {
        $member = $request->user();
        $gameRoom = GameRoom::findOrFail($id);

        if (!$gameRoom->isOwner($member)) {
            return response()->json([
                'success' => false,
                'message' => 'Only the room owner can end the game.',
            ], 403);
        }

        if ($gameRoom->status === GameRoom::STATUS_FINISHED) {
            return response()->json([
                'success' => false,
                'message' => 'Game has already ended.',
            ], 400);
        }

        $gameRoom->update([
            'status' => GameRoom::STATUS_FINISHED,
            'ended_at' => now(),
        ]);

        // Broadcast game ended event
        $this->broadcastGameEnded($gameRoom);

        return response()->json([
            'success' => true,
            'message' => 'Game ended successfully.',
        ]);
    }

    /**
     * Generate unique room code
     */
    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 6));
        } while (GameRoom::where('code', $code)->exists());

        return $code;
    }

    /**
     * Broadcast room update (placeholder for WebSocket implementation)
     */
    private function broadcastRoomUpdate(GameRoom $gameRoom): void
    {
        // TODO: Implement WebSocket broadcasting
        // This could use Laravel Broadcasting, Pusher, or custom WebSocket implementation
    }

    /**
     * Broadcast game started event
     */
    private function broadcastGameStarted(GameRoom $gameRoom, Question $question): void
    {
        // TODO: Implement WebSocket broadcasting for game start
    }

    /**
     * Broadcast answer submitted event
     */
    private function broadcastAnswerSubmitted(GameRoom $gameRoom, $member, bool $isCorrect, int $scoreEarned): void
    {
        // TODO: Implement WebSocket broadcasting for answer submission
    }

    /**
     * Broadcast game ended event
     */
    private function broadcastGameEnded(GameRoom $gameRoom): void
    {
        // TODO: Implement WebSocket broadcasting for game end
    }
}
