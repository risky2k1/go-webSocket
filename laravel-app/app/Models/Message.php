<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $table = 'chat_messages';

    protected $fillable = [
        'chat_conversation_id',
        'user_id',
        'type',
        'content',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Message thuộc conversation nào
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Người gửi message
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Trạng thái đọc
     */
    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }
}