<?php
/**
 * Midtrans Setup Script
 * This script helps you configure Midtrans and run migrations
 * Access via: http://localhost/Project%201/percobaan/Kedai/Restro/admin/setup/index.php
 */

session_start();

// Check if we have database connection
$db_connected = false;
try {
    include('../config/config.php');
    if ($mysqli) {
        $db_connected = true;
    }
} catch (Exception $e) {
    $db_connected = false;
}

$setup_complete = false;
$messages = [];
$errors = [];

// Handle setup submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'configure_midtrans') {
        $serverKey = trim($_POST['server_key']);
        $clientKey = trim($_POST['client_key']);
        $merchantId = trim($_POST['merchant_id']);
        $isProduction = isset($_POST['is_production']) ? 1 : 0;
        
        if (empty($serverKey) || empty($clientKey) || empty($merchantId)) {
            $errors[] = "All Midtrans credentials are required!";
        } else {
            // Update midtrans.php configuration
            $configFile = '../config/midtrans.php';
            $configContent = file_get_contents($configFile);
            
            // Replace values
            $configContent = preg_replace(
                "/define\('MIDTRANS_SERVER_KEY',\s*'[^']*'\);/",
                "define('MIDTRANS_SERVER_KEY', '$serverKey');",
                $configContent
            );
            
            $configContent = preg_replace(
                "/define\('MIDTRANS_CLIENT_KEY',\s*'[^']*'\);/",
                "define('MIDTRANS_CLIENT_KEY', '$clientKey');",
                $configContent
            );
            
            $configContent = preg_replace(
                "/define\('MIDTRANS_IS_PRODUCTION',\s*(?:true|false)\);/",
                "define('MIDTRANS_IS_PRODUCTION', " . ($isProduction ? 'true' : 'false') . ");",
                $configContent
            );
            
            $configContent = preg_replace(
                "/define\('MERCHANT_ID',\s*'[^']*'\);/",
                "define('MERCHANT_ID', '$merchantId');",
                $configContent
            );
            
            if (file_put_contents($configFile, $configContent)) {
                // Also update customer config
                $customerConfigFile = '../../customer/config/midtrans.php';
                file_put_contents($customerConfigFile, $configContent);
                
                $messages[] = "Midtrans configuration updated successfully!";
            } else {
                $errors[] = "Failed to write configuration file. Check file permissions.";
            }
        }
    } elseif ($action === 'run_migration') {
        // Run SQL migration
        $sqlFile = '../migrations/001_add_midtrans_tables.sql';
        if (file_exists($sqlFile)) {
            $sqlContent = file_get_contents($sqlFile);
            $queries = array_filter(array_map('trim', explode(';', $sqlContent)));
            
            $executedCount = 0;
            foreach ($queries as $query) {
                if (!empty($query) && !preg_match('/^--/', $query)) {
                    try {
                        if ($mysqli->query($query)) {
                            $executedCount++;
                        } else {
                            $errors[] = "Query error: " . $mysqli->error;
                        }
                    } catch (Exception $e) {
                        $errors[] = "Exception: " . $e->getMessage();
                    }
                }
            }
            
            if (empty($errors)) {
                $messages[] = "Database migration completed! ($executedCount queries executed)";
                $setup_complete = true;
            }
        } else {
            $errors[] = "Migration file not found!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Midtrans Setup - Kedai Ranggawulung</title>
    <link href="../assets/css/bootstrap.css" rel="stylesheet">
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
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 30px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .setup-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .setup-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }

        .setup-header::before {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -50px;
            right: -50px;
        }

        .setup-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .setup-header p {
            opacity: 0.95;
            margin-top: 0.5rem;
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
        }

        .setup-content {
            padding: 40px;
        }

        .progress-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }

        .progress-indicator::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 0;
            right: 0;
            height: 3px;
            background: #e0e0e0;
            z-index: 0;
        }

        .progress-step {
            text-align: center;
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .progress-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e0e0e0;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .progress-step.completed .progress-circle {
            background: var(--success);
            border-color: var(--success);
            color: white;
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }

        .progress-circle i {
            display: none;
        }

        .progress-step.completed .progress-circle i {
            display: inline;
        }

        .progress-step small {
            color: #666;
            font-weight: 600;
            display: block;
        }

        .progress-step.completed small {
            color: var(--success);
        }

        .setup-step {
            margin-bottom: 30px;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 15px;
            border-left: 5px solid var(--primary);
            transition: all 0.3s ease;
        }

        .setup-step:hover {
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.1);
        }

        .setup-step h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: #333;
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.7rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 0.8rem;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .alert {
            margin-bottom: 20px;
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .btn {
            border-radius: 10px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .btn-block {
            width: 100%;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .info-box {
            background: linear-gradient(135deg, #e7f3ff 0%, #f0e7ff 100%);
            border-left: 5px solid var(--primary);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
        }

        .info-box strong {
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .info-box a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .info-box a:hover {
            text-decoration: underline;
        }

        .form-check {
            padding: 1rem;
            background: white;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .form-check:hover {
            border-color: var(--primary);
            background: #f8f9fa;
        }

        .form-check-input {
            width: 20px;
            height: 20px;
            margin-top: 0.3rem;
            cursor: pointer;
            accent-color: var(--primary);
        }

        .form-check-label {
            cursor: pointer;
            margin-left: 0.5rem;
        }

        .success-check {
            color: var(--success);
            font-size: 1.2rem;
        }

        .error-mark {
            color: var(--danger);
            font-size: 1.2rem;
        }

        .help-text {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
            display: block;
        }

        @media (max-width: 768px) {
            .setup-content {
                padding: 20px;
            }

            .setup-header h1 {
                font-size: 1.8rem;
            }

            .progress-indicator {
                font-size: 0.9rem;
            }

            .progress-circle {
                width: 40px;
                height: 40px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1>Midtrans Setup Wizard</h1>
            <p>Configure Midtrans Payment Gateway for Kedai Ranggawulung</p>
        </div>
        
        <div class="setup-content">
            <!-- Display Messages -->
            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong><span class="success-check">✓</span> Success!</strong> <?php echo $msg; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong><span class="error-mark">✕</span> Error!</strong> <?php echo $err; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Progress Indicators -->
            <div class="progress-indicator">
                <div class="progress-step <?php echo $db_connected ? 'completed' : ''; ?>">
                    <div class="progress-circle">1</div>
                    <small>Database</small>
                </div>
                <div class="progress-step <?php echo !empty($messages) && strpos($messages[0], 'configuration') ? 'completed' : ''; ?>">
                    <div class="progress-circle">2</div>
                    <small>Configure</small>
                </div>
                <div class="progress-step <?php echo $setup_complete ? 'completed' : ''; ?>">
                    <div class="progress-circle">3</div>
                    <small>Migrate DB</small>
                </div>
            </div>
            
            <!-- Step 1: Database Connection Check -->
            <div class="setup-step">
                <h3>Step 1: Check Database Connection</h3>
                <?php if ($db_connected): ?>
                    <p><span class="success-check">✓</span> Database connection is active!</p>
                    <p><small class="text-muted">Database: <strong>projek</strong></small></p>
                <?php else: ?>
                    <p><span class="error-mark">✕</span> Database connection failed!</p>
                    <p><small>Please check your database configuration in admin/config/config.php</small></p>
                <?php endif; ?>
            </div>
            
            <!-- Step 2: Configure Midtrans -->
            <div class="setup-step">
                <h3>Step 2: Configure Midtrans Credentials</h3>
                <div class="info-box">
                    <strong>ℹ️ Where to find your credentials:</strong><br>
                    1. Go to <a href="https://dashboard.midtrans.com/" target="_blank">Midtrans Dashboard</a><br>
                    2. Navigate to Settings → Configuration<br>
                    3. Copy your Server Key and Client Key
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="configure_midtrans">
                    
                    <div class="form-group">
                        <label for="server_key">Midtrans Server Key *</label>
                        <input type="password" class="form-control" id="server_key" name="server_key" 
                               placeholder="SB-Mid-xxxxxxxxxxxxxxxxxxxxxx" required>
                        <small class="form-text text-muted">Keep this secret!</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="client_key">Midtrans Client Key *</label>
                        <input type="text" class="form-control" id="client_key" name="client_key" 
                               placeholder="SB-Mid-xxxxxxxxxxxxxxxxxxxxxx" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="merchant_id">Merchant ID *</label>
                        <input type="text" class="form-control" id="merchant_id" name="merchant_id" 
                               placeholder="Your Merchant ID" required>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_production" name="is_production">
                        <label class="form-check-label" for="is_production">
                            <strong>Production Mode</strong> (uncheck for sandbox/testing)
                        </label>
                        <small class="d-block text-muted">Keep unchecked for testing, check only when ready for live payments</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i> Save Midtrans Configuration
                    </button>
                </form>
            </div>
            
            <!-- Step 3: Run Migration -->
            <div class="setup-step <?php echo $setup_complete ? 'alert alert-success' : ''; ?>">
                <h3>Step 3: Update Database Schema</h3>
                <p>Add new payment tables and columns to support Midtrans and payment tracking.</p>
                
                <div class="info-box">
                    <strong>ℹ️ This migration will:</strong><br>
                    ✓ Add payment status tracking columns<br>
                    ✓ Create audit trail table<br>
                    ✓ Create payment methods reference table<br>
                    ✓ Add necessary indexes for performance
                </div>
                
                <?php if ($db_connected): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="run_migration">
                        <button type="submit" class="btn btn-success btn-block" 
                                <?php echo $setup_complete ? 'disabled' : ''; ?>>
                            <i class="fas fa-database"></i> Run Database Migration
                        </button>
                    </form>
                <?php else: ?>
                    <p class="alert alert-warning">
                        <span class="error-mark">✕</span> Database connection required to run migration
                    </p>
                <?php endif; ?>
                
                <?php if ($setup_complete): ?>
                    <p class="alert alert-success mt-3">
                        <strong><span class="success-check">✓</span> Setup Complete!</strong><br>
                        Your system is now configured for Midtrans payments. 
                        Proceed to the <a href="../payments.php">Payments page</a> to start accepting payments.
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Quick Start Guide -->
            <div class="setup-step" style="background: #fffacd; border-left-color: #ffc107;">
                <h3><i class="fas fa-lightbulb"></i> Quick Start Guide</h3>
                <ol>
                    <li>Complete all 3 setup steps above</li>
                    <li>Go to <strong>admin/payments.php</strong> and click "Pay Order"</li>
                    <li>Select <strong>"Midtrans"</strong> as payment method</li>
                    <li>Complete the payment through Midtrans Snap interface</li>
                    <li>Payment status updates automatically</li>
                </ol>
                <p><small><strong>For Testing:</strong> Use test card number 4811111111111114 with any future expiry date</small></p>
            </div>
            
            <!-- Help Section -->
            <div class="setup-step" style="background: #f0f8ff; border-left-color: #4169e1;">
                <h3><i class="fas fa-question-circle"></i> Need Help?</h3>
                <ul>
                    <li><a href="../MIDTRANS_SETUP.md" target="_blank">Read Full Setup Documentation</a></li>
                    <li><a href="https://docs.midtrans.com" target="_blank">Midtrans API Documentation</a></li>
                    <li><a href="https://midtrans.com/help" target="_blank">Midtrans Support</a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/bootstrap.js"></script>
    <script src="../assets/js/jquery.js"></script>
    <script>
        // Show password toggle
        document.querySelectorAll('[type="password"]').forEach(field => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-outline-secondary';
            btn.innerHTML = '<i class="fas fa-eye"></i>';
            btn.style.position = 'absolute';
            btn.style.right = '10px';
            btn.style.top = '50%';
            btn.style.transform = 'translateY(-50%)';
            btn.onclick = (e) => {
                e.preventDefault();
                field.type = field.type === 'password' ? 'text' : 'password';
                btn.innerHTML = field.type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            };
            field.parentElement.style.position = 'relative';
            field.parentElement.appendChild(btn);
        });
    </script>
</body>
</html>
