package domain

type Client struct {
	ID             string
    ConversationID string
    Send           chan []byte
}