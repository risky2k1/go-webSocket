package main

import (
	"log"
	"net/http"

	"go-realtime/internal/hub"
	ws "go-realtime/internal/transport/websocket"
	"github.com/joho/godotenv"
	goredis "github.com/redis/go-redis/v9"
	"go-realtime/internal/redis"
)

func main() {
	if err := godotenv.Load(); err != nil {
		log.Println("⚠️  .env not found, using system env")
	}

	// 1. Create hub
	h := hub.NewHub()
	go h.Run()

	// 2. Setup Redis
	rdb := goredis.NewClient(&goredis.Options{
		Addr: "redis:6379",
	})
	go redis.SubscribeChatMessages(rdb, h)

	// 3. Setup HTTP handler
	http.HandleFunc("/ws", func(w http.ResponseWriter, r *http.Request) {
		ws.ServeWS(h, w, r)
	})

	log.Println("🚀 Go Realtime Server running on :6001")
	log.Fatal(http.ListenAndServe(":6001", nil))
}
