package websocket

import (
	"net/http"

	"github.com/gorilla/websocket"

	"go-realtime/internal/domain"
	"go-realtime/internal/hub"
)

var upgrader = websocket.Upgrader{
	CheckOrigin: func(r *http.Request) bool { return true },
}

func ServeWS(h *hub.Hub, w http.ResponseWriter, r *http.Request) {
	conn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		return
	}

	client := &domain.Client{
		ID:   r.RemoteAddr,
		Send: make(chan []byte, 256),
	}

	go writePump(conn, client)
	go readPump(h, conn, client)
}
