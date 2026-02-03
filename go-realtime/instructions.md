# Go services handle websocket for Laravel Realtime Chat apps
# Structure
- go-realtime
-- cmd
--- server
---- main.go
-- internal
--- domain
---- client.go
--- hub
---- hub.go
--- transport
---- websocket
----- handler.go
----- read.go
----- write.go
----- message.go
-- Dockerfile
-- go.mod
-- go.sum

# File content:
- main.go:
package main

import (
	"log"
	"net/http"

	"go-realtime/internal/hub"
	ws "go-realtime/internal/transport/websocket"
	"github.com/joho/godotenv"
)

func main() {
	if err := godotenv.Load(); err != nil {
		log.Println("⚠️ .env not found, using system env")
	}

	h := hub.NewHub()
	go h.Run()

	http.HandleFunc("/ws", func(w http.ResponseWriter, r *http.Request) {
		ws.ServeWS(h, w, r)
	})

	log.Println("Go Realtime running on :6001")
	log.Fatal(http.ListenAndServe(":6001", nil))
}

- client.go:
package domain

type Client struct {
	ID             string
    ConversationID string
    Send           chan []byte
    Token          string
	UserID         int64
}
- hub.go:
package hub

import "go-realtime/internal/domain"

type Hub struct {
	Rooms      map[string]map[*domain.Client]bool
	Users      map[int64]map[*domain.Client]bool
	Register   chan *domain.Client
	Unregister chan *domain.Client
	Broadcast  chan RoomMessage
	Join       chan *JoinRoom
	// Leave   	chan *LeaveRoom
}

func NewHub() *Hub {
	return &Hub{
		Rooms:      make(map[string]map[*domain.Client]bool),
		Users:      make(map[int64]map[*domain.Client]bool),
		Register:   make(chan *domain.Client),
		Unregister: make(chan *domain.Client),
		Broadcast:  make(chan RoomMessage),
		Join:       make(chan *JoinRoom),
	}
}

func (h *Hub) Run() {
	for {
		select {

		case client := <-h.Register:
			userID := client.UserID
		
			if _, ok := h.Users[userID]; !ok {
				h.Users[userID] = make(map[*domain.Client]bool)
			}
		
			h.Users[userID][client] = true

		case client := <-h.Unregister:
			// cleanup room (đã có)
			roomID := client.ConversationID
			if roomID != "" {
				if roomClients, ok := h.Rooms[roomID]; ok {
					delete(roomClients, client)
					if len(roomClients) == 0 {
						delete(h.Rooms, roomID)
					}
				}
			}
		
			// cleanup user
			userID := client.UserID
			if userClients, ok := h.Users[userID]; ok {
				delete(userClients, client)
				if len(userClients) == 0 {
					delete(h.Users, userID)
				}
			}
		
			close(client.Send)

		case msg := <-h.Broadcast:
			roomClients, ok := h.Rooms[msg.ConversationID]
			if !ok {
				break
			}
			for client := range roomClients {
				select {
				case client.Send <- msg.Message:
				default:
					close(client.Send)
					delete(roomClients, client)
				}
			}
			if len(roomClients) == 0 {
				delete(h.Rooms, msg.ConversationID)
			}

		case join := <-h.Join:
			newRoomID := join.ConversationID
			client := join.Client
		
			// ✅ LEAVE room cũ nếu có
			if client.ConversationID != "" {
				oldRoomID := client.ConversationID
				if roomClients, ok := h.Rooms[oldRoomID]; ok {
					delete(roomClients, client)
					if len(roomClients) == 0 {
						delete(h.Rooms, oldRoomID)
					}
				}
			}
		
			// ✅ JOIN room mới
			if _, ok := h.Rooms[newRoomID]; !ok {
				h.Rooms[newRoomID] = make(map[*domain.Client]bool)
			}
		
			client.ConversationID = newRoomID
			h.Rooms[newRoomID][client] = true
		}
	}
}

type JoinRoom struct {
    Client         *domain.Client
    ConversationID string
}

type RoomMessage struct {
    ConversationID string
    Message        []byte
}

type ChatMessage struct {
    Event   string `json:"event"`
    Content string `json:"content"`
}

type OutgoingMessage struct {
    Event string      `json:"event"`
    Data  interface{} `json:"data"`
}

type AuthUser struct {
    ID   int64  `json:"id"`
    Name string `json:"name"`
}

func verifyToken(token string) (*AuthUser, error) {
    req, err := http.NewRequest(
        "GET",
        "http://laravel/api/ws/me",
        nil,
    )
    if err != nil {
        return nil, err
    }

    req.Header.Set("Authorization", "Bearer "+token)

    client := &http.Client{
        Timeout: 3 * time.Second,
    }

    resp, err := client.Do(req)
    if err != nil {
        return nil, err
    }
    defer resp.Body.Close()

    if resp.StatusCode != http.StatusOK {
        return nil, errors.New("unauthorized")
    }

    var user AuthUser
    err = json.NewDecoder(resp.Body).Decode(&user)
    if err != nil {
        return nil, err
    }

    return &user, nil
}

type PersistMessagePayload struct {
    ConversationID string `json:"conversation_id"`
    SenderID       int64  `json:"sender_id"`
    Content        string `json:"content"`
}

func persistMessage(client *domain.Client, content string) {
    payload := PersistMessagePayload{
        ConversationID: client.ConversationID,
        SenderID:       client.UserID,
        Content:        content,
    }

    body, _ := json.Marshal(payload)

    req, _ := http.NewRequest(
        "POST",
        "http://laravel/api/ws/messages",
        bytes.NewBuffer(body),
    )

    req.Header.Set("Content-Type", "application/json")
    req.Header.Set("X-Internal-Token", "secret")

    http.DefaultClient.Do(req)
}

- handler.go:
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
	token := r.URL.Query().Get("token")
	if token == "" {
		http.Error(w, "Unauthorized", http.StatusUnauthorized)
		return
	}

	user, err := verifyToken(token)
	if err != nil {
		http.Error(w, "Unauthorized", http.StatusUnauthorized)
		return
	}

	h.Register <- client

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

	go writePump(conn, client)
	go readPump(h, conn, client)
}


- read.go:
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

			go persistMessage(client, msg.Content)

			h.Broadcast <- hub.RoomMessage{
				ConversationID: client.ConversationID,
				Message:        message,
			}
		default:
			continue
		}
	}
}


- write.go:
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

- message.go:
package websocket

type IncomingMessage struct {
    Event          string `json:"event"`
    ConversationID string `json:"conversation_id,omitempty"`
    Content        string `json:"content,omitempty"`
}
- go.mod:
module go-realtime

go 1.22

require (
    github.com/gorilla/websocket v1.5.1
    github.com/redis/go-redis/v9 v9.5.1
)
