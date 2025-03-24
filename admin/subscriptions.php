<?php
session_start();
require_once __DIR__ . '/../app/utils/ResponseCompressor.php';
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../app/utils/Logger.php';
require_once __DIR__ . '/../app/models/Model.php';
require_once __DIR__ . '/../app/models/User.php';
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

// Get subscription statistics
$membershipModel = new Membership();
$subscriptionStats = $membershipModel->getSubscriptionStats();

// Handle subscription updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                if (isset($_POST['user_id']) && isset($_POST['type']) && isset($_POST['duration'])) {
                    $membershipModel->updateMembership(
                        $_POST['user_id'],
                        $_POST['type'],
                        $_POST['duration']
                    );
                }
                break;
            case 'cancel':
                if (isset($_POST['user_id'])) {
                    $membershipModel->cancelMembership($_POST['user_id']);
                }
                break;
        }
        header('Location: /chat/admin/subscriptions.php');
        exit;
    }
}

// Get all users with their subscriptions
$users = $userModel->getAllUsersWithSubscriptions();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الاشتراكات - تطبيق الدردشة</title>
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
        .modal-content {
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <?php include '../app/views/components/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1>إدارة الاشتراكات</h1>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="number"><?php echo $subscriptionStats['free_count']; ?></div>
                                <div class="label">الاشتراكات المجانية</div>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user"></i>
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
                                <div class="number"><?php echo $subscriptionStats['silver_count']; ?></div>
                                <div class="label">الاشتراكات الفضية</div>
                            </div>
                            <div class="icon">
                                <i class="fas fa-crown"></i>
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
                                <div class="number"><?php echo $subscriptionStats['gold_count']; ?></div>
                                <div class="label">الاشتراكات الذهبية</div>
                            </div>
                            <div class="icon">
                                <i class="fas fa-star"></i>
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
                                <div class="number">$<?php echo number_format($subscriptionStats['monthly_revenue'], 2); ?></div>
                                <div class="label">الإيرادات الشهرية</div>
                            </div>
                            <div class="icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>المستخدم</th>
                                <th>نوع الاشتراك</th>
                                <th>تاريخ البداية</th>
                                <th>تاريخ الانتهاء</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td>
                                    <span class="subscription-badge subscription-<?php echo strtolower($user['membership_type']); ?>">
                                        <?php echo $user['membership_type']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($user['start_date'])); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($user['end_date'])); ?></td>
                                <td>
                                    <?php if (strtotime($user['end_date']) > time()): ?>
                                        <span class="badge bg-success">نشط</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">منتهي</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="showUpdateModal(<?php echo $user['id']; ?>, '<?php echo $user['membership_type']; ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من إلغاء الاشتراك؟');">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Subscription Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تحديث الاشتراك</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" id="update_user_id">
                        <div class="mb-3">
                            <label class="form-label">نوع الاشتراك</label>
                            <select class="form-select" name="type" required>
                                <option value="free">مجاني</option>
                                <option value="silver">فضي</option>
                                <option value="gold">ذهبي</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">المدة (بالأشهر)</label>
                            <input type="number" class="form-control" name="duration" min="1" value="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showUpdateModal(userId, currentType) {
            document.getElementById('update_user_id').value = userId;
            document.querySelector('select[name="type"]').value = currentType.toLowerCase();
            new bootstrap.Modal(document.getElementById('updateModal')).show();
        }
    </script>
</body>
</html> 