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
				// Skip the excluded client (e.g., sender of typing event)
				if msg.ExcludeClient != nil && client == msg.ExcludeClient {
					continue
				}
				
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
    ExcludeClient  *domain.Client // Don't send to this client (e.g., sender of typing event)
}

type ChatMessage struct {
    Event   string `json:"event"`
    Content string `json:"content"`
}

type OutgoingMessage struct {
    Event string      `json:"event"`
    Data  interface{} `json:"data"`
}

type PersistMessagePayload struct {
    ConversationID string `json:"conversation_id"`
    SenderID       int64  `json:"sender_id"`
    Content        string `json:"content"`
}
