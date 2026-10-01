<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\GmailAuthorizationController;
use App\Http\Controllers\GmailCallbackController;
use App\Http\Controllers\GmailIntegrationController;
use App\Http\Controllers\TelegramIntegrationController;
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

Route::get('/captcha', [CaptchaController::class, 'challenge']);

Route::post(
    '/register',
    [AuthController::class, 'register']
)->middleware('throttle:registration');

Route::post(
    '/login',
    [AuthController::class, 'login']
)->middleware('throttle:login');

Route::post('/login/verify', [AuthController::class, 'verifyCode'])->middleware('throttle:10,1');
Route::post('/login/resend', [AuthController::class, 'resendCode'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', [AuthController::class, 'user']);

    Route::get(
        '/integrations/gmail/authorize',
        [GmailAuthorizationController::class, 'authorize']
    );

    Route::get(
        '/integrations/gmail',
        [GmailIntegrationController::class, 'status']
    );

    Route::post(
        '/integrations/gmail/sync',
        [GmailIntegrationController::class, 'sync']
    );

    Route::post(
        '/integrations/gmail/sync-page',
        [GmailIntegrationController::class, 'syncNextPage']
    );

    Route::delete(
        '/integrations/gmail',
        [GmailIntegrationController::class, 'disconnect']
    );

    Route::get(
        '/integrations/telegram/sample',
        [TelegramIntegrationController::class, 'sample']
    );

    Route::get('/integrations/telegram/media', [TelegramIntegrationController::class, 'media']);

    Route::get(
        '/conversations',
        [ConversationController::class, 'index']
    );

    Route::get(
        '/conversations/{conversation}',
        [ConversationController::class, 'show']
    );

    Route::get(
        '/conversations/{conversation}/messages',
        [ConversationController::class, 'messages']
    );
});
