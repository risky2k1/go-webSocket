package redis

import (
	"context"
	"log"

	goredis "github.com/redis/go-redis/v9"
	"go-realtime/internal/hub"
)

func SubscribeChatMessages(rdb *goredis.Client, h *hub.Hub) {
	ctx := context.Background()

	sub := rdb.Subscribe(ctx, "chat.messages")
	ch := sub.Channel()

	log.Println("📡 Subscribed to Redis channel: chat.messages")

	for msg := range ch {
		h.Broadcast <- hub.RoomMessage{
			ConversationID: extractConversationID(msg.Payload),
			Message:        []byte(msg.Payload),
		}
	}
}
