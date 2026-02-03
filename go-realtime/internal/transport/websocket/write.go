package websocket

import (
	"github.com/gorilla/websocket"
	"go-realtime/internal/domain"
)

func writePump(conn *websocket.Conn, client *domain.Client) {
	defer conn.Close()

	for {
		select {
		case msg, ok := <-client.Send:
			if !ok {
				// hub đã close channel
				_ = conn.WriteMessage(websocket.CloseMessage, []byte{})
				return
			}

			err := conn.WriteMessage(websocket.TextMessage, msg)
			if err != nil {
				return
			}
		}
	}
}
