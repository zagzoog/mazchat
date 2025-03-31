<?php
session_start();

// Load required classes
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Subscription.php';
require_once __DIR__ . '/../path_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . getFullUrlPath('login.php'));
    exit;
}

// Check if user is admin
$user = new User();
$userData = $user->findById($_SESSION['user_id']);

if (!$userData || $userData['role'] !== 'admin') {
    header('Location: ' . getFullUrlPath('index.php'));
    exit;
}

// Get subscription ID from URL
$subscriptionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$subscriptionId) {
    $_SESSION['error_message'] = "لم يتم تحديد الاشتراك";
    header('Location: ' . getFullUrlPath('admin/subscriptions.php'));
    exit;
}

// Get subscription details
$subscription = new Subscription();
$subscriptionData = $subscription->findById($subscriptionId);

if (!$subscriptionData) {
    $_SESSION['error_message'] = "لم يتم العثور على الاشتراك";
    header('Location: ' . getFullUrlPath('admin/subscriptions.php'));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $subscription->update($subscriptionId, $_POST);
        $_SESSION['success_message'] = "تم تحديث الاشتراك بنجاح";
        header('Location: ' . getFullUrlPath('admin/subscriptions.php'));
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get available plans
$plans = [
    'free' => 'مجاني',
    'silver' => 'فضي',
    'gold' => 'ذهبي'
];

// Get user details
$userData = $user->findById($subscriptionData['user_id']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الاشتراك - لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #343a40;
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .form-label {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-edit me-2"></i>
                            تعديل اشتراك المستخدم: <?php echo htmlspecialchars($userData['username']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="plan" class="form-label">نوع الاشتراك</label>
                                <select class="form-select" id="plan" name="plan" required>
                                    <?php foreach ($plans as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo $subscriptionData['plan'] === $value ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="start_date" class="form-label">تاريخ البداية</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo date('Y-m-d', strtotime($subscriptionData['start_date'])); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="end_date" class="form-label">تاريخ النهاية</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo date('Y-m-d', strtotime($subscriptionData['end_date'])); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">الحالة</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?php echo $subscriptionData['status'] === 'active' ? 'selected' : ''; ?>>نشط</option>
                                    <option value="expired" <?php echo $subscriptionData['status'] === 'expired' ? 'selected' : ''; ?>>منتهي</option>
                                    <option value="cancelled" <?php echo $subscriptionData['status'] === 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="<?php echo getFullUrlPath('admin/subscriptions.php'); ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-right"></i> رجوع
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> حفظ التغييرات
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 