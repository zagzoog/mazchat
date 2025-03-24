<?php
session_start();
require_once __DIR__ . '/../app/utils/ResponseCompressor.php';
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../app/utils/Logger.php';
require_once __DIR__ . '/../app/models/Model.php';
require_once __DIR__ . '/../app/models/Plugin.php';
require_once __DIR__ . '/../app/models/User.php';

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

// Handle plugin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $pluginModel = new Plugin();
        switch ($_POST['action']) {
            case 'activate':
                if (isset($_POST['plugin_id'])) {
                    $pluginModel->activate($_POST['plugin_id']);
                }
                break;
            case 'deactivate':
                if (isset($_POST['plugin_id'])) {
                    $pluginModel->deactivate($_POST['plugin_id']);
                }
                break;
            case 'delete':
                if (isset($_POST['plugin_id'])) {
                    $pluginModel->delete($_POST['plugin_id']);
                }
                break;
            case 'install':
                if (isset($_POST['plugin_name'])) {
                    $pluginModel->install($_POST['plugin_name']);
                }
                break;
        }
        header('Location: /chat/admin/plugins.php');
        exit;
    }
}

// Get plugins
$pluginModel = new Plugin();
$installedPlugins = $pluginModel->getAll();
$availablePlugins = $pluginModel->getAvailablePlugins();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الإضافات - تطبيق الدردشة</title>
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
        .plugin-card {
            transition: transform 0.2s;
        }
        .plugin-card:hover {
            transform: translateY(-5px);
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
        .plugin-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #4e73df;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>إدارة الإضافات</h1>
                    <a href="/chat/admin/plugin_add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> إضافة إضافة جديدة
                    </a>
                </div>
            </div>
        </div>

        <!-- Installed Plugins -->
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="mb-3">الإضافات المثبتة</h2>
            </div>
            <?php foreach ($installedPlugins as $plugin): ?>
            <div class="col-md-4 mb-4">
                <div class="card plugin-card">
                    <div class="card-body text-center">
                        <i class="fas fa-puzzle-piece plugin-icon"></i>
                        <h5 class="card-title"><?php echo htmlspecialchars($plugin['name']); ?></h5>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($plugin['description']); ?></p>
                        <div class="mb-3">
                            <span class="status-badge status-<?php echo $plugin['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $plugin['is_active'] ? 'نشط' : 'غير نشط'; ?>
                            </span>
                        </div>
                        <div class="btn-group">
                            <?php if ($plugin['is_active']): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="deactivate">
                                <input type="hidden" name="plugin_id" value="<?php echo $plugin['id']; ?>">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-power-off"></i> تعطيل
                                </button>
                            </form>
                            <?php else: ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="activate">
                                <input type="hidden" name="plugin_id" value="<?php echo $plugin['id']; ?>">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-power-off"></i> تفعيل
                                </button>
                            </form>
                            <?php endif; ?>
                            <a href="/chat/admin/plugin_edit.php?id=<?php echo $plugin['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> تعديل
                            </a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الإضافة؟');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="plugin_id" value="<?php echo $plugin['id']; ?>">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> حذف
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Available Plugins -->
        <div class="row">
            <div class="col-12">
                <h2 class="mb-3">الإضافات المتاحة</h2>
            </div>
            <?php foreach ($availablePlugins as $plugin): ?>
            <div class="col-md-4 mb-4">
                <div class="card plugin-card">
                    <div class="card-body text-center">
                        <i class="fas fa-puzzle-piece plugin-icon"></i>
                        <h5 class="card-title"><?php echo htmlspecialchars($plugin['name']); ?></h5>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($plugin['description']); ?></p>
                        <div class="mb-3">
                            <span class="badge bg-info">الإصدار: <?php echo htmlspecialchars($plugin['version']); ?></span>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="install">
                            <input type="hidden" name="plugin_name" value="<?php echo htmlspecialchars($plugin['name']); ?>">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-download"></i> تثبيت
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 