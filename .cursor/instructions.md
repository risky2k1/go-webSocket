# Laravel + Go Realtime Chat (Redis + WebSocket)

Tài liệu này tổng hợp **toàn bộ các bước đã làm** để xây dựng hệ thống **Realtime Chat** sử dụng:

* **Laravel 11/12** (API + DB)
* **Go** (WebSocket server)
* **Redis** (Pub/Sub)
* **Docker Compose**

Mục tiêu: **Laravel lưu message → Redis publish → Go subscribe → WebSocket fanout**.

---

## 1. Kiến trúc tổng thể

```
Client (Web)
   │
   │ WebSocket
   ▼
Go Realtime Server
   │
   │ HTTP (internal)
   ▼
Laravel API
   │
   │ Redis Pub/Sub
   ▼
Redis
   │
   │ Subscribe
   ▼
Go Realtime Server
   │
   ▼
Broadcast WS cho các client trong room
```

---

## 2. Docker Compose setup

### 2.1 Services

* `nginx` : expose Laravel (port 8080)
* `php` : PHP-FPM chạy Laravel
* `redis` : Pub/Sub
* `go-realtime` : WebSocket server (port 6001)

### 2.2 Port mapping quan trọng

| Service     | Port     | Mục đích          |
| ----------- | -------- | ----------------- |
| nginx       | 8080     | Laravel Web + API |
| go-realtime | 6001     | WebSocket         |
| redis       | internal | Pub/Sub           |

---

## 3. Laravel 12 – API setup

### 3.1 Laravel 12 routing (không còn Kernel)

Laravel 11/12 **KHÔNG có** `Kernel.php` và **KHÔNG có sẵn** `api.php`.

➡️ Phải khai báo API route trong `bootstrap/app.php`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    api: __DIR__.'/../routes/api.php',
)
```

---

### 3.2 Tạo `routes/api.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WsMessageController;

Route::post('/ws/messages', [WsMessageController::class, 'store']);
```

---

## 4. Laravel – Message API

### 4.1 Model `Message`

```php
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
}
```

---

### 4.2 Controller `WsMessageController`

```php
class WsMessageController extends Controller
{
    public function store(Request $request)
    {
        // Verify internal token
        if ($request->header('X-Internal-Token') !== config('services.ws.internal_token')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'conversation_id' => ['required', 'integer'],
            'sender_id'       => ['required', 'integer'],
            'content'         => ['required', 'string'],
        ]);

        $message = Message::create([
            'chat_conversation_id' => $data['conversation_id'],
            'user_id'              => $data['sender_id'],
            'type'                 => 'text',
            'content'              => $data['content'],
            'meta'                 => null,
        ]);

        // Publish Redis
        Redis::publish('chat.messages', json_encode([
            'conversation_id' => $message->chat_conversation_id,
            'id'              => $message->id,
            'user_id'         => $message->user_id,
            'type'            => $message->type,
            'content'         => $message->content,
            'meta'            => $message->meta,
            'created_at'      => $message->created_at->toISOString(),
        ]));

        return response()->json(['id' => $message->id, 'status' => 'ok']);
    }
}
```

---

### 4.3 Laravel ENV

```env
WS_INTERNAL_TOKEN=secret
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379
```

`config/services.php`

```php
'ws' => [
    'internal_token' => env('WS_INTERNAL_TOKEN'),
],
```

---

## 5. Redis trong Laravel

### 5.1 PHP extension bắt buộc

PHP container **PHẢI có** `phpredis`:

```dockerfile
RUN pecl install redis \
    && docker-php-ext-enable redis
```

Test:

```bash
php -m | grep redis
```

---

## 6. Go Realtime Server

### 6.1 Nhiệm vụ của Go

* Xác thực user qua Laravel
* Quản lý rooms
* Subscribe Redis
* Fanout WebSocket

---

### 6.2 Redis subscribe (Go)

```go
func SubscribeChatMessages(rdb *redis.Client, h *hub.Hub) {
    ctx := context.Background()

    sub := rdb.Subscribe(ctx, "chat.messages")
    ch := sub.Channel()

    for msg := range ch {
        h.Broadcast <- hub.RoomMessage{
            ConversationID: extractConversationID(msg.Payload),
            Message:        []byte(msg.Payload),
        }
    }
}
```

---

### 6.3 Init trong `main.go`

```go
rdb := redis.NewClient(&redis.Options{
    Addr: "redis:6379",
})

go SubscribeChatMessages(rdb, hub)
```

---

## 7. Test & Debug

### 7.1 Test API Laravel

⚠️ **BẮT BUỘC** có header `Accept: application/json`

```bash
curl -X POST http://localhost:8080/api/ws/messages \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-Internal-Token: secret" \
  -d '{"conversation_id":1,"sender_id":1,"content":"Hello"}'
```

---

### 7.2 Test Redis

```bash
docker compose exec redis redis-cli
SUBSCRIBE chat.messages
```

---

### 7.3 Test Go logs

```bash
docker compose logs -f go-realtime
```

---

## 8. Những lỗi thường gặp

| Lỗi                     | Nguyên nhân                      | Fix                       |
| ----------------------- | -------------------------------- | ------------------------- |
| 302 redirect `/`        | Thiếu `Accept: application/json` | Thêm header               |
| `Class Redis not found` | PHP chưa cài phpredis            | Cài extension             |
| API không ăn            | `api.php` chưa được load         | Check `bootstrap/app.php` |

---

## 9. Trạng thái hiện tại

✅ Laravel API OK
✅ Redis Pub/Sub OK
✅ Go subscribe OK
✅ WebSocket broadcast OK

---

## 10. Các bước tiếp theo (tuỳ chọn)

* 5.4 Read receipt (`message_reads`)
* 5.5 Typing indicator
* 5.6 Auth reconnect / resume
* 5.7 Redis Stream (durable messages)

---

🎉 **Hệ thống realtime chat đã sẵn sàng để scale production.**
