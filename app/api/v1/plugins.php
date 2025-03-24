<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 3) . '/db_config.php';
require_once dirname(__DIR__, 3) . '/app/plugins/PluginManager.php';
require_once __DIR__ . '/ApiController.php';

class PluginsController extends ApiController {
    private $pluginManager;

    public function __construct() {
        parent::__construct();
        $this->pluginManager = PluginManager::getInstance();
    }

    public function getPlugins() {
        if (!$this->requireAuth()) {
            return;
        }

        $activePlugins = $this->pluginManager->getActivePlugins();
        $allPlugins = $this->pluginManager->getAllPlugins();

        $plugins = [];
        foreach ($allPlugins as $plugin) {
            $plugins[] = [
                'name' => $plugin->getName(),
                'version' => $plugin->getVersion(),
                'description' => $plugin->getDescription(),
                'author' => $plugin->getAuthor(),
                'is_active' => isset($activePlugins[$plugin->getName()])
            ];
        }

        $this->sendResponse($plugins);
    }

    public function uploadPlugin() {
        if (!$this->requireAdmin()) {
            return;
        }

        error_log("Starting plugin upload process");
        error_log("Files received: " . print_r($_FILES, true));

        if (!isset($_FILES['plugin_file'])) {
            error_log("No plugin file found in request");
            $this->sendError('No plugin file uploaded', 400);
        }

        $file = $_FILES['plugin_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log("File upload error: " . $file['error']);
            $this->sendError('File upload failed', 400);
        }

        // Check file extension instead of MIME type
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        error_log("File extension: " . $fileExtension);
        if ($fileExtension !== 'zip') {
            error_log("Invalid file extension");
            $this->sendError('Only ZIP files are allowed', 400);
        }

        $tempDir = sys_get_temp_dir() . '/' . uniqid('plugin_');
        error_log("Creating temporary directory: " . $tempDir);
        if (!mkdir($tempDir)) {
            error_log("Failed to create temporary directory");
            $this->sendError('Failed to create temporary directory', 500);
        }

        $zip = new ZipArchive();
        error_log("Opening ZIP file: " . $file['tmp_name']);
        $openResult = $zip->open($file['tmp_name']);
        if ($openResult !== true) {
            error_log("Failed to open ZIP file. Error code: " . $openResult);
            $this->sendError('Invalid ZIP file', 400);
        }

        error_log("Extracting ZIP file to: " . $tempDir);
        if (!$zip->extractTo($tempDir)) {
            error_log("Failed to extract ZIP file");
            $this->sendError('Failed to extract ZIP file', 500);
        }
        $zip->close();

        // Validate plugin structure
        $pluginFiles = scandir($tempDir);
        error_log("Files in temp directory: " . print_r($pluginFiles, true));
        
        $mainFile = null;
        foreach ($pluginFiles as $file) {
            error_log("Checking file/directory: " . $file);
            if ($file !== '.' && $file !== '..' && is_dir($tempDir . '/' . $file)) {
                $mainFile = $file . '/' . $file . '.php';
                error_log("Found potential main file: " . $mainFile);
                break;
            }
        }

        if (!$mainFile) {
            error_log("No plugin directory found");
            $this->sendError('Invalid plugin structure', 400);
        }

        $mainFilePath = $tempDir . '/' . $mainFile;
        error_log("Checking main file: " . $mainFilePath);
        if (!file_exists($mainFilePath)) {
            error_log("Main plugin file not found at: " . $mainFilePath);
            $this->sendError('Invalid plugin structure', 400);
        }

        $pluginName = basename($mainFile, '.php');
        $pluginDir = dirname(__DIR__, 2) . '/plugins/' . $pluginName;
        error_log("Plugin name: " . $pluginName);
        error_log("Target plugin directory: " . $pluginDir);

        // Check if plugin exists in database
        try {
            $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $db->prepare("SELECT id FROM plugins WHERE name = ?");
            $stmt->execute([$pluginName]);
            if ($stmt->fetch()) {
                error_log("Deleting existing plugin from database: " . $pluginName);
                $stmt = $db->prepare("DELETE FROM plugins WHERE name = ?");
                $stmt->execute([$pluginName]);
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $this->sendError('Database error occurred', 500);
            return;
        }

        // Remove existing plugin directory if it exists
        if (is_dir($pluginDir)) {
            error_log("Removing existing plugin directory: " . $pluginDir);
            array_map('unlink', glob("$pluginDir/*.*"));
            array_map('rmdir', glob("$pluginDir/*"));
            rmdir($pluginDir);
        }

        // Move plugin to plugins directory
        $sourceDir = $tempDir . '/' . dirname($mainFile);
        error_log("Moving plugin from " . $sourceDir . " to " . $pluginDir);
        if (!rename($sourceDir, $pluginDir)) {
            error_log("Failed to move plugin directory");
            $this->sendError('Failed to move plugin directory', 500);
        }

        // Set correct permissions
        error_log("Setting file permissions");
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($pluginDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                chmod($item->getPathname(), 0755);
            } else {
                chmod($item->getPathname(), 0644);
            }
        }

        // Clean up
        error_log("Cleaning up temporary directory");
        array_map('unlink', glob("$tempDir/*.*"));
        rmdir($tempDir);

        // Reload plugins
        error_log("Reloading plugins");
        $this->pluginManager = PluginManager::getInstance();

        error_log("Plugin upload completed successfully");
        $this->sendResponse(null, 'Plugin uploaded successfully');
    }

