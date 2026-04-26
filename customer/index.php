<?php
session_start();
include('config/config.php');

// Initialize variables
$err = '';
$success = '';

// Check if already logged in
if(isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
    header("location:booking.php");
    exit();
}

// Handle login
if (isset($_POST['login'])) {
    // Validate input
    $customer_email = filter_var($_POST['customer_email'], FILTER_SANITIZE_EMAIL);
    $customer_password = $_POST['customer_password'];
    
    if(empty($customer_email) || empty($customer_password)) {
        $err = "Please enter both email and password";
    } elseif(!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $err = "Please enter a valid email address";
    } else {
        // Check login attempts
        if(isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 3) {
            if(time() - $_SESSION['last_attempt'] < 300) { // 5 minutes lockout
                $err = "Too many failed attempts. Please try again in " . ceil((300 - (time() - $_SESSION['last_attempt'])) / 60) . " minutes";
            } else {
                $_SESSION['login_attempts'] = 0;
            }
        }
        
        if(empty($err)) {
            // Use prepared statement
            $stmt = $mysqli->prepare("SELECT customer_id, customer_email, customer_password FROM rpos_customers WHERE customer_email = ?");
            $stmt->bind_param('s', $customer_email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Support hash modern (password_hash) dan hash legacy (sha1) dari dump lama.
                $is_valid_password = password_verify($customer_password, $user['customer_password']) ||
                    hash_equals($user['customer_password'], sha1($customer_password));
                if($is_valid_password) {
                    // Reset login attempts on successful login
                    unset($_SESSION['login_attempts']);
                    unset($_SESSION['last_attempt']);
                    
                    // Set session
                    $_SESSION['customer_id'] = $user['customer_id'];
                    $_SESSION['customer_email'] = $user['customer_email'];
                    
                    // Log successful login
                    error_log("Successful login for user: " . $customer_email);
                    
                    // Redirect
                    header("location:booking.php");
                    exit();
                } else {
                    // Increment failed attempts
                    $_SESSION['login_attempts'] = isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] + 1 : 1;
                    $_SESSION['last_attempt'] = time();
                    
                    $err = "Invalid email or password";
                }
            } else {
                $err = "Invalid email or password";
            }
            
            $stmt->close();
        }
    }
}

require_once('partials/_head.php');
?>

<body class="bg-dark">
    <div class="main-content">
        <div class="header bg-gradient-primar py-7">
            <div class="container">
                <div class="header-body text-center mb-7">
                    <div class="row justify-content-center">
                        <div class="col-lg-5 col-md-6">
                            <h1 class="text-white">Restaurant Point Of Sale</h1>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Page content -->
        <div class="container mt--8 pb-5">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">
                    <div class="card bg-secondary shadow border-0">
                        <div class="card-body px-lg-5 py-lg-5">
                            <?php if($err): ?>
                                <div class="alert alert-danger"><?php echo $err; ?></div>
                            <?php endif; ?>
                            
                            <?php if($success): ?>
                                <div class="alert alert-success"><?php echo $success; ?></div>
                            <?php endif; ?>
                            
                            <form method="post" role="form">
                                <div class="form-group mb-3">
                                    <div class="input-group input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="ni ni-email-83"></i></span>
                                        </div>
                                        <input class="form-control" required name="customer_email" placeholder="Email" type="email">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="input-group input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
                                        </div>
                                        <input class="form-control" required name="customer_password" placeholder="Password" type="password">
                                    </div>
                                </div>
                                <div class="custom-control custom-control-alternative custom-checkbox">
                                    <input class="custom-control-input" id="customCheckLogin" type="checkbox">
                                    <label class="custom-control-label" for="customCheckLogin">
                                        <span class="text-muted">Remember me</span>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <div class="text-left">
                                        <button type="submit" name="login" class="btn btn-primary my-4">Log In</button>
                                        <a href="create_account.php" class="btn btn-success pull-right">Create Account</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="row mt-3"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Argon Scripts -->
    <?php require_once('partials/_scripts.php'); ?>
</body>

</html>