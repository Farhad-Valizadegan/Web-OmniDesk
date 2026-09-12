<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /**
     * Return the authenticated user's conversations.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:open,archived'],
            'is_read' => ['nullable', 'boolean'],
            'label' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $conversations = $request->user()
            ->conversations()
            ->with('latestMessage')
            ->when(
                isset($validated['search']),
                function ($query) use ($validated): void {
                    $search = $validated['search'];

                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('subject', 'like', "%{$search}%")
                            ->orWhere('provider', 'like', "%{$search}%");
                    });
                },
            )
            ->when(
                isset($validated['status']),
                fn ($query) => $query->where('status', $validated['status']),
            )
            ->when(
                array_key_exists('is_read', $validated),
                fn ($query) => $query->where('is_read', $validated['is_read']),
            )
            ->when(
                isset($validated['label']),
                fn ($query) => $query->whereJsonContains('labels', $validated['label']),
            )
            ->orderByDesc('last_message_at')
            ->paginate($validated['per_page'] ?? 20);

        return response()->json($conversations);
    }

    /**
     * Return one conversation owned by the authenticated user.
     */
    public function show(
        Request $request,
        Conversation $conversation,
    ): JsonResponse {
        $this->ensureOwnership($request, $conversation);

        return response()->json([
            'conversation' => $conversation->load('latestMessage'),
        ]);
    }

    /**
     * Return messages for an owned conversation.
     */
    public function messages(
        Request $request,
        Conversation $conversation,
    ): JsonResponse {
        $this->ensureOwnership($request, $conversation);

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $messages = $conversation
            ->messages()
            ->paginate($validated['per_page'] ?? 50);

        return response()->json($messages);
    }

    /**
     * Prevent cross-user conversation access.
     */
    private function ensureOwnership(
        Request $request,
        Conversation $conversation,
    ): void {
        abort_unless(
            $conversation->user_id === $request->user()->id,
            404,
        );
    }
}
