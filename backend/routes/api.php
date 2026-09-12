<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversationController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'backend',
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages']);
});
