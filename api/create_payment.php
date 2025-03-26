<?php
session_start();
require_once __DIR__ . '/../app/utils/Logger.php';
require_once __DIR__ . '/../app/models/Payment.php';

// Initialize logger
Logger::init();

// Set up logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/app.log');

header('Content-Type: application/json');

// Log the start of the request
error_log("Payment creation request started - " . date('Y-m-d H:i:s'));

try {
    if (!isset($_SESSION['user_id'])) {
        error_log("Unauthorized access attempt - No user_id in session");
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    error_log("User ID: " . $_SESSION['user_id']);

    $input = file_get_contents('php://input');
    error_log("Raw input data: " . $input);

    $data = json_decode($input, true);
    error_log("Decoded input data: " . print_r($data, true));

    if (!isset($data['membership_type'])) {
        error_log("Missing membership_type in request data");
        http_response_code(400);
        echo json_encode(['error' => 'Membership type is required']);
        exit;
    }

    $membershipType = $data['membership_type'];
    $amount = $membershipType === 'basic' ? $config['silver_price'] : $config['gold_price'];

    error_log("Membership type: " . $membershipType);
    error_log("Amount: " . $amount);

    $payment = new Payment();

    // Create payment record
    error_log("Attempting to create payment record");
    $paymentData = $payment->createPayment(
        $_SESSION['user_id'],
        $membershipType,
        $amount
    );
    error_log("Payment record created successfully: " . print_r($paymentData, true));
    
    // Create PayPal order
    error_log("Attempting to create PayPal order");
    $orderId = $payment->createPayPalOrder($paymentData);
    error_log("PayPal order created successfully. Order ID: " . $orderId);
    
    // Get PayPal config
    error_log("Getting PayPal configuration");
    $config = $payment->getPayPalConfig();
    error_log("PayPal config: " . print_r($config, true));
    
    $baseUrl = "https://www." . ($config['paypal_mode'] === 'sandbox' ? 'sandbox.' : '') . "paypal.com/checkoutnow?token=";
    $paypalUrl = $baseUrl . $orderId;
    error_log("Generated PayPal URL: " . $paypalUrl);
    
    echo json_encode([
        'success' => true,
        'paypal_url' => $paypalUrl
    ]);
} catch (Exception $e) {
    error_log("Error in payment creation: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 