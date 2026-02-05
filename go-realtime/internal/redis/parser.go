package redis

import (
	"encoding/json"
	"fmt"
	"log"
)

func extractConversationID(payload string) string {
	// Try parsing with flexible types
	var data map[string]interface{}
	
	err := json.Unmarshal([]byte(payload), &data)
	if err != nil {
		log.Printf("❌ Failed to parse JSON payload: %v", err)
		return ""
	}
	
	log.Printf("🔍 Parsing payload: %s", payload)
	
	// Try top level conversation_id first
	if convID, ok := data["conversation_id"]; ok {
		result := convertToString(convID)
		if result != "" {
			log.Printf("✅ Found conversation_id at top level: %s", result)
			return result
		}
	}
	
	// Try nested data.conversation_id
	if dataObj, ok := data["data"].(map[string]interface{}); ok {
		if convID, ok := dataObj["conversation_id"]; ok {
			result := convertToString(convID)
			if result != "" {
				log.Printf("✅ Found conversation_id in data: %s", result)
				return result
			}
		}
	}
	
	log.Printf("⚠️  No conversation_id found in payload")
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
