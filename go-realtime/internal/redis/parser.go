package redis

import "encoding/json"

func extractConversationID(payload string) string {
	var data struct {
		ConversationID int64 `json:"conversation_id"`
	}

	_ = json.Unmarshal([]byte(payload), &data)
	return string(rune(data.ConversationID))
}
