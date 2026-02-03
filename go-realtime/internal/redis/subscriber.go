package redis

import (
	"context"
	"log"

	goredis "github.com/redis/go-redis/v9"

	"go-realtime/internal/hub"
)

func SubscribeChatMessages(rdb *goredis.Client, h *hub.Hub) {
	ctx := context.Background()

	// Subscribe to pattern chat.message.* để nhận tất cả conversation
	sub := rdb.PSubscribe(ctx, "chat.message.*")
	ch := sub.Channel()

	log.Println("📡 Subscribed to Redis pattern: chat.message.*")

	for msg := range ch {
		conversationID := extractConversationID(msg.Payload)
		if conversationID == "" {
			log.Printf("⚠️  Could not extract conversation_id from payload: %s", msg.Payload)
			continue
		}

		log.Printf("📨 Broadcasting message to conversation %s", conversationID)

		h.Broadcast <- hub.RoomMessage{
			ConversationID: conversationID,
			Message:        []byte(msg.Payload),
		}
	}
}
