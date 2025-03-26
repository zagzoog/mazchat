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
    return await response.json();
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
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to load conversations');
        }
        
        // Handle both admin and regular user response formats
        const conversations = data.data || data.conversations;
        const hasMore = data.hasMore;
        
        if (!conversations) {
            throw new Error('No conversations data received');
        }
        
        const sidebar = document.querySelector('.sidebar');
        let conversationsList = loadMore ? 
            document.querySelector('.conversations-list') : 
            document.createElement('div');
        
        if (!loadMore) {
            const footer = sidebar.querySelector('.sidebar-footer');
            const footerHtml = footer ? footer.outerHTML : '';
            
            sidebar.innerHTML = `
                <div class="sidebar-header flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800 font-cairo">المحادثات السابقة</h2>
                    <button onclick="createNewConversation()" class="new-chat-btn font-cairo">
                        <i class="fas fa-plus"></i>
                        محادثة جديدة
                    </button>
                </div>
                <div class="sidebar-content">
                    <div class="conversations-list"></div>
                </div>
                ${footerHtml}
            `;
            
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
        
        if (hasMore) {
            const loadMoreBtn = document.createElement('button');
            loadMoreBtn.className = 'load-more-btn w-full mt-4 p-2 text-center bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 font-semibold font-cairo';
            loadMoreBtn.innerHTML = '<i class="fas fa-chevron-down"></i> تحميل المزيد';
            loadMoreBtn.onclick = () => {
                currentOffset += window.conversationsPerPage;
                loadConversations(true);
            };
            conversationsList.appendChild(loadMoreBtn);
        }
    } catch (error) {
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
        
        const chatContainer = document.getElementById('chatContainer');
        chatContainer.innerHTML = `
            <div class="message assistant">
                <div class="message-content">
                    مرحباً بك في المحادثة الجديدة! كيف يمكنني مساعدتك اليوم؟
                </div>
            </div>
        `;
        
        await loadConversations();
        
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('active');
            if (item.dataset.conversationId === currentConversationId) {
                item.classList.add('active');
            }
        });

        return currentConversationId;
    } catch (error) {
        showError('حدث خطأ أثناء إنشاء محادثة جديدة');
        currentConversationId = null;
        throw error;
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
    const isUser = msg.is_user || msg.role === 'user';
    messageDiv.className = `message ${isUser ? 'user' : 'assistant'}`;
    
    const contentDiv = document.createElement('div');
    contentDiv.className = 'message-content';
    
    // Add timestamp
    const timestampDiv = document.createElement('div');
    timestampDiv.className = 'message-timestamp';
    timestampDiv.textContent = formatTimestamp(new Date());
    contentDiv.appendChild(timestampDiv);
    
    // Format message content
    const formattedContent = isUser ? 
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
    if (indicator) {
        indicator.style.display = 'block';
        indicator.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function hideTypingIndicator() {
    const indicator = document.getElementById('typingIndicator');
    if (indicator) {
        indicator.style.display = 'none';
    }
}

// Load a specific conversation
async function loadConversation(conversationId) {
    if (!conversationId) return;
    
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
        
        const response = await fetch(`/chat/api/messages.php?conversation_id=${conversationId}`, {
            credentials: 'include'
        });
        const data = await handleResponse(response);
        
        const messages = Array.isArray(data) ? data : (data.messages || []);
        
        if (messages.length === 0) {
            chatContainer.innerHTML = `
                <div class="message assistant">
                    <div class="message-content">
                        مرحباً بك في المحادثة! كيف يمكنني مساعدتك اليوم؟
                    </div>
                </div>
            `;
            return;
        }

        chatContainer.innerHTML = messages.map(msg => {
            if (!msg || typeof msg.content !== 'string') return '';
            return createMessageElement(msg).outerHTML;
        }).join('');

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
        showError('حدث خطأ أثناء تحميل المحادثة');
    }
}

// Send a message
async function sendMessage() {
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value.trim();
    
    if (!message) return;
    
    messageInput.disabled = true;
    const sendButton = document.getElementById('sendButton');
    sendButton.disabled = true;
    
    try {
        if (!currentConversationId) {
            await createNewConversation();
            if (!currentConversationId) return;
        }
        
        const chatContainer = document.getElementById('chatContainer');
        const userMessage = createMessageElement({
            content: message,
            role: 'user'
        });
        chatContainer.appendChild(userMessage);
        userMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        showTypingIndicator();
        messageInput.value = '';
        
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
        
        hideTypingIndicator();
        
        const messageData = data.data?.data;
        if (messageData && messageData.assistant_message) {
            const assistantMessage = createMessageElement({
                content: messageData.assistant_message.content,
                role: 'assistant'
            });
            chatContainer.appendChild(assistantMessage);
            assistantMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
    } catch (error) {
        showError('حدث خطأ أثناء إرسال الرسالة');
        hideTypingIndicator();
    } finally {
        messageInput.disabled = false;
        sendButton.disabled = false;
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

// Load available plugins and populate selector
async function loadPlugins() {
    try {
        const response = await fetch('/chat/api/plugins.php');
        
        if (!response.ok) {
            throw new Error('Failed to load plugins');
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to load plugins');
        }
        
        const pluginSelector = document.getElementById('pluginSelector');
        pluginSelector.innerHTML = '';
        
        data.plugins.forEach(plugin => {
            const option = document.createElement('option');
            option.value = plugin.id;
            option.textContent = plugin.name;
            if (data.selected_plugin === plugin.id) {
                option.selected = true;
            }
            pluginSelector.appendChild(option);
        });
        
        pluginSelector.addEventListener('change', async function() {
            try {
                // Update user preference
                const response = await fetch('/chat/api/user/preferences.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        plugin_id: this.value
                    })
                });
                
                if (!response.ok) {
                    throw new Error('Failed to update plugin preference');
                }

                // If there's an active conversation, update its plugin
                if (currentConversationId) {
                    const updateResponse = await fetch('/chat/api/conversations.php', {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            conversation_id: currentConversationId,
                            plugin_id: this.value
                        })
                    });

                    if (!updateResponse.ok) {
                        throw new Error('Failed to update conversation plugin');
                    }

                    // Show a message indicating the plugin has been changed
                    const chatContainer = document.getElementById('chatContainer');
                    const systemMessage = document.createElement('div');
                    systemMessage.className = 'message system';
                    systemMessage.innerHTML = `
                        <div class="message-content">
                            تم تغيير معالج الرسائل. سيتم استخدام المعالج الجديد في الرسائل القادمة.
                        </div>
                    `;
                    chatContainer.appendChild(systemMessage);
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }
            } catch (error) {
                showError('فشل في تحديث تفضيلات المعالج');
            }
        });
    } catch (error) {
        showError('فشل في تحميل المعالجات المتاحة');
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadConversations();
    loadPlugins();
    
    const sendButton = document.getElementById('sendButton');
    if (sendButton) {
        sendButton.addEventListener('click', sendMessage);
    }
    
    const messageInput = document.getElementById('messageInput');
    if (messageInput) {
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
    
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