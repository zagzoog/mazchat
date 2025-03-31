<?php
require_once __DIR__ . '/../config/path_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../app/models/Subscription.php';

session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . getFullUrlPath('login.php'));
    exit;
}

$subscription = new Subscription();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'type' => $_POST['type'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'auto_renew' => isset($_POST['auto_renew']) ? 1 : 0
        ];

        if ($subscription->update($_POST['id'], $data)) {
            $success = 'تم تحديث الاشتراك بنجاح';
        } else {
            $error = 'فشل في تحديث الاشتراك';
        }
    } catch (Exception $e) {
        error_log("Error updating subscription: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

// Get subscription data
$id = isset($_GET['id']) ? $_GET['id'] : '';
try {
    $subscriptionData = $subscription->findById($id);
    if (!$subscriptionData) {
        error_log("Subscription not found with ID: " . $id);
        $error = "لم يتم العثور على الاشتراك برقم " . $id;
    }
} catch (Exception $e) {
    error_log("Error fetching subscription: " . $e->getMessage());
    $error = "حدث خطأ أثناء تحميل بيانات الاشتراك";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الاشتراك - لوحة التحكم</title>
    <link rel="stylesheet" href="<?php echo getFullUrlPath('assets/css/style.css'); ?>">
</head>
<body>
    <div class="container">
        <h1>تعديل الاشتراك</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($subscriptionData) && $subscriptionData): ?>
        <form method="POST" class="form">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($subscriptionData['id']); ?>">
            
            <div class="form-group">
                <label for="type">نوع الاشتراك:</label>
                <select name="type" id="type" required>
                    <option value="free" <?php echo $subscriptionData['type'] === 'free' ? 'selected' : ''; ?>>مجاني</option>
                    <option value="silver" <?php echo $subscriptionData['type'] === 'silver' ? 'selected' : ''; ?>>فضي</option>
                    <option value="gold" <?php echo $subscriptionData['type'] === 'gold' ? 'selected' : ''; ?>>ذهبي</option>
                </select>
            </div>

            <div class="form-group">
                <label for="start_date">تاريخ البداية:</label>
                <input type="datetime-local" name="start_date" id="start_date" 
                       value="<?php echo date('Y-m-d\TH:i', strtotime($subscriptionData['start_date'])); ?>" required>
            </div>

            <div class="form-group">
                <label for="end_date">تاريخ النهاية:</label>
                <input type="datetime-local" name="end_date" id="end_date" 
                       value="<?php echo $subscriptionData['end_date'] ? date('Y-m-d\TH:i', strtotime($subscriptionData['end_date'])) : ''; ?>">
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="auto_renew" <?php echo $subscriptionData['auto_renew'] ? 'checked' : ''; ?>>
                    تجديد تلقائي
                </label>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                <a href="<?php echo getFullUrlPath('admin/subscriptions.php'); ?>" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
        <?php else: ?>
            <div class="alert alert-warning">
                <p>لم يتم العثور على الاشتراك المطلوب.</p>
                <a href="<?php echo getFullUrlPath('admin/subscriptions.php'); ?>" class="btn btn-secondary">العودة إلى قائمة الاشتراكات</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 