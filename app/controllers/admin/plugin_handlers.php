<?php
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/app/plugins/PluginManager.php';

// Ensure user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$pluginManager = PluginManager::getInstance();

// Handle plugin activation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'activate') {
    $data = json_decode(file_get_contents('php://input'), true);
    $pluginName = $data['plugin'] ?? '';
    
    if (empty($pluginName)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Plugin name is required']));
    }
    
    if ($pluginManager->activatePlugin($pluginName)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to activate plugin']);
    }
}

// Handle plugin deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deactivate') {
    $data = json_decode(file_get_contents('php://input'), true);
    $pluginName = $data['plugin'] ?? '';
    
    if (empty($pluginName)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Plugin name is required']));
    }
    
    if ($pluginManager->deactivatePlugin($pluginName)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to deactivate plugin']);
    }
}

// Handle plugin upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['plugin_file'])) {
    $file = $_FILES['plugin_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Upload failed']));
    }
    
    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if ($mimeType !== 'application/zip') {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Invalid file type. Only ZIP files are allowed.']));
    }
    
    // Create temporary directory for extraction
    $tempDir = sys_get_temp_dir() . '/plugin_' . uniqid();
    mkdir($tempDir);
    
    // Extract ZIP file
    $zip = new ZipArchive();
    if ($zip->open($file['tmp_name']) === TRUE) {
        $zip->extractTo($tempDir);
        $zip->close();
        
        // Validate plugin structure
        $pluginFile = $tempDir . '/' . basename($tempDir) . '.php';
        if (!file_exists($pluginFile)) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'Invalid plugin structure']));
        }
        
        // Move plugin to plugins directory
        $pluginDir = dirname(__DIR__, 3) . '/plugins/' . basename($tempDir);
        if (rename($tempDir, $pluginDir)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to install plugin']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to extract plugin']);
    }
    
    // Clean up
    array_map('unlink', glob("$tempDir/*.*"));
    rmdir($tempDir);
}

// Handle plugin settings retrieval
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['plugin'])) {
    $pluginName = $_GET['plugin'];
    $plugin = $pluginManager->getAllPlugins()[$pluginName] ?? null;
    
    if (!$plugin) {
        http_response_code(404);
        die(json_encode(['success' => false, 'message' => 'Plugin not found']));
    }
    
    // Execute the plugin's settings page hook
    ob_start();
    $plugin->executeHook('admin_settings_page');
    $settingsHtml = ob_get_clean();
    
    echo $settingsHtml;
} 