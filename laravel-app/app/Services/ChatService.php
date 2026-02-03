<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ChatService
{
    /**
     * Tạo conversation mới (private hoặc group)
     */
    public function createConversation(
        array $userIds,
        string $type = 'private',
        ?string $title = null
    ): Conversation {
        return DB::transaction(function () use ($userIds, $type, $title) {
            $conversation = Conversation::create([
                'type' => $type,
                'title' => $title,
                'created_by' => Auth::id(),
            ]);

            $attachData = [];
            foreach ($userIds as $userId) {
                $attachData[$userId] = [
                    'joined_at' => now(),
                ];
            }

            // đảm bảo creator cũng tham gia
            $attachData[Auth::id()] = [
                'joined_at' => now(),
            ];

            $conversation->users()->sync($attachData);

            return $conversation;
        });
    }

    /**
     * Gửi message
     */
    public function sendMessage(
        Conversation $conversation,
        User $sender,
        string $content,
        string $type = 'text',
        array $meta = []
    ): Message {
        $message = DB::transaction(function () use (
            $conversation,
            $sender,
            $content,
            $type,
            $meta
        ) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id'         => $sender->id,
                'type'            => $type,
                'content'         => $content,
                'meta'            => $meta,
            ]);

            // update last_read cho sender
            $conversation->users()->updateExistingPivot(
                $sender->id,
                ['last_read_message_id' => $message->id]
            );

            return $message;
        });

        // push realtime cho Golang qua Redis
        $this->pushRealtime($conversation, $message);

        return $message;
    }

    /**
     * Đánh dấu đã đọc
     */
    public function markAsRead(Conversation $conversation, User $user): void
    {
        $lastMessageId = $conversation->messages()->latest()->value('id');

        if (! $lastMessageId) {
            return;
        }

        $conversation->users()->updateExistingPivot(
            $user->id,
            ['last_read_message_id' => $lastMessageId]
        );

        // optional: push read-event realtime
        Redis::publish(
            "chat.read.{$conversation->id}",
            json_encode([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'last_read_message_id' => $lastMessageId,
            ])
        );
    }

    /**
     * Lấy danh sách conversation của user
     */
    public function getUserConversations(User $user)
    {
        return $user->conversations()
            ->with([
                'users:id,name',
                'lastMessage.sender:id,name',
            ])
            ->orderByDesc(
                Message::select('created_at')
                    ->whereColumn('chat_conversation_id', 'chat_conversations.id')
                    ->latest()
                    ->limit(1)
            )
            ->get();
    }

    /**
     * Lấy messages trong conversation
     */
    public function getMessages(
        Conversation $conversation,
        int $limit = 30
    ) {
        return $conversation->messages()
            ->with('sender:id,name')
            ->latest()
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Push event realtime sang Golang (Redis pub/sub)
     */
    protected function pushRealtime(
        Conversation $conversation,
        Message $message
    ): void {
        Redis::publish(
            "chat.message.{$conversation->id}",
            json_encode([
                'event' => 'message.sent',
                'data' => [
                    'conversation_id' => $conversation->id,
                    'message' => [
                        'id'         => $message->id,
                        'user_id'    => $message->user_id,
                        'content'    => $message->content,
                        'type'       => $message->type,
                        'meta'       => $message->meta,
                        'created_at'=> $message->created_at->toISOString(),
                    ],
                ],
            ])
        );
    }
}
