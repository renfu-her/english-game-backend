<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'category_id' => 'integer|exists:categories,id',
            'type' => 'in:multiple_choice,fill_blank',
            'limit' => 'integer|min:1|max:50',
        ]);

        $query = Question::where('is_active', true);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('type')) {
            $query->where('question_type', $request->type);
        }

        $questions = $query->with('category')
            ->inRandomOrder()
            ->limit($request->get('limit', 10))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $questions,
        ]);
    }

    public function show($id)
    {
        $question = Question::with('category')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $question,
        ]);
    }

    public function random(Request $request)
    {
        $request->validate([
            'category_id' => 'integer|exists:categories,id',
            'type' => 'in:multiple_choice,fill_blank',
        ]);

        $query = Question::where('is_active', true);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('type')) {
            $query->where('question_type', $request->type);
        }

        $question = $query->with('category')
            ->inRandomOrder()
            ->first();

        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'No questions found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $question,
        ]);
    }
}
