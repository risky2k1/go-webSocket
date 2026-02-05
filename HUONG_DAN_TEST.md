# Hướng Dẫn Test Chat Realtime - Phase 1

## 🔥 UPDATE: Realtime đã hoạt động!

**Các vấn đề đã fix:**
- ✅ Go WebSocket server nhận đúng user_id
- ✅ Subscribe event hoạt động
- ✅ Typing indicator broadcast realtime
- ✅ Messages broadcast realtime qua Redis + WebSocket
- ✅ Bug parser conversation_id đã fix

**Xem chi tiết test realtime tại: `TEST_REALTIME.md`**

---

## ✅ Đã Hoàn Thành

### 1. Giao diện chat cơ bản
- ✅ Danh sách hội thoại bên trái
- ✅ Khu vực chat bên phải
- ✅ Chọn cuộc hội thoại (click vào conversation)
- ✅ Hiển thị tin nhắn (tin của bạn bên phải màu xanh, tin người khác bên trái màu trắng)
- ✅ Form gửi tin nhắn

### 2. Chức năng realtime
- ✅ Gửi tin nhắn
- ✅ Nhận tin nhắn realtime qua WebSocket
- ✅ Hiển thị "đang nhập..." khi người khác đang gõ
- ✅ Tự động scroll xuống khi có tin nhắn mới
- ✅ Cập nhật tin nhắn cuối trong danh sách conversation

---

## 🧪 Cách Test

### Bước 1: Tạo dữ liệu test

Vào Laravel Tinker để tạo user và conversation mẫu:

```bash
docker compose exec php php artisan tinker
```

Chạy các lệnh sau trong Tinker:

```php
// Lấy hoặc tạo user
$user1 = \App\Models\User::first();
$user2 = \App\Models\User::skip(1)->first();

// Nếu chưa có user, tạo mới
if (!$user1) {
    $user1 = \App\Models\User::factory()->create([
        'name' => 'Tuấn',
        'email' => 'tuan@example.com',
        'password' => bcrypt('password')
    ]);
}

if (!$user2) {
    $user2 = \App\Models\User::factory()->create([
        'name' => 'An',
        'email' => 'an@example.com',
        'password' => bcrypt('password')
    ]);
}

// Tạo ChatService
$chatService = app(\App\Services\ChatService::class);

// Login làm user1
auth()->login($user1);

// Tạo cuộc hội thoại riêng với user2
$conv1 = $chatService->createConversation([$user2->id], 'private');

// Gửi vài tin nhắn mẫu
$chatService->sendMessage($conv1, $user1, 'Xin chào An!');
$chatService->sendMessage($conv1, $user2, 'Chào Tuấn! Khỏe không?');
$chatService->sendMessage($conv1, $user1, 'Mình khỏe, cảm ơn bạn!');

// Tạo nhóm chat
$conv2 = $chatService->createConversation([$user2->id], 'group', 'Team PHP');
$chatService->sendMessage($conv2, $user1, 'Chào mừng đến nhóm!');
$chatService->sendMessage($conv2, $user2, 'Cảm ơn!');

echo "Đã tạo xong dữ liệu test!\n";
echo "User 1: " . $user1->email . " / password\n";
echo "User 2: " . $user2->email . " / password\n";
```

### Bước 2: Truy cập giao diện chat

1. Đảm bảo tất cả service đang chạy:
```bash
docker compose up -d
```

2. Truy cập: **http://localhost:8080/chat**

3. Đăng nhập bằng một trong các user vừa tạo

### Bước 3: Test các tính năng

#### ✅ Test chọn conversation:
1. Click vào các conversation khác nhau trong danh sách bên trái
2. Kiểm tra conversation được chọn có highlight màu xanh
3. Kiểm tra header hiển thị đúng tên conversation
4. Kiểm tra tin nhắn load đúng cho conversation đã chọn

#### ✅ Test hiển thị tin nhắn:
1. Tin nhắn của bạn hiện bên phải (nền xanh)
2. Tin nhắn của người khác hiện bên trái (nền trắng)
3. Avatar hiển thị chữ cái đầu tên
4. Thời gian hiển thị đúng

#### ✅ Test gửi tin nhắn:
1. Gõ tin nhắn vào ô input
2. Click "Gửi" hoặc nhấn Enter
3. Tin nhắn xuất hiện ngay trong chat
4. Ô input được clear sau khi gửi
5. Nút "Gửi" hiện "Đang gửi..." khi đang xử lý

#### ✅ Test Realtime (quan trọng!):

