package websocket

import (
	"github.com/gorilla/websocket"
	"go-realtime/internal/domain"
)

func writePump(conn *websocket.Conn, client *domain.Client) {
	defer conn.Close()

	for message := range client.Send {
		err := conn.WriteMessage(websocket.TextMessage, message)
		if err != nil {
			break
		}
	}
}
