<?php
session_start();
error_log("Starting auth.php");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

try {
    error_log("Loading required files...");
    require_once '../app/utils/ResponseCompressor.php';
    require_once '../db_config.php';
    require_once '../app/models/Model.php';  // Load base Model class first
    require_once '../app/models/User.php';   // Then load User model
    error_log("Required files loaded successfully");

    // Initialize response compression
    error_log("Initializing response compression...");
    $compressor = ResponseCompressor::getInstance();
    error_log("ResponseCompressor instance created");
    $compressor->start();
    error_log("Response compression started");

    header('Content-Type: application/json');

    error_log("Auth endpoint called");
    $data = json_decode(file_get_contents('php://input'), true);
    error_log("Received data: " . print_r($data, true));
    
    if (!isset($data['username']) || !isset($data['password'])) {
        error_log("Missing username or password");
        http_response_code(400);
        echo json_encode(['error' => 'Username and password are required']);
        $compressor->end();
        exit;
    }
    
    $username = $data['username'];
    $password = $data['password'];
    
    error_log("Attempting to connect to database");
    $db = getDBConnection();
    error_log("Database connection successful");
    
    $user = new User();
    error_log("Looking up user: " . $username);
    $userData = $user->findByUsername($username);
    error_log("User data found: " . print_r($userData, true));
    
    if ($userData && password_verify($password, $userData['password'])) {
        error_log("Password verification successful");
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $userData['email'];
        
        // Check if user has an active membership
        $stmt = $db->prepare("
            SELECT type 
            FROM memberships 
            WHERE user_id = ? 
            AND end_date >= CURRENT_DATE 
            ORDER BY start_date DESC 
            LIMIT 1
        ");
        $stmt->execute([$userData['id']]);
        $membership = $stmt->fetch();
        error_log("Membership data: " . print_r($membership, true));
        
        if (!$membership) {
            // Create free membership if none exists
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime('+1 year'));
            
            $stmt = $db->prepare("
                INSERT INTO memberships (user_id, type, start_date, end_date) 
                VALUES (?, 'free', ?, ?)
            ");
            $stmt->execute([$userData['id'], $start_date, $end_date]);
            $membership = ['type' => 'free'];
            error_log("Created new free membership");
        }
        
        // Generate API token
        $api_token = bin2hex(random_bytes(32));
        $api_token_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Update user with API token
        $stmt = $db->prepare("
            UPDATE users 
            SET api_token = ?, api_token_expires = ? 
            WHERE id = ?
        ");
        $stmt->execute([$api_token, $api_token_expires, $userData['id']]);
        
        $_SESSION['membership_type'] = $membership['type'];
        
        $response = [
            'success' => true,
            'user' => [
                'id' => $userData['id'],
                'username' => $username,
                'email' => $userData['email'],
                'membership_type' => $membership['type'],
                'api_token' => $api_token,
                'api_token_expires' => $api_token_expires
            ]
        ];
        error_log("Sending success response: " . print_r($response, true));
        echo json_encode($response);
    } else {
        error_log("Authentication failed for user: " . $username);
        http_response_code(401);
        echo json_encode(['error' => 'Invalid username or password']);
    }
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred during login: ' . $e->getMessage()]);
} finally {
    if (isset($compressor)) {
        error_log("Ending response compression");
        $compressor->end();
    }
} 