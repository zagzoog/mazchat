<?php
session_start();
require_once __DIR__ . '/../app/models/Payment.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['membership_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Membership type is required']);
    exit;
}

$membershipType = $data['membership_type'];
$amount = $membershipType === 'basic' ? 9.99 : 19.99;

$payment = new Payment();

try {
    // Create payment record
    $paymentData = $payment->createPayment(
        $_SESSION['user_id'],
        $membershipType,
        $amount
    );
    
    // Create PayPal order
    $orderId = $payment->createPayPalOrder($paymentData);
    
    // Get PayPal config
    $config = $payment->getPayPalConfig();
    $baseUrl = "https://www." . ($config['paypal_mode'] === 'sandbox' ? 'sandbox.' : '') . "paypal.com/checkoutnow?token=";
    
    echo json_encode([
        'success' => true,
        'paypal_url' => $baseUrl . $orderId
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 