**Cách 1: Mở 2 cửa sổ trình duyệt**
1. Mở 2 cửa sổ trình duyệt (hoặc dùng chế độ ẩn danh cho cửa sổ thứ 2)
2. Đăng nhập 2 user khác nhau (user1 và user2)
3. Cả 2 đều vào **http://localhost:8080/chat**
4. Cả 2 đều chọn cùng 1 conversation
5. Gửi tin nhắn từ cửa sổ 1
6. **Kiểm tra:** Tin nhắn phải xuất hiện ngay lập tức ở cửa sổ 2 (không cần reload)

**Cách 2: Kiểm tra Console**
1. Mở DevTools (F12) → Tab Console
2. Kiểm tra log "WebSocket connected"
3. Gửi tin nhắn và xem log WebSocket message

#### ✅ Test Typing Indicator (đang nhập):
1. Mở 2 cửa sổ với 2 user khác nhau
2. Cả 2 chọn cùng conversation
3. Gõ text vào ô input ở cửa sổ 1
4. **Kiểm tra:** Ở cửa sổ 2, phải hiện text "đang nhập..." dưới tên conversation
5. **Kiểm tra:** Text "đang nhập..." tự động biến mất sau 2 giây

---

## 🔍 Kiểm tra WebSocket

### Check WebSocket connection:

```bash
# Check Go server logs
docker compose logs -f go-realtime
```

Bạn sẽ thấy các log như:
- "WebSocket client connected"
- "User subscribed to conversation"
- Message broadcast logs

### Test Redis Pub/Sub:

```bash
# Vào Redis CLI
docker compose exec redis redis-cli

# Subscribe channel
SUBSCRIBE chat.message.*
```

Sau đó gửi tin nhắn từ giao diện, bạn sẽ thấy message được publish qua Redis.

---

## 🎯 Checklist Test

- [ ] Danh sách conversation hiển thị đúng
- [ ] Click chọn conversation → highlight màu xanh
- [ ] Messages load và hiển thị đúng vị trí (trái/phải)
- [ ] Avatar và tên user hiển thị đúng
- [ ] Thời gian format đúng (tiếng Việt)
- [ ] Gửi tin nhắn thành công
- [ ] Input clear sau khi gửi
- [ ] **REALTIME**: Tin nhắn xuất hiện ngay ở cửa sổ khác (không reload)
- [ ] **REALTIME**: Typing indicator hoạt động
- [ ] WebSocket auto-reconnect khi disconnect
- [ ] Scroll tự động xuống khi có tin nhắn mới
- [ ] Empty state hiển thị khi chưa chọn conversation
- [ ] Loading state hiển thị khi đang load messages

---

## 🐛 Nếu gặp lỗi

### Lỗi: "WebSocket connection failed"
- Kiểm tra Go server có chạy không: `docker compose ps`
- Kiểm tra port 6001 có available không
- Check logs: `docker compose logs go-realtime`

### Lỗi: "Cannot read property 'content'"
- Kiểm tra data structure trong console
- Kiểm tra API response format

### Lỗi: "CSRF token mismatch"
- Hard reload trang (Ctrl + Shift + R)
- Clear cache
- Kiểm tra meta tag csrf-token có tồn tại

### Tin nhắn không realtime
- Check WebSocket connection trong Console
- Check Go server logs
- Check Redis: `docker compose exec redis redis-cli PING`

---

## 📊 Cấu trúc Code

```
Frontend (Alpine.js):
├── chatApp() - Main Alpine component
├── init() - Khởi tạo, tự động chọn conversation đầu
├── selectConversation() - Chọn và load messages
├── loadMessages() - Fetch messages từ API
├── sendMessage() - Gửi tin nhắn
├── handleTyping() - Xử lý typing indicator
├── connectWebSocket() - Kết nối WebSocket
├── handleWebSocketMessage() - Nhận message từ WS
└── Helper methods (format time, get initials, etc.)

Backend API:
├── GET /chat - Index page
├── GET /chat/conversations/{id}/messages - Lấy tin nhắn
└── POST /chat/conversations/{id}/messages - Gửi tin nhắn

WebSocket Events:
├── subscribe - Subscribe vào conversation
├── message.sent - Nhận tin nhắn mới
└── typing - Typing indicator
```

---

## ⏭️ Tiếp theo làm gì?

Bạn có thể chọn implement các tính năng sau:

### 1. New Conversation (Tạo cuộc hội thoại mới)
- Modal để tạo conversation
- Search và chọn user
- Tạo private chat hoặc group chat

### 2. Message Features
- Read receipts (đã đọc)
- Message reactions (emoji)
- Reply to message
- Delete/Edit message
- File attachments

### 3. UI Improvements
- Emoji picker
- Unread badge
- User online status
- Message search
- Infinite scroll/pagination

**Bạn muốn làm tính năng nào tiếp theo?**
