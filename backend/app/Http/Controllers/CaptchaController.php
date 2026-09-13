<?php

namespace App\Http\Controllers;

use App\Services\CaptchaService;
use Illuminate\Http\JsonResponse;

class CaptchaController extends Controller
{
    public function __construct(
        private readonly CaptchaService $captchaService
    ) {
    }

    public function challenge(): JsonResponse
    {
        return response()->json($this->captchaService->challenge());
    }
}
