// Development version with enhanced logging
console.log('[DEV] Chat application initializing...');

// Configuration
let currentConversationId = null;
let currentOffset = 0;
let hasMoreConversations = true;
let isSending = false;

// Debug logging utility
const debug = {
    log: (message, data = null) => {
        console.log(`[DEV] ${message}`, data || '');
    },
    error: (message, error = null) => {
        console.error(`[DEV] ERROR: ${message}`, error || '');
    },
    warn: (message, data = null) => {
        console.warn(`[DEV] WARNING: ${message}`, data || '');
    }
};

// Load conversations from the server
async function loadConversations(loadMore = false) {
    debug.log(`Loading conversations${loadMore ? ' (load more)' : ''}`);
    try {
        if (!loadMore) {
            currentOffset = 0;
            hasMoreConversations = true;
        }
        
        const response = await fetch(`/chat/api/conversations.php?limit=${window.conversationsPerPage}&offset=${currentOffset}`);
        debug.log('Conversations API response status:', response.status);
        
        if (!response.ok) {
            throw new Error('Failed to load conversations');
        }
        const data = await response.json();
        debug.log('Conversations data received:', data);
        
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
            debug.log('No conversations found');
            conversationsList.innerHTML = '<div class="text-gray-500 text-center py-4">لا توجد محادثات</div>';
            return;
        }
        
        debug.log(`Rendering ${conversations.length} conversations`);
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
        debug.error('Error loading conversations:', error);
        showError('Failed to load conversations');
    }
}

// Create a new conversation
async function createNewConversation() {
    debug.log('Creating new conversation');
    try {
        const response = await fetch('/chat/api/conversations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                title: 'محادثة جديدة'
            })
        });
        
        debug.log('Create conversation API response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        debug.log('Create conversation response:', data);
        
        if (response.status === 403) {
            debug.warn('User reached monthly conversation limit');
            const chatArea = document.getElementById('chatContainer');
            chatArea.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full text-center p-8">
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4 rounded-lg max-w-lg">
                        <p class="font-bold mb-2">لقد وصلت إلى الحد الأقصى من المحادثات الشهرية</p>
                        <p class="mb-4">قم بترقية عضويتك للاستمرار في استخدام المحادثات</p>
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
        
        if (!data.data || !data.data.id) {
            throw new Error('No conversation ID received');
        }
        
        currentConversationId = data.data.id;
        debug.log('New conversation created with ID:', currentConversationId);
        
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
        debug.error('Error creating conversation:', error);
        showError('حدث خطأ أثناء إنشاء محادثة جديدة');
        currentConversationId = null; // Reset the conversation ID on error
    }
}

// Load a specific conversation
async function loadConversation(conversationId) {
    debug.log('Loading conversation:', conversationId);
    
    if (!conversationId) {
        debug.error('Invalid conversation ID');
        return;
    }
    
    try {
        currentConversationId = conversationId;
        
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
        
        const response = await fetch(`/chat/api/messages.php?conversation_id=${conversationId}`);
        debug.log('Messages API response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const messages = await response.json();
        debug.log('Messages received:', messages);
        
        if (!Array.isArray(messages) || messages.length === 0) {
            debug.log('No messages found in conversation');
            chatContainer.innerHTML = `
                <div class="message assistant">
                    <div class="message-content">
                        مرحباً بك في المحادثة! كيف يمكنني مساعدتك اليوم؟
                    </div>
                </div>
            `;
            return;
        }

        debug.log(`Rendering ${messages.length} messages`);
        chatContainer.innerHTML = messages.map(msg => {
            if (!msg || typeof msg.content !== 'string') {
                debug.error('Invalid message format:', msg);
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

        const lastMessage = chatContainer.lastElementChild;
        if (lastMessage) {
            lastMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('active');
            if (item.dataset.conversationId === conversationId) {
                item.classList.add('active');
            }
        });

    } catch (error) {
        debug.error('Error loading conversation:', error);
        showError('حدث خطأ أثناء تحميل المحادثة');
    }
}

// Send a message
async function sendMessage() {
    const messageInput = document.getElementById('message-input');
    const message = messageInput.value.trim();
    
    if (!message) return;
    
    debug.log('Sending message:', message);
    
    // Disable input and button while sending
    messageInput.disabled = true;
    document.getElementById('send-button').disabled = true;
    
    try {
        // Check if we have an active conversation
        if (!currentConversationId) {
            debug.log('No active conversation, creating new one');
            await createNewConversation();
            if (!currentConversationId) {
                debug.warn('Failed to create new conversation');
                // Re-enable input and button
                messageInput.disabled = false;
                document.getElementById('send-button').disabled = false;
                return; // If createNewConversation failed, it will have shown the limit message
            }
        }
        
        // Send the message
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

        debug.log('Send message API response status:', response.status);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        debug.log('Send message response:', data);
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Clear input and reload conversation
        messageInput.value = '';
        await loadConversation(currentConversationId);
    } catch (error) {
        debug.error('Error sending message:', error);
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
    debug.warn('Showing error message:', message);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    document.body.appendChild(errorDiv);
    
    setTimeout(() => {
        errorDiv.remove();
    }, 3000);
}

// Modal functions
function showUpgradeModal() {
    debug.log('Showing upgrade modal');
    document.getElementById('upgrade-modal').classList.remove('hidden');
}

function hideUpgradeModal() {
    debug.log('Hiding upgrade modal');
    document.getElementById('upgrade-modal').classList.add('hidden');
}

// Payment functions
async function initiatePayment(membershipType) {
    debug.log('Initiating payment for membership type:', membershipType);
    try {
        const response = await fetch('/chat/api/create_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ membership_type: membershipType })
        });
        
        debug.log('Payment API response status:', response.status);
        const data = await response.json();
        debug.log('Payment response:', data);
        
        if (data.success) {
            window.location.href = data.paypal_url;
        } else {
            showError('حدث خطأ أثناء إنشاء الدفع');
        }
    } catch (error) {
        debug.error('Error initiating payment:', error);
        showError('حدث خطأ أثناء إنشاء الدفع');
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    debug.log('DOM Content Loaded, initializing chat application');
    loadConversations();
    
    // Add click event listener for send button
    document.getElementById('send-button').addEventListener('click', sendMessage);
    
    // Handle enter key in message input
    document.getElementById('message-input').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    // Auto-resize textarea
    const textarea = document.getElementById('message-input');
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
}); 