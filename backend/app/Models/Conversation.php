<?php

namespace App\Models;

use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_conversation_id',
        'subject',
        'status',
        'is_read',
        'last_message_at',
        'labels',
    ];

    /**
     * Get the owner of the conversation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all messages in the conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('sent_at');
    }

    /**
     * Get the latest message in the conversation.
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany('sent_at');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'last_message_at' => 'datetime',
            'labels' => 'array',
        ];
    }
}
