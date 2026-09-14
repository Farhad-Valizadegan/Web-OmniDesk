<?php

namespace App\Contracts;

use App\Models\User;

interface MessageProviderConnector
{
    public function provider(): string;

    /**
     * @return array<string, mixed>
     */
    public function sync(
        User $user,
        int $maxResults = 20,
        ?string $pageToken = null,
    ): array;

    /**
     * @return array<string, mixed>
     */
    public function syncNextPage(
        User $user,
        int $maxResults = 15,
    ): array;
}
