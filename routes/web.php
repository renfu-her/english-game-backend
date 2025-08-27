<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api-test', function () {
    return response()->json([
        'message' => 'English Learning Game API is running!',
        'version' => '1.0.0',
        'endpoints' => [
            'auth' => [
                'POST /api/auth/login' => 'Member login',
                'POST /api/auth/register' => 'Member registration',
                'POST /api/auth/logout' => 'Member logout',
                'GET /api/auth/profile' => 'Get member profile',
            ],
            'categories' => [
                'GET /api/categories' => 'List all categories',
                'GET /api/categories/{id}' => 'Get category details',
                'GET /api/categories/{id}/questions' => 'Get category questions',
            ],
            'questions' => [
                'GET /api/questions' => 'List questions',
                'GET /api/questions/random' => 'Get random question',
                'GET /api/questions/{id}' => 'Get question details',
            ],
            'game' => [
                'POST /api/game/submit-answer' => 'Submit answer',
                'GET /api/game/progress' => 'Get member progress',
                'GET /api/game/leaderboard' => 'Get leaderboard',
                'GET /api/game/stats' => 'Get member stats',
            ],
        ],
        'admin_panel' => '/admin',
        'sample_data' => [
            'admin_user' => 'admin@example.com / admin123',
            'member_accounts' => 'member1@example.com to member5@example.com / password',
        ],
    ]);
});
