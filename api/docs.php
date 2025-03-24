<?php
session_start();
require_once __DIR__ . '/../app/utils/ResponseCompressor.php';
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../app/utils/Logger.php';
require_once __DIR__ . '/../app/models/Model.php';
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

// Define the OpenAPI/Swagger specification
$swagger = [
    'openapi' => '3.0.0',
    'info' => [
        'title' => 'Chat App API',
        'version' => '1.0.0',
        'description' => 'API documentation for the Chat Application'
    ],
    'servers' => [
        [
            'url' => 'http://localhost/chat',
            'description' => 'Local development server'
        ]
    ],
    'components' => [
        'securitySchemes' => [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT'
            ]
        ]
    ],
    'security' => [
        ['bearerAuth' => []]
    ],
    'paths' => [
        '/api/auth' => [
            'post' => [
                'summary' => 'تسجيل الدخول',
                'description' => 'تسجيل دخول المستخدم والحصول على رمز API',
                'tags' => ['المصادقة'],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['username', 'password'],
                                'properties' => [
                                    'username' => ['type' => 'string'],
                                    'password' => ['type' => 'string', 'format' => 'password']
                                ]
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'تم تسجيل الدخول بنجاح',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'success' => ['type' => 'boolean'],
                                        'data' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'token' => ['type' => 'string'],
                                                'user' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'id' => ['type' => 'string'],
                                                        'username' => ['type' => 'string'],
                                                        'email' => ['type' => 'string'],
                                                        'role' => ['type' => 'string'],
                                                        'membership_type' => ['type' => 'string']
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '/api/developer/keys' => [
            'get' => [
                'summary' => 'الحصول على مفاتيح API',
                'description' => 'الحصول على قائمة مفاتيح API للمستخدم الحالي',
                'tags' => ['المطور'],
                'responses' => [
                    '200' => [
                        'description' => 'قائمة مفاتيح API',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'success' => ['type' => 'boolean'],
                                        'data' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'id' => ['type' => 'string'],
                                                    'name' => ['type' => 'string'],
                                                    'api_key' => ['type' => 'string'],
                                                    'status' => ['type' => 'string', 'enum' => ['active', 'inactive', 'revoked']],
                                                    'created_at' => ['type' => 'string', 'format' => 'date-time']
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'post' => [
                'summary' => 'إنشاء مفتاح API جديد',
                'description' => 'إنشاء مفتاح API جديد للمستخدم الحالي',
                'tags' => ['المطور'],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['name'],
                                'properties' => [
                                    'name' => ['type' => 'string'],
                                    'description' => ['type' => 'string']
                                ]
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'success' => ['type' => 'boolean'],
                                        'data' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'message_id' => ['type' => 'string'],
                                                'chat_id' => ['type' => 'string'],
                                                'message' => ['type' => 'string'],
                                                'type' => ['type' => 'string'],
                                                'created_at' => ['type' => 'string']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '/api/marketplace/plugins' => [
            'get' => [
                'summary' => 'الحصول على قائمة الإضافات في السوق',
                'description' => 'الحصول على قائمة الإضافات المتاحة في سوق الإضافات',
                'tags' => ['السوق'],
                'parameters' => [
                    [
                        'name' => 'category',
                        'in' => 'query',
                        'schema' => ['type' => 'string'],
                        'description' => 'تصفية حسب الفئة'
                    ],
                    [
                        'name' => 'sort',
                        'in' => 'query',
                        'schema' => [
                            'type' => 'string',
                            'enum' => ['popular', 'rating', 'newest']
                        ],
                        'description' => 'ترتيب النتائج'
                    ],
                    [
                        'name' => 'official',
                        'in' => 'query',
                        'schema' => ['type' => 'boolean'],
                        'description' => 'تصفية الإضافات الرسمية فقط'
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'قائمة الإضافات',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'success' => ['type' => 'boolean'],
                                        'data' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'id' => ['type' => 'string'],
                                                    'name' => ['type' => 'string'],
                                                    'description' => ['type' => 'string'],
                                                    'version' => ['type' => 'string'],
                                                    'author' => ['type' => 'string'],
                                                    'price' => ['type' => 'number'],
                                                    'rating' => ['type' => 'number'],
                                                    'downloads' => ['type' => 'integer'],
                                                    'is_official' => ['type' => 'boolean']
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '/api/marketplace/plugins/{id}/install' => [
            'post' => [
                'summary' => 'تثبيت إضافة',
                'description' => 'تثبيت إضافة من السوق',
                'tags' => ['السوق'],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'string']
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'تم تثبيت الإضافة بنجاح'
                    ]
                ]
            ]
        ],
        '/api/conversations' => [
            'get' => [
                'summary' => 'الحصول على المحادثات',
                'description' => 'الحصول على قائمة محادثات المستخدم',
                'tags' => ['المحادثات'],
                'parameters' => [
                    [
                        'name' => 'page',
                        'in' => 'query',
                        'schema' => ['type' => 'integer', 'default' => 1]
                    ],
                    [
                        'name' => 'limit',
                        'in' => 'query',
                        'schema' => ['type' => 'integer', 'default' => 20]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'قائمة المحادثات',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'success' => ['type' => 'boolean'],
                                        'data' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'id' => ['type' => 'string'],
                                                    'title' => ['type' => 'string'],
                                                    'last_message' => ['type' => 'string'],
                                                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                                                    'updated_at' => ['type' => 'string', 'format' => 'date-time']
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'post' => [
                'summary' => 'إنشاء محادثة جديدة',
                'description' => 'إنشاء محادثة جديدة',
                'tags' => ['المحادثات'],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['title'],
                                'properties' => [
                                    'title' => ['type' => 'string']
                                ]
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '201' => [
                        'description' => 'تم إنشاء المحادثة بنجاح'
                    ]
                ]
            ]
        ],
        '/api/payments/subscriptions' => [
            'get' => [
                'summary' => 'الحصول على الاشتراكات',
                'description' => 'الحصول على قائمة اشتراكات المستخدم',
                'tags' => ['المدفوعات'],
                'responses' => [
                    '200' => [
                        'description' => 'قائمة الاشتراكات',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'success' => ['type' => 'boolean'],
                                        'data' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'id' => ['type' => 'string'],
                                                    'plan' => ['type' => 'string'],
                                                    'status' => ['type' => 'string'],
                                                    'start_date' => ['type' => 'string', 'format' => 'date-time'],
                                                    'end_date' => ['type' => 'string', 'format' => 'date-time']
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'post' => [
                'summary' => 'إنشاء اشتراك جديد',
                'description' => 'إنشاء اشتراك جديد للمستخدم',
                'tags' => ['المدفوعات'],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['plan_id', 'payment_method'],
                                'properties' => [
                                    'plan_id' => ['type' => 'string'],
                                    'payment_method' => ['type' => 'string']
                                ]
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '201' => [
                        'description' => 'تم إنشاء الاشتراك بنجاح'
                    ]
                ]
            ]
        ],
        '/api/dashboard/stats' => [
            'get' => [
                'summary' => 'إحصائيات لوحة التحكم',
                'description' => 'الحصول على إحصائيات لوحة التحكم',
                'tags' => ['لوحة التحكم'],
                'responses' => [
                    '200' => [
                        'description' => 'إحصائيات لوحة التحكم',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'success' => ['type' => 'boolean'],
                                        'data' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'total_conversations' => ['type' => 'integer'],
                                                'total_messages' => ['type' => 'integer'],
                                                'active_plugins' => ['type' => 'integer'],
                                                'subscription_status' => ['type' => 'string'],
                                                'api_usage' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'requests_today' => ['type' => 'integer'],
                                                        'requests_total' => ['type' => 'integer']
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '/api/messages' => [
            'get' => [
                'summary' => 'الحصول على الرسائل',
                'description' => 'الحصول على رسائل محادثة محددة',
                'tags' => ['الرسائل'],
                'parameters' => [
                    [
                        'name' => 'conversation_id',
                        'in' => 'query',
                        'required' => true,
                        'schema' => ['type' => 'string']
                    ],
                    [
                        'name' => 'page',
                        'in' => 'query',
                        'schema' => ['type' => 'integer', 'default' => 1]
                    ],
                    [
                        'name' => 'limit',
                        'in' => 'query',
                        'schema' => ['type' => 'integer', 'default' => 50]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'قائمة الرسائل',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'id' => ['type' => 'string'],
                                            'conversation_id' => ['type' => 'string'],
                                            'content' => ['type' => 'string'],
                                            'is_user' => ['type' => 'boolean'],
                                            'created_at' => ['type' => 'string', 'format' => 'date-time']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'post' => [
                'summary' => 'إرسال رسالة',
                'description' => 'إرسال رسالة جديدة في محادثة',
                'tags' => ['الرسائل'],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['conversation_id', 'content'],
                                'properties' => [
                                    'conversation_id' => ['type' => 'string'],
                                    'content' => ['type' => 'string'],
                                    'is_user' => ['type' => 'boolean', 'default' => true]
                                ]
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'تم إرسال الرسالة بنجاح'
                    ]
                ]
            ]
        ],
        '/api/plugins' => [
            'get' => [
                'summary' => 'الحصول على الإضافات المثبتة',
                'description' => 'الحصول على قائمة الإضافات المثبتة',
                'tags' => ['الإضافات'],
                'responses' => [
                    '200' => [
                        'description' => 'قائمة الإضافات',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'success' => ['type' => 'boolean'],
                                        'data' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'name' => ['type' => 'string'],
                                                    'version' => ['type' => 'string'],
                                                    'description' => ['type' => 'string'],
                                                    'author' => ['type' => 'string'],
                                                    'is_active' => ['type' => 'boolean']
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'post' => [
                'summary' => 'تفعيل/تعطيل إضافة',
                'description' => 'تفعيل أو تعطيل إضافة محددة',
                'tags' => ['الإضافات'],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['name', 'action'],
                                'properties' => [
                                    'name' => ['type' => 'string'],
                                    'action' => ['type' => 'string', 'enum' => ['activate', 'deactivate']]
                                ]
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'تم تحديث حالة الإضافة بنجاح'
                    ]
                ]
            ]
        ]
    ]
];

// Output the Swagger UI HTML
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>توثيق API - تطبيق الدردشة</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4/swagger-ui.css">
    <script src="https://unpkg.com/swagger-ui-dist@4/swagger-ui-bundle.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body {
            font-family: 'Cairo', sans-serif;
        }
        .swagger-ui .topbar {
            display: none;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script>
        window.onload = function() {
            SwaggerUIBundle({
                spec: <?php echo json_encode($swagger); ?>,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.SwaggerUIStandalonePreset
                ],
                layout: "BaseLayout",
            });
        };
    </script>
</body>
</html> 