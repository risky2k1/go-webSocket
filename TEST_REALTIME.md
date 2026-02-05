# Test Realtime WebSocket + Redis

## ✅ Đã Fix

### Go WebSocket Server
- ✅ Simple authentication với `user_id` (không cần token)
- ✅ Xử lý event "subscribe" và "join"
- ✅ Xử lý typing indicator
- ✅ Subscribe Redis pattern `chat.message.*`
- ✅ Fix bug parser conversation_id
- ✅ Support cả int và string cho conversation_id
- ✅ Broadcast message đến tất cả client trong room

### Laravel
- ✅ Publish Redis với đầy đủ thông tin sender
- ✅ Format message chuẩn cho WebSocket

---

## 🧪 Cách Test Realtime

### Bước 1: Kiểm tra services đang chạy

```bash
docker compose ps
```

Đảm bảo các service sau đang chạy:
- ✅ nginx (port 8080)
- ✅ php
- ✅ redis
- ✅ go-realtime (port 6001)

### Bước 2: Kiểm tra Go WebSocket Server logs

```bash
docker compose logs -f go-realtime
```

Bạn sẽ thấy:
```
🚀 Go Realtime Server running on :6001
📡 Subscribed to Redis pattern: chat.message.*
```

### Bước 3: Tạo dữ liệu test (nếu chưa có)

```bash
docker compose exec php php artisan tinker
```

```php
// Tạo 2 user
$user1 = \App\Models\User::factory()->create([
    'name' => 'Alice',
    'email' => 'alice@test.com',
    'password' => bcrypt('password')
]);

$user2 = \App\Models\User::factory()->create([
    'name' => 'Bob', 
    'email' => 'bob@test.com',
    'password' => bcrypt('password')
]);

// Tạo conversation
$chatService = app(\App\Services\ChatService::class);
auth()->login($user1);

$conv = $chatService->createConversation([$user2->id], 'private');

echo "✅ Conversation ID: {$conv->id}\n";
echo "✅ User 1: alice@test.com / password\n";
echo "✅ User 2: bob@test.com / password\n";
```

### Bước 4: Test Realtime trong 2 cửa sổ trình duyệt

#### Cửa sổ 1 - User Alice:
1. Mở http://localhost:8080/chat
2. Login: `alice@test.com` / `password`
3. Mở DevTools (F12) → Console
4. Chọn conversation với Bob

**Kiểm tra Console log:**
```
WebSocket connected
User 1 subscribing to conversation 1  // <- Trong Go logs
```

#### Cửa sổ 2 - User Bob:
1. Mở http://localhost:8080/chat (cửa sổ ẩn danh hoặc browser khác)
2. Login: `bob@test.com` / `password`
3. Mở DevTools (F12) → Console
4. Chọn cùng conversation

**Kiểm tra Console log:**
```
WebSocket connected
User 2 subscribing to conversation 1  // <- Trong Go logs
```

### Bước 5: Test gửi tin nhắn realtime

**Tại cửa sổ Alice:**
1. Gõ "Hello Bob!" vào ô chat
2. Click "Gửi"

**Kiểm tra:**
- ✅ Tin nhắn xuất hiện ngay tại cửa sổ Alice (bên phải, màu xanh)
- ✅ **Tin nhắn xuất hiện NGAY tại cửa sổ Bob** (bên trái, màu trắng) - KHÔNG CẦN RELOAD!

**Trong Go logs bạn sẽ thấy:**
```
📨 Broadcasting message to conversation 1
```

**Tại cửa sổ Bob:**
1. Gõ "Hi Alice!" vào ô chat
2. Click "Gửi"

**Kiểm tra:**
- ✅ Tin nhắn xuất hiện ngay tại cửa sổ Bob
- ✅ **Tin nhắn xuất hiện NGAY tại cửa sổ Alice** - REALTIME!

### Bước 6: Test Typing Indicator (đang nhập)

**Tại cửa sổ Alice:**
1. Click vào ô input
2. Bắt đầu gõ chữ (KHÔNG GỬI)

**Kiểm tra tại cửa sổ Bob:**
- ✅ Dưới header conversation phải hiện text "đang nhập..."
- ✅ Text tự động biến mất sau 2 giây khi Alice ngừng gõ

**Trong Go logs:**
```
User 1 typing in conversation 1
```

---

## 🔍 Debug WebSocket

### 1. Kiểm tra WebSocket connection

**Mở DevTools → Network → WS (WebSocket)**

Bạn sẽ thấy:
```
ws://localhost:6001/ws?user_id=1
Status: 101 Switching Protocols
```

Click vào connection → Messages tab để xem:

**Messages gửi đi (Frontend → Go):**
```json
{"event":"subscribe","conversation_id":1}
{"event":"typing","conversation_id":1,"user_id":1}
```

**Messages nhận về (Go → Frontend):**
```json
{
  "event": "message.sent",
  "conversation_id": 1,
  "data": {
    "conversation_id": 1,
    "message": {
      "id": 5,
      "user_id": 2,
      "content": "Hello!",
      "sender": {
        "id": 2,
        "name": "Bob"
      },
      "created_at": "2026-02-03T10:30:00.000000Z"
    }
  }
}
```

