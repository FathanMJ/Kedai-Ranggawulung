<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
include('config/midtrans.php');
include('config/MidtransHelper.php');

check_login();

// Get order code from URL
$order_code = isset($_GET['order_id']) ? htmlspecialchars($_GET['order_id']) : '';
$status = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : '';

if (!$order_code) {
    header("Location: payments.php?error=Invalid order");
    exit();
}

// Get order and payment details
$getOrder = "SELECT * FROM rpos_orders WHERE order_code = ?";
$orderStmt = $mysqli->prepare($getOrder);
$orderStmt->bind_param('s', $order_code);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();
$order = $orderResult->fetch_object();

if (!$order) {
    header("Location: payments.php?error=Order not found");
    exit();
}

// Get payment details from database
$getPayment = "SELECT * FROM rpos_payments WHERE order_code = ? AND pay_method = 'Midtrans' ORDER BY created_at DESC LIMIT 1";
$paymentStmt = $mysqli->prepare($getPayment);
$paymentStmt->bind_param('s', $order_code);
$paymentStmt->execute();
$paymentResult = $paymentStmt->get_result();
$payment = $paymentResult->fetch_object();

$paymentStatus = 'failed';
$message = '';

if ($payment) {
    // Check with Midtrans API for actual transaction status
    $midtrans = new MidtransHelper();
    $transactionStatus = $midtrans->getTransactionStatus($order_code);
    
    if ($transactionStatus['success'] && isset($transactionStatus['data']['transaction_status'])) {
        $midtransStatus = $transactionStatus['data']['transaction_status'];
        
        // Handle different Midtrans statuses
        switch ($midtransStatus) {
            case 'capture':
            case 'settlement':
                $paymentStatus = 'completed';
                $message = 'Payment successful! Your order has been marked as paid.';
                
                // Update payment status in database
                $updatePayment = "UPDATE rpos_payments SET payment_status = 'completed' WHERE id = ?";
                $updatePaymentStmt = $mysqli->prepare($updatePayment);
                $updatePaymentStmt->bind_param('s', $payment->id);
                $updatePaymentStmt->execute();
                
                // Update order status to Paid
                $updateOrder = "UPDATE rpos_orders SET order_status = 'Paid' WHERE order_code = ?";
                $updateOrderStmt = $mysqli->prepare($updateOrder);
                $updateOrderStmt->bind_param('s', $order_code);
                $updateOrderStmt->execute();
                
                // Log successful payment
                error_log("Midtrans Payment Successful - Order: $order_code, Amount: " . $payment->pay_amt);
                break;
                
            case 'pending':
                $paymentStatus = 'pending';
                $message = 'Payment is pending. Please wait for confirmation or complete your payment.';
                
                // Update payment status in database
                $updatePayment = "UPDATE rpos_payments SET payment_status = 'pending' WHERE id = ?";
                $updatePaymentStmt = $mysqli->prepare($updatePayment);
                $updatePaymentStmt->bind_param('s', $payment->id);
                $updatePaymentStmt->execute();
                break;
                
            case 'deny':
            case 'cancel':
            case 'expire':
                $paymentStatus = 'failed';
                $message = 'Payment failed or was cancelled. Please try again.';
                
                // Update payment status in database
                $updatePayment = "UPDATE rpos_payments SET payment_status = 'failed' WHERE id = ?";
                $updatePaymentStmt = $mysqli->prepare($updatePayment);
                $updatePaymentStmt->bind_param('s', $payment->id);
                $updatePaymentStmt->execute();
                
                error_log("Midtrans Payment Failed - Order: $order_code, Status: $midtransStatus");
                break;
                
            default:
                $message = 'Payment status unknown. Please contact support.';
        }
    } else {
        $message = 'Could not verify payment status. Please contact support.';
        error_log("Midtrans API Error: " . print_r($transactionStatus, true));
    }
} else {
    $message = 'Payment record not found. Please contact support.';
}

require_once('partials/_head.php');
?>

