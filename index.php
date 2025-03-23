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
    <link href="/chat/public/css/styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .chat-container {
            min-height: 200px;
            overflow-y: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .chat-input-container {
            position: sticky;
            bottom: 0;
            background-color: white;
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
            margin-top: auto;
        }
        .message {
            max-width: 80%;
            margin: 1rem 0;
            padding: 1rem;
            border-radius: 1rem;
            position: relative;
        }
        .message.user {
            margin-left: auto;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-left-radius: 0.25rem;
        }
        .message.assistant {
            margin-right: auto;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-bottom-right-radius: 0.25rem;
        }
        .message-content {
            word-wrap: break-word;
        }
        .message-content code {
            background-color: rgba(0, 0, 0, 0.1);
            padding: 0.2em 0.4em;
            border-radius: 3px;
            font-family: monospace;
        }
        .message-content pre {
            background-color: rgba(0, 0, 0, 0.1);
            padding: 1em;
            border-radius: 5px;
            overflow-x: auto;
        }
        .message-content pre code {
            background-color: transparent;
            padding: 0;
        }
        .message-content p {
            margin: 0.5em 0;
        }
        .message-content ul, .message-content ol {
            margin: 0.5em 0;
            padding-right: 1.5em;
        }
        .message-content blockquote {
            border-right: 4px solid rgba(0, 0, 0, 0.2);
            padding-right: 1em;
            margin: 0.5em 0;
            color: rgba(0, 0, 0, 0.7);
        }
        .loading-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background: white;
            border-radius: 1rem;
            margin-right: auto;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: fit-content;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10;
        }
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5;
        }
        .loading-dots {
            display: flex;
            gap: 0.25rem;
        }
        .loading-dot {
            width: 0.5rem;
            height: 0.5rem;
            background: #667eea;
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out;
        }
        .loading-dot:nth-child(1) { animation-delay: -0.32s; }
        .loading-dot:nth-child(2) { animation-delay: -0.16s; }
        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
        .send-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .sidebar {
            width: 300px;
            background-color: #f8f9fa;
            border-left: 1px solid #dee2e6;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            position: relative;
        }
        .sidebar.collapsed {
            width: 0;
            padding: 0;
            margin: 0;
            overflow: hidden;
        }
        .sidebar.collapsed .sidebar-content {
            display: none;
        }
        .sidebar.collapsed .sidebar-header {
            display: none;
        }
        .sidebar.collapsed .sidebar-footer {
            display: none;
        }
        .toggle-sidebar {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .toggle-sidebar:hover {
            background-color: #f3f4f6;
        }
        .toggle-sidebar i {
            font-size: 1.25rem;
            transition: transform 0.3s ease;
        }
        .sidebar.collapsed + .main-content {
            margin-right: 0;
        }
        .sidebar-content {
            flex: 1;
            overflow-y: auto;
        }
        .sidebar-footer {
            padding: 15px 0;
            border-top: 1px solid #dee2e6;
            margin-top: auto;
        }
        .profile-link {
            display: flex;
            align-items: center;
            padding: 10px;
            color: #374151;
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.2s;
        }
        .profile-link:hover {
            background-color: #f3f4f6;
        }
        .profile-link i {
            margin-left: 10px;
            font-size: 1.2rem;
            color: #4f46e5;
        }
        .profile-link span {
            font-weight: 500;
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
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                z-index: 50;
                height: 100vh;
                width: 100%;
                transform: translateX(100%);
                background-color: white;
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-right: 0 !important;
                width: 100%;
            }
            .sidebar.collapsed {
                transform: translateX(100%);
            }
        }
        /* Add dropdown styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            z-index: 1000;
            border-radius: 0.5rem;
            padding: 0.5rem 0;
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
        .dropdown-item {
            color: #374151;
            padding: 0.75rem 1rem;
            text-decoration: none;
            display: block;
            transition: background-color 0.2s;
        }
        .dropdown-item:hover {
            background-color: #f3f4f6;
        }
        .dropdown-item i {
            margin-left: 0.5rem;
            width: 1.25rem;
            text-align: center;
        }
        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 0.5rem;
            position: relative;
            background-color: white;
        }
        .conversation-item:hover {
            background-color: #f3f4f6;
            transform: translateX(-5px);
            z-index: 1;
        }
        .conversation-item:hover::before {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background-color: #4f46e5;
            border-radius: 0 4px 4px 0;
            z-index: 2;
        }
        .conversation-item.active {
            background-color: #eef2ff;
            transform: translateX(-5px);
            z-index: 1;
        }
        .conversation-item.active::before {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background-color: #4f46e5;
            border-radius: 0 4px 4px 0;
            z-index: 2;
        }
        .conversation-title {
            color: #374151;
            font-weight: 600;
        }
        .conversation-time {
            color: #6b7280;
            font-size: 0.875rem;
        }
        .conversation-preview {
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div class="flex items-center">
                <button class="toggle-sidebar text-gray-600 hover:text-gray-800">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="text-3xl font-bold text-gray-800 mr-4">المحادثة الذكية</h1>
            </div>
            <div class="flex items-center space-x-4 space-x-reverse">
                <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-chart-line"></i> لوحة التحكم
                </a>
            </div>
        </div>

        <div class="flex gap-6">
            <!-- Sidebar -->
            <div class="sidebar bg-white rounded-lg shadow-lg p-4">
                <div class="sidebar-header flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">المحادثات السابقة</h2>
                </div>
                <div class="sidebar-content">
                    <div id="conversationsList" class="space-y-2">
                        <!-- Conversations will be added here dynamically -->
                    </div>
                </div>
                <div class="sidebar-footer">
                    <a href="profile.php" class="profile-link">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </a>
                </div>
            </div>

            <!-- Main Chat Area -->
            <div class="flex-1 bg-white rounded-lg shadow-lg flex flex-col">
                <div id="chatContainer" class="chat-container">
                    <!-- Messages will be added here -->
                </div>
                
                <div class="chat-input-container">
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
    </div>

    <!-- Upgrade Modal -->
    <div id="upgrade-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-md mx-auto mt-20">
            <h2 class="text-xl font-semibold mb-4">ترقية العضوية</h2>
            <div class="space-y-4">
                <div class="border rounded p-4">
                    <h3 class="font-semibold">العضوية الأساسية</h3>
                    <p class="text-gray-600">$9.99/شهرياً</p>
                    <ul class="mt-2 space-y-1">
                        <li><i class="fas fa-check text-green-500"></i> 100 محادثة شهرياً</li>
                        <li><i class="fas fa-check text-green-500"></i> دعم البريد الإلكتروني</li>
                    </ul>
                    <button onclick="initiatePayment('basic')" class="mt-4 w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        اختيار
                    </button>
                </div>
                <div class="border rounded p-4">
                    <h3 class="font-semibold">العضوية المميزة</h3>
                    <p class="text-gray-600">$19.99/شهرياً</p>
                    <ul class="mt-2 space-y-1">
                        <li><i class="fas fa-check text-green-500"></i> محادثات غير محدودة</li>
                        <li><i class="fas fa-check text-green-500"></i> دعم مباشر</li>
                        <li><i class="fas fa-check text-green-500"></i> ميزات متقدمة</li>
                    </ul>
                    <button onclick="initiatePayment('premium')" class="mt-4 w-full bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                        اختيار
                    </button>
                </div>
            </div>
            <button onclick="hideUpgradeModal()" class="mt-4 w-full bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300">
                إلغاء
            </button>
        </div>
    </div>

    <script>
        let currentConversationId = null;
        let currentOffset = 0;
        let hasMoreConversations = true;
        let conversationsPerPage = <?php echo $config['conversations_per_page']; ?>;
        let isSending = false;

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
                    // Store the footer before updating the sidebar
                    const footer = sidebar.querySelector('.sidebar-footer');
                    
                    // Update the sidebar content with proper structure
                    sidebar.innerHTML = `
                        <div class="sidebar-header flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold text-gray-800">المحادثات السابقة</h2>
                            <button onclick="createNewConversation()" class="new-chat-btn">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div class="sidebar-content">
                            <div class="conversations-list"></div>
                        </div>
                    `;
                    
                    // Restore the footer
                    sidebar.appendChild(footer);
                    
                    conversationsList = sidebar.querySelector('.conversations-list');
                }
                
                if (!conversations || conversations.length === 0) {
                    conversationsList.innerHTML = '<div class="text-gray-500 text-center py-4">لا توجد محادثات</div>';
                    return;
                }
                
                // Create a document fragment to build the conversations list
                const fragment = document.createDocumentFragment();
                
                conversations.forEach(conv => {
                    const date = new Date(conv.updated_at);
                    const formattedDate = date.toLocaleDateString('ar-SA', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    const firstLine = conv.last_message ? 
                        conv.last_message.split('\n')[0].substring(0, 50) + 
                        (conv.last_message.split('\n')[0].length > 50 ? '...' : '') : 
                        'بدون رسائل';
                    
                    const conversationDiv = document.createElement('div');
                    conversationDiv.className = `conversation-item ${conv.id === currentConversationId ? 'active' : ''}`;
                    conversationDiv.dataset.conversationId = conv.id;
                    conversationDiv.onclick = () => loadConversation(conv.id);
                    
                    conversationDiv.innerHTML = `
                        <div class="flex justify-between items-start mb-1">
                            <div class="conversation-title font-semibold">${conv.title}</div>
                            <div class="conversation-time text-sm text-gray-500">${formattedDate}</div>
                        </div>
                        <div class="conversation-preview text-sm text-gray-600">${firstLine}</div>
                    `;
                    
                    fragment.appendChild(conversationDiv);
                });
                
                if (loadMore) {
                    const existingLoadMoreBtn = conversationsList.querySelector('.load-more-btn');
                    if (existingLoadMoreBtn) {
                        existingLoadMoreBtn.remove();
                    }
                    conversationsList.appendChild(fragment);
                } else {
                    // Clear the container first
                    conversationsList.innerHTML = '';
                    // Then add the new conversations
                    conversationsList.appendChild(fragment);
                }
                
                // Add Load More button if there are more conversations
                if (hasMoreConversations) {
                    const loadMoreBtn = document.createElement('button');
                    loadMoreBtn.className = 'load-more-btn w-full mt-4 p-2 text-center text-indigo-600 hover:text-indigo-800 font-semibold';
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
                
                const data = await response.json();
                
                if (response.status === 403) {
                    // Show limit reached message in the chat area
                    const chatContainer = document.getElementById('chatContainer');
                    chatContainer.innerHTML = `
                        <div class="flex flex-col items-center justify-center h-full text-center p-8">
                            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4 rounded-lg max-w-lg">
                                <p class="font-bold mb-2">لقد وصلت إلى الحد الأقصى للمحادثات الشهري</p>
                                <p class="mb-4">قم بترقية عضويتك للاستمرار في إنشاء محادثات جديدة</p>
                                <button onclick="showUpgradeModal()" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                                    <i class="fas fa-crown"></i> ترقية العضوية
                                </button>
                            </div>
                        </div>
                    `;
                    return;
                }
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                currentConversationId = data.id;
                await loadConversations();
                await loadConversation(data.id);
            } catch (error) {
                console.error('Error creating conversation:', error);
                showError('حدث خطأ أثناء إنشاء محادثة جديدة');
            }
        }

        // Load a specific conversation
        async function loadConversation(conversationId) {
            try {
                currentConversationId = conversationId;
                
                // Show loading indicator without overlay
                const chatContainer = document.getElementById('chatContainer');
                chatContainer.innerHTML = `
                    <div class="loading-indicator">
                        <div class="loading-dots">
                            <div class="loading-dot"></div>
                            <div class="loading-dot"></div>
                            <div class="loading-dot"></div>
                        </div>
                    </div>
                `;
                
                const response = await fetch(`api/messages.php?conversation_id=${conversationId}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const messages = await response.json();
                
                // Clear loading indicator and show messages
                if (!Array.isArray(messages) || messages.length === 0) {
                    chatContainer.innerHTML = '<div class="message assistant"><div class="message-content">No messages found in this conversation.</div></div>';
                    return;
                }

                chatContainer.innerHTML = messages.map(msg => {
                    if (!msg || typeof msg.content !== 'string') {
                        console.error('Invalid message format:', msg);
                        return '';
                    }
                    const formattedContent = msg.is_user ? 
                        msg.content.trim().replace(/\n/g, '<br>') : 
                        msg.content.replace(/\n/g, '<br>');
                    return `
                        <div class="message ${msg.is_user ? 'user' : 'assistant'}">
                            <div class="message-content">${marked.parse(formattedContent)}</div>
                        </div>
                    `;
                }).filter(Boolean).join('');

                // Scroll to the latest message
                const lastMessage = chatContainer.lastElementChild;
                if (lastMessage) {
                    lastMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                // Update active state in conversations list
                document.querySelectorAll('.conversation-item').forEach(item => {
                    item.classList.remove('active');
                    if (item.dataset.conversationId === conversationId) {
                        item.classList.add('active');
                    }
                });

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
            
            // Disable input and button while sending
            messageInput.disabled = true;
            document.getElementById('send-button').disabled = true;
            
            // Check if we have an active conversation
            if (!currentConversationId) {
                try {
                    const response = await fetch('/chat/api/conversations.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            title: message.substring(0, 50) + '...'
                        })
                    });
                    
                    if (response.status === 403) {
                        // User has reached their conversation limit
                        const chatArea = document.getElementById('chatContainer');
                        chatArea.innerHTML = `
                            <div class="alert alert-warning text-center mb-3">
                                <h5 class="mb-2">لقد وصلت إلى الحد الأقصى من المحادثات الشهرية</h5>
                                <p class="mb-2">قم بترقية عضويتك للاستمرار في استخدام المحادثات</p>
                                <button class="btn btn-primary" onclick="openUpgradeModal()">
                                    ترقية العضوية
                                </button>
                            </div>
                        `;
                        return;
                    }
                    
                    const data = await response.json();
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    currentConversationId = data.id;
                    await loadConversations();
                } catch (error) {
                    console.error('Error creating conversation:', error);
                    showError('حدث خطأ أثناء إنشاء المحادثة');
                    return;
                }
            }
            
            // Check question limit before sending message
            try {
                const response = await fetch('/chat/api/check_question_limit.php');
                if (response.status === 403) {
                    // User has reached their question limit
                    const chatArea = document.getElementById('chatContainer');
                    chatArea.innerHTML += `
                        <div class="alert alert-warning text-center mb-3">
                            <h5 class="mb-2">لقد وصلت إلى الحد الأقصى من الأسئلة الشهرية</h5>
                            <p class="mb-2">قم بترقية عضويتك للاستمرار في طرح الأسئلة</p>
                            <button class="btn btn-primary" onclick="openUpgradeModal()">
                                ترقية العضوية
                            </button>
                        </div>
                    `;
                    return;
                }
            } catch (error) {
                console.error('Error checking question limit:', error);
                showError('حدث خطأ أثناء التحقق من حد الأسئلة');
                return;
            }
            
            // Send the message
            try {
                const response = await fetch('/chat/api/messages.php', {
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
                
                if (!response.ok) {
                    throw new Error('Failed to send message');
                }
                
                const data = await response.json();
                if (data.error) {
                    throw new Error(data.error);
                }
                
                // Clear input and reload conversation
                messageInput.value = '';
                await loadConversation(currentConversationId);
                
                // Show loading indicator for AI response
                const chatContainer = document.getElementById('chatContainer');
                chatContainer.innerHTML += `
                    <div class="message assistant">
                        <div class="message-content">
                            <div class="loading-indicator">
                                <div class="loading-dots">
                                    <div class="loading-dot"></div>
                                    <div class="loading-dot"></div>
                                    <div class="loading-dot"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Get AI response
                const aiResponse = await fetch('/chat/send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: message,
                        conversation_id: currentConversationId
                    })
                });
                
                if (!aiResponse.ok) {
                    const errorData = await aiResponse.json();
                    if (aiResponse.status === 403 && errorData.limit_reached) {
                        // User has reached their limit
                        const chatArea = document.getElementById('chatContainer');
                        chatArea.innerHTML += `
                            <div class="flex flex-col items-center justify-center text-center p-8">
                                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4 rounded-lg max-w-lg">
                                    <p class="font-bold mb-2">لقد وصلت إلى الحد الأقصى ${errorData.limit_type === 'questions' ? 'للأسئلة' : 'للمحادثات'} الشهري</p>
                                    <p class="mb-4">قم بترقية عضويتك للاستمرار في ${errorData.limit_type === 'questions' ? 'طرح الأسئلة' : 'إنشاء محادثات جديدة'}</p>
                                    <button onclick="showUpgradeModal()" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                                        <i class="fas fa-crown"></i> ترقية العضوية
                                    </button>
                                </div>
                            </div>
                        `;
                        return;
                    }
                    throw new Error(errorData.error || 'Failed to get AI response');
                }
                
                const aiData = await aiResponse.json();
                if (aiData.error) {
                    throw new Error(aiData.error);
                }
                
                // Remove loading indicator and add AI response
                const loadingIndicator = chatContainer.querySelector('.loading-indicator').closest('.message');
                loadingIndicator.remove();
                
                // Add AI response
                chatContainer.innerHTML += `
                    <div class="message assistant">
                        <div class="message-content">${marked.parse(aiData.response)}</div>
                    </div>
                `;
                
                // Scroll to the latest message
                chatContainer.scrollTop = chatContainer.scrollHeight;
                
            } catch (error) {
                console.error('Error sending message:', error);
                showError('حدث خطأ أثناء إرسال الرسالة');
            } finally {
                // Re-enable input and button
                messageInput.disabled = false;
                document.getElementById('send-button').disabled = false;
                messageInput.focus();
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

        // Update the sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const toggleButton = document.querySelector('.toggle-sidebar');
            
            // Set initial state for mobile
            if (window.innerWidth <= 768) {
                sidebar.classList.add('collapsed');
            }
            
            toggleButton.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    sidebar.classList.toggle('open');
                } else {
                    sidebar.classList.toggle('collapsed');
                }
            });

            // Close sidebar on mobile when clicking outside
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 768 && 
                    !sidebar.contains(e.target) && 
                    !toggleButton.contains(e.target) &&
                    sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                }
            });

            // Close sidebar when selecting a conversation on mobile
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 768 && 
                    e.target.closest('.conversation-item') && 
                    sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                }
            });
        });

        // Auto-resize textarea
        const textarea = document.getElementById('message-input');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Modal functions
        function showUpgradeModal() {
            document.getElementById('upgrade-modal').classList.remove('hidden');
        }
        
        function hideUpgradeModal() {
            document.getElementById('upgrade-modal').classList.add('hidden');
        }
        
        // Payment functions
        async function initiatePayment(membershipType) {
            try {
                const response = await fetch('/chat/api/create_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ membership_type: membershipType })
                });
                
                const data = await response.json();
                if (data.success) {
                    window.location.href = data.paypal_url;
                } else {
                    showError('حدث خطأ أثناء إنشاء الدفع');
                }
            } catch (error) {
                console.error('Error initiating payment:', error);
                showError('حدث خطأ أثناء إنشاء الدفع');
            }
        }
    </script>
</body>
</html> 