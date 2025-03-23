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

// Load configuration
$config = require_once 'config.php';

// Get the raw POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (!isset($data['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No message provided'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check question limit before processing
require_once 'app/models/Membership.php';
$membership = new Membership();

if (!$membership->checkQuestionLimit($_SESSION['user_id'])) {
    error_log("User " . $_SESSION['user_id'] . " has reached their monthly question limit");
    http_response_code(403);
    echo json_encode(['error' => 'You have reached your monthly question limit. Please upgrade your membership to continue.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$message = sanitize_utf8($data['message']);

// Use the existing session ID or create a new one if it doesn't exist
if (!isset($_SESSION['chat_session_id'])) {
    $_SESSION['chat_session_id'] = uniqid() . time();
}
$sessionId = $_SESSION['chat_session_id'];

// Prepare the request data
$requestData = [
    'message' => $message,
    'sessionId' => $sessionId
];

// Debug logging
debug_log("=== New Request Debug Log ===");
debug_log("Request Data", $requestData);
debug_log("Webhook URL", $config['webhook_url']);

// Initialize cURL session
$ch = curl_init($config['webhook_url']);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData, JSON_UNESCAPED_UNICODE));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json; charset=utf-8',
    'Accept: application/json; charset=utf-8'
]);

// SSL Verification settings
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

// Timeout settings
curl_setopt($ch, CURLOPT_TIMEOUT, 300);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Debug logging for response
debug_log("=== Response Debug Info ===");
debug_log("HTTP Code", $httpCode);
debug_log("Response Length", strlen($response));
debug_log("Response Type", gettype($response));
debug_log("First 1000 chars", substr($response, 0, 1000));
debug_log("Last 1000 chars", substr($response, -1000));
debug_log("cURL Info", curl_getinfo($ch));

// Check for errors
if (curl_errno($ch)) {
    debug_log("cURL Error", curl_error($ch));
    debug_log("cURL Error Number", curl_errno($ch));
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to connect to server: ' . curl_error($ch),
        'details' => 'Error number: ' . curl_errno($ch)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

curl_close($ch);

// Handle the response
if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode([
        'error' => 'Server returned an error with code: ' . $httpCode,
        'response' => $response
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Try to parse as JSON first
$responseData = json_decode($response, true);
$jsonError = json_last_error();

// Debug logging for JSON parsing
debug_log("=== JSON Parsing Debug ===");
debug_log("JSON Parse Error", $jsonError);
debug_log("JSON Parse Error Message", json_last_error_msg());
debug_log("Response Data Type", gettype($responseData));
if (is_array($responseData)) {
    debug_log("Response Data Keys", array_keys($responseData));
}

// If JSON parsing failed, treat the response as a plain string
if ($jsonError !== JSON_ERROR_NONE) {
    try {
        debug_log("=== String Response Processing ===");
        // Sanitize the response before processing
        $response = sanitize_utf8($response);
        
        // The response is a plain string, format it as markdown
        $formattedResponse = formatAsMarkdown($response);
        debug_log("Formatted Response Length", strlen($formattedResponse));
        debug_log("Formatted Response Type", gettype($formattedResponse));
        
        // Validate the response before sending
        if (empty($formattedResponse)) {
            throw new Exception("Formatted response is empty");
        }
        
        // Send the response back to the client
        $jsonResponse = json_encode(['response' => $formattedResponse], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonResponse === false) {
            throw new Exception("Failed to encode response as JSON: " . json_last_error_msg());
        }
        
        debug_log("Final JSON Response Length", strlen($jsonResponse));
        
        // Store the AI response in the database
        require_once 'db_config.php';
        require_once 'app/models/Message.php';
        
        try {
            $messageModel = new Message();
            $messageModel->create(
                $data['conversation_id'],
                $_SESSION['user_id'],
                $formattedResponse,
                'assistant'
            );
        } catch (Exception $e) {
            error_log("Error storing AI response: " . $e->getMessage());
            // Don't throw the error to the client, just log it
        }
        
        echo $jsonResponse;
    } catch (Exception $e) {
        debug_log("Error processing string response", $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Error processing response: ' . $e->getMessage(),
            'details' => 'Response length: ' . strlen($response)
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// If we got here, we have valid JSON data
// Extract the response content
$responseContent = '';
if (is_string($responseData)) {
    $responseContent = formatAsMarkdown(sanitize_utf8($responseData));
} elseif (is_array($responseData)) {
    // Try to find the response in various possible formats
    if (isset($responseData['response'])) {
        $responseContent = formatAsMarkdown(sanitize_utf8($responseData['response']));
    } elseif (isset($responseData['message'])) {
        $responseContent = formatAsMarkdown(sanitize_utf8($responseData['message']));
    } elseif (isset($responseData['content'])) {
        $responseContent = formatAsMarkdown(sanitize_utf8($responseData['content']));
    } elseif (isset($responseData['text'])) {
        $responseContent = formatAsMarkdown(sanitize_utf8($responseData['text']));
    } elseif (isset($responseData['result'])) {
        $responseContent = formatAsMarkdown(sanitize_utf8($responseData['result']));
    } elseif (isset($responseData['output'])) {
        $responseContent = formatAsMarkdown(sanitize_utf8($responseData['output']));
    } elseif (isset($responseData['data'])) {
        $responseContent = formatAsMarkdown(sanitize_utf8($responseData['data']));
    } else {
        // If no specific field is found, try to get the first string value
        foreach ($responseData as $key => $value) {
            if (is_string($value)) {
                $responseContent = formatAsMarkdown(sanitize_utf8($value));
                break;
            }
        }
        // If still no content, encode the entire response
        if (empty($responseContent)) {
            $responseContent = formatAsMarkdown(sanitize_utf8(json_encode($responseData, JSON_UNESCAPED_UNICODE)));
        }
    }
}

if (empty($responseContent)) {
    debug_log("Empty response content from N8N");
    debug_log("Raw response data", $responseData);
    http_response_code(500);
    echo json_encode([
        'error' => 'Invalid response from server',
        'raw_response' => $response,
        'parsed_data' => $responseData
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Debug logging for final response
debug_log("=== Final Response Debug ===");
debug_log("Response Content Length", strlen($responseContent));
debug_log("Response Content Type", gettype($responseContent));

try {
    // Validate the response before sending
    if (empty($responseContent)) {
        throw new Exception("Response content is empty");
    }
    
    // Send the response back to the client
    $jsonResponse = json_encode(['response' => $responseContent], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($jsonResponse === false) {
        throw new Exception("Failed to encode response as JSON: " . json_last_error_msg());
    }
    
    debug_log("Final JSON Response Length", strlen($jsonResponse));
    
    // Store the AI response in the database
    require_once 'db_config.php';
    require_once 'app/models/Message.php';
    
    try {
        $messageModel = new Message();
        $messageModel->create(
            $data['conversation_id'],
            $_SESSION['user_id'],
            $responseContent,
            'assistant'
        );
    } catch (Exception $e) {
        error_log("Error storing AI response: " . $e->getMessage());
        // Don't throw the error to the client, just log it
    }
    
    echo $jsonResponse;
} catch (Exception $e) {
    debug_log("Error sending response", $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Error processing response: ' . $e->getMessage(),
        'details' => 'Response length: ' . strlen($responseContent)
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