<body>
    <!-- Sidenav -->
    <?php require_once('partials/_sidebar.php'); ?>
    
    <!-- Main content -->
    <div class="main-content">
        <!-- Top navbar -->
        <?php require_once('partials/_topnav.php'); ?>
        
        <!-- Header -->
        <div style="background-image: url(assets/img/theme/restro00.jpg); background-size: cover;" class="header pb-8 pt-5 pt-md-8">
            <span class="mask bg-gradient-dark opacity-8"></span>
            <div class="container-fluid">
                <div class="header-body"></div>
            </div>
        </div>
        
        <!-- Page content -->
        <div class="container-fluid mt--8">
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="card shadow">
                        <div class="card-header border-0">
                            <h3>Payment Status</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($paymentStatus === 'completed') { ?>
                                <div class="alert alert-success" role="alert">
                                    <h4 class="alert-heading">
                                        <i class="fas fa-check-circle"></i> Payment Successful!
                                    </h4>
                                    <p><?php echo $message; ?></p>
                                    <hr>
                                    <p class="mb-0">
                                        <strong>Order Code:</strong> <?php echo $order_code; ?><br>
                                        <strong>Amount:</strong> RP <?php echo number_format($order->prod_price * $order->prod_qty, 0, ',', '.'); ?><br>
                                        <strong>Payment Method:</strong> Midtrans<br>
                                        <strong>Transaction Date:</strong> <?php echo date('d/M/Y H:i:s'); ?>
                                    </p>
                                </div>
                                
                                <div style="margin-top: 20px; text-align: center;">
                                    <a href="receipts.php" class="btn btn-success mr-2">
                                        <i class="fas fa-receipt"></i> View Receipt
                                    </a>
                                    <a href="payments.php" class="btn btn-outline-primary">
                                        <i class="fas fa-arrow-left"></i> Back to Payments
                                    </a>
                                </div>
                                
                            <?php } elseif ($paymentStatus === 'pending') { ?>
                                <div class="alert alert-warning" role="alert">
                                    <h4 class="alert-heading">
                                        <i class="fas fa-hourglass-half"></i> Payment Pending
                                    </h4>
                                    <p><?php echo $message; ?></p>
                                    <hr>
                                    <p class="mb-0">
                                        <strong>Order Code:</strong> <?php echo $order_code; ?><br>
                                        <strong>Amount:</strong> RP <?php echo number_format($order->prod_price * $order->prod_qty, 0, ',', '.'); ?><br>
                                        <strong>Status:</strong> Awaiting confirmation...
                                    </p>
                                </div>
                                
                                <div style="margin-top: 20px; text-align: center;">
                                    <a href="payments.php" class="btn btn-outline-primary">
                                        <i class="fas fa-arrow-left"></i> Back to Payments
                                    </a>
                                </div>
                                
                            <?php } else { ?>
                                <div class="alert alert-danger" role="alert">
                                    <h4 class="alert-heading">
                                        <i class="fas fa-times-circle"></i> Payment Failed
                                    </h4>
                                    <p><?php echo $message; ?></p>
                                    <hr>
                                    <p class="mb-0">
                                        <strong>Order Code:</strong> <?php echo $order_code; ?><br>
                                        <strong>Amount:</strong> RP <?php echo number_format($order->prod_price * $order->prod_qty, 0, ',', '.'); ?><br>
                                        <strong>Status:</strong> Payment Failed or Cancelled
                                    </p>
                                </div>
                                
                                <div style="margin-top: 20px; text-align: center;">
                                    <a href="pay_order_midtrans.php?order_code=<?php echo urlencode($order_code); ?>" class="btn btn-warning mr-2">
                                        <i class="fas fa-redo"></i> Retry Payment
                                    </a>
                                    <a href="payments.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left"></i> Back to Payments
                                    </a>
                                </div>
                                
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <?php require_once('partials/_footer.php'); ?>
    </div>
    
    <!-- Argon Scripts -->
    <?php require_once('partials/_scripts.php'); ?>
</body>
</html>
