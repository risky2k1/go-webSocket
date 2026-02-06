<x-layouts.app>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ __('Chat') }}
        </h1>
    </div>


    <!-- Chat Container: 2 cột với danh sách chat bên trái và khu vực chat bên phải -->
    <div class="flex gap-4 h-[calc(100vh-180px)]" x-data='chatApp(@json($conversations), {{ auth()->id() }})'
        x-init="init()">
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

</x-layouts.app>
