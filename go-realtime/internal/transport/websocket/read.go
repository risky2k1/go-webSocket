package websocket

import (
	"encoding/json"
	"github.com/gorilla/websocket"
	"go-realtime/internal/domain"
	"go-realtime/internal/hub"
	"go-realtime/internal/service"
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
			continue
		}

		switch msg.Event {
		case "join":
			if msg.ConversationID == "" {
				continue
			}

			h.Join <- &hub.JoinRoom{
				Client: client,
				ConversationID: msg.ConversationID,
			}
		case "message":
			if client.ConversationID == "" || msg.Content == "" {
				continue
			}

			go service.PersistMessage(client, msg.Content)

			h.Broadcast <- hub.RoomMessage{
				ConversationID: client.ConversationID,
				Message:        message,
			}
		default:
			continue
		}
	}
}
