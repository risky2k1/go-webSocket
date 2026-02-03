package websocket

import (
	"net/http"

	"github.com/gorilla/websocket"

	"go-realtime/internal/domain"
	"go-realtime/internal/hub"
	"go-realtime/internal/service"
)

var upgrader = websocket.Upgrader{
	CheckOrigin: func(r *http.Request) bool { return true },
}

func ServeWS(h *hub.Hub, w http.ResponseWriter, r *http.Request) {
	token := r.URL.Query().Get("token")
	if token == "" {
		http.Error(w, "Unauthorized", http.StatusUnauthorized)
		return
	}

	user, err := service.VerifyToken(token)
	if err != nil {
		http.Error(w, "Unauthorized", http.StatusUnauthorized)
		return
	}

	conn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		return
	}

	client := &domain.Client{
		ID:   r.RemoteAddr,
		Token: token,
		UserID: user.ID,
		Send: make(chan []byte, 256),
	}

	h.Register <- client

	go writePump(conn, client)
	go readPump(h, conn, client)
}
