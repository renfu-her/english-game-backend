<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\GameRoomController;
use App\Http\Controllers\Api\GameSessionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    
    // Category routes
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::get('/categories/{id}/questions', [CategoryController::class, 'getQuestions']);
    
    // Question routes
    Route::get('/questions', [QuestionController::class, 'index']);
    Route::get('/questions/random', [QuestionController::class, 'random']);
    Route::get('/questions/{id}', [QuestionController::class, 'show']);
    
    // Single-player Game routes
    Route::post('/game/submit-answer', [GameController::class, 'submitAnswer']);
    Route::get('/game/progress', [GameController::class, 'getProgress']);
    Route::get('/game/leaderboard', [GameController::class, 'getLeaderboard']);
    Route::get('/game/stats', [GameController::class, 'getStats']);
    
    // Game Room CRUD routes
    Route::prefix('game-rooms')->group(function () {
        Route::get('/', [GameRoomController::class, 'index']);
        Route::post('/', [GameRoomController::class, 'store']);
        Route::get('/find-by-code', [GameRoomController::class, 'findByCode']);
        Route::get('/{id}', [GameRoomController::class, 'show']);
        
        // Player actions
        Route::post('/{id}/join', [GameRoomController::class, 'join']);
        Route::post('/{id}/leave', [GameRoomController::class, 'leave']);
        Route::post('/{id}/toggle-ready', [GameRoomController::class, 'toggleReady']);
        
        // Room owner controls
        Route::post('/{id}/start', [GameRoomController::class, 'start']);
        Route::post('/{id}/end', [GameRoomController::class, 'end']);
        
        // Game actions
        Route::post('/{id}/submit-answer', [GameRoomController::class, 'submitAnswer']);
        Route::get('/{id}/leaderboard', [GameRoomController::class, 'leaderboard']);
    });
    
    // Game Session Management routes
    Route::prefix('game-sessions')->group(function () {
        Route::get('/{roomId}/state', [GameSessionController::class, 'getGameState']);
        Route::post('/{roomId}/next-question', [GameSessionController::class, 'nextQuestion']);
        Route::post('/{roomId}/pause', [GameSessionController::class, 'pauseGame']);
        Route::post('/{roomId}/resume', [GameSessionController::class, 'resumeGame']);
        Route::post('/{roomId}/skip-question', [GameSessionController::class, 'skipQuestion']);
        Route::get('/{roomId}/question-results', [GameSessionController::class, 'getQuestionResults']);
        Route::get('/{roomId}/summary', [GameSessionController::class, 'getGameSummary']);
    });
});
