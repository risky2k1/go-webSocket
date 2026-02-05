package redis

import (
	"encoding/json"
	"fmt"
)

func extractConversationID(payload string) string {
	// Try parsing with flexible types
	var data map[string]interface{}
	
	err := json.Unmarshal([]byte(payload), &data)
	if err != nil {
		return ""
	}
	
	// Try top level conversation_id first
	if convID, ok := data["conversation_id"]; ok {
		result := convertToString(convID)
		if result != "" {
			return result
		}
	}
	
	// Try nested data.conversation_id
	if dataObj, ok := data["data"].(map[string]interface{}); ok {
		if convID, ok := dataObj["conversation_id"]; ok {
			result := convertToString(convID)
			if result != "" {
				return result
			}
		}
	}
	
	return ""
}

// Helper to convert interface{} to string (handles int, float, string)
func convertToString(val interface{}) string {
	switch v := val.(type) {
	case string:
		return v
	case float64:
		return fmt.Sprintf("%.0f", v)
	case int:
		return fmt.Sprintf("%d", v)
	case int64:
		return fmt.Sprintf("%d", v)
	default:
		return ""
	}
}
