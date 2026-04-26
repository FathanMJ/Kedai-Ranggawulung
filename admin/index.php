<?php
session_start();
include('config/config.php');

// Initialize variables
$err = '';
$success = '';

// Check if already logged in
if(isset($_SESSION['staff_id']) && !empty($_SESSION['staff_id'])) {
    header("location:dashboard.php");
    exit();
}

// Handle login
if (isset($_POST['login'])) {
    // Validate input
    $staff_email = filter_var($_POST['staff_email'], FILTER_SANITIZE_EMAIL);
    $staff_password = $_POST['staff_password'];
    
    if(empty($staff_email) || empty($staff_password)) {
        $err = "Please enter both email and password";
    } elseif(!filter_var($staff_email, FILTER_VALIDATE_EMAIL)) {
        $err = "Please enter a valid email address";
    } else {
        // Check login attempts
        if(isset($_SESSION['admin_login_attempts']) && $_SESSION['admin_login_attempts'] >= 3) {
            if(time() - $_SESSION['admin_last_attempt'] < 300) { // 5 minutes lockout
                $err = "Too many failed attempts. Please try again in " . ceil((300 - (time() - $_SESSION['admin_last_attempt'])) / 60) . " minutes";
            } else {
                $_SESSION['admin_login_attempts'] = 0;
            }
        }
        
        if(empty($err)) {
            // Use prepared statement
            $stmt = $mysqli->prepare("SELECT staff_id, staff_email, staff_password FROM rpos_staff WHERE staff_email = ?");
            $stmt->bind_param('s', $staff_email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Support hash modern (password_hash) dan hash legacy (sha1) dari dump lama.
                $is_valid_password = password_verify($staff_password, $user['staff_password']) ||
                    hash_equals($user['staff_password'], sha1($staff_password));
                if($is_valid_password) {
                    // Reset login attempts on successful login
                    unset($_SESSION['admin_login_attempts']);
                    unset($_SESSION['admin_last_attempt']);
                    
                    // Set session
                    $_SESSION['staff_id'] = $user['staff_id'];
                    $_SESSION['staff_email'] = $user['staff_email'];
                    
                    // Log successful login
                    error_log("Successful admin login: " . $staff_email);
                    
                    // Redirect
                    header("location:dashboard.php");
                    exit();
                } else {
                    // Increment failed attempts
                    $_SESSION['admin_login_attempts'] = isset($_SESSION['admin_login_attempts']) ? $_SESSION['admin_login_attempts'] + 1 : 1;
                    $_SESSION['admin_last_attempt'] = time();
                    
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
                            <h1 class="text-white">Kedai Ranggawulung</h1>
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
                                        <input class="form-control" required name="staff_email" placeholder="Email" type="email">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="input-group input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
                                        </div>
                                        <input class="form-control" required name="staff_password" placeholder="Password" type="password">
                                    </div>
                                </div>
                                <div class="custom-control custom-control-alternative custom-checkbox">
                                    <input class="custom-control-input" id="customCheckLogin" type="checkbox">
                                    <label class="custom-control-label" for="customCheckLogin">
                                        <span class="text-muted">Remember me</span>
                                    </label>
                                </div>
                                <div class="text-center">
                                    <button type="submit" name="login" class="btn btn-primary my-4">Log In</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-6">
                            <a href="forgot_pwd.php" class="text-light"><small>Forgot password?</small></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Argon Scripts -->
    <?php require_once('partials/_scripts.php'); ?>
</body>
</html>