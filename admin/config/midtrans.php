<?php
/**
 * Midtrans Configuration
 * Set your Midtrans credentials here
 */

// Midtrans Server Key dan Client Key
// Get from: https://dashboard.midtrans.com/
define('MIDTRANS_SERVER_KEY', 'SB-Mid-server-Qko3Ve2udri-3rLrpEH22xNH');
define('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-xh7cB6GK-mTPFvjY');

// Set Midtrans environment (true for production, false for sandbox)
define('MIDTRANS_IS_PRODUCTION', false);

// Set API URL based on environment
define('MIDTRANS_API_URL', MIDTRANS_IS_PRODUCTION 
    ? 'https://api.midtrans.com/v2' 
    : 'https://api.sandbox.midtrans.com/v2');

/**
 * Merchant Information
 */
define('MERCHANT_ID', 'G102115432');
define('MERCHANT_NAME', 'Kedai Ranggawulung');
define('MERCHANT_EMAIL', 'contact@kedairanggawulung.com');

/**
 * Get Authorization Header for Midtrans API
 */
function getMidtransAuthHeader() {
    $credentials = base64_encode(MIDTRANS_SERVER_KEY . ':');
    return 'Basic ' . $credentials;
}

/**
 * Make request to Midtrans API
 */
function makeMidtransRequest($endpoint, $method = 'GET', $data = null) {
    $url = MIDTRANS_API_URL . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . getMidtransAuthHeader(),
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
 * Verify Midtrans signature for webhook
 */
function verifyMidtransSignature($orderId, $statusCode, $grossAmount, $serverKey, $signatureKey) {
    $signatureString = $orderId . $statusCode . $grossAmount . $serverKey;
    $signature = hash('sha512', $signatureString);
    return $signature === $signatureKey;
}
?>
