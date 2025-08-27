<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\GameController;

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
    
    // Game routes
    Route::post('/game/submit-answer', [GameController::class, 'submitAnswer']);
    Route::get('/game/progress', [GameController::class, 'getProgress']);
    Route::get('/game/leaderboard', [GameController::class, 'getLeaderboard']);
    Route::get('/game/stats', [GameController::class, 'getStats']);
});
