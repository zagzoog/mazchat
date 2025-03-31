<?php
session_start();
require_once __DIR__ . '/../app/utils/ResponseCompressor.php';
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../app/utils/Logger.php';
require_once __DIR__ . '/../app/models/Model.php';
require_once __DIR__ . '/../app/models/Plugin.php';
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
$user = new User();
$userData = $user->findById($_SESSION['user_id']);

if (!$userData || $userData['role'] !== 'admin') {
    header('Location: ' . getFullUrlPath('index.php'));
    exit;
}

// Get plugin ID from URL
$pluginId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$pluginId) {
    $_SESSION['error_message'] = "لم يتم تحديد الإضافة";
    header('Location: ' . getFullUrlPath('admin/plugins.php'));
    exit;
}

// Get plugin model
$pluginModel = new PluginModel();

// Get plugin data
$plugin = $pluginModel->findById($pluginId);

if (!$plugin) {
    $_SESSION['error_message'] = "لم يتم العثور على الإضافة";
    header('Location: ' . getFullUrlPath('admin/plugins.php'));
    exit;
}

// Load plugin class
$pluginClass = $plugin['name'];
$pluginFile = dirname(__DIR__) . '/plugins/' . $pluginClass . '/' . $pluginClass . '.php';

if (file_exists($pluginFile)) {
    require_once $pluginFile;
    if (class_exists($pluginClass)) {
        $pluginInstance = new $pluginClass($pluginId);
        error_log("Plugin loaded successfully: " . $pluginClass);
        
        // Ensure plugin is activated and tables are created
        if (method_exists($pluginInstance, 'activate')) {
            $pluginInstance->activate($pluginId);
        }
    } else {
        error_log("Plugin class not found: " . $pluginClass);
    }
} else {
    error_log("Plugin file not found: " . $pluginFile);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $plugin->update($_POST['id'], $_POST);
        header('Location: ' . getFullUrlPath('admin/plugins.php'));
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الإضافة - تطبيق الدردشة</title>
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
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        .plugin-settings {
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <?php include '../app/views/components/navbar.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- General Plugin Settings -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">الإعدادات العامة</h4>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo getFullUrlPath('admin/plugin_edit.php'); ?>" method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">اسم الإضافة</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($plugin['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">الوصف</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($plugin['description']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="author" class="form-label">المؤلف</label>
                                <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($plugin['author']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="version" class="form-label">الإصدار</label>
                                <input type="text" class="form-control" id="version" name="version" value="<?php echo htmlspecialchars($plugin['version']); ?>">
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?php echo $plugin['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">تفعيل الإضافة</label>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo getFullUrlPath('admin/plugins.php'); ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-right"></i> رجوع
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> حفظ التغييرات
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Plugin-Specific Settings -->
                <?php if (isset($pluginInstance)): ?>
                <div class="card plugin-settings">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">إعدادات <?php echo htmlspecialchars($plugin['name']); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php 
                        // Debug information
                        error_log("Plugin Class: " . $pluginClass);
                        error_log("Plugin Instance: " . get_class($pluginInstance));
                        
                        if (strpos($pluginClass, 'N8nWebhookHandler') !== false): 
                            // Get plugin settings using the plugin's method
                            $settings = $pluginInstance->getSettings();
                            error_log("Settings: " . print_r($settings, true));
                        ?>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="n8nwebhookhandler_settings">
                                <div class="mb-3">
                                    <label for="webhook_url" class="form-label">رابط Webhook</label>
                                    <input type="url" class="form-control" id="webhook_url" name="webhook_url" 
                                           value="<?php echo htmlspecialchars($settings['webhook_url'] ?? ''); ?>" required>
                                    <div class="form-text">أدخل رابط Webhook الخاص بـ n8n</div>
                                </div>
                                <div class="mb-3">
                                    <label for="timeout" class="form-label">مهلة الاتصال (بالثواني)</label>
                                    <input type="number" class="form-control" id="timeout" name="timeout" 
                                           value="<?php echo htmlspecialchars($settings['timeout'] ?? 30); ?>" min="1" max="300">
                                    <div class="form-text">الوقت الأقصى للانتظار للحصول على استجابة من Webhook</div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="webhook_active" name="is_active" 
                                               <?php echo ($settings['is_active'] ?? true) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="webhook_active">تفعيل Webhook</label>
                                        <div class="form-text">تفعيل أو تعطيل معالجة الرسائل من خلال Webhook</div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
                            </form>
                        <?php else: ?>
                            <p class="text-muted">لا توجد إعدادات خاصة لهذه الإضافة</p>
                            <p class="text-muted">Plugin Class: <?php echo htmlspecialchars($pluginClass); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 