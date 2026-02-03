package main

import (
	"log"
	"net/http"

	"go-realtime/internal/hub"
	ws "go-realtime/internal/transport/websocket"
)

func main() {
	h := hub.NewHub()
	go h.Run()

	http.HandleFunc("/ws", func(w http.ResponseWriter, r *http.Request) {
		ws.ServeWS(h, w, r)
	})

	log.Println("Go Realtime running on :6001")
	log.Fatal(http.ListenAndServe(":6001", nil))
}
