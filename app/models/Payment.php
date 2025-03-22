<?php
class Payment extends Model {
    protected $table = 'payments';
    
    public function createPayment($userId, $membershipType, $amount) {
        $paymentId = uniqid();
        $stmt = $this->query(
            'INSERT INTO payments (id, user_id, membership_type, amount, status, created_at) 
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)',
            [$paymentId, $userId, $membershipType, $amount, 'pending']
        );
        
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
        $stmt = $this->query(
            'SELECT * FROM payments WHERE id = ?',
            [$paymentId]
        );
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updatePaymentStatus($paymentId, $status, $paypalOrderId = null) {
        $stmt = $this->query(
            'UPDATE payments 
            SET 
                status = ?,
                paypal_order_id = ?,
                completed_at = CASE WHEN ? = "completed" THEN CURRENT_TIMESTAMP ELSE NULL END
            WHERE id = ?',
            [$status, $paypalOrderId, $status, $paymentId]
        );
        
        return $stmt->rowCount() > 0;
    }
    
    public function getPayPalConfig() {
        $db = getDBConnection();
        $stmt = $db->prepare(
            'SELECT setting_value FROM admin_settings WHERE setting_key IN (?, ?, ?)'
        );
        $stmt->execute(['paypal_client_id', 'paypal_secret', 'paypal_mode']);
        
        $config = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['setting_key']] = $row['setting_value'];
        }
        
        return $config;
    }
    
    public function createPayPalOrder($payment) {
        $config = $this->getPayPalConfig();
        $baseUrl = $_SERVER['HTTP_HOST'];
        
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
                'return_url' => "https://{$baseUrl}/api/payment_success.php?payment_id={$payment['id']}",
                'cancel_url' => "https://{$baseUrl}/api/payment_cancel.php?payment_id={$payment['id']}"
            ]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api-m." . ($config['paypal_mode'] === 'sandbox' ? 'sandbox.' : '') . "paypal.com/v2/checkout/orders");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($config['paypal_client_id'] . ':' . $config['paypal_secret'])
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 201) {
            throw new Exception('Failed to create PayPal order');
        }
        
        $order = json_decode($response, true);
        return $order['id'];
    }
    
    public function capturePayPalOrder($orderId) {
        $config = $this->getPayPalConfig();
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api-m." . ($config['paypal_mode'] === 'sandbox' ? 'sandbox.' : '') . "paypal.com/v2/checkout/orders/{$orderId}/capture");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($config['paypal_client_id'] . ':' . $config['paypal_secret'])
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 201) {
            throw new Exception('Failed to capture PayPal order');
        }
        
        return json_decode($response, true);
    }
} 