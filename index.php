<?php
// Start the session
session_start();

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
    <title>محلل المطابقة الذكي</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #343541;
            color: #fff;
        }
        .chat-container {
            max-width: 800px;
            margin: 0 auto;
            height: calc(100vh - 180px);
            overflow-y: auto;
        }
        .message {
            padding: 24px 8px;
            border-bottom: 1px solid #4a4b53;
        }
        .message.user {
            background-color: #343541;
        }
        .message.assistant {
            background-color: #444654;
        }
        .message-content {
            max-width: 768px;
            margin: 0 auto;
            padding: 0 16px;
        }
        .input-container {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: #343541;
            border-top: 1px solid #4a4b53;
            padding: 24px 8px;
        }
        .input-wrapper {
            max-width: 768px;
            margin: 0 auto;
            position: relative;
        }
        .input-box {
            width: 100%;
            padding: 12px 45px 12px 16px;
            border-radius: 12px;
            border: 1px solid #565869;
            background-color: #40414f;
            color: #fff;
            font-size: 16px;
            line-height: 1.5;
            resize: none;
            max-height: 200px;
            text-align: right;
        }
        .input-box:focus {
            outline: none;
            border-color: #ff6b6b;
        }
        .send-button {
            position: absolute;
            left: 8px;
            bottom: 8px;
            background-color: #ff6b6b;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .send-button:hover {
            background-color: #ff5252;
        }
        .send-button:disabled {
            background-color: #565869;
            cursor: not-allowed;
        }
        .welcome-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            padding: 24px;
        }
        .welcome-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 16px;
            background: linear-gradient(to right, #ff6b6b, #ff8787);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .welcome-subtitle {
            font-size: 18px;
            color: #c5c5d2;
            margin-bottom: 32px;
            max-width: 600px;
            line-height: 1.6;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            max-width: 800px;
            margin: 0 auto;
        }
        .feature-card {
            background-color: #444654;
            border-radius: 12px;
            padding: 24px;
            text-align: right;
            transition: transform 0.2s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #ff6b6b;
        }
        .feature-description {
            color: #c5c5d2;
            line-height: 1.6;
        }
        .heart-icon {
            font-size: 24px;
            margin-bottom: 16px;
            color: #ff6b6b;
        }
        .loader {
            display: none;
            padding: 24px 8px;
            background-color: #444654;
        }
        .loader-content {
            max-width: 768px;
            margin: 0 auto;
            padding: 0 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .loader-dots {
            display: flex;
            gap: 4px;
        }
        .loader-dot {
            width: 8px;
            height: 8px;
            background-color: #ff6b6b;
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out;
        }
        .loader-dot:nth-child(1) { animation-delay: -0.32s; }
        .loader-dot:nth-child(2) { animation-delay: -0.16s; }
        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
        .loader-text {
            color: #c5c5d2;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="chat-container" id="messages">
        <div class="welcome-container" id="welcome">
            <div class="heart-icon">❤️</div>
            <h1 class="welcome-title">محلل المطابقة الذكي</h1>
            <p class="welcome-subtitle">خبير العلاقات المدعوم بالبيانات. أقوم بتحليل السمات الشخصية والتفضيلات وعوامل التوافق لمساعدتك في العثور على شريك حياتك المثالي.</p>
            <div class="features-grid">
                <div class="feature-card">
                    <h3 class="feature-title">تحليل الشخصية</h3>
                    <p class="feature-description">تحليل عميق للسمات الشخصية وأنماط التواصل وتفضيلات العلاقات لتحديد التطابق المناسب.</p>
                </div>
                <div class="feature-card">
                    <h3 class="feature-title">رؤى مدفوعة بالبيانات</h3>
                    <p class="feature-description">استخدام خوارزميات متقدمة وبيانات العلاقات لتقديم توصيات توافق مدعومة علمياً.</p>
                </div>
                <div class="feature-card">
                    <h3 class="feature-title">توجيه مخصص</h3>
                    <p class="feature-description">نصائح واستراتيجيات علاقات مخصصة بناءً على ملفك الشخصي الفريد وأهداف علاقاتك.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="loader" id="loader">
        <div class="loader-content">
            <div class="loader-dots">
                <div class="loader-dot"></div>
                <div class="loader-dot"></div>
                <div class="loader-dot"></div>
            </div>
            <div class="loader-text">جاري تحليل رسالتك...</div>
        </div>
    </div>
    
    <div class="input-container">
        <div class="input-wrapper">
            <textarea 
                id="messageInput" 
                class="input-box" 
                placeholder="أخبرني عن شريك حياتك المثالي أو اطلب نصيحة في العلاقات..." 
                rows="1"
                onInput="this.style.height = 'auto'; this.style.height = this.scrollHeight + 'px'"
            ></textarea>
            <button id="sendButton" class="send-button" onclick="sendMessage()">إرسال</button>
        </div>
    </div>

    <script>
        const messageInput = document.getElementById('messageInput');
        const messagesContainer = document.getElementById('messages');
        const welcomeContainer = document.getElementById('welcome');
        const sendButton = document.getElementById('sendButton');
        const loader = document.getElementById('loader');

        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        async function sendMessage() {
            const message = messageInput.value.trim();
            if (!message) return;

            // Disable input and button while sending
            messageInput.disabled = true;
            sendButton.disabled = true;

            // Hide welcome screen
            welcomeContainer.style.display = 'none';

            // Add user message
            addMessage('user', message);
            messageInput.value = '';
            messageInput.style.height = 'auto';

            // Show loader
            loader.style.display = 'block';
            messagesContainer.scrollTop = messagesContainer.scrollHeight;

            try {
                const response = await fetch('send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ message })
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();
                
                // Hide loader before adding the response
                loader.style.display = 'none';
                
                addMessage('assistant', data.response);
            } catch (error) {
                // Hide loader before showing error
                loader.style.display = 'none';
                addMessage('assistant', 'عذراً، واجهت خطأ. يرجى المحاولة مرة أخرى.');
            } finally {
                // Re-enable input and button
                messageInput.disabled = false;
                sendButton.disabled = false;
                messageInput.focus();
            }
        }

        function addMessage(role, content) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${role}`;
            
            const messageContent = document.createElement('div');
            messageContent.className = 'message-content';
            messageContent.textContent = content;
            
            messageDiv.appendChild(messageContent);
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    </script>
</body>
</html> 