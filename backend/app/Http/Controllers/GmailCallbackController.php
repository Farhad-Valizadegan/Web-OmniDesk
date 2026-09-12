<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GmailCallbackController extends Controller
{
    public function callback(Request $request): JsonResponse
    {
        $state = $request->string('state')->toString();

        if ($state === '') {
            return response()->json([
                'message' => 'The OAuth state is invalid or expired.',
                'error' => 'oauth_state_invalid',
            ], 422);
        }

        $stateData = Cache::pull(
            GmailAuthorizationController::stateCacheKey($state)
        );

        if (! is_array($stateData) || ! isset($stateData['user_id'])) {
            return response()->json([
                'message' => 'The OAuth state is invalid or expired.',
                'error' => 'oauth_state_invalid',
            ], 422);
        }

        if ($request->filled('error')) {
            return response()->json([
                'message' => 'Gmail authorization was not completed.',
                'error' => 'authorization_denied',
            ], 422);
        }

        if (! $request->filled('code')) {
            return response()->json([
                'message' => 'The OAuth authorization code is missing.',
                'error' => 'authorization_code_missing',
            ], 422);
        }

        return response()->json([
            'message' => 'OAuth state validated. Token exchange is pending.',
        ], 501);
    }
}
