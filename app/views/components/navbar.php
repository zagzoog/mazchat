<?php
if (!defined('ADMIN_PANEL')) {
    die('Direct access not permitted');
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="/chat/admin">لوحة التحكم</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="/chat/admin">
                        <i class="fas fa-home"></i> الرئيسية
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>" href="/chat/admin/users.php">
                        <i class="fas fa-users"></i> المستخدمين
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'subscriptions.php' ? 'active' : ''; ?>" href="/chat/admin/subscriptions.php">
                        <i class="fas fa-crown"></i> الاشتراكات
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'developer_portal.php' ? 'active' : ''; ?>" href="/chat/admin/developer_portal.php">
                        <i class="fas fa-code"></i> بوابة المطورين
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>" href="/chat/admin/settings.php">
                        <i class="fas fa-cog"></i> الإعدادات
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="/chat/admin/profile.php">
                        <i class="fas fa-user"></i> الملف الشخصي
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/chat/admin/logout.php">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav> 