<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameResult;
use App\Models\Question;
use App\Models\CategoryProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameController extends Controller
{
    public function submitAnswer(Request $request)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'user_answer' => 'required|string',
            'time_taken' => 'integer|min:0',
        ]);

        $member = $request->user();
        $question = Question::findOrFail($request->question_id);
        
        $isCorrect = strtolower(trim($request->user_answer)) === strtolower(trim($question->correct_answer));
        $scoreEarned = $isCorrect ? 10 : 0;
        
        // Bonus points for quick answers
        if ($isCorrect && $request->time_taken && $request->time_taken < 30) {
            $scoreEarned += 5;
        }

        DB::transaction(function () use ($member, $question, $request, $isCorrect, $scoreEarned) {
            // Save game result
            GameResult::create([
                'member_id' => $member->id,
                'question_id' => $question->id,
                'category_id' => $question->category_id,
                'user_answer' => $request->user_answer,
                'is_correct' => $isCorrect,
                'time_taken' => $request->time_taken,
                'score_earned' => $scoreEarned,
            ]);

            // Update member score
            $member->increment('score', $scoreEarned);

            // Update category progress
            $progress = CategoryProgress::firstOrCreate(
                [
                    'member_id' => $member->id,
                    'category_id' => $question->category_id,
                ],
                [
                    'questions_attempted' => 0,
                    'questions_correct' => 0,
                    'total_score' => 0,
                    'completion_percentage' => 0,
                ]
            );

            $progress->increment('questions_attempted');
            if ($isCorrect) {
                $progress->increment('questions_correct');
            }
            $progress->increment('total_score', $scoreEarned);
            $progress->update([
                'last_played_at' => now(),
                'completion_percentage' => ($progress->questions_correct / $progress->questions_attempted) * 100,
            ]);

            // Level up logic
            $this->checkLevelUp($member);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'is_correct' => $isCorrect,
                'score_earned' => $scoreEarned,
                'correct_answer' => $question->correct_answer,
                'explanation' => $question->explanation,
                'member_score' => $member->fresh()->score,
                'member_level' => $member->fresh()->level,
            ],
        ]);
    }

    public function getProgress(Request $request)
    {
        $member = $request->user();
        
        $progress = CategoryProgress::where('member_id', $member->id)
            ->with('category')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'member' => $member,
                'category_progress' => $progress,
            ],
        ]);
    }

    public function getLeaderboard(Request $request)
    {
        $request->validate([
            'limit' => 'integer|min:1|max:100',
        ]);

        $limit = $request->get('limit', 10);

        $leaderboard = DB::table('members')
            ->select('id', 'name', 'score', 'level')
            ->orderBy('score', 'desc')
            ->orderBy('level', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $leaderboard,
        ]);
    }

    public function getStats(Request $request)
    {
        $member = $request->user();

        $stats = [
            'total_questions_attempted' => GameResult::where('member_id', $member->id)->count(),
            'total_correct_answers' => GameResult::where('member_id', $member->id)->where('is_correct', true)->count(),
            'total_score' => $member->score,
            'current_level' => $member->level,
            'accuracy_rate' => 0,
        ];

        if ($stats['total_questions_attempted'] > 0) {
            $stats['accuracy_rate'] = round(($stats['total_correct_answers'] / $stats['total_questions_attempted']) * 100, 2);
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    private function checkLevelUp($member)
    {
        $currentLevel = $member->level;
        $score = $member->score;
        
        // Simple level up logic: every 100 points = 1 level
        $newLevel = floor($score / 100) + 1;
        
        if ($newLevel > $currentLevel) {
            $member->update(['level' => $newLevel]);
        }
    }
}
