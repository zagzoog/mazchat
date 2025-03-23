<?php

session_start();
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../app/utils/Logger.php';  // Load Logger first
require_once __DIR__ . '/../app/models/Model.php';  // Then load base Model class
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Membership.php';
require_once __DIR__ . '/../app/models/UsageStats.php';

header('Content-Type: application/json');

// Log session information
Logger::log('Session data', 'INFO', [
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'cookies' => $_COOKIE,
    'session_path' => session_save_path(),
    'session_status' => session_status()
]);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    Logger::log('Unauthorized access attempt - No user_id in session', 'WARNING');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$range = $_GET['range'] ?? 'month';

try {
    Logger::log('Starting dashboard data fetch', 'INFO', ['user_id' => $userId]);
    
    // Initialize database connection
    try {
        $db = getDBConnection();
        if (!$db) {
            throw new Exception('Database connection failed');
        }
        Logger::log('Database connection successful', 'INFO');
    } catch (Exception $e) {
        Logger::log('Database connection error', 'ERROR', ['message' => $e->getMessage()]);
        throw new Exception('Failed to connect to database: ' . $e->getMessage());
    }

    // Verify user exists
    try {
        $stmt = $db->prepare('SELECT id, username FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            Logger::log('User not found in database', 'ERROR', ['user_id' => $userId]);
            throw new Exception('User not found');
        }
        Logger::log('User verified', 'INFO', ['user' => $user]);
    } catch (Exception $e) {
        Logger::log('User verification error', 'ERROR', ['message' => $e->getMessage()]);
        throw new Exception('Failed to verify user: ' . $e->getMessage());
    }

    $membership = new Membership();
    $usageStats = new UsageStats();
    
    // Get current membership
    try {
        Logger::log('Fetching current membership', 'INFO');
        $currentMembership = $membership->getCurrentMembership($userId);
        if (!$currentMembership) {
            Logger::log('No current membership found, creating free membership', 'INFO');
            // Create free membership if none exists
            $currentMembership = $membership->createFreeMembership($userId);
        }
        Logger::log('Current membership retrieved', 'INFO', $currentMembership);
    } catch (Exception $e) {
        Logger::log('Membership error', 'ERROR', ['message' => $e->getMessage()]);
        throw new Exception('Failed to process membership: ' . $e->getMessage());
    }
    
    // Get monthly stats
    try {
        Logger::log('Fetching monthly stats', 'INFO');
        $currentMonth = date('Y-m');
        $monthlyStats = $usageStats->getMonthlyStats($userId, $currentMonth);
        if (!$monthlyStats) {
            Logger::log('No monthly stats found, using defaults', 'INFO');
            $monthlyStats = [
                'total_conversations' => 0,
                'total_questions' => 0,
                'total_words' => 0
            ];
        }
        Logger::log('Monthly stats retrieved', 'INFO', $monthlyStats);
    } catch (Exception $e) {
        Logger::log('Monthly stats error', 'ERROR', ['message' => $e->getMessage()]);
        throw new Exception('Failed to get monthly stats: ' . $e->getMessage());
    }
    
    // Get daily stats for the chart
    try {
        Logger::log('Fetching daily stats', 'INFO');
        $dailyStats = $usageStats->getDailyStats($userId);
        if (!$dailyStats) {
            Logger::log('No daily stats found, using empty array', 'INFO');
            $dailyStats = [];
        }
        Logger::log('Daily stats retrieved', 'INFO', ['count' => count($dailyStats)]);
    } catch (Exception $e) {
        Logger::log('Daily stats error', 'ERROR', ['message' => $e->getMessage()]);
        throw new Exception('Failed to get daily stats: ' . $e->getMessage());
    }
    
    // Get top topics
    try {
        Logger::log('Fetching top topics', 'INFO');
        $topTopics = $usageStats->getTopTopics($userId);
        if (!$topTopics) {
            Logger::log('No top topics found, using empty array', 'INFO');
            $topTopics = [];
        }
        Logger::log('Top topics retrieved', 'INFO', ['count' => count($topTopics)]);
    } catch (Exception $e) {
        Logger::log('Top topics error', 'ERROR', ['message' => $e->getMessage()]);
        throw new Exception('Failed to get top topics: ' . $e->getMessage());
    }
    
    // Get monthly limit from settings
    try {
        Logger::log('Fetching monthly limit', 'INFO', ['membership_type' => $currentMembership['type']]);
        $stmt = $db->prepare(
            'SELECT setting_value FROM admin_settings WHERE setting_key = ?'
        );
        $stmt->execute([$currentMembership['type'] . '_monthly_limit']);
        $monthlyLimit = $stmt->fetchColumn();
        
        if (!$monthlyLimit) {
            Logger::log('No monthly limit found in settings, using defaults', 'INFO');
            // Set default limits if not found in settings
            $monthlyLimit = $currentMembership['type'] === 'free' ? 50 : 
                           ($currentMembership['type'] === 'basic' ? 100 : 999999);
        }
        Logger::log('Monthly limit retrieved', 'INFO', ['limit' => $monthlyLimit]);
    } catch (Exception $e) {
        Logger::log('Monthly limit error', 'ERROR', ['message' => $e->getMessage()]);
        throw new Exception('Failed to get monthly limit: ' . $e->getMessage());
    }

    // Get question limit from settings
    try {
        Logger::log('Fetching question limit', 'INFO', ['membership_type' => $currentMembership['type']]);
        $stmt = $db->prepare(
            'SELECT setting_value FROM admin_settings WHERE setting_key = ?'
        );
        $stmt->execute([$currentMembership['type'] . '_question_limit']);
        $questionLimit = $stmt->fetchColumn();
        
        if (!$questionLimit) {
            Logger::log('No question limit found in settings, using defaults', 'INFO');
            // Set default limits if not found in settings
            $questionLimit = $currentMembership['type'] === 'free' ? 500 : 
                           ($currentMembership['type'] === 'basic' ? 2000 : 999999);
        }
        Logger::log('Question limit retrieved', 'INFO', ['limit' => $questionLimit]);
    } catch (Exception $e) {
        Logger::log('Question limit error', 'ERROR', ['message' => $e->getMessage()]);
        throw new Exception('Failed to get question limit: ' . $e->getMessage());
    }
    
    // Get current month's usage
    try {
        Logger::log('Fetching current month\'s usage', 'INFO');
        $currentMonth = date('Y-m');
        $currentUsage = $usageStats->getMonthlyStats($userId, $currentMonth);
        if (!$currentUsage) {
            Logger::log('No current month usage found, using defaults', 'INFO');
            $currentUsage = ['total_conversations' => 0, 'total_questions' => 0];
        }
        Logger::log('Current month usage retrieved', 'INFO', $currentUsage);
    } catch (Exception $e) {
        Logger::log('Current month usage error', 'ERROR', ['message' => $e->getMessage()]);
        throw new Exception('Failed to get current month usage: ' . $e->getMessage());
    }
    
    $response = [
        'membership' => [
            'type' => $currentMembership['type'],
            'monthly_limit' => $monthlyLimit,
            'question_limit' => $questionLimit,
            'current_usage' => $currentUsage['total_conversations'],
            'current_questions' => $currentUsage['total_questions'],
            'start_date' => $currentMembership['start_date'],
            'end_date' => $currentMembership['end_date']
        ],
        'stats' => [
            'total_conversations' => $monthlyStats['total_conversations'],
            'total_questions' => $monthlyStats['total_questions'],
            'total_words' => $monthlyStats['total_words']
        ],
        'daily_stats' => $dailyStats,
        'top_topics' => $topTopics
    ];
    
    Logger::log('Sending response', 'INFO', $response);
    echo json_encode($response);
} catch (Exception $e) {
    Logger::log('Dashboard error', 'ERROR', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load dashboard data',
        'details' => $e->getMessage()
    ]);
} 