<?php
if (!defined('ADMIN_PANEL')) {
    die('Direct access not permitted');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat App API Documentation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4/swagger-ui.css">
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
            background-color: #212529 !important;
        }
        .swagger-ui .topbar .download-url-wrapper .select-label select {
            border-color: #212529;
        }
        .custom-navbar {
            background-color: #212529;
            padding: 1rem;
            color: white;
        }
        .custom-navbar a {
            color: white;
            text-decoration: none;
        }
        .custom-navbar a:hover {
            color: #adb5bd;
        }
    </style>
</head>
<body>
    <div class="custom-navbar d-flex justify-content-between align-items-center px-4">
        <div>
            <h4 class="mb-0">
                <a href="/chat">
                    <i class="fas fa-chevron-left"></i> Back to Chat App
                </a>
            </h4>
        </div>
        <div>
            <a href="/chat/admin/developer_portal.php" class="me-3">
                <i class="fas fa-code"></i> Developer Portal
            </a>
            <a href="/chat/admin/plugin_marketplace.php">
                <i class="fas fa-store"></i> Marketplace
            </a>
        </div>
    </div>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@4/swagger-ui-bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        window.onload = function() {
            SwaggerUIBundle({
                url: "swagger.json",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.SwaggerUIStandalonePreset
                ],
                layout: "BaseLayout",
                docExpansion: "list",
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 1,
                plugins: [
                    function(system) {
                        return {
                            components: {
                                authorizeBtn: () => null,
                                authorizeOperationBtn: () => null
                            }
                        };
                    }
                ]
            });
        };
    </script>
</body>
</html> 