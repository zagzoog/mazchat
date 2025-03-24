<?php
session_start();
require_once '../app/utils/Logger.php';

// Log the logout action
Logger::log("Admin user logged out", 'INFO', [
    'user_id' => $_SESSION['user_id'] ?? null,
    'username' => $_SESSION['username'] ?? null
]);

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: /chat/login.php');
exit; 