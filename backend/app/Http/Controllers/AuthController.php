<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CaptchaService;
use App\Services\LoginCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(
        private readonly CaptchaService $captchaService
    ) {}

    public function register(Request $request): JsonResponse
    {
        $this->captchaService->verify($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('web')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $this->captchaService->verify($request);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response()->json(app(LoginCodeService::class)->issue($user));
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'challenge' => ['required', 'string', 'size:64', 'alpha_num:ascii'],
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
        ]);

        return response()->json(app(LoginCodeService::class)->complete($data['challenge'], $data['code']));
    }

    public function resendCode(Request $request): JsonResponse
    {
        $data = $request->validate(['challenge' => ['required', 'string', 'size:64', 'alpha_num:ascii']]);

        return response()->json(app(LoginCodeService::class)->complete($data['challenge']));
    }

    public function logout(Request $request): Response
    {
        $plainTextToken = $request->bearerToken();

        if ($plainTextToken !== null) {
            $accessToken = PersonalAccessToken::findToken($plainTextToken);
            $accessToken?->delete();
        }

        return response()->noContent();
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }
}
