<?php
session_start();
require_once __DIR__ . '/../app/utils/ResponseCompressor.php';
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../app/utils/Logger.php';
require_once __DIR__ . '/../app/models/Model.php';
require_once __DIR__ . '/../app/models/User.php';

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

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'delete') {
                $user->delete($_POST['user_id']);
            } elseif ($_POST['action'] === 'toggle_status') {
                $user->toggleStatus($_POST['user_id']);
            }
        }
        header('Location: <?php echo getFullUrlPath("admin/users.php"); ?>');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$users = $user->getAll($limit, $offset);
$totalUsers = $user->countAll();
$totalPages = ceil($totalUsers / $limit);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين - تطبيق الدردشة</title>
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
        }
        .table th {
            font-weight: 600;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .status-active {
            background-color: #e3fcef;
            color: #0a7b3e;
        }
        .status-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .role-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .role-admin {
            background-color: #e0e7ff;
            color: #3730a3;
        }
        .role-user {
            background-color: #f3f4f6;
            color: #4b5563;
        }
    </style>
</head>
<body>
    <?php include '../app/views/components/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>إدارة المستخدمين</h1>
                    <a href="/chat/admin/add_user.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> إضافة مستخدم جديد
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>المعرف</th>
                                <th>اسم المستخدم</th>
                                <th>البريد الإلكتروني</th>
                                <th>الدور</th>
                                <th>الحالة</th>
                                <th>تاريخ التسجيل</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo strtolower($user['role']); ?>">
                                        <?php echo $user['role'] === 'admin' ? 'مدير' : 'مستخدم'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-active">
                                        نشط
                                    </span>
                                </td>
                                <td><?php echo isset($user['created_at']) ? date('Y/m/d H:i', strtotime($user['created_at'])) : 'غير محدد'; ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo getFullUrlPath('admin/user_edit.php?id=' . $user['id']); ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-warning">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 