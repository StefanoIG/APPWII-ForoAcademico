<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;

// Rutas públicas (sin autenticación)
Route::group(['prefix' => 'public'], function () {
    // Ver contenido público
    Route::get('questions', [QuestionController::class, 'index']);
    Route::get('questions/{question}', [QuestionController::class, 'show']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}', [CategoryController::class, 'show']);
    Route::get('categories/{category}/questions', [CategoryController::class, 'questions']);
    Route::get('tags', [TagController::class, 'index']);
    Route::get('tags/{tag}', [TagController::class, 'show']);
    Route::get('tags/{tag}/questions', [TagController::class, 'questions']);
    Route::get('tags/popular', [TagController::class, 'popular']);
    Route::get('users/leaderboard', [UserController::class, 'leaderboard']);
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::get('users/{user}/questions', [UserController::class, 'questions']);
    Route::get('users/{user}/answers', [UserController::class, 'answers']);
    Route::get('vote-stats', [VoteController::class, 'getVoteStats']);
    Route::get('stats', [DashboardController::class, 'publicStats']);
});

// Rutas de autenticación (públicas)
Route::group(['prefix' => 'auth'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:api');
});

// Rutas protegidas por JWT
Route::group(['middleware' => 'auth:api'], function () {

    // === PREGUNTAS ===
    Route::apiResource('questions', QuestionController::class);
    Route::post('questions/{question}/favorite', [QuestionController::class, 'addToFavorites']);
    Route::delete('questions/{question}/favorite', [QuestionController::class, 'removeFromFavorites']);
    Route::get('my/favorites', [QuestionController::class, 'favorites']);

    // === RESPUESTAS ===
    Route::apiResource('answers', AnswerController::class);
    Route::post('answers/{answer}/mark-as-best', [AnswerController::class, 'markAsBest']);

    // === VOTOS ===
    Route::apiResource('votes', VoteController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('questions/{question}/vote', [VoteController::class, 'vote']); // Legacy support
    Route::get('vote-stats', [VoteController::class, 'getVoteStats']);

    // === REPORTES ===
    Route::apiResource('reports', ReportController::class);
    Route::get('reports/statistics', [ReportController::class, 'statistics']);

    // === CATEGORÍAS (solo admin) ===
    Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
    Route::get('categories/{category}/questions', [CategoryController::class, 'questions']);

    // === ETIQUETAS (solo admin para CRUD) ===
    Route::apiResource('tags', TagController::class)->except(['index', 'show']);

    // === ARCHIVOS ADJUNTOS ===
    Route::delete('attachments/{attachment}', [QuestionController::class, 'deleteAttachment']);
    Route::get('attachments/info', [QuestionController::class, 'getFileUploadInfo']);
    
    // === SUBIDA DE IMÁGENES ===
    Route::post('images/upload', [QuestionController::class, 'uploadImage']);
    Route::post('images/upload-multiple', [QuestionController::class, 'uploadMultipleImages']);

    // === USUARIOS ===
    Route::get('profile', [UserController::class, 'profile']);
    Route::apiResource('users', UserController::class)->except(['create', 'store']);
    Route::get('users/statistics', [UserController::class, 'statistics']);

    // === DASHBOARD ===
    Route::get('dashboard/admin-stats', [DashboardController::class, 'adminStats'])->middleware('admin');
    Route::get('dashboard/user-activity', [DashboardController::class, 'userActivity']);

});
