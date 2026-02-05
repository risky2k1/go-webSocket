<x-layouts.app>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ __('Chat') }}
        </h1>
    </div>


    <!-- Chat Container: 2 cột với danh sách chat bên trái và khu vực chat bên phải -->
    <div class="flex gap-4 h-[calc(100vh-180px)]" x-data="chatApp()" x-init="init()">
        <!-- Left Sidebar: Danh sách các cuộc hội thoại -->
        <div
            class="w-80 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col">
            <!-- Header danh sách chat -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ __('Conversations') }}</h2>
            </div>

            <!-- Danh sách các cuộc hội thoại -->
            <div class="flex-1 overflow-y-auto custom-scrollbar">
                <ul>
                    <template x-for="conv in conversations" :key="conv.id">
                        <li @click="selectConversation(conv.id)"
                            :class="selectedConversationId === conv.id ?
                                'bg-blue-50 dark:bg-blue-900/30' :
                                'hover:bg-gray-50 dark:hover:bg-gray-700'"
                            class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 cursor-pointer transition-colors">
                            <div class="flex items-center gap-3">
                                <div :class="conv.type === 'group' ?
                                    'bg-indigo-500 dark:bg-indigo-600 text-white' :
                                    'bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200'"
                                    class="flex-shrink-0 h-12 w-12 rounded-full flex items-center justify-center text-sm font-bold">
                                    <span x-text="getConversationInitials(conv)"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate"
                                            x-text="getConversationTitle(conv)">
                                        </h3>
                                        <span class="text-xs text-gray-500 dark:text-gray-400"
                                            x-text="formatTime(conv.last_message?.created_at)">
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 truncate"
                                        x-text="conv.last_message?.content || 'Chưa có tin nhắn'">
                                    </p>
                                </div>
                            </div>
                        </li>
                    </template>

                    <template x-if="conversations.length === 0">
                        <li class="px-4 py-3 text-gray-500 dark:text-gray-400 text-center">
                            {{ __('No conversations found') }}
                        </li>
                    </template>
                </ul>
            </div>

            <!-- Footer: Nút tạo cuộc hội thoại mới -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <button
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium px-4 py-2.5 rounded-lg shadow-sm transition-colors">
                    <i class="fas fa-plus mr-2"></i>{{ __('New Conversation') }}
                </button>
            </div>
        </div>

        <!-- Right Section: Khu vực chat -->
        <div
            class="flex-1 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col">

            <!-- Empty state: Chưa chọn conversation -->
            <template x-if="!selectedConversation">
                <div class="flex-1 flex items-center justify-center text-gray-500 dark:text-gray-400">
                    <div class="text-center">
                        <i class="fas fa-comments text-6xl mb-4 opacity-50"></i>
                        <p class="text-lg">{{ __('Select a conversation to start chatting') }}</p>
                    </div>
                </div>
            </template>

            <!-- Chat area: Khi đã chọn conversation -->
            <template x-if="selectedConversation">
                <div class="flex-1 flex flex-col">
                    <!-- Chat Header: Thông tin người/nhóm chat -->
                    <div class="border-b border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900/50">
                        <div class="flex items-center gap-3">
                            <div :class="selectedConversation.type === 'group' ?
                                'bg-indigo-500 dark:bg-indigo-600 text-white' :
                                'bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200'"
                                class="flex-shrink-0 h-12 w-12 rounded-full flex items-center justify-center text-sm font-bold">
                                <span x-text="getConversationInitials(selectedConversation)"></span>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100"
                                    x-text="getConversationTitle(selectedConversation)"></h3>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    <span x-show="selectedConversation.type === 'group'"
                                        x-text="selectedConversation.users?.length + ' thành viên'">
                                    </span>
                                    <span x-show="selectedConversation.type !== 'group' && isTyping"
                                        class="text-blue-500">
                                        đang nhập...
                                    </span>
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Body: Khu vực hiển thị tin nhắn -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 dark:bg-gray-900 custom-scrollbar"
                        x-ref="messagesContainer">

                        <!-- Loading messages -->
                        <template x-if="loadingMessages">
                            <div class="flex items-center justify-center h-full">
                                <div class="text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-spinner fa-spin text-2xl"></i>
                                    <p class="mt-2">{{ __('Loading messages...') }}</p>
                                </div>
                            </div>
                        </template>

                        <!-- Messages list -->
                        <template x-if="!loadingMessages">
                            <div>
                                <template x-for="message in messages" :key="message.id">
                                    <div :class="message.user_id === currentUserId ? 'flex justify-end' : 'flex justify-start'"
                                        class="mb-4">

                                        <!-- Tin nhắn của người khác (bên trái) -->
                                        <template x-if="message.user_id !== currentUserId">
                                            <div class="max-w-[70%]">
                                                <div class="flex items-end gap-2 mb-1">
                                                    <div
                                                        class="h-8 w-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center text-xs font-bold text-gray-700 dark:text-gray-200 flex-shrink-0">
                                                        <span x-text="getUserInitials(message.sender)"></span>
                                                    </div>
                                                    <span class="text-xs text-gray-600 dark:text-gray-400"
                                                        x-text="message.sender?.name">
                                                    </span>
                                                </div>
                                                <div class="ml-10">
                                                    <div
                                                        class="bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 p-3 rounded-2xl rounded-bl-md shadow-sm">
                                                        <p class="text-sm whitespace-pre-wrap" x-text="message.content">
                                                        </p>
                                                    </div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                                                        x-text="formatTime(message.created_at)">
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Tin nhắn của bạn (bên phải) -->
                                        <template x-if="message.user_id === currentUserId">
                                            <div class="max-w-[70%]">
                                                <div
                                                    class="bg-blue-500 text-white p-3 rounded-2xl rounded-br-md shadow-sm">
                                                    <p class="text-sm whitespace-pre-wrap" x-text="message.content"></p>
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 text-right mt-1">
                                                    <span>Bạn • </span>
                                                    <span x-text="formatTime(message.created_at)"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <!-- Empty messages -->
                                <template x-if="messages.length === 0">
                                    <div
                                        class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
                                        <p>{{ __('No messages yet. Start the conversation!') }}</p>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Chat Input: Khu vực nhập tin nhắn -->
                    <div class="border-t border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                        <form @submit.prevent="sendMessage" class="flex items-center gap-2">
                            <button type="button"
                                class="p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <i class="fas fa-paperclip text-lg"></i>
                            </button>
                            <input type="text" placeholder="Nhập tin nhắn..." x-model="messageInput"
                                @input="handleTyping" :disabled="sending"
                                class="flex-1 px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent text-sm text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 disabled:opacity-50" />
                            <button type="submit" :disabled="!messageInput.trim() || sending"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-medium px-5 py-2.5 rounded-lg shadow-sm transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-paper-plane" :class="{ 'fa-spin': sending }"></i>
                                <span x-text="sending ? 'Đang gửi...' : 'Gửi'"></span>
                            </button>
                        </form>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <script>
        function chatApp() {
            return {
                // Data từ server
                conversations: @json($conversations),
                currentUserId: {{ auth()->id() }},

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
                    // Kết nối WebSocket TRƯỚC
                    this.connectWebSocket();

                    // Tự động chọn conversation đầu tiên (nếu có)
                    // Delay một chút để WebSocket có thời gian connect
                    if (this.conversations.length > 0) {
                        setTimeout(() => {
                            this.selectConversation(this.conversations[0].id);
                        }, 500);
                    }
                },

                async selectConversation(conversationId) {
                    // Nếu đã chọn conversation này rồi thì không làm gì
                    if (this.selectedConversationId === conversationId && this.messages.length > 0) {
                        return;
                    }

                    this.selectedConversationId = conversationId;
                    this.selectedConversation = this.conversations.find(c => c.id === conversationId);

                    // Load messages
                    await this.loadMessages(conversationId);

                    // Subscribe to conversation room qua WebSocket
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
                        alert('Không thể tải tin nhắn. Vui lòng thử lại.');
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

                    console.log('💬 Sending message:', content);

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

                        console.log('📡 Response status:', response.status);

                        if (!response.ok) {
                            const errorData = await response.json();
                            console.error('❌ Error response:', errorData);
                            throw new Error('Failed to send message');
                        }

                        const data = await response.json();
                        console.log('✅ Message sent:', data);

                        // Message sẽ được nhận qua WebSocket, không cần push thủ công
                        // Nhưng nếu chưa có WS, có thể push tạm
                        if (!this.wsConnected) {
                            console.log('⚠️ WebSocket not connected, adding message manually');
                            this.messages.push(data.message);
                            this.$nextTick(() => {
                                this.scrollToBottom();
                            });
                        } else {
                            console.log('⏳ Waiting for WebSocket broadcast...');
                        }
                    } catch (error) {
                        console.error('❌ Error sending message:', error);
                        alert('Không thể gửi tin nhắn. Vui lòng thử lại.');
                        // Restore message input
                        this.messageInput = content;
                    } finally {
                        this.sending = false;
                    }
                },

                handleTyping() {
                    // Gửi typing indicator qua WebSocket
                    if (this.wsConnected && this.selectedConversationId) {
                        console.log('⌨️ User is typing - sending indicator');
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
                        console.log('📤 Sending typing event:', typingEvent);
                        this.ws.send(JSON.stringify(typingEvent));
                    }
                },

                connectWebSocket() {
                    const wsUrl = `ws://localhost:6001/ws?user_id=${this.currentUserId}`;

                    this.ws = new WebSocket(wsUrl);

                    this.ws.onopen = () => {
                        console.log('WebSocket connected');
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
                        console.log('WebSocket disconnected');
                        this.wsConnected = false;

                        // Reconnect after 3 seconds
                        setTimeout(() => {
                            this.connectWebSocket();
                        }, 3000);
                    };
                },

                subscribeToConversation(conversationId) {
                    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                        console.log('📡 Subscribing to conversation:', conversationId);
                        this.ws.send(JSON.stringify({
                            event: 'subscribe',
                            conversation_id: conversationId,
                        }));
                    } else {
                        console.warn('⚠️ Cannot subscribe - WebSocket not ready');
                    }
                },

                handleWebSocketMessage(data) {
                    console.log('📨 WebSocket message received:', data);

                    switch (data.event) {
                        case 'message.sent':
                            console.log('💬 New message event');
                            // Nhận tin nhắn mới
                            if (data.data.conversation_id == this.selectedConversationId) {
                                const message = data.data.message;

                                // Kiểm tra xem message đã tồn tại chưa (tránh duplicate)
                                if (!this.messages.find(m => m.id === message.id)) {
                                    console.log('➕ Adding message to list:', message);
                                    this.messages.push(message);
                                    this.$nextTick(() => {
                                        this.scrollToBottom();
                                    });
                                } else {
                                    console.log('⚠️ Message already exists, skipping');
                                }
                            } else {
                                console.log('⚠️ Message for different conversation');
                            }

                            // Cập nhật lastMessage trong conversation list
                            this.updateConversationLastMessage(data.data.conversation_id, data.data.message);
                            break;

                        case 'typing':
                            console.log('⌨️ Typing event received:', data);
                            console.log('  📍 Current conversation:', this.selectedConversationId);
                            console.log('  👤 Current user ID:', this.currentUserId);
                            console.log('  📨 Event conversation:', data.conversation_id);
                            console.log('  👤 Event user ID:', data.user_id);

                            // Nhận typing indicator
                            if (data.conversation_id == this.selectedConversationId &&
                                data.user_id !== this.currentUserId) {
                                console.log('✅ Conditions met - Showing typing indicator');
                                console.log('  🔄 isTyping changed: false → true');
                                this.isTyping = true;

                                // Clear previous timeout
                                if (this.typingTimeout) {
                                    clearTimeout(this.typingTimeout);
                                    console.log('  ⏱️  Cleared previous timeout');
                                }

                                // Hide after 2 seconds
                                this.typingTimeout = setTimeout(() => {
                                    console.log('  🔄 isTyping changed: true → false');
                                    this.isTyping = false;
                                    console.log('🙈 Hiding typing indicator');
                                }, 2000);
                            } else {
                                console.log('❌ Conditions NOT met - Typing indicator NOT shown');
                                if (data.conversation_id != this.selectedConversationId) {
                                    console.log('  ⚠️  Different conversation');
                                }
                                if (data.user_id === this.currentUserId) {
                                    console.log('  ⚠️  Same user (sender)');
                                }
                            }
                            break;

                        default:
                            console.log('⚠️ Unknown event:', data.event);
                    }
                },

                updateConversationLastMessage(conversationId, message) {
                    const conv = this.conversations.find(c => c.id === conversationId);
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
                        return 'Vừa xong';
                    }

                    // If today
                    if (date.toDateString() === now.toDateString()) {
                        return date.toLocaleTimeString('vi-VN', {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }

                    // If yesterday
                    const yesterday = new Date(now);
                    yesterday.setDate(yesterday.getDate() - 1);
                    if (date.toDateString() === yesterday.toDateString()) {
                        return 'Hôm qua';
                    }

                    // Otherwise
                    return date.toLocaleDateString('vi-VN', {
                        day: '2-digit',
                        month: '2-digit'
                    });
                },
            };
        }
    </script>

</x-layouts.app>
