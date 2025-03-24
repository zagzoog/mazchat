<?php
session_start();
require_once '../../app/utils/ResponseCompressor.php';
require_once '../../db_config.php';
require_once '../../app/utils/Logger.php';
require_once '../../app/models/Model.php';
require_once '../../app/models/User.php';

// Initialize response compression
$compressor = ResponseCompressor::getInstance();
$compressor->start();

header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    $compressor->end();
    exit;
}

try {
    $userModel = new User();
    $user = $userModel->findById($_SESSION['user_id']);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        $compressor->end();
        exit;
    }
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Return user profile data
            echo json_encode([
                'success' => true,
                'data' => [
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'membership_type' => $user['membership_type'] ?? 'free',
                    'created_at' => $user['created_at'],
                    'last_login' => $user['last_login'] ?? null
                ]
            ]);
            break;
            
        case 'POST':
            // Update user profile
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['username']) || !isset($data['email'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Username and email are required']);
                break;
            }
            
            // Check if username is already taken by another user
            $existingUser = $userModel->getByUsername($data['username']);
            if ($existingUser && $existingUser['id'] != $_SESSION['user_id']) {
                http_response_code(400);
                echo json_encode(['error' => 'Username is already taken']);
                break;
            }
            
            // Check if email is already taken by another user
            $existingUser = $userModel->getByEmail($data['email']);
            if ($existingUser && $existingUser['id'] != $_SESSION['user_id']) {
                http_response_code(400);
                echo json_encode(['error' => 'Email is already taken']);
                break;
            }
            
            // If password change is requested
            if (isset($data['current_password']) && isset($data['new_password'])) {
                // Verify current password
                if (!password_verify($data['current_password'], $user['password'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Current password is incorrect']);
                    break;
                }
                
                // Update password
                $userModel->updatePassword($_SESSION['user_id'], $data['new_password']);
            }
            
            // Update profile information
            $userModel->update($_SESSION['user_id'], [
                'username' => $data['username'],
                'email' => $data['email']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    Logger::log("Error in profile endpoint", 'ERROR', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

$compressor->end(); 