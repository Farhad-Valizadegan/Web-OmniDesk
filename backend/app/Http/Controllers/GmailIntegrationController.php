<?php

namespace App\Http\Controllers;

use App\Models\ProviderConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GmailIntegrationController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $connection = ProviderConnection::query()
            ->where('user_id', $request->user()->id)
            ->where('provider', 'gmail')
            ->first();

        if ($connection === null) {
            return response()->json([
                'connected' => false,
                'connection' => null,
            ]);
        }

        return response()->json([
            'connected' => $connection->status === 'connected',
            'connection' => [
                'id' => $connection->id,
                'provider' => $connection->provider,
                'provider_account_id' =>
                    $connection->provider_account_id,
                'account_email' => $connection->account_email,
                'status' => $connection->status,
                'scopes' => $connection->scopes,
                'token_expires_at' => $connection->token_expires_at,
            ],
        ]);
    }

    public function disconnect(Request $request): Response
    {
        ProviderConnection::query()
            ->where('user_id', $request->user()->id)
            ->where('provider', 'gmail')
            ->delete();

        return response()->noContent();
    }
}
