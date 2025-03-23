<?php
require_once __DIR__ . '/Model.php';

class Payment extends Model {
    protected $table = 'payments';
    
    public function createPayment($userId, $membershipType, $amount) {
        error_log("Creating payment record for user {$userId}");
        $paymentId = uniqid();
        error_log("Generated payment ID: {$paymentId}");
        
        try {
            $stmt = $this->query(
                'INSERT INTO payments (id, user_id, membership_type, amount, status, created_at) 
                VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)',
                [$paymentId, $userId, $membershipType, $amount, 'pending']
            );
            error_log("Payment record inserted successfully");
        } catch (Exception $e) {
            error_log("Error creating payment record: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
        
        return [
            'id' => $paymentId,
            'user_id' => $userId,
            'membership_type' => $membershipType,
            'amount' => $amount,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    public function getPayment($paymentId) {
        error_log("Fetching payment record for ID: {$paymentId}");
        try {
            $stmt = $this->query(
                'SELECT * FROM payments WHERE id = ?',
                [$paymentId]
            );
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Payment record fetched: " . print_r($result, true));
            return $result;
        } catch (Exception $e) {
            error_log("Error fetching payment record: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    public function updatePaymentStatus($paymentId, $status, $paypalOrderId = null) {
        error_log("Updating payment status for ID: {$paymentId} to: {$status}");
        try {
            $stmt = $this->query(
                'UPDATE payments 
                SET 
                    status = ?,
                    paypal_order_id = ?,
                    completed_at = CASE WHEN ? = "completed" THEN CURRENT_TIMESTAMP ELSE NULL END
                WHERE id = ?',
                [$status, $paypalOrderId, $status, $paymentId]
            );
            $success = $stmt->rowCount() > 0;
            error_log("Payment status update " . ($success ? "successful" : "failed"));
            return $success;
        } catch (Exception $e) {
            error_log("Error updating payment status: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    public function getPayPalConfig() {
        error_log("Fetching PayPal configuration");
        try {
            $db = getDBConnection();
            $stmt = $db->prepare(
                'SELECT setting_key, setting_value FROM admin_settings WHERE setting_key IN (?, ?, ?)'
            );
            $stmt->execute(['paypal_client_id', 'paypal_secret', 'paypal_mode']);
            
            $config = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $config[$row['setting_key']] = $row['setting_value'];
            }
            
            // Log the config without sensitive data
            $safeConfig = $config;
            if (isset($safeConfig['paypal_secret'])) {
                $safeConfig['paypal_secret'] = '***HIDDEN***';
            }
            error_log("PayPal configuration fetched: " . print_r($safeConfig, true));
            
            // Validate required settings
            if (!isset($config['paypal_client_id']) || empty($config['paypal_client_id'])) {
                throw new Exception("PayPal Client ID is missing or empty");
            }
            if (!isset($config['paypal_secret']) || empty($config['paypal_secret'])) {
                throw new Exception("PayPal Secret is missing or empty");
            }
            if (!isset($config['paypal_mode'])) {
                $config['paypal_mode'] = 'sandbox'; // Default to sandbox if not set
            }
            
            return $config;
        } catch (Exception $e) {
            error_log("Error fetching PayPal configuration: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    public function createPayPalOrder($payment) {
        error_log("Creating PayPal order for payment: " . print_r($payment, true));
        try {
            $config = $this->getPayPalConfig();
            $baseUrl = $_SERVER['HTTP_HOST'];
            error_log("Base URL: {$baseUrl}");
            
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => $payment['amount']
                        ],
                        'description' => ucfirst($payment['membership_type']) . ' Membership'
                    ]
                ],
                'application_context' => [
                    'return_url' => "http://{$baseUrl}/api/payment_success.php?payment_id={$payment['id']}",
                    'cancel_url' => "http://{$baseUrl}/api/payment_cancel.php?payment_id={$payment['id']}"
                ]
            ];
            error_log("PayPal order data: " . print_r($orderData, true));
            
            $paypalUrl = "https://api-m." . ($config['paypal_mode'] === 'sandbox' ? 'sandbox.' : '') . "paypal.com/v2/checkout/orders";
            error_log("PayPal API URL: {$paypalUrl}");
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $paypalUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($config['paypal_client_id'] . ':' . $config['paypal_secret'])
            ]);
            // Add SSL verification options
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            error_log("PayPal API Response Code: {$httpCode}");
            error_log("PayPal API Response: {$response}");
            if ($curlError) {
                error_log("CURL Error: {$curlError}");
            }
            
            if ($httpCode !== 201) {
                throw new Exception("Failed to create PayPal order. HTTP Code: {$httpCode}, Response: {$response}");
            }
            
            $order = json_decode($response, true);
            if (!$order || !isset($order['id'])) {
                throw new Exception("Invalid PayPal order response: " . $response);
            }
            
            error_log("PayPal order created successfully. Order ID: " . $order['id']);
            return $order['id'];
        } catch (Exception $e) {
            error_log("Error creating PayPal order: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    public function capturePayPalOrder($orderId) {
        error_log("Capturing PayPal order: {$orderId}");
        try {
            $config = $this->getPayPalConfig();
            
            $captureUrl = "https://api-m." . ($config['paypal_mode'] === 'sandbox' ? 'sandbox.' : '') . "paypal.com/v2/checkout/orders/{$orderId}/capture";
            error_log("PayPal capture URL: {$captureUrl}");
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $captureUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($config['paypal_client_id'] . ':' . $config['paypal_secret'])
            ]);
            // Add SSL verification options
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            error_log("PayPal Capture Response Code: {$httpCode}");
            error_log("PayPal Capture Response: {$response}");
            if ($curlError) {
                error_log("CURL Error: {$curlError}");
            }
            
            if ($httpCode !== 201) {
                throw new Exception("Failed to capture PayPal order. HTTP Code: {$httpCode}, Response: {$response}");
            }
            
            $result = json_decode($response, true);
            error_log("PayPal order captured successfully: " . print_r($result, true));
            return $result;
        } catch (Exception $e) {
            error_log("Error capturing PayPal order: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
} 