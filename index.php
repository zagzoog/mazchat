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
require_once __DIR__ . '/path_config.php';

// Ensure required database columns exist
require_once 'app/models/Message.php';
require_once 'app/models/UsageStats.php';
$messageModel = new Message();
$usageStatsModel = new UsageStats();
$messageModel->ensureColumns();
$usageStatsModel->ensureColumns();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام المطابقة الذكي</title>
    <link href="<?php echo getFullUrlPath('public/css/chat.css'); ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <?php if ($current_config['development_mode']): ?>
        <script src="<?php echo getFullUrlPath('public/js/chat.dev.js'); ?>" defer></script>
    <?php else: ?>
        <script src="<?php echo getFullUrlPath('public/js/chat.js'); ?>" defer></script>
    <?php endif; ?>
    <script>
        window.conversationsPerPage = <?php echo json_encode($current_config['conversations_per_page']); ?>;
        window.baseUrlPath = <?php echo json_encode($base_url_path); ?>;
    </script>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <button class="new-chat-btn" onclick="createNewConversation()">
                    <i class="fas fa-plus"></i>
                    محادثة جديدة
                </button>
            </div>
            <div class="sidebar-content">
                <div id="conversationsList">
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
        <div class="main-chat">
            <div class="chat-header">
                <button class="toggle-sidebar" aria-label="Toggle Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="header-actions">
                    <a href="dashboard.php" class="dashboard-link" aria-label="لوحة التحكم">
                        <i class="fas fa-chart-line"></i>
                    </a>
                </div>
                <div class="plugin-selector">
                    <label for="pluginSelector">معالج الرسائل:</label>
                    <select id="pluginSelector" aria-label="Select Message Processor">
                        <option value="">جاري التحميل...</option>
                    </select>
                </div>
            </div>
            
            <div class="chat-container" role="log" aria-live="polite">
                <div id="chatContainer">
                    <!-- Messages will be loaded here -->
                </div>
                <div id="typingIndicator" class="typing-indicator" style="display: none;">
                    <div class="loading-dots">
                        <div class="loading-dot"></div>
                        <div class="loading-dot"></div>
                        <div class="loading-dot"></div>
                    </div>
                </div>
            </div>
            
            <div class="chat-input-container">
                <div class="chat-input">
                    <input 
                        type="text"
                        id="messageInput" 
                        placeholder="اكتب رسالتك هنا..." 
                        aria-label="Message Input"
                    />
                    <div class="chat-controls">
                        <button id="sendButton" class="send-button" aria-label="Send Message">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay"></div>
</body>
</html> 