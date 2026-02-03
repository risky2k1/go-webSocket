package websocket

import (
	"encoding/json"
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
			continue
		}

		if msg.Event == "join" {
			h.Join <- &hub.JoinRoom{
				Client: client,
				ConversationID: msg.ConversationID,
			}
			continue
		}

		if msg.Event == "message" {
			if client.ConversationID == "" {
				continue
			}
		
			h.Broadcast <- hub.RoomMessage{
				ConversationID: client.ConversationID,
				Message:        message,
			}
		}
	}
}