    public function activatePlugin() {
        if (!$this->requireAuth()) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['name'])) {
            $this->sendError('Plugin name is required', 400);
            return;
        }

        try {
            if ($this->pluginManager->activatePlugin($data['name'])) {
                $this->sendResponse(null, 'Plugin activated successfully');
            } else {
                $this->sendError('Failed to activate plugin', 500);
            }
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }

    public function deactivatePlugin() {
        if (!$this->requireAuth()) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['name'])) {
            $this->sendError('Plugin name is required', 400);
            return;
        }

        try {
            if ($this->pluginManager->deactivatePlugin($data['name'])) {
                $this->sendResponse(null, 'Plugin deactivated successfully');
            } else {
                $this->sendError('Failed to deactivate plugin', 500);
            }
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }

    public function getPluginSettings() {
        if (!$this->requireAdmin()) {
            return;
        }

        $pluginName = $_GET['name'] ?? null;
        if (!$pluginName) {
            $this->sendError('Plugin name is required', 400);
            return;
        }

        try {
            $plugin = $this->pluginManager->getPlugin($pluginName);
            if (!$plugin) {
                $this->sendResponse(null);
                return;
            }
            $settings = $plugin->getConfig();
            $this->sendResponse($settings);
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function updatePluginSettings() {
        if (!$this->requireAdmin()) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        $this->validateInput($data, [
            'name' => 'required',
            'settings' => 'required'
        ]);

        try {
            $plugin = $this->pluginManager->getPlugin($data['name']);
            $plugin->updateConfig($data['settings']);
            $this->sendResponse(null, 'Plugin settings updated successfully');
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }
}

// Route handling
$controller = new PluginsController();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['name'])) {
            $controller->getPluginSettings();
        } else {
            $controller->getPlugins();
        }
        break;
    case 'POST':
        if (isset($_FILES['plugin_file'])) {
            $controller->uploadPlugin();
        } else {
            $data = json_decode(file_get_contents('php://input'), true);
            if (isset($data['action'])) {
                switch ($data['action']) {
                    case 'activate':
                        $controller->activatePlugin();
                        break;
                    case 'deactivate':
                        $controller->deactivatePlugin();
                        break;
                    case 'update_settings':
                        $controller->updatePluginSettings();
                        break;
                    default:
                        $controller->sendError('Invalid action', 400);
                }
            } else {
                $controller->sendError('Action is required', 400);
            }
        }
        break;
    default:
        $controller->sendError('Method not allowed', 405);
} 