<?php

namespace App\Http\Controllers;

use App\Services\GoogleOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GmailAuthorizationController extends Controller
{
    private const STATE_TTL_MINUTES = 10;

    public function __construct(
        private readonly GoogleOAuthService $googleOAuthService
    ) {
    }

    public function authorize(Request $request): JsonResponse
    {
        $state = Str::random(64);

        Cache::put(
            self::stateCacheKey($state),
            [
                'user_id' => $request->user()->id,
            ],
            now()->addMinutes(self::STATE_TTL_MINUTES)
        );

        return response()->json([
            'authorization_url' => $this->googleOAuthService
                ->authorizationUrl($state),
            'expires_in' => self::STATE_TTL_MINUTES * 60,
        ]);
    }

    public static function stateCacheKey(string $state): string
    {
        return 'gmail_oauth_state:' . $state;
    }
}
