<?php

// Start output buffering to prevent header issues
ob_start();

// Initialize session if not started
session_write_close(); // Close any existing session
session_start();

// Set up database configuration for command line execution
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

define('DB_HOST', 'localhost');
define('DB_NAME', 'mychat');
define('DB_USER', 'mychat');
define('DB_PASS', 'moha1212');

require_once __DIR__ . '/../app/utils/DatabasePool.php';
require_once __DIR__ . '/../app/api/v1/ApiController.php';
require_once __DIR__ . '/../app/api/v1/messages.php';
require_once __DIR__ . '/../app/plugins/PluginInterface.php';

// Mock plugin for testing
class MockPlugin implements PluginInterface {
    public function getHooks() {
        return [
            'before_send_message' => true,
            'after_send_message' => true
        ];
    }
    
    public function executeHook($hookName, $args = []) {
        $message = $args[0] ?? null;
        
        switch ($hookName) {
            case 'before_send_message':
                if ($message) {
                    return "Processing your message: " . $message['content'];
                }
                return "Processing your message...";
                
            case 'after_send_message':
                if ($message) {
                    return "Here's your response to: " . $message['content'];
                }
                return "Here's your response...";
                
            default:
                return null;
        }
    }
}

class MessageJourneyTest {
    private $db;
    private $pool;
    private $userId;
    private $conversationId;
    private $testInputFile;
    
    public function __construct() {
        // Create a temporary file for php://input simulation
        $this->testInputFile = tempnam(sys_get_temp_dir(), 'test_input_');
        
        $this->pool = DatabasePool::getInstance();
        $this->db = $this->pool->getConnection();
        
        // Create a test user if not exists
        $this->createTestUser();
        
        // Create a test conversation
        $this->createTestConversation();
    }
    
    private function createTestUser() {
        try {
            // Check if test user exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute(['test@example.com']);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                // Create test user
                $stmt = $this->db->prepare("
                    INSERT INTO users (username, email, password)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    'testuser',
                    'test@example.com',
                    password_hash('test123', PASSWORD_DEFAULT)
                ]);
                $this->userId = $this->db->lastInsertId();
            } else {
                $this->userId = $user['id'];
            }
            
