<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// If no session ID exists, create one
if (!isset($_SESSION['chat_session_id'])) {
    $_SESSION['chat_session_id'] = uniqid() . time();
}

// Load configuration
$config = require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام المطابقة الذكي</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .chat-container {
            height: calc(100vh - 180px);
        }
        .message {
            max-width: 80%;
        }
        .user-message {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .bot-message {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .sidebar {
            width: 300px;
            transition: all 0.3s ease;
        }
        .sidebar.collapsed {
            width: 60px;
        }
        .conversation-item {
            transition: all 0.2s ease;
        }
        .conversation-item:hover {
            background-color: rgba(102, 126, 234, 0.1);
        }
        .conversation-item.active {
            background-color: rgba(102, 126, 234, 0.2);
        }
        /* Error message styling */
        .error-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #ff4444;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        /* Sidebar styling */
        .sidebar {
            width: 300px;
            height: 100vh;
            background-color: #f8f9fa;
            border-left: 1px solid #dee2e6;
            padding: 20px;
            overflow-y: auto;
        }
        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .sidebar-header h2 {
            margin: 0;
            color: #333;
        }
        .new-chat-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .user-info {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 15px;
        }
        .user-info .username {
            font-weight: 600;
            color: #333;
        }
        .user-info .logout-btn {
            color: #dc3545;
            text-decoration: none;
            font-size: 0.9em;
        }
        .user-info .logout-btn:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-800">نظام المطابقة الذكي</h1>
                <div class="flex items-center gap-4">
                    <span class="text-gray-600">مرحباً، <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php" class="text-red-600 hover:text-red-700">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                </div>
            </div>
        </div>

        <div class="flex gap-6">
            <!-- Sidebar -->
            <div id="sidebar" class="sidebar bg-white rounded-lg shadow-lg p-4">
                <div class="user-info">
                    <div class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                </div>
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">المحادثات السابقة</h2>
                    <button id="toggleSidebar" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div id="conversationsList" class="space-y-2">
                    <!-- Conversations will be added here dynamically -->
                </div>
            </div>

            <!-- Main Chat Area -->
            <div class="flex-1 bg-white rounded-lg shadow-lg p-6">
                <div id="chatContainer" class="chat-container overflow-y-auto mb-4 space-y-4">
                    <!-- Messages will be added here -->
                </div>
                
                <div class="flex gap-4">
                    <input type="text" id="message-input" 
                           class="flex-1 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           placeholder="اكتب رسالتك هنا...">
                    <button id="send-button" 
                            class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentConversationId = null;
        let currentOffset = 0;
        let hasMoreConversations = true;
        let conversationsPerPage = <?php echo $config['conversations_per_page']; ?>;

        // Load conversations from the server
        async function loadConversations(loadMore = false) {
            try {
                if (!loadMore) {
                    currentOffset = 0;
                    hasMoreConversations = true;
                }
                
                const response = await fetch(`api/conversations.php?limit=${conversationsPerPage}&offset=${currentOffset}`);
                const data = await response.json();
                console.log('Conversations API Response:', data); // Debug log
                
                const conversations = data.conversations;
                hasMoreConversations = data.hasMore;
                
                const sidebar = document.querySelector('.sidebar');
                let conversationsList = loadMore ? 
                    document.querySelector('.conversations-list') : 
                    document.createElement('div');
                
                if (!loadMore) {
                    sidebar.innerHTML = `
                        <div class="user-info">
                            <div class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                            <a href="logout.php" class="logout-btn">
                                <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                            </a>
                        </div>
                        <div class="sidebar-header">
                            <h2>المحادثات</h2>
                            <button onclick="createNewConversation()" class="new-chat-btn">
                                <i class="fas fa-plus"></i> محادثة جديدة
                            </button>
                        </div>
                        <div class="conversations-list"></div>
                    `;
                    conversationsList = sidebar.querySelector('.conversations-list');
                }
                
                if (!conversations || conversations.length === 0) {
                    console.log('No conversations found'); // Debug log
                    conversationsList.innerHTML = '<div class="text-gray-500 text-center py-4">لا توجد محادثات</div>';
                    return;
                }
                
                const newConversations = conversations.map(conv => {
                    console.log('Processing conversation:', conv); // Debug log
                    // Format the date
                    const date = new Date(conv.updated_at);
                    const formattedDate = date.toLocaleDateString('ar-SA', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    // Get first line of the message
                    const firstLine = conv.last_message ? 
                        conv.last_message.split('\n')[0].substring(0, 50) + 
                        (conv.last_message.split('\n')[0].length > 50 ? '...' : '') : 
                        'بدون رسائل';
                    
                    return `
                        <div class="conversation-item ${conv.id === currentConversationId ? 'active' : ''}" 
                             onclick="loadConversation('${conv.id}')">
                            <div class="flex justify-between items-start mb-1">
                                <div class="conversation-title font-semibold">${conv.title}</div>
                                <div class="conversation-time text-sm text-gray-500">${formattedDate}</div>
                            </div>
                            <div class="conversation-preview text-sm text-gray-600">${firstLine}</div>
                        </div>
                    `;
                }).join('');
                
                if (loadMore) {
                    conversationsList.insertAdjacentHTML('beforeend', newConversations);
                } else {
                    conversationsList.innerHTML = newConversations;
                }
                
                // Add Load More button if there are more conversations
                if (hasMoreConversations) {
                    const loadMoreBtn = document.createElement('button');
                    loadMoreBtn.className = 'w-full mt-4 p-2 text-center text-indigo-600 hover:text-indigo-800 font-semibold';
                    loadMoreBtn.innerHTML = '<i class="fas fa-chevron-down"></i> تحميل المزيد';
                    loadMoreBtn.onclick = () => {
                        currentOffset += conversationsPerPage;
                        loadConversations(true);
                    };
                    conversationsList.appendChild(loadMoreBtn);
                }
                
            } catch (error) {
                console.error('Error loading conversations:', error);
                showError('حدث خطأ أثناء تحميل المحادثات');
            }
        }

        // Create a new conversation
        async function createNewConversation() {
            try {
                const response = await fetch('api/conversations.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        title: 'محادثة جديدة'
                    })
                });
                
                const conversation = await response.json();
                currentConversationId = conversation.id;
                await loadConversations();
                await loadConversation(conversation.id);
            } catch (error) {
                console.error('Error creating conversation:', error);
                showError('حدث خطأ أثناء إنشاء محادثة جديدة');
            }
        }

        // Load a specific conversation
        async function loadConversation(conversationId) {
            try {
                currentConversationId = conversationId;
                const response = await fetch(`api/messages.php?conversation_id=${conversationId}`);
                const messages = await response.json();
                
                const chatContainer = document.getElementById('chatContainer');
                chatContainer.innerHTML = messages.map(msg => `
                    <div class="message ${msg.is_user ? 'user' : 'assistant'}">
                        <div class="message-content">${msg.content}</div>
                    </div>
                `).join('');
                
                chatContainer.scrollTop = chatContainer.scrollHeight;
                await loadConversations(); // Refresh sidebar
            } catch (error) {
                console.error('Error loading conversation:', error);
                showError('حدث خطأ أثناء تحميل المحادثة');
            }
        }

        // Send a message
        async function sendMessage() {
            const messageInput = document.getElementById('message-input');
            const message = messageInput.value.trim();
            
            if (!message) return;
            
            if (!currentConversationId) {
                await createNewConversation();
            }
            
            try {
                // Add user message to chat
                const chatContainer = document.getElementById('chatContainer');
                chatContainer.innerHTML += `
                    <div class="message user">
                        <div class="message-content">${message}</div>
                    </div>
                `;
                chatContainer.scrollTop = chatContainer.scrollHeight;
                
                // Save user message to database
                await fetch('api/messages.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        conversation_id: currentConversationId,
                        content: message,
                        is_user: true
                    })
                });
                
                // Clear input
                messageInput.value = '';
                
                // Send to AI and get response
                const response = await fetch('send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: message
                    })
                });
                
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                // Add AI response to chat
                chatContainer.innerHTML += `
                    <div class="message assistant">
                        <div class="message-content">${data.response}</div>
                    </div>
                `;
                
                // Save AI response to database
                try {
                    const saveResponse = await fetch('api/messages.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            conversation_id: currentConversationId,
                            content: data.response,
                            is_user: false  // Explicitly set as boolean false
                        })
                    });
                    
                    if (!saveResponse.ok) {
                        const errorData = await saveResponse.json();
                        throw new Error(errorData.error || 'Failed to save AI response');
                    }
                    
                    console.log('AI response saved successfully');
                } catch (error) {
                    console.error('Error saving AI response:', error);
                    showError('حدث خطأ أثناء حفظ رد المساعد');
                }
                
                chatContainer.scrollTop = chatContainer.scrollHeight;
                await loadConversations(); // Refresh sidebar
            } catch (error) {
                console.error('Error sending message:', error);
                showError('عذراً، واجهت خطأ. يرجى المحاولة مرة أخرى.');
            }
        }

        // Show error message
        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = message;
            document.body.appendChild(errorDiv);
            
            setTimeout(() => {
                errorDiv.remove();
            }, 3000);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadConversations();
            
            // Add click event listener for send button
            document.getElementById('send-button').addEventListener('click', sendMessage);
            
            // Handle enter key in message input
            document.getElementById('message-input').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        });
    </script>
</body>
</html> 