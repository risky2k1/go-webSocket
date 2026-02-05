
export default function chatApp(conversations, currentUserId) {
    window.Alpine.data('chatApp', (conversations, currentUserId) => ({
        // Data from server
        conversations: conversations,
        currentUserId: currentUserId,

        // State
        selectedConversationId: null,
        selectedConversation: null,
        messages: [],
        messageInput: '',
        sending: false,
        loadingMessages: false,
        isTyping: false,

        // WebSocket
        ws: null,
        wsConnected: false,

        // Typing indicator
        typingTimeout: null,

        init() {
            // Connect WebSocket BEFORE
            this.connectWebSocket();

            // Auto-select first conversation (if any)
            // Delay slightly to allow WebSocket to connect
            if (this.conversations.length > 0) {
                setTimeout(() => {
                    this.selectConversation(this.conversations[0].id);
                }, 500);
            }
        },

        async selectConversation(conversationId) {
            // If already selected, do nothing
            if (this.selectedConversationId === conversationId && this.messages.length > 0) {
                return;
            }

            this.selectedConversationId = conversationId;
            this.selectedConversation = this.conversations.find(c => c.id === conversationId);

            // Load messages
            await this.loadMessages(conversationId);

            // Subscribe to conversation room via WebSocket
            if (this.wsConnected) {
                this.subscribeToConversation(conversationId);
            }
        },

        async loadMessages(conversationId) {
            this.loadingMessages = true;

            try {
                const response = await fetch(`/chat/conversations/${conversationId}/messages`);
                const data = await response.json();
                this.messages = data.messages || [];

                // Scroll to bottom
                this.$nextTick(() => {
                    this.scrollToBottom();
                });
            } catch (error) {
                console.error('Error loading messages:', error);
            } finally {
                this.loadingMessages = false;
            }
        },

        async sendMessage() {
            if (!this.messageInput.trim() || this.sending) {
                return;
            }

            const content = this.messageInput.trim();
            this.messageInput = '';
            this.sending = true;

            try {
                const response = await fetch(`/chat/conversations/${this.selectedConversationId}/messages`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        content: content,
                    }),
                });

                if (!response.ok) {
                    throw new Error('Failed to send message');
                }

                const data = await response.json();

                // Message will be received via WebSocket, no need to push manually
                if (!this.wsConnected) {
                    this.messages.push(data.message);
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                }
            } catch (error) {
                console.error('Error sending message:', error);
                // Restore message input
                this.messageInput = content;
            } finally {
                this.sending = false;
            }
        },

        handleTyping() {
            // Send typing indicator via WebSocket
            if (this.wsConnected && this.selectedConversationId) {
                this.sendTypingIndicator();
            }
        },

        sendTypingIndicator() {
            if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                const typingEvent = {
                    event: 'typing',
                    conversation_id: this.selectedConversationId,
                    user_id: this.currentUserId,
                };
                this.ws.send(JSON.stringify(typingEvent));
            }
        },

        connectWebSocket() {
            const wsUrl = `ws://localhost:6001/ws?user_id=${this.currentUserId}`;

            this.ws = new WebSocket(wsUrl);

            this.ws.onopen = () => {
                this.wsConnected = true;

                // Subscribe to current conversation
                if (this.selectedConversationId) {
                    this.subscribeToConversation(this.selectedConversationId);
                }
            };

            this.ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                this.handleWebSocketMessage(data);
            };

            this.ws.onerror = (error) => {
                console.error('WebSocket error:', error);
            };

            this.ws.onclose = () => {
                this.wsConnected = false;

                // Reconnect after 3 seconds
                setTimeout(() => {
                    this.connectWebSocket();
                }, 3000);
            };
        },

        subscribeToConversation(conversationId) {
            if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                this.ws.send(JSON.stringify({
                    event: 'subscribe',
                    conversation_id: conversationId,
                }));
            }
        },

        handleWebSocketMessage(data) {
            switch (data.event) {
                case 'message.sent':
                    // Check loosely for string/number match
                    if (data.data.conversation_id == this.selectedConversationId) {
                        const message = data.data.message;

                        // Check if message exists (avoid duplicates)
                        if (!this.messages.find(m => m.id === message.id)) {
                            this.messages.push(message);
                            this.$nextTick(() => {
                                this.scrollToBottom();
                            });
                        }
                    }

                    // Update lastMessage in conversation list
                    this.updateConversationLastMessage(data.data.conversation_id, data.data.message);
                    break;

                case 'typing':
                    // Receive typing indicator
                    // Check loosely for string/number match
                    if (data.conversation_id == this.selectedConversationId &&
                        data.user_id !== this.currentUserId) {
                        this.isTyping = true;

                        // Clear previous timeout
                        if (this.typingTimeout) {
                            clearTimeout(this.typingTimeout);
                        }

                        // Hide after 2 seconds
                        this.typingTimeout = setTimeout(() => {
                            this.isTyping = false;
                        }, 2000);
                    }
                    break;
            }
        },

        updateConversationLastMessage(conversationId, message) {
            // Check loosely for string/number match
            const conv = this.conversations.find(c => c.id == conversationId);
            if (conv) {
                conv.last_message = message;
            }
        },

        scrollToBottom() {
            const container = this.$refs.messagesContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        // Helper methods
        getConversationTitle(conv) {
            if (conv.type === 'group') {
                return conv.title || 'Chat nhóm';
            }

            // For private chat, get the other user's name
            const otherUser = conv.users?.find(u => u.id !== this.currentUserId);
            return otherUser?.name || 'Unknown';
        },

        getConversationInitials(conv) {
            const title = this.getConversationTitle(conv);
            const words = title.split(' ');
            if (words.length >= 2) {
                return (words[0][0] + words[words.length - 1][0]).toUpperCase();
            }
            return title.substring(0, 2).toUpperCase();
        },

        getUserInitials(user) {
            if (!user || !user.name) return '?';
            const words = user.name.split(' ');
            if (words.length >= 2) {
                return (words[0][0] + words[words.length - 1][0]).toUpperCase();
            }
            return user.name.substring(0, 2).toUpperCase();
        },

        formatTime(timestamp) {
            if (!timestamp) return '';

            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;

            // If less than 1 minute
            if (diff < 60000) {
                return 'Just now';
            }

            // If today
            if (date.toDateString() === now.toDateString()) {
                return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
            }

            // If yesterday
            const yesterday = new Date(now);
            yesterday.setDate(yesterday.getDate() - 1);
            if (date.toDateString() === yesterday.toDateString()) {
                return 'Yesterday';
            }

            // Otherwise
            return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' });
        },
    }));
}
