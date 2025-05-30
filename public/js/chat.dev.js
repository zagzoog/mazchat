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

async function handleResponse(response) {
    debug.log('Response status:', response.status);
    if (!response.ok) {
        if (response.status === 401) {
            debug.log('Session expired, redirecting to login page');
            window.location.href = '<?php echo getFullUrlPath("login.php"); ?>';
            return;
        }
        const errorData = await response.json();
        debug.log('Error response data:', errorData);
        if (errorData.limit_reached) {
            throw new Error(JSON.stringify(errorData));
        }
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    const data = await response.json();
    debug.log('Response data:', data);
    return data;
}

// Load conversations from the server
async function loadConversations(loadMore = false) {
    debug.log(`Loading conversations${loadMore ? ' (load more)' : ''}`);
    try {
        if (!loadMore) {
            currentOffset = 0;
            hasMoreConversations = true;
        }
        
        const response = await fetch(`${window.apiBaseUrl}/conversations.php?limit=${window.conversationsPerPage}&offset=${currentOffset}`);
        debug.log('Conversations API response status:', response.status);
        
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
        let conversationsList = document.querySelector('.conversations-list');
        
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
        debug.error('Error loading conversations:', error);
        showError('Failed to load conversations');
    }
}

// Create a new conversation
async function createNewConversation() {
    debug.log('Creating new conversation');
    try {
        // Get selected plugin
        const pluginSelector = document.getElementById('pluginSelector');
        const selectedPluginId = pluginSelector.value;
        
        if (!selectedPluginId) {
            throw new Error('No plugin selected');
        }

        const response = await fetch(`/${baseUrlPath}/api/conversations.php`, {
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
        
        if (!data.success) {
            if (data.limit_reached && data.limit_type === 'conversations') {
                debug.warn('Monthly conversation limit reached');
                showError('لقد وصلت إلى الحد الشهري للمحادثات. يرجى ترقية اشتراكك للمتابعة.', true);
                return null;
            }
            throw new Error(data.error || 'Failed to create conversation');
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

        return currentConversationId;
    } catch (error) {
        try {
            const errorData = JSON.parse(error.message);
            debug.log('Parsed error data:', errorData);
            if (errorData.limit_reached && errorData.limit_type === 'conversations') {
                debug.warn('Monthly conversation limit reached');
                showError('لقد وصلت إلى الحد الشهري للمحادثات. يرجى ترقية اشتراكك للمتابعة.', true);
            } else {
                debug.error('Error creating conversation:', error);
                showError('حدث خطأ أثناء إنشاء محادثة جديدة');
            }
        } catch (e) {
            debug.error('Error parsing error message:', e);
            showError('حدث خطأ أثناء إنشاء محادثة جديدة');
        }
        currentConversationId = null;
        throw error;
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
        
        // Hide sidebar on mobile when conversation is clicked
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        
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
        
        const response = await fetch(`/${baseUrlPath}/api/messages.php?conversation_id=${conversationId}`, {
            credentials: 'include'
        });
        const data = await handleResponse(response);
        
        // Handle both array format and object format with messages property
        const messages = Array.isArray(data) ? data : (data.messages || []);
        
        if (messages.length === 0) {
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
            return createMessageElement(msg).outerHTML;
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
        debug.error('Error loading conversation:', error);
        showError('حدث خطأ أثناء تحميل المحادثة');
    }
}

// Create a message element
function createMessageElement(msg) {
    debug.log('Creating message element:', msg);
    const messageDiv = document.createElement('div');
    // Check both is_user and role properties
    const isUser = msg.is_user || msg.role === 'user';
    messageDiv.className = `message ${isUser ? 'user' : 'assistant'}`;
    
    const contentDiv = document.createElement('div');
    contentDiv.className = 'message-content';
    
    // Format message content
    const formattedContent = isUser ? 
        msg.content.trim().replace(/\n/g, '<br>') : 
        marked.parse(msg.content);
    
    contentDiv.innerHTML = formattedContent;
    messageDiv.appendChild(contentDiv);
    
    return messageDiv;
}

// Show typing indicator
function showTypingIndicator() {
    const indicator = document.getElementById('typingIndicator');
    if (indicator) {
        indicator.style.display = 'block';
        indicator.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Hide typing indicator
function hideTypingIndicator() {
    const indicator = document.getElementById('typingIndicator');
    if (indicator) {
        indicator.style.display = 'none';
    }
}

// Send a message
async function sendMessage() {
    debug.log('Starting message send process');
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value.trim();
    
    if (!message) {
        debug.warn('Empty message, ignoring send request');
        return;
    }
    
    // Disable input and button while sending
    messageInput.disabled = true;
    const sendButton = document.getElementById('sendButton');
    sendButton.disabled = true;
    
    try {
        // Check if we have an active conversation
        if (!currentConversationId) {
            debug.log('No active conversation, creating new one');
            await createNewConversation();
            if (!currentConversationId) {
                debug.error('Failed to create new conversation');
                throw new Error('Failed to create new conversation');
            }
            debug.log('New conversation created:', currentConversationId);
        }
        
        // Add user message immediately
        const chatContainer = document.getElementById('chatContainer');
        const userMessage = createMessageElement({
            content: message,
            role: 'user'
        });
        chatContainer.appendChild(userMessage);
        userMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
        debug.log('User message added to chat');
        
        // Show typing indicator
        showTypingIndicator();
        
        // Clear input
        messageInput.value = '';
        
        // Prepare request data
        const requestData = {
            conversation_id: currentConversationId,
            content: message
        };
        debug.log('Sending request to server:', requestData);
        
        // Send the message
        const response = await fetch(`/${baseUrlPath}/app/api/v1/messages.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(requestData)
        });
        
        // Log response status
        debug.log('Server response status:', response.status);
        
        // Handle different response statuses
        if (!response.ok) {
            if (response.status === 401) {
                debug.error('Unauthorized - Session may have expired');
                showError('Your session has expired. Please refresh the page and try again.');
                return;
            }
            if (response.status === 403) {
                debug.error('Forbidden - Access denied');
                showError('You do not have permission to send messages.');
                return;
            }
            if (response.status === 429) {
                debug.error('Rate limit exceeded');
                showError('Too many requests. Please wait a moment before trying again.');
                return;
            }
            if (response.status === 500) {
                debug.error('Server error');
                showError('A server error occurred. Please try again later.');
                return;
            }
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Parse and validate response
        const data = await handleResponse(response);
        debug.log('Parsed server response:', data);
        
        // Validate response structure
        if (!data || typeof data !== 'object') {
            debug.error('Invalid response format:', data);
            throw new Error('Invalid response format from server');
        }
        
        // Handle error responses
        if (!data.success) {
            if (data.limit_reached && data.limit_type === 'questions') {
                debug.warn('Monthly question limit reached');
                showError('لقد وصلت إلى الحد الشهري للأسئلة. يرجى ترقية اشتراكك للمتابعة.', true);
                return;
            }
            if (data.error) {
                debug.error('Server returned error:', data.error);
                throw new Error(data.error);
            }
            throw new Error('Unknown error from server');
        }
        
        // Hide typing indicator
        hideTypingIndicator();
        
        // Handle the response structure
        const messageData = data.data?.data;
        debug.log('Message data from response:', messageData);
        
        if (messageData && messageData.assistant_message) {
            debug.log('Processing assistant message:', messageData.assistant_message);
            
            // Validate assistant message format
            if (!messageData.assistant_message.content || !messageData.assistant_message.role) {
                debug.error('Invalid assistant message format:', messageData.assistant_message);
                throw new Error('Invalid assistant message format');
            }
            
            const assistantMessage = createMessageElement({
                content: messageData.assistant_message.content,
                role: messageData.assistant_message.role
            });
            chatContainer.appendChild(assistantMessage);
            assistantMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
            debug.log('Assistant message added to chat');
        } else {
            debug.warn('No assistant message in response:', data);
            // Don't throw error, just log warning
        }
        
    } catch (error) {
        debug.error('Error in sendMessage:', error);
        debug.error('Error stack:', error.stack);
        showError('حدث خطأ أثناء إرسال الرسالة');
        hideTypingIndicator();
        
        // Remove the user message if it was added but the request failed
        const lastMessage = chatContainer.lastElementChild;
        if (lastMessage && lastMessage.classList.contains('user')) {
            lastMessage.remove();
        }
    } finally {
        // Re-enable input and button
        messageInput.disabled = false;
        sendButton.disabled = false;
        messageInput.focus();
        debug.log('Message send process completed');
    }
}

// Show error message
function showError(message, isLimitError = false) {
    debug.warn('Showing error message:', message);
    const errorDiv = document.createElement('div');
    errorDiv.className = isLimitError ? 'limit-warning-message' : 'error-message';
    
    if (isLimitError) {
        errorDiv.innerHTML = `
            <div class="flex items-center justify-between p-4 bg-yellow-50 border-l-4 border-yellow-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">${message}</p>
                    </div>
                </div>
            </div>
        `;
    } else {
        errorDiv.innerHTML = `
            <div class="flex items-center justify-between p-4 bg-red-50 border-l-4 border-red-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">${message}</p>
                    </div>
                </div>
            </div>
        `;
    }
    
    const chatContainer = document.getElementById('chatContainer');
    chatContainer.appendChild(errorDiv);
    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
    
    // Auto-remove error after 5 seconds
    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
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
        const response = await fetch(`/${baseUrlPath}/api/create_payment.php`, {
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

// Load available plugins and populate selector
async function loadPlugins() {
    debug.log('Loading plugins');
    try {
        const response = await fetch(`${window.apiBaseUrl}/plugins.php`);
        debug.log('Plugins API response status:', response.status);
        
        if (!response.ok) {
            throw new Error('Failed to load plugins');
        }
        
        const data = await response.json();
        debug.log('Plugins data received:', data);
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to load plugins');
        }
        
        const pluginSelector = document.getElementById('pluginSelector');
        pluginSelector.innerHTML = ''; // Clear existing options
        
        data.plugins.forEach(plugin => {
            const option = document.createElement('option');
            option.value = plugin.id;
            option.textContent = plugin.name;
            if (data.selected_plugin === plugin.id) {
                option.selected = true;
            }
            pluginSelector.appendChild(option);
        });
        
        // Add event listener for plugin selection
        pluginSelector.addEventListener('change', async function() {
            debug.log('Plugin selection changed:', this.value);
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
                
                debug.log('Plugin preference updated successfully');

                // If there's an active conversation, update its plugin
                if (currentConversationId) {
                    debug.log('Updating current conversation plugin:', {
                        conversation_id: currentConversationId,
                        plugin_id: this.value
                    });

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

                    debug.log('Conversation plugin updated successfully');

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
                    debug.log('Added system message about plugin change');
                }
            } catch (error) {
                debug.error('Error updating plugin preference:', error);
                showError('فشل في تحديث تفضيلات المعالج');
            }
        });
    } catch (error) {
        debug.error('Error loading plugins:', error);
        showError('فشل في تحميل المعالجات المتاحة');
    }
}

// Sidebar toggle functionality
const sidebar = document.querySelector('.sidebar');
const sidebarOverlay = document.querySelector('.sidebar-overlay');
const toggleSidebarBtn = document.querySelector('.toggle-sidebar');

function toggleSidebar() {
    sidebar.classList.toggle('open');
    sidebarOverlay.classList.toggle('active');
    document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
}

// Add event listeners for sidebar toggle
toggleSidebarBtn.addEventListener('click', toggleSidebar);
sidebarOverlay.addEventListener('click', toggleSidebar);

// Close sidebar when clicking on a conversation
document.addEventListener('DOMContentLoaded', () => {
    const conversationsList = document.getElementById('conversationsList');
    if (conversationsList) {
        conversationsList.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                toggleSidebar();
            }
        });
    }
});

// Handle window resize
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    debug.log('DOM Content Loaded, initializing chat application');
    loadConversations();
    loadPlugins().then(() => {
        debug.log('Plugin selector contents:', document.getElementById('pluginSelector').innerHTML);
    });
    
    // Add click event listener for send button
    const sendButton = document.getElementById('sendButton');
    if (sendButton) {
        debug.log('Adding click listener to send button');
        sendButton.addEventListener('click', () => {
            debug.log('Send button clicked');
            sendMessage();
        });
    } else {
        debug.error('Send button not found in DOM');
    }
    
    // Handle enter key in message input
    const messageInput = document.getElementById('messageInput');
    if (messageInput) {
        debug.log('Adding keypress listener to message input');
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                debug.log('Enter key pressed in message input');
                e.preventDefault();
                sendMessage();
            }
        });
    } else {
        debug.error('Message input not found in DOM');
    }

    // Remove auto-resize functionality
    if (messageInput) {
        messageInput.style.height = '48px';
    }
}); 