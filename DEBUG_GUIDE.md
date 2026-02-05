# Debug Guide - Realtime Chat

## 🐛 Đã thêm logging để debug

### Frontend (Browser Console)
Mở DevTools → Console, bạn sẽ thấy các log:

```
🔌 WebSocket đang kết nối...
✅ WebSocket connected
📡 Subscribing to conversation: 1
💬 Sending message: Hello
📡 Response status: 200
✅ Message sent: {...}
⏳ Waiting for WebSocket broadcast...
📨 WebSocket message received: {...}
💬 New message event
➕ Adding message to list: {...}
```

### Backend (Laravel Logs)
```bash
docker compose logs -f php --tail=50 | grep -E "💬|📤|✅|⛔"
```

Bạn sẽ thấy:
```
💬 Sending message
📤 Publishing to Redis
✅ Published to Redis successfully
✅ Message sent successfully
```

### Go Server Logs
```bash
docker compose logs -f go-realtime --tail=50
```

Bạn sẽ thấy:
```
🚀 Go Realtime Server running on :6001
📡 Subscribed to Redis pattern: chat.message.*
User 1 subscribing to conversation 1
📨 Broadcasting message to conversation 1
```

---

## 🧪 Test Step by Step

### 1. Mở 2 cửa sổ trình duyệt

**Cửa sổ 1 (Normal):**
- Truy cập: http://localhost:8080/chat
- Mở DevTools (F12) → Console
- Login với user 1

**Cửa sổ 2 (Incognito/Private):**
- Truy cập: http://localhost:8080/chat
- Mở DevTools (F12) → Console  
- Login với user 2

### 2. Kiểm tra WebSocket Connection

**Tại mỗi cửa sổ, check Console:**

✅ **Phải thấy:**
```
WebSocket connected
📡 Subscribing to conversation: X
```

❌ **Nếu thấy:**
```
WebSocket error: ...
Cannot subscribe - WebSocket not ready
```
→ Có vấn đề với WebSocket connection

### 3. Test gửi tin nhắn

**Tại cửa sổ User 1:**
1. Chọn conversation
2. Gõ "Test message 1"
3. Click "Gửi"

**Check Console cửa sổ 1:**
```
💬 Sending message: Test message 1
📡 Response status: 200
✅ Message sent: {...}
⏳ Waiting for WebSocket broadcast...
📨 WebSocket message received: {...}
➕ Adding message to list: {...}
```

**Check Console cửa sổ 2 (User 2):**
```
📨 WebSocket message received: {...}
💬 New message event
➕ Adding message to list: {...}
```

**Check Laravel logs:**
```bash
docker compose logs php --tail=20 | grep "💬"
```

Phải thấy:
```
💬 Sending message
📤 Publishing to Redis
✅ Published to Redis successfully
```

**Check Go logs:**
```bash
docker compose logs go-realtime --tail=20
```

Phải thấy:
```
📨 Broadcasting message to conversation 1
```

### 4. Test Typing Indicator

**Tại cửa sổ User 1:**
1. Click vào ô input
2. Bắt đầu gõ (KHÔNG GỬI)

**Check Console cửa sổ 1:**
```
(Không có log gì - vì là người gõ)
```

**Check Console cửa sổ 2:**
```
📨 WebSocket message received: {event: "typing", ...}
⌨️ Typing event
👀 Showing typing indicator
🙈 Hiding typing indicator (sau 2s)
```

**Check Go logs:**
```
User 1 typing in conversation 1
```

---

## 🔍 Common Issues

### Issue 1: WebSocket không connect

**Triệu chứng:**
```
WebSocket error: ...
```

**Check:**
```bash
# Go server có chạy không?
docker compose ps go-realtime

# Port 6001 có mở không?
docker compose logs go-realtime --tail=10
```

**Fix:**
```bash
docker compose restart go-realtime
```

### Issue 2: Gửi tin nhắn nhưng không realtime

**Triệu chứng:**
- Ấn gửi → Tin nhắn không xuất hiện
- Reload → Mới thấy tin nhắn

**Debug Console:**

❌ **Nếu không thấy log "💬 Sending message"**
→ JavaScript có lỗi hoặc form không submit

❌ **Nếu thấy "📡 Response status: 500"**
→ Laravel có lỗi

