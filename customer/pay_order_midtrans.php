<?php
session_start();
include('../config/config.php');
include('../config/checklogin.php');
include('../config/code-generator.php');
include('../config/midtrans.php');
include('../config/MidtransHelper.php');

check_login();

if (isset($_POST['pay'])) {
    if (empty($_POST["pay_code"]) || empty($_POST["pay_method"])) {
        $err = "Blank Values Not Accepted";
    } else {
        $pay_code = sanitizeInput($_POST['pay_code']);
        $order_code = sanitizeInput($_GET['order_code']);
        $customer_id = sanitizeInput($_GET['customer_id']);
        $pay_method = sanitizeInput($_POST['pay_method']);
        
        // Get order details
        $checkOrder = "SELECT (prod_price * prod_qty) as order_total, order_status, customer_name, prod_name FROM rpos_orders WHERE order_code = ?";
        $checkStmt = $mysqli->prepare($checkOrder);
        $checkStmt->bind_param('s', $order_code);
        $checkStmt->execute();
        $orderResult = $checkStmt->get_result();
        $orderRow = $orderResult->fetch_object();
        
        if (!$orderRow) {
            $err = "Order not found";
        } elseif ($orderRow->order_status == 'Paid') {
            $err = "Order has already been paid";
        } elseif ($pay_method == 'Midtrans') {
            // Handle Midtrans payment
            $midtrans = new MidtransHelper();
            
            // Prepare customer details
            $customerDetails = [
                'name' => $orderRow->customer_name,
                'email' => $_SESSION['email'] ?? 'customer@kedairanggawulung.com',
                'phone' => $_SESSION['phone'] ?? ''
            ];
            
            // Prepare item details
            $itemDetails = [
                [
                    'id' => $order_code,
                    'price' => (int)$orderRow->order_total,
                    'quantity' => 1,
                    'name' => $orderRow->prod_name
                ]
            ];
            
            // Get Snap token
            $token = $midtrans->getSnapToken($order_code, $orderRow->order_total, $customerDetails, $itemDetails);
            
            if ($token) {
                // Store payment attempt in database
                $pay_id = isset($payid) ? $payid : uniqid('PAY-');
                $insertPayment = "INSERT INTO rpos_payments (pay_id, pay_code, order_code, customer_id, pay_amt, pay_method, payment_status) VALUES(?,?,?,?,?,?,?)";
                $paymentStmt = $mysqli->prepare($insertPayment);
                $status = 'pending';
                $paymentStmt->bind_param('sssssss', $pay_id, $pay_code, $order_code, $customer_id, $orderRow->order_total, $pay_method, $status);
                
                if ($paymentStmt->execute()) {
                    $_SESSION['snap_token'] = $token;
                    $_SESSION['order_code_midtrans'] = $order_code;
                    $_SESSION['order_total_midtrans'] = $orderRow->order_total;
                    $success = "Redirecting to payment gateway...";
                    header("Location: pay_with_midtrans.php?token=" . urlencode($token) . "&order_code=" . urlencode($order_code));
                    exit();
                } else {
                    $err = "Failed to create payment record";
                }
            } else {
                $err = "Failed to create payment token. Please check Midtrans configuration.";
            }
        } else {
            // Handle traditional payment methods
            $order_status = "Paid";
            $pay_id = isset($payid) ? $payid : uniqid('PAY-');
            
            $postQuery = "INSERT INTO rpos_payments (pay_id, pay_code, order_code, customer_id, pay_amt, pay_method, payment_status) VALUES(?,?,?,?,?,?,?)";
            $upQry = "UPDATE rpos_orders SET order_status = ? WHERE order_code = ?";
            
            $postStmt = $mysqli->prepare($postQuery);
            $upStmt = $mysqli->prepare($upQry);
            $paymentStatus = "completed";
            
            $postStmt->bind_param('sssssis', $pay_id, $pay_code, $order_code, $customer_id, $orderRow->order_total, $pay_method, $paymentStatus);
            $upStmt->bind_param('ss', $order_status, $order_code);
            
            $postStmt->execute();
            $upStmt->execute();
            
            if ($upStmt && $postStmt) {
                $success = "Payment Recorded Successfully";
                header("refresh:2; url=payments_reports.php");
            } else {
                $err = "Please Try Again Or Try Later";
            }
        }
    }
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

require_once('../partials/_head.php');
?>

<body>
    <?php require_once('../partials/_sidebar.php'); ?>
    
    <div class="main-content">
        <?php require_once('../partials/_topnav.php'); ?>
        
        <?php
        $order_code = sanitizeInput($_GET['order_code']);
        $ret = "SELECT * FROM rpos_orders WHERE order_code = ?";
        $stmt = $mysqli->prepare($ret);
        $stmt->bind_param('s', $order_code);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_object()) {
            $total = ($row->prod_price * $row->prod_qty);
        ?>
        
        <div style="background-image: url(../assets/img/theme/restro00.jpg); background-size: cover;" class="header pb-8 pt-5 pt-md-8">
            <span class="mask bg-gradient-dark opacity-8"></span>
            <div class="container-fluid"><div class="header-body"></div></div>
        </div>
        
        <div class="container-fluid mt--8">
            <div class="row">
                <div class="col">
                    <div class="card shadow">
                        <div class="card-header border-0">
                            <h3>Payment Gateway - Order <?php echo $order_code; ?></h3>
                        </div>
                        <div class="card-body">
                            <?php if (isset($err)) { ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <span class="alert-inner--icon"><i class="fas fa-exclamation-circle"></i></span>
                                    <span class="alert-inner--text"><strong>Error:</strong> <?php echo $err; ?></span>
                                </div>
                            <?php } ?>
                            
                            <?php if (isset($success)) { ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <span class="alert-inner--icon"><i class="fas fa-check-circle"></i></span>
                                    <span class="alert-inner--text"><strong>Success:</strong> <?php echo $success; ?></span>
                                </div>
                            <?php } ?>
                            
                            <form method="POST" enctype="multipart/form-data">
                                <div class="form-row">
                                    <div class="col-md-6">
                                        <label>Payment ID</label>
                                        <input type="text" name="pay_id" readonly value="<?php echo isset($payid) ? $payid : ''; ?>" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label>Payment Code</label>
                                        <input type="text" name="pay_code" placeholder="Reference number" class="form-control" required>
                                    </div>
                                </div>
                                <hr>
                                
                                <div class="form-row">
                                    <div class="col-md-6">
                                        <label>Total Amount (RP)</label>
                                        <input type="number" readonly value="<?php echo $total; ?>" class="form-control" style="font-weight: bold; font-size: 16px;">
                                    </div>
                                    <div class="col-md-6">
                                        <label>Payment Method</label>
                                        <select class="form-control" name="pay_method" required>
                                            <option value="">-- Select --</option>
                                            <option value="Cash">Cash</option>
                                            <option value="Paypal">Paypal</option>
                                            <option value="Midtrans">Midtrans (Secure Online Payment)</option>
                                        </select>
                                    </div>
                                </div>
                                <br>
                                
                                <div class="form-row">
                                    <div class="col-md-6">
                                        <input type="submit" name="pay" value="Proceed" class="btn btn-success btn-block">
                                    </div>
                                    <div class="col-md-6">
                                        <a href="payments_reports.php" class="btn btn-outline-secondary btn-block">Cancel</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php } ?>
        
        <?php require_once('../partials/_footer.php'); ?>
    </div>
    
    <?php require_once('../partials/_scripts.php'); ?>
</body>
</html>
