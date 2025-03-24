<?php
// Set PHP configuration for handling large responses
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);
ini_set('post_max_size', '128M');
ini_set('upload_max_filesize', '128M');
ini_set('output_buffering', '4096');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set UTF-8 encoding
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('P');

// Custom logging function
function debug_log($message, $data = null) {
    $logFile = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $logMessage .= "\n" . print_r($data, true);
        } else {
            $logMessage .= "\n" . $data;
        }
    }
    
    $logMessage .= "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Function to sanitize UTF-8 string
function sanitize_utf8($string) {
    // Remove invalid UTF-8 characters
    $string = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]|[\x00-\x7F][\x80-\xBF]+|([\xC0\xC1]|[\xE0-\xFF])[\x80-\xBF]*|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S', '', $string);
    
    // Convert to UTF-8
    $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    
    // Remove any remaining invalid characters
    $string = preg_replace('/[\x00-\x1F\x7F]/u', '', $string);
    
    return $string;
}

// Start output buffering
ob_start();

header('Content-Type: application/json; charset=utf-8');

// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Load configuration
$config = require_once 'config.php';
require_once 'db_config.php';
require_once 'app/models/Membership.php';
require_once 'app/models/Message.php';

// Get the raw POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (!isset($data['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No message provided'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check question limit before processing
$membership = new Membership();
if (!$membership->checkQuestionLimit($_SESSION['user_id'])) {
    error_log("User " . $_SESSION['user_id'] . " has reached their monthly question limit");
    http_response_code(403);
    echo json_encode([
        'error' => 'You have reached your monthly question limit. Please upgrade your membership to continue.',
        'limit_reached' => true,
        'limit_type' => 'questions'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = getDBConnection();
    
    // Get user's selected plugin
    $stmt = $db->prepare("
        SELECT p.id, p.name, p.class_name 
        FROM plugins p 
        JOIN user_plugin_preferences upp ON p.id = upp.plugin_id 
        WHERE upp.user_id = ? AND p.is_active = TRUE
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $plugin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plugin) {
        // If no plugin selected, use the first available one
        $stmt = $db->query("
            SELECT p.id, p.name, p.class_name 
            FROM plugins p 
            WHERE p.is_active = TRUE 
            AND (
                EXISTS (SELECT 1 FROM n8n_webhook_settings n WHERE n.plugin_id = p.id AND n.is_active = TRUE)
                OR EXISTS (SELECT 1 FROM direct_message_settings d WHERE d.plugin_id = p.id AND d.is_active = TRUE)
            )
            LIMIT 1
        ");
        $plugin = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$plugin) {
        throw new Exception('No active message handling plugin available');
    }
    
    // Load and initialize the plugin
    require_once "plugins/{$plugin['name']}/{$plugin['class_name']}.php";
    $pluginInstance = new $plugin['class_name']($plugin['id']);
    
    // Process the message
    $message = [
        'content' => $data['message'],
        'conversation_id' => $data['conversation_id']
    ];
    
    $response = $pluginInstance->processMessage($message);
    
    if ($response === false) {
        throw new Exception('Failed to process message');
    }
    
    // Store the AI response in the database
    $messageModel = new Message();
    $messageModel->create(
        $data['conversation_id'],
        $_SESSION['user_id'],
        $response,
        'assistant'
    );
    
    // Send response back to client
    echo json_encode(['response' => $response], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error processing message: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Error processing message: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// Flush output buffer
ob_end_flush();

/**
 * Format text as markdown
 * @param string $text The text to format
 * @return string Formatted markdown text
 */
function formatAsMarkdown($text) {
    // Debug logging for markdown formatting
    debug_log("=== Markdown Formatting Debug ===");
    debug_log("Input Text Length", strlen($text));
    debug_log("Input Text Type", gettype($text));
    
    // Ensure text is UTF-8 and sanitized
    $text = sanitize_utf8($text);
    
    // First, normalize line endings
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    
    // Convert multiple consecutive newlines to double newlines
    $text = preg_replace("/\n{3,}/", "\n\n", $text);
    
    // Convert single newlines to markdown line breaks (two spaces + newline)
    $text = preg_replace("/\n/", "  \n", $text);
    
    // Convert URLs to markdown links
    $text = preg_replace(
        '/(https?:\/\/[^\s]+)/',
        '[$1]($1)',
        $text
    );
    
    // Convert bullet points
    $text = preg_replace(
        '/^\s*[-•*]\s*(.+)$/m',
        '* $1',
        $text
    );
    
    // Convert numbered lists
    $text = preg_replace(
        '/^\s*\d+\.\s*(.+)$/m',
        '1. $1',
        $text
    );
    
    // Convert bold text (text between **)
    $text = preg_replace(
        '/\*\*(.*?)\*\*/',
        '**$1**',
        $text
    );
    
    // Convert italic text (text between *)
    $text = preg_replace(
        '/\*(.*?)\*/',
        '*$1*',
        $text
    );
    
    // Convert code blocks (text between `)
    $text = preg_replace(
        '/`(.*?)`/',
        '`$1`',
        $text
    );
    
    // Convert headings (lines starting with #)
    $text = preg_replace(
        '/^#\s*(.+)$/m',
        '# $1',
        $text
    );
    
    // Convert blockquotes (lines starting with >)
    $text = preg_replace(
        '/^\s*>\s*(.+)$/m',
        '> $1',
        $text
    );
    
    // Ensure the text ends with a newline
    if (substr($text, -1) !== "\n") {
        $text .= "\n";
    }
    
    debug_log("Output Text Length", strlen($text));
    debug_log("Output Text Type", gettype($text));
    
    return $text;
} 