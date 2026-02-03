<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $table = 'chat_conversations';

    protected $fillable = [
        'type',
        'title',
        'created_by',
    ];

    /**
     * Users tham gia cuộc trò chuyện
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_conversation_user', 'chat_conversation_id', 'user_id')
            ->withPivot(['joined_at', 'last_read_at'])
            ->withTimestamps();
    }

    /**
     * Messages trong cuộc trò chuyện
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Tin nhắn cuối (phục vụ list chat)
     */
    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function isGroup(): bool
    {
        return $this->type === 'group';
    }
}