            echo "Test user ID: " . $this->userId . "\n";
            
        } catch (Exception $e) {
            echo "Error creating test user: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    private function createTestConversation() {
        try {
            // Create a test conversation
            $stmt = $this->db->prepare("
                INSERT INTO conversations (user_id, title, created_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$this->userId, 'Test Conversation']);
            $this->conversationId = $this->db->lastInsertId();
            
            echo "Test conversation ID: " . $this->conversationId . "\n";
            
            // Create and assign a test plugin
            $this->createTestPlugin();
            
        } catch (Exception $e) {
            echo "Error creating test conversation: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    private function createTestPlugin() {
        try {
            $uniqueId = uniqid();
            // Create test plugin
            $stmt = $this->db->prepare("
                INSERT INTO plugins (name, slug, description, version, is_active, created_at)
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([
                'TestPlugin_' . $uniqueId,
                'test-plugin-' . $uniqueId,
                'A test plugin for message journey testing',
                '1.0.0'
            ]);
            $pluginId = $this->db->lastInsertId();
            
            // Update conversation with plugin
            $stmt = $this->db->prepare("
                UPDATE conversations 
                SET plugin_id = ? 
                WHERE id = ?
            ");
            $stmt->execute([$pluginId, $this->conversationId]);
            
            echo "Test plugin created and assigned to conversation\n";
            
        } catch (Exception $e) {
            echo "Error creating test plugin: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    private function simulateJsonInput($data) {
        // Write JSON data to temporary file
        file_put_contents($this->testInputFile, json_encode($data));
        
        // Create a custom stream wrapper
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', 'TestInputStreamWrapper');
        TestInputStreamWrapper::$testFile = $this->testInputFile;
    }
    
    public function testMessageCreation() {
        echo "Starting message creation test...\n";
        
        try {
            // Create message data
            $messageData = [
                'conversation_id' => $this->conversationId,
                'content' => 'Test message content'
            ];
            
            // Set up request data
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_SERVER['CONTENT_TYPE'] = 'application/json';
            
            // Set up session
            session_write_close(); // Close any existing session
            session_start();
            $_SESSION['user_id'] = $this->userId;
            
            // Simulate JSON input
            $this->simulateJsonInput($messageData);
            
            // Create MessagesController instance in test mode
            $controller = new MessagesController(true);
            
            // Call createMessage method
            ob_start(); // Capture output
            $controller->createMessage();
            $output = ob_get_clean();
            
            // Restore original php stream wrapper
            stream_wrapper_restore('php');
            
            echo "Controller response: " . $output . "\n";
            
            // Verify messages were created
            $stmt = $this->db->prepare("
                SELECT * FROM messages 
                WHERE conversation_id = ? 
                ORDER BY created_at ASC
            ");
            $stmt->execute([$this->conversationId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($messages) >= 3) {
                echo "\nMessages created:\n";
                foreach ($messages as $msg) {
                    echo "Message ID: " . $msg['id'] . "\n";
                    echo "Role: " . $msg['role'] . "\n";
                    echo "Content: " . $msg['content'] . "\n\n";
                }
                
                // Verify message sequence
                $beforeMessage = $messages[0];
                $userMessage = $messages[1];
                $afterMessage = $messages[2];
                
                // Verify before_send_message response
                if ($beforeMessage['role'] === 'assistant' && 
                    strpos($beforeMessage['content'], 'Processing your message') !== false) {
                    echo "✓ Before send message hook response verified\n";
                } else {
                    echo "✗ Before send message hook response not as expected\n";
                }
                
                // Verify user message
                if ($userMessage['role'] === 'user' && 
                    $userMessage['content'] === 'Test message content') {
                    echo "✓ User message verified\n";
                } else {
                    echo "✗ User message not as expected\n";
                }
                
                // Verify after_send_message response
                if ($afterMessage['role'] === 'assistant' && 
                    strpos($afterMessage['content'], "Here's your response") !== false) {
                    echo "✓ After send message hook response verified\n";
                } else {
                    echo "✗ After send message hook response not as expected\n";
                }
                
                return true;
            } else {
                echo "Message creation failed - expected at least 3 messages but found " . count($messages) . "\n";
                return false;
            }
            
        } catch (Exception $e) {
            echo "Error in message creation test: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function testConnectionPool() {
        echo "\nTesting connection pool...\n";
        
        try {
            // Get multiple connections
            $connections = [];
            for ($i = 0; $i < 3; $i++) {
                $connections[] = $this->pool->getConnection();
                echo "Got connection " . ($i + 1) . "\n";
            }
            
            // Release connections
            foreach ($connections as $conn) {
                $this->pool->releaseConnection($conn);
                echo "Released connection\n";
            }
            
            echo "Connection pool test completed successfully\n";
            return true;
            
        } catch (Exception $e) {
            echo "Error in connection pool test: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function cleanup() {
        try {
            // Clean up test data
            $stmt = $this->db->prepare("DELETE FROM messages WHERE conversation_id = ?");
            $stmt->execute([$this->conversationId]);
            
            // Get plugin ID before deleting conversation
            $stmt = $this->db->prepare("SELECT plugin_id FROM conversations WHERE id = ?");
            $stmt->execute([$this->conversationId]);
            $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $this->db->prepare("DELETE FROM conversations WHERE id = ?");
            $stmt->execute([$this->conversationId]);
            
            // Clean up plugin
            if ($conversation && $conversation['plugin_id']) {
                $stmt = $this->db->prepare("DELETE FROM plugins WHERE id = ?");
                $stmt->execute([$conversation['plugin_id']]);
            }
            
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$this->userId]);
            
            // Clean up temporary file
            if (file_exists($this->testInputFile)) {
                unlink($this->testInputFile);
            }
            
            echo "Test data cleaned up successfully\n";
            
        } catch (Exception $e) {
            echo "Error during cleanup: " . $e->getMessage() . "\n";
        }
    }
    
    public function __destruct() {
        if ($this->db && $this->pool) {
            $this->pool->releaseConnection($this->db);
        }
        
        // Clean up temporary file if it exists
        if (file_exists($this->testInputFile)) {
            unlink($this->testInputFile);
        }
        
        // Restore original php stream wrapper if needed
        if (!in_array('php', stream_get_wrappers())) {
            stream_wrapper_restore('php');
        }
    }
}

// Custom stream wrapper for php://input simulation
class TestInputStreamWrapper {
    public static $testFile;
    private $position;
    private $data;
    public $context;
    
    public function stream_open($path, $mode, $options, &$opened_path) {
        $this->data = file_get_contents(self::$testFile);
        $this->position = 0;
        return true;
    }
    
    public function stream_read($count) {
        $ret = substr($this->data, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }
    
    public function stream_eof() {
        return $this->position >= strlen($this->data);
    }
    
    public function stream_stat() {
        return [];
    }
    
    public function stream_seek($offset, $whence) {
        switch ($whence) {
            case SEEK_SET:
                if ($offset < strlen($this->data) && $offset >= 0) {
                    $this->position = $offset;
                    return true;
                }
                return false;
            case SEEK_CUR:
                if ($offset >= 0) {
                    $this->position += $offset;
                    return true;
                }
                return false;
            case SEEK_END:
                if (strlen($this->data) + $offset >= 0) {
                    $this->position = strlen($this->data) + $offset;
                    return true;
                }
                return false;
        }
        return false;
    }
    
    public function stream_tell() {
        return $this->position;
    }
}

// Run the tests
echo "Starting Message Journey Tests...\n\n";

$test = new MessageJourneyTest();

// Test connection pool
$test->testConnectionPool();

// Test message creation
$test->testMessageCreation();

// Clean up
$test->cleanup();

echo "\nMessage Journey Tests completed.\n";

// Clean up output buffer
ob_end_flush(); 