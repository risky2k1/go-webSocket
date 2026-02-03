<x-layouts.app>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ __('Chat') }}
        </h1>
    </div>


    <!-- Chat Container: 2 cột với danh sách chat bên trái và khu vực chat bên phải -->
    <div class="flex gap-4 h-[calc(100vh-180px)]">
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
                    @forelse ($conversations as $conversation)
                        @if (!$conversation->isGroup())
                            <!-- Item chat 1 -->
                            <li
                                class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex-shrink-0 h-12 w-12 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center text-sm font-bold text-gray-700 dark:text-gray-200">
                                        AB
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                                An Bùi
                                            </h3>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">10:35</span>
                                        </div>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate">Tin nhắn cuối
                                            cùng...</p>
                                    </div>
                                </div>
                            </li>
                        @else
                            <!-- Item chat 2 - Active -->
                            <li
                                class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 hover:bg-blue-50 dark:hover:bg-blue-900/50 cursor-pointer transition-colors bg-blue-50 dark:bg-blue-900/30">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex-shrink-0 h-12 w-12 bg-indigo-500 dark:bg-indigo-600 rounded-full flex items-center justify-center text-sm font-bold text-white">
                                        CN
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                                Chat
                                                nhóm</h3>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">09:20</span>
                                        </div>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate">Thành viên mới đã
                                            tham
                                            gia...</p>
                                    </div>
                                </div>
                            </li>
                        @endif
                    @empty
                        <li class="px-4 py-3 text-gray-500 dark:text-gray-400">
                            {{ __('No conversations found') }}
                        </li>
                    @endforelse
                    <!-- Thêm các item chat khác tại đây -->
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
            <!-- Chat Header: Thông tin người/nhóm chat -->
            <div class="border-b border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900/50">
                <div class="flex items-center gap-3">
                    <div
                        class="flex-shrink-0 h-12 w-12 bg-indigo-500 dark:bg-indigo-600 rounded-full flex items-center justify-center text-sm font-bold text-white">
                        CN
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Chat nhóm</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400">3 thành viên</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Thêm các nút action tại đây (video call, info, etc.) -->
                        <button
                            class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Chat Body: Khu vực hiển thị tin nhắn -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 dark:bg-gray-900 custom-scrollbar">
                <!-- Tin nhắn đã gửi (bên phải) -->
                <div class="flex justify-end">
                    <div class="max-w-[70%]">
                        <div class="bg-blue-500 text-white p-3 rounded-2xl rounded-br-md shadow-sm">
                            <p class="text-sm">Xin chào mọi người!</p>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 text-right mt-1">Bạn • 10:35</div>
                    </div>
                </div>

                <!-- Tin nhắn đã nhận (bên trái) -->
                <div class="flex justify-start">
                    <div class="max-w-[70%]">
                        <div class="flex items-end gap-2 mb-1">
                            <div
                                class="h-8 w-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center text-xs font-bold text-gray-700 dark:text-gray-200 flex-shrink-0">
                                AB
                            </div>
                            <span class="text-xs text-gray-600 dark:text-gray-400">An Bùi</span>
                        </div>
                        <div class="ml-10">
                            <div
                                class="bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 p-3 rounded-2xl rounded-bl-md shadow-sm">
                                <p class="text-sm">Chào bạn!</p>
                                <p class="text-sm">Có gì mới nhỉ?</p>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">10:36</div>
                        </div>
                    </div>
                </div>

                <!-- Thêm các tin nhắn khác tại đây -->
            </div>

            <!-- Chat Input: Khu vực nhập tin nhắn -->
            <div class="border-t border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                <form class="flex items-center gap-2">
                    <button type="button"
                        class="p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <i class="fas fa-paperclip text-lg"></i>
                    </button>
                    <input type="text" placeholder="Nhập tin nhắn..."
                        class="flex-1 px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent text-sm text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400" />
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-medium px-5 py-2.5 rounded-lg shadow-sm transition-colors flex items-center gap-2">
                        <i class="fas fa-paper-plane"></i>
                        <span>Gửi</span>
                    </button>
                </form>
            </div>
        </div>
    </div>


</x-layouts.app>
