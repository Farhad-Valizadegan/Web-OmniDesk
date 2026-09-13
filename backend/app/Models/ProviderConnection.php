<?php

namespace App\Models;

use Database\Factories\ProviderConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderConnection extends Model
{
    /** @use HasFactory<ProviderConnectionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_account_id',
        'account_email',
        'account_avatar_url',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'history_id',
        'gmail_sync_page_token',
        'gmail_sync_snapshot_history_id',
        'gmail_sync_completed',
        'scopes',
        'status',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'scopes' => 'array',
            'gmail_sync_completed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
