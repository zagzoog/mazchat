<?php
session_start();
require_once __DIR__ . '/../app/utils/ResponseCompressor.php';
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../app/utils/Logger.php';
require_once __DIR__ . '/../app/models/Model.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Message.php';
require_once __DIR__ . '/../app/models/Conversation.php';
require_once __DIR__ . '/../app/models/Plugin.php';

// Define ADMIN_PANEL constant for navbar access
define('ADMIN_PANEL', true);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: <?php echo getFullUrlPath("login.php"); ?>');
    exit;
}

// Load configuration
require_once __DIR__ . '/../path_config.php';

// Check if user is admin
$user = new User();
$userData = $user->findById($_SESSION['user_id']);

if (!$userData || $userData['role'] !== 'admin') {
    header('Location: <?php echo getFullUrlPath("index.php"); ?>');
    exit;
}

// Get dashboard statistics
$messageModel = new Message();
$conversationModel = new Conversation();
$pluginModel = new Plugin();

$totalMessages = $messageModel->countAll();
$totalConversations = $conversationModel->countAll();
$activePlugins = $pluginModel->countActive();
$recentMessages = $messageModel->getRecent(5);
$recentConversations = $conversationModel->getRecent(5);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - تطبيق الدردشة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
        }
        .stat-card i {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
        }
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        .recent-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .recent-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <?php include '../app/views/components/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">لوحة التحكم</h1>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?php echo $totalMessages; ?></div>
                                <div class="stat-label">الرسائل</div>
                            </div>
                            <i class="fas fa-comments"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?php echo $totalConversations; ?></div>
                                <div class="stat-label">المحادثات</div>
                            </div>
                            <i class="fas fa-chat"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?php echo $activePlugins; ?></div>
                                <div class="stat-label">الإضافات النشطة</div>
                            </div>
                            <i class="fas fa-puzzle-piece"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?php echo $userModel->countAll(); ?></div>
                                <div class="stat-label">المستخدمين</div>
                            </div>
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">آخر المحادثات</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($recentConversations as $conversation): ?>
                        <div class="recent-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($conversation['title']); ?></h6>
                                    <small class="text-muted"><?php echo date('Y/m/d H:i', strtotime($conversation['created_at'])); ?></small>
                                </div>
                                <a href="/chat/conversation.php?id=<?php echo $conversation['id']; ?>" class="btn btn-sm btn-primary">
                                    عرض
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">آخر الرسائل</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($recentMessages as $message): ?>
                        <div class="recent-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars(substr($message['content'], 0, 50)) . '...'; ?></h6>
                                    <small class="text-muted"><?php echo date('Y/m/d H:i', strtotime($message['created_at'])); ?></small>
                                </div>
                                <a href="/chat/conversation.php?id=<?php echo $message['conversation_id']; ?>" class="btn btn-sm btn-primary">
                                    عرض
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 