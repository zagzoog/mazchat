// Configuration
// const conversationsPerPage = 10;  // Removed since it's now declared in index.php

let currentConversationId = null;
let currentOffset = 0;
let hasMoreConversations = true;
let isSending = false;

async function handleResponse(response) {
    if (!response.ok) {
        if (response.status === 401) {
            // Session expired or unauthorized
            window.location.href = '/chat/login.php';
            return;
        }
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
}

// Load conversations from the server
async function loadConversations(loadMore = false) {
    try {
        if (!loadMore) {
            currentOffset = 0;
            hasMoreConversations = true;
        }
        
        const response = await fetch(`/chat/api/conversations.php?limit=${window.conversationsPerPage}&offset=${currentOffset}`, {
            credentials: 'include'
        });
        const data = await handleResponse(response);
        
        const conversations = data.conversations;
        hasMoreConversations = data.hasMore;
        
        const sidebar = document.querySelector('.sidebar');
        let conversationsList = loadMore ? 
            document.querySelector('.conversations-list') : 
            document.createElement('div');
        
        if (!loadMore) {
            const footer = sidebar.querySelector('.sidebar-footer');
            
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
            
            sidebar.appendChild(footer);
            conversationsList = sidebar.querySelector('.conversations-list');
        }
        
        if (!conversations || conversations.length === 0) {
            conversationsList.innerHTML = '<div class="text-gray-500 text-center py-4">لا توجد محادثات</div>';
            return;
        }
        
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
            conversationsList.innerHTML = '';
            conversationsList.appendChild(fragment);
        }
        
        if (hasMoreConversations) {
            const loadMoreBtn = document.createElement('button');
            loadMoreBtn.className = 'load-more-btn w-full mt-4 p-2 text-center text-indigo-600 hover:text-indigo-800 font-semibold';
            loadMoreBtn.innerHTML = '<i class="fas fa-chevron-down"></i> تحميل المزيد';
            loadMoreBtn.onclick = () => {
                currentOffset += window.conversationsPerPage;
                loadConversations(true);
            };
            conversationsList.appendChild(loadMoreBtn);
        }
    } catch (error) {
        console.error('Error loading conversations:', error);
        showError('Failed to load conversations');
    }
}

// Create a new conversation
async function createNewConversation() {
    try {
        const pluginSelector = document.getElementById('pluginSelector');
        const selectedPluginId = pluginSelector.value;
        
        if (!selectedPluginId) {
            throw new Error('No plugin selected');
        }

        const response = await fetch('/chat/api/conversations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                plugin_id: selectedPluginId
            })
        });
        const data = await handleResponse(response);
        
        if (!data.data || !data.data.id) {
            throw new Error('No conversation ID received');
        }
        
        currentConversationId = data.data.id;
        console.log('New conversation created with ID:', currentConversationId);
        
        // Clear the chat container and show welcome message
        const chatContainer = document.getElementById('chatContainer');
        chatContainer.innerHTML = `
            <div class="message assistant">
                <div class="message-content">
                    مرحباً بك في المحادثة الجديدة! كيف يمكنني مساعدتك اليوم؟
                </div>
            </div>
        `;
        
        // Update the conversations list
        await loadConversations();
        
        // Update active state in sidebar
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('active');
            if (item.dataset.conversationId === currentConversationId) {
                item.classList.add('active');
            }
        });
    } catch (error) {
        console.error('Error creating conversation:', error);
        showError('حدث خطأ أثناء إنشاء محادثة جديدة');
        currentConversationId = null; // Reset the conversation ID on error
    }
}

function formatTimestamp(date) {
    return new Intl.DateTimeFormat('ar-EG', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    }).format(date);
}

