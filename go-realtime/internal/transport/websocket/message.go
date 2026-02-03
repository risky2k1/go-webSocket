package websocket

import (
	"encoding/json"
	"fmt"
)

type IncomingMessage struct {
	Event          string          `json:"event"`
	ConversationID json.RawMessage `json:"conversation_id,omitempty"`
	Content        string          `json:"content,omitempty"`
	UserID         int64           `json:"user_id,omitempty"`
}

// GetConversationID extracts conversation_id as string (handles both int and string)
func (m *IncomingMessage) GetConversationID() string {
	if len(m.ConversationID) == 0 {
		return ""
	}

	// Try parse as int first
	var intID int64
	if err := json.Unmarshal(m.ConversationID, &intID); err == nil {
		return fmt.Sprintf("%d", intID)
	}

	// Try parse as string
	var strID string
	if err := json.Unmarshal(m.ConversationID, &strID); err == nil {
		return strID
	}

	return ""
}
