<?php
session_start();
include('../config/config.php');
include('../config/checklogin.php');
include('../config/midtrans.php');

check_login();

if (!isset($_GET['token']) || !isset($_SESSION['snap_token'])) {
    header("Location: payments_reports.php");
    exit();
}

$token = htmlspecialchars($_GET['token']);
$order_code = isset($_SESSION['order_code_midtrans']) ? htmlspecialchars($_SESSION['order_code_midtrans']) : '';
$amount = isset($_SESSION['order_total_midtrans']) ? htmlspecialchars($_SESSION['order_total_midtrans']) : '';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Midtrans Payment - Kedai Ranggawulung</title>
    <link href="../assets/css/bootstrap.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .payment-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .payment-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .order-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .order-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .loading {
            text-align: center;
            padding: 30px 0;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h1>Secure Payment</h1>
            <p>Powered by Midtrans</p>
        </div>
        
        <div class="order-info">
            <div class="order-info-row">
                <span>Order Code:</span>
                <span style="font-weight: bold;"><?php echo $order_code; ?></span>
            </div>
            <div class="order-info-row">
                <span>Amount:</span>
                <span style="font-weight: bold;">RP <?php echo number_format($amount, 0, ',', '.'); ?></span>
            </div>
        </div>
        
        <div class="loading">
            <div class="spinner"></div>
            <p>Loading payment gateway...</p>
        </div>
        
        <div id="snap-container"></div>
    </div>
    
    <script src="<?php echo MIDTRANS_IS_PRODUCTION ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js'; ?>" 
            data-client-key="<?php echo MIDTRANS_CLIENT_KEY; ?>"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            snap.pay('<?php echo $token; ?>', {
                onSuccess: function(result) {
                    window.location.href = 'midtrans_callback.php?status=success&order_id=<?php echo urlencode($order_code); ?>';
                },
                onPending: function(result) {
                    window.location.href = 'midtrans_callback.php?status=pending&order_id=<?php echo urlencode($order_code); ?>';
                },
                onError: function(result) {
                    window.location.href = 'midtrans_callback.php?status=error&order_id=<?php echo urlencode($order_code); ?>';
                },
                onClose: function() {
                    window.location.href = 'payments_reports.php?error=Payment cancelled';
                }
            });
        });
    </script>
</body>
</html>
