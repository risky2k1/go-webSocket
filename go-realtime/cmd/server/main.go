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
	rdb := goredis.NewClient(&goredis.Options{
		Addr: "redis:6379",
	})
	go redis.SubscribeChatMessages(rdb, hub)

	if err := godotenv.Load(); err != nil {
		log.Println("⚠️ .env not found, using system env")
	}
	

	h := hub.NewHub()
	go h.Run()

	http.HandleFunc("/ws", func(w http.ResponseWriter, r *http.Request) {
		ws.ServeWS(h, w, r)
	})

	log.Println("Go Realtime running on :6001")
	log.Fatal(http.ListenAndServe(":6001", nil))
}
