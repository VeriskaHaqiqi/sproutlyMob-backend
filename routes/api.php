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