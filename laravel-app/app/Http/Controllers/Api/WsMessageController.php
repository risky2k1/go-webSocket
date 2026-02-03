<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Message;
use Illuminate\Support\Facades\Redis;

class WsMessageController extends Controller
{
    public function store(Request $request)
    {
        // 1️⃣ Verify internal token
        if ($request->header('X-Internal-Token') !== config('services.ws.internal_token')) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // 2️⃣ Validate payload từ Go
        $data = $request->validate([
            'conversation_id' => ['required', 'integer'],
            'sender_id'       => ['required', 'integer'],
            'content'         => ['required', 'string'],
        ]);

        // 3️⃣ Lưu chat_messages
        $message = Message::create([
            'chat_conversation_id' => $data['conversation_id'],
            'user_id'              => $data['sender_id'],
            'type'                 => 'text',
            'content'              => $data['content'],
            'meta'                 => null,
        ]);

        // 4️⃣ Push event realtime sang Golang (Redis pub/sub)
        Redis::publish('chat.messages', json_encode([
            'conversation_id' => $data['conversation_id'],
            'id' => $message->id,
            'user_id' => $message->user_id,
            'content' => $message->content,
            'type' => $message->type,
            'meta' => $message->meta,
            'created_at' => $message->created_at->toISOString(),
        ]));

        return response()->json([
            'id' => $message->id,
            'status' => 'ok',
        ]);
    }
}