### 2. Test Redis Pub/Sub thủ công

**Terminal 1 - Subscribe:**
```bash
docker compose exec redis redis-cli
PSUBSCRIBE chat.message.*
```

**Terminal 2 - Gửi tin nhắn từ giao diện**

Tại Terminal 1, bạn sẽ thấy:
```
1) "pmessage"
2) "chat.message.*"
3) "chat.message.1"
4) "{\"event\":\"message.sent\",\"conversation_id\":1,...}"
```

### 3. Kiểm tra Go logs realtime

```bash
docker compose logs -f go-realtime
```

Khi gửi tin nhắn, bạn sẽ thấy:
```
User 1 subscribing to conversation 1
User 2 subscribing to conversation 1
User 1 typing in conversation 1
📨 Broadcasting message to conversation 1
```

---

## 🐛 Troubleshooting

### Lỗi: "WebSocket connection failed"

**Nguyên nhân:** Go server chưa chạy hoặc port 6001 bị block

**Fix:**
```bash
docker compose ps go-realtime
docker compose logs go-realtime
docker compose restart go-realtime
```

### Lỗi: Tin nhắn không realtime (phải reload mới thấy)

**Nguyên nhân:** WebSocket không connect hoặc không subscribe đúng room

**Kiểm tra:**
1. Mở DevTools → Console
2. Có thấy log "WebSocket connected" không?
3. Mở DevTools → Network → WS
4. Connection có status 101 không?

**Fix:**
```bash
# Restart Go server
docker compose restart go-realtime

# Check logs
docker compose logs -f go-realtime

# Hard reload frontend
Ctrl + Shift + R
```

### Lỗi: Typing indicator không hiện

**Nguyên nhân:** Frontend không gửi typing event hoặc Go không broadcast

**Kiểm tra Go logs:**
```bash
docker compose logs -f go-realtime | grep typing
```

Nếu không thấy log "User X typing", có nghĩa frontend không gửi.

**Kiểm tra frontend console:**
- Có error về WebSocket không?
- `wsConnected` có = true không?

### Lỗi: "Cannot extract conversation_id"

**Nguyên nhân:** Format message từ Redis không đúng

**Kiểm tra:**
```bash
docker compose exec redis redis-cli
PSUBSCRIBE chat.message.*
```

Gửi 1 tin nhắn và xem payload có đúng format không.

---

## 📊 Luồng hoạt động

### Gửi tin nhắn:

```
Frontend (Alice)
  ↓ POST /chat/conversations/1/messages
Laravel ChatController
  ↓ ChatService::sendMessage()
  ├─→ Lưu vào DB
  └─→ Redis::publish('chat.message.1', {...})
        ↓
Go Redis Subscriber
  ↓ Nhận message từ Redis
  ↓ Extract conversation_id
  ↓ h.Broadcast <- RoomMessage
Hub
  ↓ Broadcast đến tất cả clients trong room "1"
  ├─→ Client Alice (WebSocket)
  └─→ Client Bob (WebSocket) ← REALTIME!
        ↓
Frontend (Bob)
  ↓ handleWebSocketMessage()
  ↓ Thêm message vào messages[]
  ✅ Tin nhắn hiện ngay!
```

### Typing Indicator:

```
Frontend (Alice)
  ↓ @input event
  ↓ handleTyping()
  ↓ ws.send({event: 'typing', ...})
Go WebSocket
  ↓ readPump nhận event
  ↓ h.Broadcast <- typing event
Hub
  ↓ Broadcast đến clients khác trong room
  └─→ Client Bob (WebSocket)
        ↓
Frontend (Bob)
  ↓ handleWebSocketMessage()
  ↓ isTyping = true
  ✅ Hiện "đang nhập..."
  ↓ setTimeout 2s
  ✅ Ẩn "đang nhập..."
```

---

## ✅ Checklist Test Realtime

- [ ] 2 user login vào 2 cửa sổ khác nhau
- [ ] Cả 2 đều thấy "WebSocket connected" trong console
- [ ] Cả 2 đều chọn cùng 1 conversation
- [ ] Go logs hiện "User X subscribing to conversation Y"
- [ ] User 1 gửi tin → User 2 nhận NGAY (không reload)
- [ ] User 2 gửi tin → User 1 nhận NGAY (không reload)
- [ ] User 1 gõ chữ → User 2 thấy "đang nhập..."
- [ ] User 2 gõ chữ → User 1 thấy "đang nhập..."
- [ ] Text "đang nhập..." tự động biến mất sau 2s
- [ ] Tin nhắn mới cập nhật conversation list (last message)
- [ ] Auto scroll xuống khi có tin nhắn mới

---

## 🎉 Kết quả mong đợi

Khi hoàn tất, bạn sẽ có:
- ✅ Chat hoàn toàn realtime (như Facebook Messenger)
- ✅ Typing indicator hoạt động mượt mà
- ✅ Không cần reload trang
- ✅ Nhiều user có thể chat đồng thời
- ✅ Message broadcast instant qua WebSocket

**Chúc bạn test thành công!** 🚀
