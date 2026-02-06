#!/bin/bash

echo "🚀 Setting up test data for chat..."

# Create test users and conversation using PHP directly
docker compose exec php php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Conversation;
use App\Services\ChatService;

// Create users
echo '👤 Creating users...' . PHP_EOL;

\$user1 = User::firstOrCreate(
    ['email' => 'alice@test.com'],
    [
        'name' => 'Alice',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]
);

\$user2 = User::firstOrCreate(
    ['email' => 'bob@test.com'],
    [
        'name' => 'Bob',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]
);

echo '✅ User 1: ' . \$user1->name . ' (ID: ' . \$user1->id . ')' . PHP_EOL;
echo '✅ User 2: ' . \$user2->name . ' (ID: ' . \$user2->id . ')' . PHP_EOL;

// Create conversation
echo PHP_EOL . '💬 Creating conversation...' . PHP_EOL;

\$chatService = app(ChatService::class);
auth()->login(\$user1);

// Check if conversation already exists
\$existingConv = \$user1->conversations()
    ->whereHas('users', function(\$q) use (\$user2) {
        \$q->where('user_id', \$user2->id);
    })
    ->where('type', 'private')
    ->first();

if (\$existingConv) {
    echo '⚠️  Conversation already exists (ID: ' . \$existingConv->id . ')' . PHP_EOL;
    \$conv = \$existingConv;
} else {
    \$conv = \$chatService->createConversation([\$user2->id], 'private');
    echo '✅ Conversation created (ID: ' . \$conv->id . ')' . PHP_EOL;
    
    // Add some test messages
    echo PHP_EOL . '📝 Adding test messages...' . PHP_EOL;
    \$chatService->sendMessage(\$conv, \$user1, 'Hi Bob! How are you?');
    \$chatService->sendMessage(\$conv, \$user2, 'Hi Alice! I am good, thanks!');
    \$chatService->sendMessage(\$conv, \$user1, 'Great! Let me test the realtime chat.');
    echo '✅ Test messages added' . PHP_EOL;
}

echo PHP_EOL;
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
echo '✅ TEST DATA READY!' . PHP_EOL;
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
echo PHP_EOL;
echo '🔐 Login credentials:' . PHP_EOL;
echo '   User 1: alice@test.com / password' . PHP_EOL;
echo '   User 2: bob@test.com / password' . PHP_EOL;
echo PHP_EOL;
echo '🌐 Chat URL: http://localhost:8080/chat' . PHP_EOL;
echo PHP_EOL;
echo '📝 Next steps:' . PHP_EOL;
echo '   1. Mở 2 cửa sổ trình duyệt' . PHP_EOL;
echo '   2. Cửa sổ 1: Login alice@test.com' . PHP_EOL;
echo '   3. Cửa sổ 2: Login bob@test.com (incognito)' . PHP_EOL;
echo '   4. Cả 2 vào http://localhost:8080/chat' . PHP_EOL;
echo '   5. Mở DevTools → Console để xem logs' . PHP_EOL;
echo '   6. Gửi tin nhắn và kiểm tra realtime!' . PHP_EOL;
echo PHP_EOL;
"

echo ""
echo "🎉 Done! You can now test the chat."
