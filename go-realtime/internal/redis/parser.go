package redis

import (
	"encoding/json"
	"fmt"
)

func extractConversationID(payload string) string {
	var data struct {
		ConversationID int64 `json:"conversation_id"`
		Data           struct {
			ConversationID int64 `json:"conversation_id"`
		} `json:"data"`
	}

	_ = json.Unmarshal([]byte(payload), &data)

	// Try top level first
	if data.ConversationID > 0 {
		return fmt.Sprintf("%d", data.ConversationID)
	}

	// Try nested data
	if data.Data.ConversationID > 0 {
		return fmt.Sprintf("%d", data.Data.ConversationID)
	}

	return ""
}