❌ **Nếu thấy "✅ Message sent" nhưng KHÔNG thấy "📨 WebSocket message received"**
→ Redis hoặc Go server không broadcast

**Check Laravel logs:**
```bash
docker compose logs php --tail=50 | grep -E "💬|📤|✅"
```

Phải thấy cả 3 dòng:
```
💬 Sending message
📤 Publishing to Redis  
✅ Published to Redis successfully
```

❌ **Nếu KHÔNG thấy "📤 Publishing to Redis"**
→ `pushRealtime()` không được gọi

**Check Go logs:**
```bash
docker compose logs go-realtime --tail=50 | grep "Broadcasting"
```

❌ **Nếu KHÔNG thấy "📨 Broadcasting message"**
→ Go không nhận được message từ Redis

**Test Redis manually:**
```bash
# Terminal 1 - Subscribe
docker compose exec redis redis-cli
PSUBSCRIBE chat.message.*

# Terminal 2 - Gửi tin nhắn từ web
# Terminal 1 phải thấy message được publish
```

### Issue 3: Typing indicator không hiện

**Check Console cửa sổ người nhận:**

❌ **Nếu không thấy log "📨 WebSocket message received"**
→ WebSocket không hoạt động

❌ **Nếu thấy "📨" nhưng không thấy "⌨️ Typing event"**
→ Event type không đúng hoặc conversation_id không khớp

❌ **Nếu thấy "⚠️ Message for different conversation"**
→ 2 user không cùng conversation

**Check Go logs:**
```bash
docker compose logs go-realtime --tail=50 | grep "typing"
```

Phải thấy:
```
User X typing in conversation Y
```

---

## 🛠️ Quick Fixes

### Reset Everything

```bash
# Restart all services
docker compose restart

# Clear Laravel cache
docker compose exec php php artisan cache:clear

# Check all services running
docker compose ps
```

### View All Logs Together

```bash
# Terminal 1 - Go logs
docker compose logs -f go-realtime

# Terminal 2 - Laravel logs  
docker compose logs -f php

# Terminal 3 - Redis monitor
docker compose exec redis redis-cli
MONITOR
```

### Test API Directly

```bash
# Test send message API
curl -X POST http://localhost:8080/chat/conversations/1/messages \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Cookie: laravel_session=YOUR_SESSION" \
  -d '{"content":"Test from curl"}'
```

---

## ✅ Expected Flow

### Successful Message Send:

```
Frontend (User 1)
  ↓ console.log("💬 Sending message")
  ↓ POST /chat/conversations/1/messages
Laravel Controller
  ↓ Log::info("💬 Sending message")
  ↓ ChatService::sendMessage()
  ↓ Save to DB
  ↓ pushRealtime()
  ↓ Log::info("📤 Publishing to Redis")
  ↓ Redis::publish('chat.message.1', {...})
  ↓ Log::info("✅ Published to Redis successfully")
Redis
  ↓ Broadcast to subscribers
Go Server
  ↓ Subscriber receives message
  ↓ log.Printf("📨 Broadcasting message to conversation 1")
  ↓ h.Broadcast <- RoomMessage{...}
Hub
  ↓ Send to all clients in room
WebSocket Clients
  ├─→ User 1: console.log("📨 WebSocket message received")
  │           console.log("💬 New message event")
  │           console.log("➕ Adding message to list")
  │
  └─→ User 2: console.log("📨 WebSocket message received")
              console.log("💬 New message event")  
              console.log("➕ Adding message to list")
```

---

## 📊 Debug Checklist

- [ ] Go server running và subscribed Redis
- [ ] Laravel logs show "💬 Sending message"
- [ ] Laravel logs show "📤 Publishing to Redis"
- [ ] Go logs show "📨 Broadcasting message"
- [ ] Frontend console show "✅ WebSocket connected"
- [ ] Frontend console show "📡 Subscribing to conversation"
- [ ] Frontend console show "💬 Sending message"
- [ ] Frontend console show "📨 WebSocket message received"
- [ ] Message xuất hiện ở cả 2 cửa sổ
- [ ] Typing indicator hoạt động

Nếu TẤT CẢ đều ✅ → Realtime hoạt động hoàn hảo! 🎉
