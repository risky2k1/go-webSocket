package domain

type Client struct {
	ID   string
	Send chan []byte
}