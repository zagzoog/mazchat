<?php
session_start();
require_once '../app/utils/ResponseCompressor.php';
require_once '../db_config.php';
require_once '../app/models/UsageStats.php';

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
    $db = getDBConnection();
    $usageStats = new UsageStats();
    
    // Get current month in YYYY-MM format
    $currentMonth = date('Y-m');
    
    // Get all stats
    $stats = $usageStats->getAllStats($_SESSION['user_id'], $currentMonth);
    
    if ($stats === null) {
        throw new Exception("Failed to retrieve usage statistics");
    }
    
    echo json_encode($stats);
} catch (Exception $e) {
    error_log("Error in usage stats API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $compressor->end();
} 