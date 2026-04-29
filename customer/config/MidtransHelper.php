<?php
/**
 * Midtrans Payment Helper Class (Customer Portal)
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
     * Get transaction status from Midtrans
     */
    public function getTransactionStatus($orderId) {
        return $this->makeApiRequest('/transactions/' . $orderId . '/status', 'GET');
    }
    
    /**
     * Make API request to Midtrans
     */
    private function makeApiRequest($endpoint, $method = 'GET', $data = null) {
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
}
?>
