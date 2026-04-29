<?php
/**
 * Midtrans Payment Helper Class
 * Handles all Midtrans payment operations
 */

class MidtransHelper {
    
    private $serverKey;
    private $clientKey;
    private $apiUrl;
    private $isProduction;
    
    public function __construct() {
        $this->serverKey = MIDTRANS_SERVER_KEY;
        $this->clientKey = MIDTRANS_CLIENT_KEY;
        $this->apiUrl = MIDTRANS_API_URL;
        $this->isProduction = MIDTRANS_IS_PRODUCTION;
    }
    
    /**
     * Create Midtrans transaction token for client-side payment
     */
    public function getSnapToken($orderId, $amount, $customerDetails, $itemDetails) {
        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int)$amount
            ],
            'customer_details' => [
                'first_name' => $customerDetails['name'] ?? '',
                'email' => $customerDetails['email'] ?? '',
                'phone' => $customerDetails['phone'] ?? ''
            ],
            'item_details' => $itemDetails,
            'enabled_payments' => [
                'credit_card',
                'gcg_online',
                'mandiri_clickpay',
                'cimb_clicks',
                'bca_klikbca',
                'bca_klikpay',
                'bri_epay',
                'echannel',
                'permata_va',
                'bca_va',
                'bni_va',
                'other_va',
                'gopay',
                'shopeepay'
            ],
            'vt_web' => []
        ];
        
        $response = $this->makeApiRequest('/snap/v1/transactions', 'POST', $params);
        
        if ($response['success'] && isset($response['data']['token'])) {
            return $response['data']['token'];
        }
        
        return false;
    }
    
    /**
     * Create Midtrans transaction (for direct charge)
     */
    public function createTransaction($orderId, $amount, $paymentType, $customerDetails, $itemDetails) {
        $params = [
            'payment_type' => $paymentType,
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int)$amount
            ],
            'customer_details' => [
                'first_name' => $customerDetails['name'] ?? '',
                'email' => $customerDetails['email'] ?? '',
                'phone' => $customerDetails['phone'] ?? ''
            ],
            'item_details' => $itemDetails
        ];
        
        // Add specific payment type details
        switch ($paymentType) {
            case 'bank_transfer':
                $params['bank_transfer'] = ['bank' => 'bca'];
                break;
            case 'echannel':
                $params['echannel'] = ['client_key' => $this->clientKey];
                break;
        }
        
        return $this->makeApiRequest('/charge', 'POST', $params);
    }
    
    /**
     * Get transaction status from Midtrans
     */
    public function getTransactionStatus($orderId) {
        return $this->makeApiRequest('/transactions/' . $orderId . '/status', 'GET');
    }
    
    /**
     * Cancel Midtrans transaction
     */
    public function cancelTransaction($orderId) {
        return $this->makeApiRequest('/transactions/' . $orderId . '/cancel', 'POST');
    }
    
    /**
     * Refund Midtrans transaction
     */
    public function refundTransaction($orderId, $refundAmount = null) {
        $params = [];
        if ($refundAmount !== null) {
            $params['refund_amount'] = (int)$refundAmount;
        }
        
        return $this->makeApiRequest('/transactions/' . $orderId . '/refund', 'POST', $params);
    }
    
    /**
     * Make API request to Midtrans
     */
    private function makeApiRequest($endpoint, $method = 'GET', $data = null): array {
        $url = $this->apiUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        $credentials = base64_encode($this->serverKey . ':');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $credentials,
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        
        if ($data && in_array($method, ['POST', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log('Midtrans API Error: ' . $error);
            return [
                'success' => false,
                'error' => $error,
                'http_code' => $httpCode
            ];
        }
        
        return [
            'success' => true,
            'data' => json_decode($response, true),
            'http_code' => $httpCode
        ];
    }
    
    /**
     * Verify Midtrans webhook signature
     */
    public function verifyWebhookSignature($data, $signatureKey) {
        $orderId = $data['order_id'];
        $statusCode = $data['status_code'];
        $grossAmount = $data['gross_amount'];
        
        $signatureString = $orderId . $statusCode . $grossAmount . $this->serverKey;
        $expectedSignature = hash('sha512', $signatureString);
        
        return $expectedSignature === $signatureKey;
    }
    
    /**
     * Get Midtrans Snap redirect URL
     */
    public function getSnapRedirectUrl($token) {
        return $this->isProduction 
            ? 'https://app.midtrans.com/snap/v1/payment/' . $token
            : 'https://app.sandbox.midtrans.com/snap/v1/payment/' . $token;
    }
}
?>
