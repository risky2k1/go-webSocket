package hub

import "go-realtime/internal/domain"

type Hub struct {
	Rooms      map[string]map[*domain.Client]bool
	Register   chan *domain.Client
	Unregister chan *domain.Client
	Broadcast  chan RoomMessage
	Join       chan *JoinRoom
	// Leave   	chan *LeaveRoom
}

func NewHub() *Hub {
	return &Hub{
		Rooms:      make(map[string]map[*domain.Client]bool),
		// Register:   make(chan *domain.Client),
		Unregister: make(chan *domain.Client),
		Broadcast:  make(chan RoomMessage),
		Join:       make(chan *JoinRoom),
	}
}

func (h *Hub) Run() {
	for {
		select {

		// case client := <-h.Register:
		// 	h.Clients[client] = true

		case client := <-h.Unregister:
			roomID := client.ConversationID
			if roomID != "" {
				if roomClients, ok := h.Rooms[roomID]; ok {
					delete(roomClients, client)
					if len(roomClients) == 0 {
						delete(h.Rooms, roomID)
					}
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