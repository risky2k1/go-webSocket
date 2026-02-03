<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ConversationUser extends Pivot
{
    protected $table = 'chat_conversation_user';

    protected $fillable = [
        'chat_conversation_id',
        'user_id',
        'joined_at',
        'last_read_at',
    ];

    public $timestamps = true;
}
