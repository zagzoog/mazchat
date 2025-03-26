<?php
session_start();
require_once __DIR__ . '/../../db_config.php';
require_once __DIR__ . '/../../app/utils/Logger.php';
require_once __DIR__ . '/../../app/models/Model.php';
require_once __DIR__ . '/../../app/models/User.php';

// Define ADMIN_PANEL constant for navbar access
define('ADMIN_PANEL', true);

try {
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

    // Read the Swagger JSON file
    $swaggerJson = file_get_contents(__DIR__ . '/swagger.json');
    if ($swaggerJson === false) {
        throw new Exception('Failed to load Swagger documentation');
    }
} catch (Exception $e) {
    Logger::error('API docs error', ['message' => $e->getMessage()]);
    http_response_code(500);
    echo "Internal Server Error: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - Chat Application</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui.css">
    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-standalone-preset.js"></script>
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }
        body {
            margin: 0;
            background: #fafafa;
        }
        .swagger-ui .topbar {
            display: none;
        }
        .swagger-ui .info .title {
            color: #333;
        }
        .swagger-ui .info .description {
            color: #666;
        }
        .swagger-ui .opblock.opblock-post {
            background: rgba(73, 144, 226, 0.1);
            border-color: #4990e2;
        }
        .swagger-ui .opblock.opblock-get {
            background: rgba(97, 175, 254, 0.1);
            border-color: #61affe;
        }
        .swagger-ui .opblock.opblock-put {
            background: rgba(252, 161, 48, 0.1);
            border-color: #fca130;
        }
        .swagger-ui .opblock.opblock-delete {
            background: rgba(249, 62, 62, 0.1);
            border-color: #f93e3e;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                spec: <?php echo $swaggerJson; ?>,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                docExpansion: 'list',
                defaultModelsExpandDepth: -1,
                defaultModelExpandDepth: 1,
                defaultModelRendering: 'model',
                displayRequestDuration: true,
                filter: true,
                tryItOutEnabled: true,
                requestSnippetsEnabled: true,
                requestSnippetsGenerators: {
                    curl_bash: {
                        template: {
                            method: '{{method}}',
                            url: '{{url}}',
                            headers: '{{headers}}',
                            body: '{{body}}'
                        },
                        curlCmd: ['curl -X {{method}}', '{{url}}', '{{headers}}', '{{body}}']
                    }
                }
            });
            window.ui = ui;
        };
    </script>
</body>
</html> 