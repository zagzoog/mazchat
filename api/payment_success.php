<?php
session_start();
require_once __DIR__ . '/../app/models/Payment.php';
require_once __DIR__ . '/../app/models/Membership.php';

header('Content-Type: application/json');

if (!isset($_GET['payment_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Payment ID is required']);
    exit;
}

$paymentId = $_GET['payment_id'];
$payment = new Payment();
$membership = new Membership();

try {
    $paymentData = $payment->getPayment($paymentId);
    if (!$paymentData) {
        throw new Exception('Payment not found');
    }
    
    if ($paymentData['status'] !== 'pending') {
        throw new Exception('Payment already processed');
    }
    
    // Update payment status
    $payment->updatePaymentStatus($paymentId, 'completed', $_GET['token']);
    
    // Upgrade membership
    $membership->upgradeMembership(
        $paymentData['user_id'],
        $paymentData['membership_type'],
        $paymentId
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment processed successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 