package websocket

import (
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
		h.Broadcast <- message
	}
}
