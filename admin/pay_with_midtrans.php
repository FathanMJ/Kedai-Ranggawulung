<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
include('config/midtrans.php');

check_login();

// Verify we have a valid token
if (!isset($_GET['token']) || !isset($_SESSION['snap_token'])) {
    header("Location: payments.php");
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
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --accent: #f093fb;
        }

        body {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        
        .payment-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            padding: 50px;
            max-width: 550px;
            width: 100%;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .payment-header .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin: 0 auto 20px;
        }

        .payment-header h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .payment-header p {
            color: #999;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .order-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f1ff 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        
        .order-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 1rem;
            align-items: center;
        }
        
        .order-info-row:last-child {
            margin-bottom: 0;
        }
        
        .order-info-label {
            color: #666;
            font-weight: 500;
        }
        
        .order-info-value {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .order-info-row:last-child .order-info-value {
            color: #10b981;
            font-size: 1.5rem;
        }
        
        .loading {
            text-align: center;
            padding: 40px 0;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading p:first-of-type {
            font-size: 1.1rem;
            color: #333;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .loading p:last-of-type {
            color: #999;
            font-size: 0.9rem;
        }
        
        .payment-info {
            background: linear-gradient(135deg, #e8f4f8 0%, #e0f0f8 100%);
            border: 2px solid #b8e0e8;
            color: #0c5977;
            padding: 18px;
            border-radius: 12px;
            font-size: 0.95rem;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .payment-info strong {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 10px;
            color: #0c5977;
        }

        .payment-info ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }

        .payment-info li {
            margin-bottom: 5px;
        }
        
        .cancel-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e0e0e0;
        }
        
        .cancel-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .cancel-link a:hover {
            color: #764ba2;
            gap: 0.8rem;
        }

        @media (max-width: 768px) {
            .payment-container {
                padding: 30px;
            }

            .payment-header h1 {
                font-size: 1.5rem;
            }

            .order-info {
                padding: 15px;
            }
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
                <span class="order-info-label">Order Code:</span>
                <span class="order-info-value"><?php echo $order_code; ?></span>
            </div>
            <div class="order-info-row">
                <span class="order-info-label">Amount:</span>
                <span class="order-info-value">RP <?php echo number_format($amount, 0, ',', '.'); ?></span>
            </div>
        </div>
        
        <div class="payment-info">
            <strong>ℹ️ Payment Methods Available:</strong>
            <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                <li>Credit/Debit Card (Visa, Mastercard)</li>
                <li>Bank Transfer (BCA, BNI, Mandiri, CIMB)</li>
                <li>E-Wallet (GCash, OVO, Dana, LinkAja)</li>
                <li>GoPay, ShopeePay</li>
            </ul>
        </div>
        
        <div class="loading">
            <div class="spinner"></div>
            <p>Loading payment gateway...</p>
            <p style="font-size: 12px; color: #999; margin-top: 10px;">
                If payment page doesn't load, click the button below
            </p>
        </div>
        
        <div id="snap-container"></div>
        
        <div class="cancel-link">
            <a href="payments.php">← Back to Payments</a>
        </div>
    </div>
    
    <!-- Include Midtrans Snap JavaScript -->
    <script src="<?php echo MIDTRANS_IS_PRODUCTION ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js'; ?>" 
            data-client-key="<?php echo MIDTRANS_CLIENT_KEY; ?>"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Display Snap payment UI
            snap.pay('<?php echo $token; ?>', {
                onSuccess: function(result) {
                    // Payment successful
                    window.location.href = 'midtrans_callback.php?status=success&order_id=<?php echo urlencode($order_code); ?>';
                },
                onPending: function(result) {
                    // Payment pending
                    window.location.href = 'midtrans_callback.php?status=pending&order_id=<?php echo urlencode($order_code); ?>';
                },
                onError: function(result) {
                    // Payment failed
                    window.location.href = 'midtrans_callback.php?status=error&order_id=<?php echo urlencode($order_code); ?>';
                },
                onClose: function() {
                    // User closed payment window
                    window.location.href = 'payments.php?error=Payment cancelled by user';
                }
            });
        });
    </script>
</body>
</html>
