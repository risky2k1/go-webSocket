package redis

import (
	"context"
	"log"

	goredis "github.com/redis/go-redis/v9"

	"go-realtime/internal/hub"
)

func SubscribeChatMessages(rdb *goredis.Client, h *hub.Hub) {
	ctx := context.Background()

	// Subscribe to pattern *chat.message.* to handle prefixed channels (e.g. laravel_database_chat.message.1)
	sub := rdb.PSubscribe(ctx, "*chat.message.*")
	ch := sub.Channel()

	log.Println("📡 Subscribed to Redis pattern: *chat.message.*")

	for msg := range ch {
		conversationID := extractConversationID(msg.Payload)
		if conversationID == "" {
			continue
		}

		h.Broadcast <- hub.RoomMessage{
			ConversationID: conversationID,
			Message:        []byte(msg.Payload),
		}
	}
}
