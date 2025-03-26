<?php
session_start();
require_once '../app/utils/ResponseCompressor.php';
require_once '../db_config.php';
require_once '../app/utils/Logger.php';
require_once '../app/models/Model.php';
require_once '../app/models/User.php';
require_once '../environment.php';
require_once __DIR__ . '/../path_config.php';

// Define ADMIN_PANEL constant for navbar access
define('ADMIN_PANEL', true);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: <?php echo getFullUrlPath("login.php"); ?>');
    exit;
}

// Check if user is admin
$userModel = new User();
$user = $userModel->findById($_SESSION['user_id']);

if (!$user || !$userModel->isAdmin($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Get current settings
$config = require '../config.php';
$currentEnvironment = ENVIRONMENT;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات النظام</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .settings-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .form-label {
            font-weight: 600;
        }
        .form-check-label {
            font-weight: 500;
        }
        .alert {
            display: none;
            margin-top: 1rem;
        }
        .nav-link {
            color: #4f46e5;
        }
        .nav-link:hover {
            color: #4338ca;
        }
        .nav-link.active {
            color: #312e81;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include '../app/views/components/navbar.php'; ?>

    <div class="container mt-4">
        <div class="settings-container">
            <h2 class="mb-4">إعدادات النظام</h2>
            
            <!-- Success/Error Alert -->
            <div class="alert alert-success" role="alert" id="successAlert">
                تم تحديث الإعدادات بنجاح
            </div>
            <div class="alert alert-danger" role="alert" id="errorAlert">
                حدث خطأ أثناء تحديث الإعدادات
            </div>

            <!-- Application Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">إعدادات التطبيق</h5>
                </div>
                <div class="card-body">
                    <form id="appSettingsForm">
                        <div class="mb-3">
                            <label class="form-label">البيئة</label>
                            <select class="form-select" name="environment" required>
                                <option value="test" <?php echo $currentEnvironment === 'test' ? 'selected' : ''; ?>>اختبار</option>
                                <option value="production" <?php echo $currentEnvironment === 'production' ? 'selected' : ''; ?>>إنتاج</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="debug" id="debugSwitch" <?php echo $config['debug'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="debugSwitch">وضع التصحيح</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">عدد المحادثات في الصفحة</label>
                            <input type="number" class="form-control" name="conversations_per_page" value="<?php echo $config['conversations_per_page']; ?>" min="1" max="100" required>
                        </div>
                        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                    </form>
                </div>
            </div>

            <!-- Subscription Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">إعدادات الاشتراكات</h5>
                </div>
                <div class="card-body">
                    <form id="subscriptionSettingsForm">
                        <div class="mb-4">
                            <h6 class="mb-3">الحدود الشهرية للمحادثات</h6>
                            <div class="mb-3">
                                <label class="form-label">الحد الشهري للمستخدمين المجانيين</label>
                                <input type="number" class="form-control" name="free_monthly_limit" value="<?php echo $config['free_monthly_limit'] ?? 50; ?>" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الحد الشهري للعضو الفضي</label>
                                <input type="number" class="form-control" name="silver_monthly_limit" value="<?php echo $config['silver_monthly_limit'] ?? 100; ?>" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الحد الشهري للعضو الذهبي</label>
                                <input type="number" class="form-control" name="gold_monthly_limit" value="<?php echo $config['gold_monthly_limit'] ?? 999999; ?>" min="1" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="mb-3">الحدود الشهرية للأسئلة</h6>
                            <div class="mb-3">
                                <label class="form-label">الحد الشهري للمستخدمين المجانيين</label>
                                <input type="number" class="form-control" name="free_question_limit" value="<?php echo $config['free_question_limit'] ?? 500; ?>" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الحد الشهري للعضو الفضي</label>
                                <input type="number" class="form-control" name="silver_question_limit" value="<?php echo $config['silver_question_limit'] ?? 2000; ?>" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الحد الشهري للعضو الذهبي</label>
                                <input type="number" class="form-control" name="gold_question_limit" value="<?php echo $config['gold_question_limit'] ?? 999999; ?>" min="1" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="mb-3">أسعار الاشتراكات</h6>
                            <div class="mb-3">
                                <label class="form-label">سعر العضوية الفضية (بالدولار)</label>
                                <input type="number" class="form-control" name="silver_price" value="<?php echo $config['silver_price'] ?? 9.99; ?>" min="0" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">سعر العضوية الذهبية (بالدولار)</label>
                                <input type="number" class="form-control" name="gold_price" value="<?php echo $config['gold_price'] ?? 19.99; ?>" min="0" step="0.01" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                    </form>
                </div>
            </div>

            <!-- PayPal Settings -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">إعدادات PayPal</h5>
                </div>
                <div class="card-body">
                    <form id="paypalSettingsForm">
                        <div class="mb-3">
                            <label class="form-label">معرف العميل PayPal</label>
                            <input type="text" class="form-control" name="paypal_client_id" value="<?php echo $config['paypal_client_id'] ?? ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">السر الخاص بـ PayPal</label>
                            <input type="password" class="form-control" name="paypal_secret" value="<?php echo $config['paypal_secret'] ?? ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">وضع PayPal</label>
                            <select class="form-select" name="paypal_mode" required>
                                <option value="sandbox" <?php echo ($config['paypal_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : ''; ?>>بيئة الاختبار</option>
                                <option value="live" <?php echo ($config['paypal_mode'] ?? 'sandbox') === 'live' ? 'selected' : ''; ?>>بيئة الإنتاج</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to show alert
        function showAlert(type, message) {
            const alert = document.getElementById(type + 'Alert');
            alert.textContent = message;
            alert.style.display = 'block';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        // Handle Application Settings Form
        document.getElementById('appSettingsForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const settings = {
                environment: formData.get('environment'),
                debug: formData.get('debug') === 'on',
                conversations_per_page: parseInt(formData.get('conversations_per_page'))
            };

            try {
                const response = await fetch('<?php echo getFullUrlPath("api/admin/settings.php"); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        type: 'app',
                        settings: settings
                    })
                });

                const data = await response.json();
                if (data.success) {
                    showAlert('success', 'تم تحديث إعدادات التطبيق بنجاح');
                } else {
                    showAlert('error', data.error || 'حدث خطأ أثناء تحديث الإعدادات');
                }
            } catch (error) {
                showAlert('error', 'حدث خطأ أثناء تحديث الإعدادات');
            }
        });

        // Handle Subscription Settings Form
        document.getElementById('subscriptionSettingsForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const settings = {
                free_monthly_limit: parseInt(formData.get('free_monthly_limit')),
                silver_monthly_limit: parseInt(formData.get('silver_monthly_limit')),
                gold_monthly_limit: parseInt(formData.get('gold_monthly_limit')),
                free_question_limit: parseInt(formData.get('free_question_limit')),
                silver_question_limit: parseInt(formData.get('silver_question_limit')),
                gold_question_limit: parseInt(formData.get('gold_question_limit')),
                silver_price: parseFloat(formData.get('silver_price')),
                gold_price: parseFloat(formData.get('gold_price'))
            };

            try {
                const response = await fetch('<?php echo getFullUrlPath("api/admin/settings.php"); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        type: 'subscription',
                        settings: settings
                    })
                });

                const data = await response.json();
                if (data.success) {
                    showAlert('success', 'تم تحديث إعدادات الاشتراكات بنجاح');
                } else {
                    showAlert('error', data.error || 'حدث خطأ أثناء تحديث الإعدادات');
                }
            } catch (error) {
                showAlert('error', 'حدث خطأ أثناء تحديث الإعدادات');
            }
        });

        // Handle PayPal Settings Form
        document.getElementById('paypalSettingsForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const settings = {
                paypal_client_id: formData.get('paypal_client_id'),
                paypal_secret: formData.get('paypal_secret'),
                paypal_mode: formData.get('paypal_mode')
            };

            try {
                const response = await fetch('<?php echo getFullUrlPath("api/admin/settings.php"); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        type: 'paypal',
                        settings: settings
                    })
                });

                const data = await response.json();
                if (data.success) {
                    showAlert('success', 'تم تحديث إعدادات PayPal بنجاح');
                } else {
                    showAlert('error', data.error || 'حدث خطأ أثناء تحديث الإعدادات');
                }
            } catch (error) {
                showAlert('error', 'حدث خطأ أثناء تحديث الإعدادات');
            }
        });
    </script>
</body>
</html> 