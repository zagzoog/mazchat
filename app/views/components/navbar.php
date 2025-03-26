<?php
if (!defined('ADMIN_PANEL')) {
    die('Direct access not permitted');
}

require_once __DIR__ . '/../../../path_config.php';

$current_page = basename($_SERVER['PHP_SELF']);
?>

<?php include 'internet_status.php'; ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo getFullUrlPath('admin'); ?>">لوحة التحكم</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="<?php echo getFullUrlPath('admin'); ?>">
                        <i class="fas fa-home"></i> الرئيسية
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>" href="<?php echo getFullUrlPath('admin/users.php'); ?>">
                        <i class="fas fa-users"></i> المستخدمين
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'subscriptions.php' ? 'active' : ''; ?>" href="<?php echo getFullUrlPath('admin/subscriptions.php'); ?>">
                        <i class="fas fa-crown"></i> الاشتراكات
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'plugins.php' ? 'active' : ''; ?>" href="<?php echo getFullUrlPath('admin/plugins.php'); ?>">
                        <i class="fas fa-puzzle-piece"></i> الإضافات
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'developer_portal.php' ? 'active' : ''; ?>" href="<?php echo getFullUrlPath('admin/developer_portal.php'); ?>">
                        <i class="fas fa-code"></i> بوابة المطورين
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>" href="<?php echo getFullUrlPath('admin/settings.php'); ?>">
                        <i class="fas fa-cog"></i> الإعدادات
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo getFullUrlPath('admin/profile.php'); ?>">
                        <i class="fas fa-user"></i> الملف الشخصي
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo getFullUrlPath('admin/logout.php'); ?>">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav> 