// Configuration
// const conversationsPerPage = 10;  // Removed since it's now declared in index.php

let currentConversationId = null;
let currentOffset = 0;
let hasMoreConversations = true;
let isSending = false;

// Load conversations from the server
async function loadConversations(loadMore = false) {
    try {
        if (!loadMore) {
            currentOffset = 0;
            hasMoreConversations = true;
        }
        
        const response = await fetch(`/chat/api/conversations.php?limit=${window.conversationsPerPage}&offset=${currentOffset}`);
        if (!response.ok) {
            throw new Error('Failed to load conversations');
        }
        const data = await response.json();
        
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
            showUpgradeModal();
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
        console.error('Error loading conversation:', error);
        showError('حدث خطأ أثناء تحميل المحادثة');
    }
}

// Send a message
async function sendMessage() {
    const messageInput = document.getElementById('message-input');
    const message = messageInput.value.trim();
    
    if (!message) return;
    
    messageInput.disabled = true;
    document.getElementById('send-button').disabled = true;
    
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
                showUpgradeModal();
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
    
    try {
        const response = await fetch('/chat/api/check_question_limit.php');
        if (response.status === 403) {
            showUpgradeModal();
            return;
        }
    } catch (error) {
        console.error('Error checking question limit:', error);
        showError('حدث خطأ أثناء التحقق من حد الأسئلة');
        return;
    }
    
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
        
        messageInput.value = '';
        await loadConversation(currentConversationId);
        
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
                showUpgradeModal();
                return;
            }
            throw new Error(errorData.error || 'Failed to get AI response');
        }
        
        const aiData = await aiResponse.json();
        if (aiData.error) {
            throw new Error(aiData.error);
        }
        
        const loadingIndicator = chatContainer.querySelector('.loading-indicator').closest('.message');
        loadingIndicator.remove();
        
        chatContainer.innerHTML += `
            <div class="message assistant">
                <div class="message-content">${marked.parse(aiData.response)}</div>
            </div>
        `;
        
        chatContainer.scrollTop = chatContainer.scrollHeight;
        
    } catch (error) {
        console.error('Error sending message:', error);
        showError('حدث خطأ أثناء إرسال الرسالة');
    } finally {
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

    // Sidebar toggle functionality
    const sidebar = document.querySelector('.sidebar');
    const toggleButton = document.querySelector('.toggle-sidebar');
    
    if (!sidebar || !toggleButton) {
        console.error('Sidebar or toggle button not found');
        return;
    }
    
    // Set initial state for mobile
    if (window.innerWidth <= 768) {
        sidebar.classList.add('collapsed');
        sidebar.classList.remove('open');
    }
    
    // Toggle sidebar on button click
    toggleButton.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        if (window.innerWidth <= 768) {
            // Mobile view
            if (sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
                sidebar.classList.add('collapsed');
            } else {
                sidebar.classList.remove('collapsed');
                sidebar.classList.add('open');
            }
        } else {
            // Desktop view
            if (sidebar.classList.contains('collapsed')) {
                sidebar.classList.remove('collapsed');
            } else {
                sidebar.classList.add('collapsed');
            }
        }
    });

    // Handle window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth <= 768) {
            sidebar.classList.add('collapsed');
            sidebar.classList.remove('open');
        } else {
            sidebar.classList.remove('open');
            // Preserve collapsed state on desktop
            if (!sidebar.classList.contains('collapsed')) {
                sidebar.classList.add('collapsed');
            }
        }
    });

    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768 && 
            !sidebar.contains(e.target) && 
            !toggleButton.contains(e.target) &&
            sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            sidebar.classList.add('collapsed');
        }
    });

    // Close sidebar when selecting a conversation on mobile
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768 && 
            e.target.closest('.conversation-item') && 
            sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            sidebar.classList.add('collapsed');
        }
    });

    // Auto-resize textarea
    const textarea = document.getElementById('message-input');
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
}); 