<?php
if (!defined('ADMIN_PANEL')) {
    exit('Direct access not permitted');
}
?>
<nav class="bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <a href="/chat/admin/dashboard.php" class="text-xl font-bold text-gray-800">
                        لوحة التحكم
                    </a>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8 sm:space-x-reverse">
                    <a href="/chat/admin/dashboard.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-chart-line"></i> لوحة المعلومات
                    </a>
                    <a href="/chat/admin/users.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-users"></i> المستخدمين
                    </a>
                    <a href="/chat/admin/subscriptions.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-crown"></i> الاشتراكات
                    </a>
                    <a href="/chat/admin/plugins.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-puzzle-piece"></i> الإضافات
                    </a>
                    <a href="/chat/admin/settings.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-cog"></i> الإعدادات
                    </a>
                </div>
            </div>
            <div class="flex items-center">
                <div class="ml-3 relative">
                    <div class="flex items-center">
                        <span class="text-gray-700 mr-4">
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </span>
                        <a href="/chat/admin/logout.php" class="text-red-600 hover:text-red-900">
                            <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div class="sm:hidden" id="mobile-menu">
        <div class="pt-2 pb-3 space-y-1">
            <a href="/chat/admin/dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50">
                <i class="fas fa-chart-line"></i> لوحة المعلومات
            </a>
            <a href="/chat/admin/users.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50">
                <i class="fas fa-users"></i> المستخدمين
            </a>
            <a href="/chat/admin/subscriptions.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50">
                <i class="fas fa-crown"></i> الاشتراكات
            </a>
            <a href="/chat/admin/plugins.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50">
                <i class="fas fa-puzzle-piece"></i> الإضافات
            </a>
            <a href="/chat/admin/settings.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50">
                <i class="fas fa-cog"></i> الإعدادات
            </a>
        </div>
    </div>
</nav> 