<?php
/**
 * Midtrans Webhook Handler
 * This endpoint receives notifications from Midtrans server
 * Make sure this file is publicly accessible and webhook URL is configured in Midtrans dashboard
 */

include('../config/config.php');
include('../config/midtrans.php');
include('../config/MidtransHelper.php');

// Set content type
header('Content-Type: application/json');

// Log incoming request
$input = file_get_contents('php://input');
error_log("Midtrans Webhook Received: " . $input);

if (empty($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Empty request']);
    exit;
}

$data = json_decode($input, true);

// Verify required fields
if (!isset($data['order_id']) || !isset($data['transaction_status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$orderId = $data['order_id'];
$transactionStatus = $data['transaction_status'];
$signatureKey = $data['signature_key'] ?? '';

// Verify signature (optional but recommended for security)
if ($signatureKey && !empty(MIDTRANS_SERVER_KEY)) {
    $midtrans = new MidtransHelper();
    if (!$midtrans->verifyWebhookSignature($data, $signatureKey)) {
        error_log("Invalid Midtrans Signature for Order: $orderId");
        // Log but don't reject - Midtrans requires us to respond with 200
    }
}

// Get payment from database
$getPayment = "SELECT * FROM rpos_payments WHERE order_code = ? AND pay_method = 'Midtrans' ORDER BY created_at DESC LIMIT 1";
$paymentStmt = $mysqli->prepare($getPayment);
$paymentStmt->bind_param('s', $orderId);
$paymentStmt->execute();
$paymentResult = $paymentStmt->get_result();
$payment = $paymentResult->fetch_object();

if (!$payment) {
    error_log("Payment not found for Order: $orderId");
    http_response_code(200);
    echo json_encode(['success' => false, 'message' => 'Payment not found']);
    exit;
}

// Handle transaction status
$updated = false;
switch ($transactionStatus) {
    case 'capture':
    case 'settlement':
        // Payment successful
        $updatePayment = "UPDATE rpos_payments SET payment_status = 'completed', updated_at = NOW() WHERE order_code = ? AND pay_method = 'Midtrans'";
        $updateStmt = $mysqli->prepare($updatePayment);
        $updateStmt->bind_param('s', $orderId);
        $updated = $updateStmt->execute();
        
        // Update order status
        if ($updated && $payment->payment_status !== 'completed') {
            $updateOrder = "UPDATE rpos_orders SET order_status = 'Paid' WHERE order_code = ?";
            $orderStmt = $mysqli->prepare($updateOrder);
            $orderStmt->bind_param('s', $orderId);
            $orderStmt->execute();
            
            error_log("Payment Completed - Order: $orderId, Amount: " . $payment->pay_amt);
        }
        break;
        
    case 'pending':
        // Payment pending - usually for bank transfer or e-wallet
        $updatePayment = "UPDATE rpos_payments SET payment_status = 'pending', updated_at = NOW() WHERE order_code = ? AND pay_method = 'Midtrans'";
        $updateStmt = $mysqli->prepare($updatePayment);
        $updateStmt->bind_param('s', $orderId);
        $updated = $updateStmt->execute();
        
        error_log("Payment Pending - Order: $orderId");
        break;
        
    case 'deny':
    case 'cancel':
        // Payment denied or cancelled
        $updatePayment = "UPDATE rpos_payments SET payment_status = 'failed', updated_at = NOW() WHERE order_code = ? AND pay_method = 'Midtrans'";
        $updateStmt = $mysqli->prepare($updatePayment);
        $updateStmt->bind_param('s', $orderId);
        $updated = $updateStmt->execute();
        
        error_log("Payment Denied/Cancelled - Order: $orderId, Status: $transactionStatus");
        break;
        
    case 'expire':
        // Payment expired
        $updatePayment = "UPDATE rpos_payments SET payment_status = 'expired', updated_at = NOW() WHERE order_code = ? AND pay_method = 'Midtrans'";
        $updateStmt = $mysqli->prepare($updatePayment);
        $updateStmt->bind_param('s', $orderId);
        $updated = $updateStmt->execute();
        
        error_log("Payment Expired - Order: $orderId");
        break;
        
    case 'refund':
        // Refund processed
        $refundAmount = $data['gross_amount'] ?? $payment->pay_amt;
        $updatePayment = "UPDATE rpos_payments SET payment_status = 'refunded', pay_amt = ?, updated_at = NOW() WHERE order_code = ? AND pay_method = 'Midtrans'";
        $updateStmt = $mysqli->prepare($updatePayment);
        $updateStmt->bind_param('ss', $refundAmount, $orderId);
        $updated = $updateStmt->execute();
        
        // Update order status back to pending
        $updateOrder = "UPDATE rpos_orders SET order_status = '' WHERE order_code = ?";
        $orderStmt = $mysqli->prepare($updateOrder);
        $orderStmt->bind_param('s', $orderId);
        $orderStmt->execute();
        
        error_log("Payment Refunded - Order: $orderId, Amount: $refundAmount");
        break;
}

// Always respond with 200 to Midtrans
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Webhook received and processed',
    'order_id' => $orderId,
    'transaction_status' => $transactionStatus,
    'updated' => $updated
]);
?>
