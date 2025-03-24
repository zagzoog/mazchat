<?php
session_start();
require_once __DIR__ . '/../app/utils/ResponseCompressor.php';
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../app/utils/Logger.php';
require_once __DIR__ . '/../app/models/Model.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Conversation.php';
require_once __DIR__ . '/../app/models/Message.php';
require_once __DIR__ . '/../app/models/Membership.php';

// Define ADMIN_PANEL constant for navbar access
define('ADMIN_PANEL', true);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /chat/login.php');
    exit;
}

// Check if user is admin
$userModel = new User();
$user = $userModel->findById($_SESSION['user_id']);

if (!$user || !$userModel->isAdmin($_SESSION['user_id'])) {
    header('Location: /chat/index.php');
    exit;
}

// Get statistics
$totalUsers = $userModel->countAll();
$conversationModel = new Conversation();
$totalConversations = $conversationModel->countAll();
$messageModel = new Message();
$totalMessages = $messageModel->countAll();

// Get subscription statistics
$membershipModel = new Membership();
$subscriptionStats = $membershipModel->getSubscriptionStats();

// Get recent users
$recentUsers = $userModel->getRecentUsers(5);
// Get recent conversations
$recentConversations = $conversationModel->getRecentConversations(5);
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
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            background: linear-gradient(45deg, #4f46e5, #7c3aed);
            color: white;
        }
        .stat-card .icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
        }
        .stat-card .label {
            font-size: 1rem;
            opacity: 0.9;
        }
        .table th {
            font-weight: 600;
        }
        .nav-link {
            color: #4f46e5;
        }
        .nav-link:hover {
            color: #7c3aed;
        }
        .subscription-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .subscription-free {
            background-color: #e5e7eb;
            color: #374151;
        }
        .subscription-silver {
            background-color: #c0c0c0;
            color: #1f2937;
        }
        .subscription-gold {
            background-color: #ffd700;
            color: #1f2937;
        }
    </style>
</head>
<body>
    <?php include '../app/views/components/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1>لوحة التحكم</h1>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="number"><?php echo $totalUsers; ?></div>
                                <div class="label">المستخدمين</div>
                            </div>
                            <div class="icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="number"><?php echo $totalConversations; ?></div>
                                <div class="label">المحادثات</div>
                            </div>
                            <div class="icon">
                                <i class="fas fa-comments"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="number"><?php echo $totalMessages; ?></div>
                                <div class="label">الرسائل</div>
                            </div>
                            <div class="icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="number"><?php echo $subscriptionStats['total_active']; ?></div>
                                <div class="label">الاشتراكات النشطة</div>
                            </div>
                            <div class="icon">
                                <i class="fas fa-crown"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscription Statistics -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">إحصائيات الاشتراكات</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="subscription-badge subscription-free me-2">مجاني</span>
                                    <span class="ms-2"><?php echo $subscriptionStats['free_count']; ?> مستخدم</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="subscription-badge subscription-silver me-2">فضي</span>
                                    <span class="ms-2"><?php echo $subscriptionStats['silver_count']; ?> مستخدم</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="subscription-badge subscription-gold me-2">ذهبي</span>
                                    <span class="ms-2"><?php echo $subscriptionStats['gold_count']; ?> مستخدم</span>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    إجمالي الإيرادات الشهرية: <?php echo number_format($subscriptionStats['monthly_revenue'], 2); ?> ريال
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    الاشتراكات المنتهية: <?php echo $subscriptionStats['expired']; ?> اشتراك
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <!-- Recent Users -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">أحدث المستخدمين</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>المستخدم</th>
                                        <th>البريد الإلكتروني</th>
                                        <th>الاشتراك</th>
                                        <th>تاريخ التسجيل</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentUsers as $user): 
                                        $membership = $membershipModel->getCurrentMembership($user['id']);
                                        $subscriptionType = $membership ? $membership['type'] : 'free';
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="subscription-badge subscription-<?php echo $subscriptionType; ?>">
                                                <?php echo $subscriptionType === 'free' ? 'مجاني' : ($subscriptionType === 'silver' ? 'فضي' : 'ذهبي'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y/m/d', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Conversations -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">أحدث المحادثات</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>العنوان</th>
                                        <th>المستخدم</th>
                                        <th>الاشتراك</th>
                                        <th>التاريخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentConversations as $conversation): 
                                        $membership = $membershipModel->getCurrentMembership($conversation['user_id']);
                                        $subscriptionType = $membership ? $membership['type'] : 'free';
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($conversation['title']); ?></td>
                                        <td><?php echo htmlspecialchars($conversation['username']); ?></td>
                                        <td>
                                            <span class="subscription-badge subscription-<?php echo $subscriptionType; ?>">
                                                <?php echo $subscriptionType === 'free' ? 'مجاني' : ($subscriptionType === 'silver' ? 'فضي' : 'ذهبي'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y/m/d', strtotime($conversation['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Admin Panel Links -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">إدارة المستخدمين</h5>
                        <p class="card-text">إدارة المستخدمين وعضوياتهم</p>
                        <a href="/chat/admin/users.php" class="btn btn-primary">
                            <i class="fas fa-users"></i> عرض المستخدمين
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">إعدادات النظام</h5>
                        <p class="card-text">تكوين إعدادات النظام العامة</p>
                        <a href="/chat/admin/settings.php" class="btn btn-primary">
                            <i class="fas fa-cog"></i> الإعدادات
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 