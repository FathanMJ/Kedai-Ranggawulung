<?php
session_start();
include('../config/config.php');
include('../config/checklogin.php');
include('../config/midtrans.php');
include('../config/MidtransHelper.php');

check_login();

$order_code = isset($_GET['order_id']) ? htmlspecialchars($_GET['order_id']) : '';
$status = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : '';

if (!$order_code) {
    header("Location: payments_reports.php?error=Invalid order");
    exit();
}

$getOrder = "SELECT * FROM rpos_orders WHERE order_code = ?";
$orderStmt = $mysqli->prepare($getOrder);
$orderStmt->bind_param('s', $order_code);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();
$order = $orderResult->fetch_object();

if (!$order) {
    header("Location: payments_reports.php?error=Order not found");
    exit();
}

$getPayment = "SELECT * FROM rpos_payments WHERE order_code = ? AND pay_method = 'Midtrans' ORDER BY created_at DESC LIMIT 1";
$paymentStmt = $mysqli->prepare($getPayment);
$paymentStmt->bind_param('s', $order_code);
$paymentStmt->execute();
$paymentResult = $paymentStmt->get_result();
$payment = $paymentResult->fetch_object();

$paymentStatus = 'failed';
$message = '';

if ($payment) {
    $midtrans = new MidtransHelper();
    $transactionStatus = $midtrans->getTransactionStatus($order_code);
    
    if ($transactionStatus['success'] && isset($transactionStatus['data']['transaction_status'])) {
        $midtransStatus = $transactionStatus['data']['transaction_status'];
        
        switch ($midtransStatus) {
            case 'capture':
            case 'settlement':
                $paymentStatus = 'completed';
                $message = 'Payment successful!';
                
                $updatePayment = "UPDATE rpos_payments SET payment_status = 'completed' WHERE id = ?";
                $updatePaymentStmt = $mysqli->prepare($updatePayment);
                $updatePaymentStmt->bind_param('s', $payment->id);
                $updatePaymentStmt->execute();
                
                $updateOrder = "UPDATE rpos_orders SET order_status = 'Paid' WHERE order_code = ?";
                $updateOrderStmt = $mysqli->prepare($updateOrder);
                $updateOrderStmt->bind_param('s', $order_code);
                $updateOrderStmt->execute();
                break;
                
            case 'pending':
                $paymentStatus = 'pending';
                $message = 'Payment is pending. Please wait for confirmation.';
                break;
                
            case 'deny':
            case 'cancel':
            case 'expire':
                $paymentStatus = 'failed';
                $message = 'Payment failed. Please try again.';
                break;
        }
    }
}

require_once('../partials/_head.php');
?>

<body>
    <?php require_once('../partials/_sidebar.php'); ?>
    
    <div class="main-content">
        <?php require_once('../partials/_topnav.php'); ?>
        
        <div style="background-image: url(../assets/img/theme/restro00.jpg); background-size: cover;" class="header pb-8 pt-5 pt-md-8">
            <span class="mask bg-gradient-dark opacity-8"></span>
        </div>
        
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
                                    <h4 class="alert-heading"><i class="fas fa-check-circle"></i> Payment Successful!</h4>
                                    <p><?php echo $message; ?></p>
                                    <hr>
                                    <p class="mb-0">
                                        <strong>Order Code:</strong> <?php echo $order_code; ?><br>
                                        <strong>Amount:</strong> RP <?php echo number_format($order->prod_price * $order->prod_qty, 0, ',', '.'); ?><br>
                                        <strong>Date:</strong> <?php echo date('d/M/Y H:i:s'); ?>
                                    </p>
                                </div>
                                <div style="margin-top: 20px; text-align: center;">
                                    <a href="payments_reports.php" class="btn btn-success"><i class="fas fa-check"></i> Continue</a>
                                </div>
                            <?php } elseif ($paymentStatus === 'pending') { ?>
                                <div class="alert alert-warning" role="alert">
                                    <h4 class="alert-heading"><i class="fas fa-hourglass-half"></i> Payment Pending</h4>
                                    <p><?php echo $message; ?></p>
                                </div>
                                <div style="margin-top: 20px; text-align: center;">
                                    <a href="payments_reports.php" class="btn btn-outline-primary">Back</a>
                                </div>
                            <?php } else { ?>
                                <div class="alert alert-danger" role="alert">
                                    <h4 class="alert-heading"><i class="fas fa-times-circle"></i> Payment Failed</h4>
                                    <p><?php echo $message; ?></p>
                                </div>
                                <div style="margin-top: 20px; text-align: center;">
                                    <a href="pay_order_midtrans.php?order_code=<?php echo urlencode($order_code); ?>" class="btn btn-warning">Retry</a>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php require_once('../partials/_footer.php'); ?>
    </div>
    
    <?php require_once('../partials/_scripts.php'); ?>
</body>
</html>
