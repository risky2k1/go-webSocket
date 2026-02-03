package websocket

type IncomingMessage struct {
    Event          string `json:"event"`
    ConversationID string `json:"conversation_id"`
}