<?php
session_start();
require_once 'db_config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'login') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            try {
                $db = getDBConnection();
                $stmt = $db->prepare("SELECT id, password FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $username;
                    
                    // Get user's email
                    $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    $userData = $stmt->fetch();
                    $_SESSION['email'] = $userData['email'];
                    
                    // Check if user has an active membership
                    $stmt = $db->prepare("SELECT type FROM memberships WHERE user_id = ? AND end_date >= CURRENT_DATE ORDER BY start_date DESC LIMIT 1");
                    $stmt->execute([$user['id']]);
                    $membership = $stmt->fetch();
                    
                    if (!$membership) {
                        // Create free membership if none exists
                        $start_date = date('Y-m-d');
                        $end_date = date('Y-m-d', strtotime('+1 year'));
                        
                        $stmt = $db->prepare("INSERT INTO memberships (user_id, type, start_date, end_date) VALUES (?, 'free', ?, ?)");
                        $stmt->execute([$user['id'], $start_date, $end_date]);
                    }
                    
                    $_SESSION['membership_type'] = $membership ? $membership['type'] : 'free';
                    
                    header('Location: index.php');
                    exit;
                } else {
                    error_log("Login failed for username: " . $username);
                    $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
                }
            } catch (Exception $e) {
                error_log("Login error: " . $e->getMessage());
                $error = 'حدث خطأ أثناء تسجيل الدخول';
            }
        } elseif ($_POST['action'] === 'register') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validate input
            if (empty($username) || empty($email) || empty($password)) {
                $error = 'جميع الحقول مطلوبة';
            } elseif (strlen($username) < 3) {
                $error = 'يجب أن يكون اسم المستخدم 3 أحرف على الأقل';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'البريد الإلكتروني غير صالح';
            } elseif (strlen($password) < 6) {
                $error = 'يجب أن تكون كلمة المرور 6 أحرف على الأقل';
            } elseif ($password !== $confirm_password) {
                $error = 'كلمات المرور غير متطابقة';
            } else {
                try {
                    $db = getDBConnection();
                    
                    // Check if username exists
                    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $error = 'اسم المستخدم موجود مسبقاً';
                    } else {
                        // Check if email exists
                        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                        $stmt->execute([$email]);
                        if ($stmt->fetch()) {
                            $error = 'البريد الإلكتروني موجود مسبقاً';
                        } else {
                            // Create new user
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            
                            $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                            $stmt->execute([$username, $email, $hashed_password]);
                            
                            // Get the new user's ID
                            $user_id = $db->lastInsertId();
                            
                            // Create free membership for the new user
                            $start_date = date('Y-m-d');
                            $end_date = date('Y-m-d', strtotime('+1 year'));
                            
                            $stmt = $db->prepare("INSERT INTO memberships (user_id, type, start_date, end_date) VALUES (?, 'free', ?, ?)");
                            $stmt->execute([$user_id, $start_date, $end_date]);
                            
                            $success = 'تم إنشاء الحساب بنجاح. يمكنك الآن تسجيل الدخول.';
                            
                            // Clear the registration form
                            echo "<script>
                                document.getElementById('reg-username').value = '';
                                document.getElementById('email').value = '';
                                document.getElementById('reg-password').value = '';
                                document.getElementById('confirm-password').value = '';
                            </script>";
                        }
                    }
                } catch (Exception $e) {
                    error_log("Registration error: " . $e->getMessage());
                    $error = 'حدث خطأ أثناء إنشاء الحساب';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - نظام المطابقة الذكي</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    نظام المطابقة الذكي
                </h2>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <div class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">
                            اسم المستخدم
                        </label>
                        <div class="mt-1">
                            <input id="username" name="username" type="text" required 
                                   class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            كلمة المرور
                        </label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" required 
                                   class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <button type="submit" form="loginForm" 
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            تسجيل الدخول
                        </button>
                    </div>
                </div>

                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">
                                أو
                            </span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <form id="registerForm" method="POST" class="space-y-6">
                            <input type="hidden" name="action" value="register">
                            
                            <div>
                                <label for="reg-username" class="block text-sm font-medium text-gray-700">
                                    اسم المستخدم
                                </label>
                                <div class="mt-1">
                                    <input id="reg-username" name="username" type="text" required 
                                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">
                                    البريد الإلكتروني
                                </label>
                                <div class="mt-1">
                                    <input id="email" name="email" type="email" required 
                                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                            </div>

                            <div>
                                <label for="reg-password" class="block text-sm font-medium text-gray-700">
                                    كلمة المرور
                                </label>
                                <div class="mt-1">
                                    <input id="reg-password" name="password" type="password" required 
                                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                            </div>

                            <div>
                                <label for="confirm-password" class="block text-sm font-medium text-gray-700">
                                    تأكيد كلمة المرور
                                </label>
                                <div class="mt-1">
                                    <input id="confirm-password" name="confirm_password" type="password" required 
                                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                            </div>

                            <div>
                                <button type="submit" 
                                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    إنشاء حساب جديد
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="loginForm" method="POST" class="hidden">
        <input type="hidden" name="action" value="login">
        <input type="hidden" name="username" id="login-username">
        <input type="hidden" name="password" id="login-password">
    </form>

    <script>
        document.querySelector('button[form="loginForm"]').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('login-username').value = document.getElementById('username').value;
            document.getElementById('login-password').value = document.getElementById('password').value;
            document.getElementById('loginForm').submit();
        });
    </script>
</body>
</html> 