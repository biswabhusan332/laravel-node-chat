<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Chat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="http://localhost:3000/socket.io/socket.io.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Custom scrollbar for Webkit browsers */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background-color: rgba(156, 163, 175, 0.5);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background-color: rgba(107, 114, 128, 0.8);
        }
    </style>
</head>

<body class="bg-gray-100 h-screen flex overflow-hidden">

    <!-- Sidebar (Optional, for future use) -->
    <div class="hidden md:flex flex-col w-64 bg-slate-900 text-white shadow-xl">
        <div class="p-6 border-b border-slate-700">
            <h1 class="text-2xl font-bold tracking-tight text-blue-400">ChatApp</h1>
            <p class="text-xs text-slate-400 mt-1">Real-time workspace</p>
        </div>
        <div class="flex-1 overflow-y-auto p-4">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Online Users</div>
            <!-- Placeholder for users -->
            <div class="space-y-3">
                <div class="flex items-center gap-3 opacity-75 hover:opacity-100 cursor-pointer transition">
                    <div class="w-2 h-2 rounded-full bg-green-500"></div>
                    <span class="text-sm font-medium">You</span>
                </div>
                <!-- Add more static users here if needed -->
            </div>
        </div>
        <div class="p-4 border-t border-slate-800 bg-slate-900/50">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-500 to-purple-500 flex items-center justify-center text-xs font-bold">U</div>
                <div>
                    <input type="text" id="username" class="bg-transparent text-sm font-medium focus:outline-none text-white placeholder-slate-500 w-full" placeholder="Enter your name">
                    <div class="text-[10px] text-slate-400">Click to change name</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Chat Area -->
    <div class="flex-1 flex flex-col bg-white shadow-2xl m-0 md:m-4 md:rounded-2xl overflow-hidden relative">

        <!-- Header -->
        <div class="bg-white border-b border-gray-100 p-4 flex items-center justify-between shadow-sm z-10">
            <div class="flex items-center gap-3">
                <div class="md:hidden w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold">C</div>
                <div>
                    <h2 class="font-bold text-gray-800">General Channel</h2>
                    <p class="text-xs text-green-500 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> Live
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <!-- Mobile username input fallback -->
                <input type="text" id="username-mobile" class="md:hidden border-b border-gray-200 text-sm focus:outline-none focus:border-blue-500 w-32 text-right" placeholder="Your Name">
            </div>
        </div>

        <!-- Chat Messages -->
        <div id="chat-box" class="flex-1 overflow-y-auto p-6 space-y-4 bg-slate-50 scroll-smooth">
            <!-- Messages will be injected here -->
        </div>

        <!-- Input Area -->
        <div class="p-4 bg-white border-t border-gray-100">
            <div class="flex items-end gap-3 max-w-4xl mx-auto bg-gray-50 p-2 rounded-xl border border-gray-200 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100 transition-all shadow-sm">
                <input type="text" id="message-input" class="flex-1 bg-transparent border-none focus:ring-0 text-gray-700 placeholder-gray-400 px-4 py-3 max-h-32" placeholder="Type your message..." autocomplete="off">
                <button id="send-btn" class="bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-lg transition-colors shadow-md hover:shadow-lg active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                        <path d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z" />
                    </svg>
                </button>
            </div>
            <div class="text-center mt-2 text-[10px] text-gray-400">Press Enter to send</div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let socket;
            if (typeof io !== 'undefined') {
                socket = io('http://localhost:3000');
            } else {
                console.error('Socket.io client not loaded.');
                // Optional: Show a subtle toast instead of alert
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

            const chatBox = document.getElementById('chat-box');
            const messageInput = document.getElementById('message-input');
            const usernameInput = document.getElementById('username');
            const usernameMobile = document.getElementById('username-mobile');
            const sendBtn = document.getElementById('send-btn');

            // Sync username inputs
            function syncUsernames(val) {
                usernameInput.value = val;
                usernameMobile.value = val;
                localStorage.setItem('chat_username', val);
            }

            // Initial setup
            const savedName = localStorage.getItem('chat_username') || 'Guest_' + Math.floor(Math.random() * 1000);
            syncUsernames(savedName);

            usernameInput.addEventListener('input', (e) => syncUsernames(e.target.value));
            usernameMobile.addEventListener('input', (e) => syncUsernames(e.target.value));

            // Fetch messages
            window.axios.get('/messages')
                .then(response => {
                    response.data.forEach(msg => appendMessage(msg.username, msg.message, msg.created_at));
                    scrollToBottom();
                })
                .catch(err => console.error(err));

            // Socket listener
            if (socket) {
                socket.on('chat message', (msg) => {
                    appendMessage(msg.username, msg.message);
                    scrollToBottom();
                });
            }

            // Send Logic
            function sendMessage() {
                const message = messageInput.value.trim();
                const username = usernameInput.value.trim(); // Use the main input as source of truth

                if (!message) return;
                if (!username) {
                    alert('Please enter your name first');
                    return;
                }

                // Optimistic UI update
                appendMessage(username, message, new Date().toISOString(), true);
                messageInput.value = '';
                scrollToBottom();

                // API Call
                window.axios.post('/messages', {
                        username,
                        message
                    })
                    .then(() => {
                        if (socket) {
                            socket.emit('chat message', {
                                username,
                                message,
                                created_at: new Date().toISOString()
                            });
                        }
                    })
                    .catch(err => {
                        console.error('Failed to send', err);
                        // Optional: Show error state on the message
                    });
            }

            sendBtn.addEventListener('click', sendMessage);
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') sendMessage();
            });

            function appendMessage(username, message, timestamp, isSelf = false) {
                const currentName = usernameInput.value;
                const isMe = isSelf || (username === currentName);

                const div = document.createElement('div');
                div.className = `flex flex-col ${isMe ? 'items-end' : 'items-start'} animate-fade-in-up`;

                // Avatar (Initials)
                const initial = username.charAt(0).toUpperCase();
                const avatarColor = isMe ? 'bg-blue-600' : 'bg-gray-400';

                div.innerHTML = `
                    <div class="flex items-end gap-2 max-w-[85%] md:max-w-[70%] ${isMe ? 'flex-row-reverse' : 'flex-row'}">
                        <div class="w-6 h-6 rounded-full ${avatarColor} text-white flex items-center justify-center text-[10px] font-bold shadow-sm shrink-0 mb-1">
                            ${initial}
                        </div>
                        <div class="flex flex-col ${isMe ? 'items-end' : 'items-start'}">
                            <span class="text-[10px] text-gray-400 mb-1 px-1">${username}</span>
                            <div class="${isMe ? 'bg-blue-600 text-white rounded-br-none' : 'bg-white text-gray-800 border border-gray-200 rounded-bl-none'} px-4 py-2.5 rounded-2xl shadow-sm text-sm leading-relaxed break-words">
                                ${message}
                            </div>
                        </div>
                    </div>
                `;
                chatBox.appendChild(div);
            }

            function scrollToBottom() {
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        });
    </script>
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.3s ease-out forwards;
        }
    </style>
</body>

</html>