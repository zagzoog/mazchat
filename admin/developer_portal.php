<?php
session_start();
require_once '../app/utils/ResponseCompressor.php';
require_once '../db_config.php';
require_once '../app/utils/Logger.php';
require_once '../app/models/Model.php';
require_once '../app/models/User.php';
require_once '../app/models/ApiKey.php';
require_once __DIR__ . '/../path_config.php';

// Define ADMIN_PANEL constant for navbar access
define('ADMIN_PANEL', true);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: <?php echo getFullUrlPath("login.php"); ?>');
    exit;
}

// Check if user is admin
$user = new User();
$userData = $user->findById($_SESSION['user_id']);

if (!$userData || $userData['role'] !== 'admin') {
    header('Location: <?php echo getFullUrlPath("index.php"); ?>');
    exit;
}

// Get API keys
$apiKeyModel = new ApiKey();
$apiKeys = $apiKeyModel->getByUserId($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بوابة المطورين</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .dev-portal-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .api-key {
            font-family: monospace;
            background-color: #f8f9fa;
            padding: 0.5rem;
            border-radius: 0.25rem;
            word-break: break-all;
        }
        .copy-button {
            cursor: pointer;
        }
        .alert {
            display: none;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../app/views/components/navbar.php'; ?>

    <div class="container mt-4">
        <div class="dev-portal-container">
            <h2 class="mb-4">بوابة المطورين</h2>
            
            <!-- Success/Error Alert -->
            <div class="alert alert-success" role="alert" id="successAlert">
                تم إنشاء مفتاح API بنجاح
            </div>
            <div class="alert alert-danger" role="alert" id="errorAlert">
                حدث خطأ أثناء إنشاء مفتاح API
            </div>

            <!-- Create API Key Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">إنشاء مفتاح API جديد</h5>
                </div>
                <div class="card-body">
                    <form id="createApiKeyForm">
                        <div class="mb-3">
                            <label class="form-label">اسم المفتاح</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">إنشاء مفتاح API</button>
                    </form>
                </div>
            </div>

            <!-- API Keys List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">مفاتيح API الحالية</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>المفتاح</th>
                                    <th>الحالة</th>
                                    <th>آخر استخدام</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="apiKeysList">
                                <?php foreach ($apiKeys as $key): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($key['name']); ?></td>
                                    <td>
                                        <div class="api-key">
                                            <?php echo htmlspecialchars($key['api_key']); ?>
                                            <i class="fas fa-copy ms-2 copy-button" onclick="copyApiKey('<?php echo htmlspecialchars($key['api_key']); ?>')"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $key['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $key['is_active'] ? 'نشط' : 'غير نشط'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $key['last_used_at'] ? date('Y-m-d H:i:s', strtotime($key['last_used_at'])) : 'لم يستخدم بعد'; ?></td>
                                    <td>
                                        <button class="btn btn-sm <?php echo $key['is_active'] ? 'btn-danger' : 'btn-success'; ?>"
                                                onclick="toggleApiKey('<?php echo $key['id']; ?>', <?php echo $key['is_active'] ? 'false' : 'true'; ?>)">
                                            <?php echo $key['is_active'] ? 'تعطيل' : 'تفعيل'; ?>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Documentation Section -->
    <div class="container mt-4">
        <div class="dev-portal-container">
            <h2 class="mb-4">توثيق API</h2>
            
            <!-- Authentication -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">المصادقة</h5>
                </div>
                <div class="card-body">
                    <p>جميع طلبات API تتطلب مصادقة باستخدام مفتاح API. يجب إرسال المفتاح في رأس الطلب:</p>
                    <pre class="bg-light p-3 rounded"><code>Authorization: Bearer YOUR_API_KEY</code></pre>
                    <div class="mt-3">
                        <a href="<?php echo getFullUrlPath('docs/developer_guide.md'); ?>" class="btn btn-info me-2" target="_blank">
                            <i class="fas fa-book me-2"></i>عرض توثيق Swagger الكامل
                        </a>
                    </div>
                </div>
            </div>

            <!-- Available Endpoints -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">نقاط النهاية المتاحة</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>النقطة النهائية</th>
                                    <th>الطريقة</th>
                                    <th>الوصف</th>
                                    <th>المعاملات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>/api/user/profile</code></td>
                                    <td>GET</td>
                                    <td>الحصول على معلومات الملف الشخصي</td>
                                    <td>لا يوجد</td>
                                </tr>
                                <tr>
                                    <td><code>/api/user/profile</code></td>
                                    <td>POST</td>
                                    <td>تحديث معلومات الملف الشخصي</td>
                                    <td>
                                        <ul class="mb-0">
                                            <li>username (اختياري)</li>
                                            <li>email (اختياري)</li>
                                            <li>current_password (مطلوب لتغيير كلمة المرور)</li>
                                            <li>new_password (مطلوب لتغيير كلمة المرور)</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>/api/chat/messages</code></td>
                                    <td>GET</td>
                                    <td>الحصول على رسائل المحادثة</td>
                                    <td>
                                        <ul class="mb-0">
                                            <li>chat_id (مطلوب)</li>
                                            <li>page (اختياري، الافتراضي: 1)</li>
                                            <li>limit (اختياري، الافتراضي: 50)</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>/api/chat/send</code></td>
                                    <td>POST</td>
                                    <td>إرسال رسالة جديدة</td>
                                    <td>
                                        <ul class="mb-0">
                                            <li>chat_id (مطلوب)</li>
                                            <li>message (مطلوب)</li>
                                            <li>type (اختياري، الافتراضي: text)</li>
                                        </ul>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Example Usage -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">مثال على الاستخدام</h5>
                </div>
                <div class="card-body">
                    <h6>مثال على طلب GET:</h6>
                    <pre class="bg-light p-3 rounded"><code>curl -X GET \
  'http://your-domain.com/chat/api/user/profile' \
  -H 'Authorization: Bearer YOUR_API_KEY'</code></pre>

                    <h6 class="mt-4">مثال على طلب POST:</h6>
                    <pre class="bg-light p-3 rounded"><code>curl -X POST \
  'http://your-domain.com/chat/api/chat/send' \
  -H 'Authorization: Bearer YOUR_API_KEY' \
  -H 'Content-Type: application/json' \
  -d '{
    "chat_id": "123",
    "message": "مرحباً بالعالم",
    "type": "text"
  }'</code></pre>

                    <h6 class="mt-4">مثال على الاستجابة:</h6>
                    <pre class="bg-light p-3 rounded"><code>{
  "success": true,
  "data": {
    "message_id": "456",
    "chat_id": "123",
    "message": "مرحباً بالعالم",
    "type": "text",
    "created_at": "2024-03-20 10:30:00"
  }
}</code></pre>
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

        // Function to copy API key
        function copyApiKey(key) {
            navigator.clipboard.writeText(key).then(() => {
                showAlert('success', 'تم نسخ مفتاح API');
            }).catch(() => {
                showAlert('error', 'فشل نسخ مفتاح API');
            });
        }

        // Function to toggle API key status
        async function toggleApiKey(keyId, newStatus) {
            try {
                const response = await fetch('<?php echo getFullUrlPath("api/developer/keys.php"); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'toggle',
                        key_id: keyId,
                        is_active: newStatus
                    })
                });

                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    showAlert('error', data.error || 'حدث خطأ أثناء تحديث حالة المفتاح');
                }
            } catch (error) {
                showAlert('error', 'حدث خطأ أثناء تحديث حالة المفتاح');
            }
        }

        // Handle Create API Key Form
        document.getElementById('createApiKeyForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('<?php echo getFullUrlPath("api/developer/keys.php"); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'create',
                        name: formData.get('name'),
                        description: formData.get('description')
                    })
                });

                const data = await response.json();
                if (data.success) {
                    showAlert('success', 'تم إنشاء مفتاح API بنجاح');
                    location.reload();
                } else {
                    showAlert('error', data.error || 'حدث خطأ أثناء إنشاء المفتاح');
                }
            } catch (error) {
                showAlert('error', 'حدث خطأ أثناء إنشاء المفتاح');
            }
        });
    </script>
</body>
</html> 