package websocket

import (
	"fmt"
	"net/http"

	"github.com/gorilla/websocket"

	"go-realtime/internal/domain"
	"go-realtime/internal/hub"
)

var upgrader = websocket.Upgrader{
	CheckOrigin: func(r *http.Request) bool { return true },
}

func ServeWS(h *hub.Hub, w http.ResponseWriter, r *http.Request) {
	// Simple auth: Lấy user_id từ query string
	userIDStr := r.URL.Query().Get("user_id")
	if userIDStr == "" {
		http.Error(w, "Missing user_id", http.StatusBadRequest)
		return
	}

	// Convert user_id to int64
	var userID int64
	_, err := fmt.Sscanf(userIDStr, "%d", &userID)
	if err != nil || userID <= 0 {
		http.Error(w, "Invalid user_id", http.StatusBadRequest)
		return
	}

	// Upgrade to WebSocket
	conn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		return
	}

	// Create client
	client := &domain.Client{
		ID:     r.RemoteAddr,
		UserID: userID,
		Send:   make(chan []byte, 256),
	}

	h.Register <- client

	go writePump(conn, client)
	go readPump(h, conn, client)
}
