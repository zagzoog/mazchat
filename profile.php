<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Load configuration
$config = require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات الملف الشخصي</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .error-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #ff4444;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }
        .success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #00C851;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <a href="index.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <h1 class="text-3xl font-bold text-gray-800">إعدادات الملف الشخصي</h1>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-gray-600">مرحباً، <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php" class="text-red-600 hover:text-red-700">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Profile Information Form -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold mb-6">المعلومات الشخصية</h2>
                <form id="profile-form" class="space-y-4">
                    <div>
                        <label class="block text-gray-700 mb-2">اسم المستخدم</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($_SESSION['username']); ?>"
                               class="w-full p-2 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed"
                               readonly>
                        <p class="text-sm text-gray-500 mt-1">لا يمكن تغيير اسم المستخدم</p>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">البريد الإلكتروني</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_SESSION['email']); ?>"
                               class="w-full p-2 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed"
                               readonly>
                        <p class="text-sm text-gray-500 mt-1">لا يمكن تغيير البريد الإلكتروني</p>
                    </div>
                </form>
            </div>

            <!-- Password Change Form -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold mb-6">تغيير كلمة المرور</h2>
                <form id="password-form" class="space-y-4">
                    <div>
                        <label class="block text-gray-700 mb-2">كلمة المرور الحالية</label>
                        <input type="password" id="current-password" name="current_password"
                               class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">كلمة المرور الجديدة</label>
                        <input type="password" id="new-password" name="new_password"
                               class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">تأكيد كلمة المرور الجديدة</label>
                        <input type="password" id="confirm-password" name="confirm_password"
                               class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <button type="submit" 
                            class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition-colors">
                        تغيير كلمة المرور
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Show message function
        function showMessage(message, isError = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = isError ? 'error-message' : 'success-message';
            messageDiv.textContent = message;
            document.body.appendChild(messageDiv);
            
            setTimeout(() => {
                messageDiv.remove();
            }, 3000);
        }

        // Handle password form submission
        document.getElementById('password-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (newPassword !== confirmPassword) {
                showMessage('كلمات المرور غير متطابقة', true);
                return;
            }
            
            try {
                const response = await fetch('api/update_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        current_password: currentPassword,
                        new_password: newPassword
                    })
                });
                
                const data = await response.json();
                
                if (data.error) {
                    showMessage(data.error, true);
                } else {
                    showMessage('تم تغيير كلمة المرور بنجاح');
                    e.target.reset();
                }
            } catch (error) {
                showMessage('حدث خطأ أثناء تغيير كلمة المرور', true);
            }
        });
    </script>
</body>
</html> 