function createMessageElement(msg) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${msg.is_user ? 'user' : 'assistant'}`;
    
    const contentDiv = document.createElement('div');
    contentDiv.className = 'message-content';
    
    // Add timestamp
    const timestampDiv = document.createElement('div');
    timestampDiv.className = 'message-timestamp';
    timestampDiv.textContent = formatTimestamp(new Date());
    contentDiv.appendChild(timestampDiv);
    
    // Format message content
    const formattedContent = msg.is_user ? 
        msg.content.trim().replace(/\n/g, '<br>') : 
        marked.parse(msg.content);
    
    const contentWrapper = document.createElement('div');
    contentWrapper.innerHTML = formattedContent;
    contentDiv.appendChild(contentWrapper);
    
    messageDiv.appendChild(contentDiv);
    return messageDiv;
}

function showTypingIndicator() {
    const indicator = document.getElementById('typingIndicator');
    indicator.style.display = 'block';
    indicator.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function hideTypingIndicator() {
    document.getElementById('typingIndicator').style.display = 'none';
}

// Load a specific conversation
async function loadConversation(conversationId) {
    try {
        const response = await fetch(`/chat/api/conversations.php?id=${conversationId}`, {
            credentials: 'include'
        });
        const data = await handleResponse(response);
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        currentConversationId = conversationId;
        
        // Update plugin selector to match conversation's plugin
        const pluginSelector = document.getElementById('pluginSelector');
        if (data.conversation && data.conversation.plugin_id) {
            pluginSelector.value = data.conversation.plugin_id;
        }
        
        // Load and display messages
        const chatContainer = document.getElementById('chatContainer');
        chatContainer.innerHTML = data.messages.map(msg => {
            const messageElement = createMessageElement(msg);
            return messageElement.outerHTML;
        }).join('');
        
        // Scroll to the last message
        const lastMessage = chatContainer.lastElementChild;
        if (lastMessage) {
            lastMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        // Update active state in sidebar
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
    const sendButton = document.getElementById('send-button');
    sendButton.disabled = true;
    
    try {
        // Check if we have an active conversation
        if (!currentConversationId) {
            await createNewConversation();
            if (!currentConversationId) {
                return;
            }
        }
        
        // Add user message immediately
        const chatContainer = document.getElementById('chatContainer');
        const userMessage = createMessageElement({
            content: message,
            is_user: true
        });
        chatContainer.appendChild(userMessage);
        userMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        // Show typing indicator
        showTypingIndicator();
        
        // Clear input
        messageInput.value = '';
        
        // Send the message
        const response = await fetch('/chat/app/api/v1/messages.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                conversation_id: currentConversationId,
                content: message
            })
        });
        const data = await handleResponse(response);
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Hide typing indicator
        hideTypingIndicator();
        
        // Check for assistant response in the nested data structure
        if (data.data && data.data.assistant_message) {
            const assistantMessage = createMessageElement({
                content: data.data.assistant_message.content,
                is_user: false
            });
            chatContainer.appendChild(assistantMessage);
            assistantMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
    } catch (error) {
        console.error('Error sending message:', error);
        hideTypingIndicator();
        showError('An error occurred while sending the message');
    } finally {
        // Re-enable input and button
        messageInput.disabled = false;
        sendButton.disabled = false;
        messageInput.focus();
    }
}

// Show error message
function showError(message) {
    const existingError = document.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    document.body.appendChild(errorDiv);
    
    setTimeout(() => {
        errorDiv.style.opacity = '0';
        setTimeout(() => errorDiv.remove(), 300);
    }, 3000);
}

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

// Sidebar toggle functionality
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
    
    // Lock body scroll when sidebar is open
    document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadConversations();
    
    // Add click event listener for send button
    document.getElementById('send-button').addEventListener('click', sendMessage);
    
    // Handle enter key in message input
    document.getElementById('message-input').addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    // Add sidebar toggle functionality
    const toggleButton = document.querySelector('.toggle-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    toggleButton.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', toggleSidebar);
    
    // Handle escape key to close sidebar
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && document.querySelector('.sidebar').classList.contains('open')) {
            toggleSidebar();
        }
    });
}); 