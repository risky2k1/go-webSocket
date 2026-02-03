package service

import (
	"bytes"
	"encoding/json"
	"errors"
	"net/http"
	"os"
	"time"

	"go-realtime/internal/domain"
)

type AuthUser struct {
	ID   int64  `json:"id"`
	Name string `json:"name"`
}

func VerifyToken(token string) (*AuthUser, error) {
	baseURL := os.Getenv("LARAVEL_BASE_URL")
	if baseURL == "" {
		return nil, errors.New("LARAVEL_BASE_URL not set")
	}

	req, err := http.NewRequest(
		"GET",
		baseURL+"/api/ws/me",
		nil,
	)
	if err != nil {
		return nil, err
	}

	req.Header.Set("Authorization", "Bearer "+token)

	client := &http.Client{Timeout: 3 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return nil, errors.New("unauthorized")
	}

	var user AuthUser
	if err := json.NewDecoder(resp.Body).Decode(&user); err != nil {
		return nil, err
	}

	return &user, nil
}

type PersistMessagePayload struct {
	ConversationID string `json:"conversation_id"`
	SenderID       int64  `json:"sender_id"`
	Content        string `json:"content"`
}

func PersistMessage(client *domain.Client, content string) {
	baseURL := os.Getenv("LARAVEL_BASE_URL")
	internalToken := os.Getenv("INTERNAL_API_TOKEN")

	if baseURL == "" || internalToken == "" {
		return
	}

	payload := PersistMessagePayload{
		ConversationID: client.ConversationID,
		SenderID:       client.UserID,
		Content:        content,
	}

	body, _ := json.Marshal(payload)

	req, err := http.NewRequest(
		"POST",
		baseURL+"/api/ws/messages",
		bytes.NewBuffer(body),
	)
	if err != nil {
		return
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-Internal-Token", internalToken)

	http.DefaultClient.Do(req)
}
