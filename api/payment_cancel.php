<?php
session_start();
require_once __DIR__ . '/../app/models/Payment.php';

header('Content-Type: application/json');

if (!isset($_GET['payment_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Payment ID is required']);
    exit;
}

$paymentId = $_GET['payment_id'];
$payment = new Payment();

try {
    $paymentData = $payment->getPayment($paymentId);
    if (!$paymentData) {
        throw new Exception('Payment not found');
    }
    
    if ($paymentData['status'] !== 'pending') {
        throw new Exception('Payment already processed');
    }
    
    // Update payment status
    $payment->updatePaymentStatus($paymentId, 'cancelled');
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment cancelled successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 