package websocket

import (
	"encoding/json"
	"log"

	"github.com/gorilla/websocket"

	"go-realtime/internal/domain"
	"go-realtime/internal/hub"
)

func readPump(h *hub.Hub, conn *websocket.Conn, client *domain.Client) {
	defer func() {
		h.Unregister <- client
		conn.Close()
	}()

	for {
		_, message, err := conn.ReadMessage()
		if err != nil {
			break
		}

		var msg IncomingMessage
		err = json.Unmarshal(message, &msg)
		if err != nil {
			log.Printf("Error unmarshaling message: %v", err)
			continue
		}

		switch msg.Event {
		case "subscribe", "join":
			// Client muốn subscribe vào một conversation
			conversationID := msg.GetConversationID()
			if conversationID == "" {
				continue
			}

			log.Printf("User %d subscribing to conversation %s", client.UserID, conversationID)

			h.Join <- &hub.JoinRoom{
				Client:         client,
				ConversationID: conversationID,
			}

		case "typing":
			// Client đang typing
			if client.ConversationID == "" {
				continue
			}

			log.Printf("User %d typing in conversation %s", client.UserID, client.ConversationID)

			// Broadcast typing event đến những người khác trong room
			typingMsg := map[string]interface{}{
				"event":           "typing",
				"conversation_id": client.ConversationID,
				"user_id":         client.UserID,
			}

			typingBytes, _ := json.Marshal(typingMsg)

			h.Broadcast <- hub.RoomMessage{
				ConversationID: client.ConversationID,
				Message:        typingBytes,
				ExcludeClient:  client, // Don't send typing event back to sender
			}

		default:
			log.Printf("Unknown event: %s", msg.Event)
			continue
		}
	}
}
