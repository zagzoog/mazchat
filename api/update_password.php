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

if (!isset($data['current_password']) || !isset($data['new_password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$currentPassword = $data['current_password'];
$newPassword = $data['new_password'];
$userId = $_SESSION['user_id'];

// Validate input
if (empty($currentPassword) || empty($newPassword)) {
    http_response_code(400);
    echo json_encode(['error' => 'Current password and new password are required']);
    exit;
}

if (strlen($newPassword) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'New password must be at least 8 characters long']);
    exit;
}

try {
    $user = new User();
    
    // Verify current password
    $userData = $user->findById($userId);
    if (!$userData || !password_verify($currentPassword, $userData['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Current password is incorrect']);
        exit;
    }
    
    // Update password
    $user->updatePassword($userId, $newPassword);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Password update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update password']);
} 