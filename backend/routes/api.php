<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\GmailAuthorizationController;
use App\Http\Controllers\GmailCallbackController;
use App\Http\Controllers\GmailIntegrationController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'backend',
    ]);
});

Route::get(
    '/integrations/gmail/callback',
    [GmailCallbackController::class, 'callback']
);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/integrations/gmail/authorize', [GmailAuthorizationController::class, 'authorize']);
    Route::get('/integrations/gmail', [GmailIntegrationController::class, 'status']);
    Route::delete('/integrations/gmail', [GmailIntegrationController::class, 'disconnect']);
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages']);
});
