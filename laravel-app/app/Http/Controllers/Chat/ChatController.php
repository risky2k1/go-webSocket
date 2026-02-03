<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Services\ChatService;
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
}