<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// ==================== AUTH ROUTES ====================
Route::prefix('auth')->group(function () {

    // Public routes (tidak perlu token)
    Route::post('/register/user',   [AuthController::class, 'registerUser']);
    Route::post('/register/expert', [AuthController::class, 'registerExpert']);
    Route::post('/login',           [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);

    // Protected routes (perlu token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout',  [AuthController::class, 'logout']);
        Route::get('/profile',  [AuthController::class, 'profile']);
    });

});

use App\Http\Controllers\Api\ArticleController;

// ==================== ARTICLE ROUTES ====================
Route::prefix('articles')->group(function () {

    // Public routes
    Route::get('/',             [ArticleController::class, 'index']);
    Route::get('/categories',   [ArticleController::class, 'categories']);
    Route::get('/{id}',         [ArticleController::class, 'show']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/',                    [ArticleController::class, 'store']);
        Route::post('/{id}',                [ArticleController::class, 'update']);
        Route::delete('/{id}',              [ArticleController::class, 'destroy']);
        Route::get('/user/my-articles',     [ArticleController::class, 'myArticles']);
        Route::post('/{id}/bookmark',       [ArticleController::class, 'bookmark']);
        Route::get('/user/bookmarks',       [ArticleController::class, 'bookmarkedArticles']);
    });
});

use App\Http\Controllers\Api\ExpertController;

// ==================== EXPERT ROUTES ====================
Route::prefix('experts')->group(function () {

    // Public routes
    Route::get('/',      [ExpertController::class, 'index']);
    Route::get('/{id}',  [ExpertController::class, 'show']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::put('/profile/update',           [ExpertController::class, 'updateProfile']);
        Route::patch('/profile/availability',   [ExpertController::class, 'updateAvailability']);
        Route::get('/profile/schedules',        [ExpertController::class, 'getSchedules']);
        Route::post('/profile/schedules',       [ExpertController::class, 'saveSchedules']);
        Route::get('/profile/specializations',  [ExpertController::class, 'getSpecializations']);
        Route::post('/profile/specializations', [ExpertController::class, 'saveSpecializations']);
        Route::post('/profile/set-fee',         [ExpertController::class, 'setFee']);
        Route::get('/profile/income-history',   [ExpertController::class, 'incomeHistory']);
    });
});