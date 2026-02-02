package main

import (
	"log"
	"net/http"
)

func main() {
	http.HandleFunc("/ws", func(w http.ResponseWriter, r *http.Request) {
		w.Write([]byte("WebSocket endpoint"))
	})

	log.Println("Go Realtime running on :6001")
	log.Fatal(http.ListenAndServe(":6001", nil))
}
