<?php
session_start();
require_once __DIR__ . '/../app/utils/ResponseCompressor.php';
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../app/utils/Logger.php';
require_once __DIR__ . '/../app/models/Model.php';
require_once __DIR__ . '/../app/models/User.php';

// Define ADMIN_PANEL constant for navbar access
define('ADMIN_PANEL', true);

// Load configuration
require_once __DIR__ . '/../path_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . getFullUrlPath('login.php'));
    exit;
}

// Check if user is admin
$userModel = new User();
$userData = $userModel->findById($_SESSION['user_id']);

if (!$userData || $userData['role'] !== 'admin') {
    header('Location: ' . getFullUrlPath('index.php'));
    exit;
}

$error = '';
$success = '';
$userToEdit = null;

// Get user ID from URL
$userId = $_GET['id'] ?? '';
if (empty($userId)) {
    header('Location: ' . getFullUrlPath('admin/users.php'));
    exit;
}

// Get user data
$userToEdit = $userModel->findById($userId);
if (!$userToEdit) {
    header('Location: ' . getFullUrlPath('admin/users.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($username) || empty($email)) {
        $error = 'جميع الحقول مطلوبة';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'البريد الإلكتروني غير صالح';
    } elseif (!empty($password) && strlen($password) < 6) {
        $error = 'يجب أن تكون كلمة المرور 6 أحرف على الأقل';
    } else {
        try {
            // Check if username or email already exists for other users
            $existingUser = $userModel->findByUsername($username);
            if ($existingUser && $existingUser['id'] !== $userId) {
                $error = 'اسم المستخدم موجود بالفعل';
            } else {
                $existingUser = $userModel->findByEmail($email);
                if ($existingUser && $existingUser['id'] !== $userId) {
                    $error = 'البريد الإلكتروني موجود بالفعل';
                } else {
                    // Update user data
                    $userData = [
                        'username' => $username,
                        'email' => $email,
                        'role' => $role
                    ];

                    // Only update password if provided
                    if (!empty($password)) {
                        $userData['password'] = password_hash($password, PASSWORD_DEFAULT);
                    }

                    if ($userModel->update($userId, $userData)) {
                        $success = 'تم تحديث بيانات المستخدم بنجاح';
                        Logger::log("Updated user", 'INFO', [
                            'admin_id' => $_SESSION['user_id'],
                            'user_id' => $userId,
                            'username' => $username
                        ]);
                        header('Location: ' . getFullUrlPath('admin/users.php'));
                        exit;
                    } else {
                        $error = 'حدث خطأ أثناء تحديث بيانات المستخدم';
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'حدث خطأ أثناء تحديث بيانات المستخدم';
            Logger::log("Error updating user", 'ERROR', [
                'error' => $e->getMessage(),
                'admin_id' => $_SESSION['user_id'],
                'user_id' => $userId
            ]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل بيانات المستخدم - تطبيق الدردشة</title>
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
    </style>
</head>
<body>
    <?php include '../app/views/components/navbar.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title mb-4">تعديل بيانات المستخدم</h2>

                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                            <br>
                            <a href="<?php echo getFullUrlPath('admin/users.php'); ?>" class="btn btn-primary mt-2">
                                <i class="fas fa-arrow-right"></i> العودة إلى قائمة المستخدمين
                            </a>
                        </div>
                        <?php else: ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">اسم المستخدم</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($userToEdit['username']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($userToEdit['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">كلمة المرور الجديدة (اتركها فارغة إذا لم ترد تغييرها)</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">الدور</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="user" <?php echo $userToEdit['role'] === 'user' ? 'selected' : ''; ?>>مستخدم</option>
                                    <option value="admin" <?php echo $userToEdit['role'] === 'admin' ? 'selected' : ''; ?>>مدير</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="<?php echo getFullUrlPath('admin/users.php'); ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-right"></i> إلغاء
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> حفظ التغييرات
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 