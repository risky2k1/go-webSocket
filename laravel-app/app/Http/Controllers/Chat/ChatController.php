<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index(ChatService $chatService)
    {
        $conversations = $chatService->getUserConversations(Auth::user());
        return view('chat.index', [
            'conversations' => $conversations,
        ]);
    }

    public function getMessages(Conversation $conversation, ChatService $chatService)
    {
        // Verify user is part of conversation
        if (!$conversation->users()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = $chatService->getMessages($conversation);

        return response()->json([
            'messages' => $messages,
        ]);
    }

    public function sendMessage(Request $request, Conversation $conversation, ChatService $chatService)
    {
        \Log::info("💬 Sending message", [
            'conversation_id' => $conversation->id,
            'user_id' => Auth::id(),
        ]);

        // Verify user is part of conversation
        if (! $conversation->users()->where('user_id', Auth::id())->exists()) {
            \Log::warning("⛔ Unauthorized message attempt");

            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $message = $chatService->sendMessage(
            $conversation,
            Auth::user(),
            $validated['content']
        );

        // Load sender relation
        $message->load('sender:id,name');

        \Log::info("✅ Message sent successfully", ['message_id' => $message->id]);

        return response()->json([
            'message' => $message,
        ]);
    }
}