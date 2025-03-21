<?php
session_start();
require_once '../app/config/database.php';
require_once '../app/models/User.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || !isset($data['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$username = trim($data['username']);
$email = trim($data['email']);
$userId = $_SESSION['user_id'];

// Validate input
if (empty($username) || empty($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Username and email are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

try {
    $user = new User();
    
    // Check if username is already taken by another user
    $existingUser = $user->findByUsername($username);
    if ($existingUser && $existingUser['id'] != $userId) {
        http_response_code(400);
        echo json_encode(['error' => 'Username is already taken']);
        exit;
    }
    
    // Check if email is already taken by another user
    $existingUser = $user->findByEmail($email);
    if ($existingUser && $existingUser['id'] != $userId) {
        http_response_code(400);
        echo json_encode(['error' => 'Email is already taken']);
        exit;
    }
    
    // Update user profile
    $user->updateProfile($userId, $username, $email);
    
    // Update session data
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Profile update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update profile']);
} 