<?php
require_once '../app/models/Membership.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $membership = new Membership();
    $hasQuestionsRemaining = $membership->checkQuestionLimit($_SESSION['user_id']);
    
    if (!$hasQuestionsRemaining) {
        http_response_code(403);
        echo json_encode(['error' => 'Monthly question limit reached']);
        exit;
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Error checking question limit: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
} 