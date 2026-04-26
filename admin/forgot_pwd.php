<?php
session_start();
include('config/config.php');

// Initialize variables
$err = '';
$success = '';

// Handle password reset request
if (isset($_POST['reset'])) {
    // Validate input
    $staff_email = filter_var($_POST['staff_email'], FILTER_SANITIZE_EMAIL);
    
    if(empty($staff_email)) {
        $err = "Please enter your email address";
    } elseif(!filter_var($staff_email, FILTER_VALIDATE_EMAIL)) {
        $err = "Please enter a valid email address";
    } else {
        // Check if email exists
        $stmt = $mysqli->prepare("SELECT staff_id, staff_name FROM rpos_staff WHERE staff_email = ?");
        $stmt->bind_param('s', $staff_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows === 1) {
            $staff = $result->fetch_object();
            
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32));
            $reset_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token in database
            $updateStmt = $mysqli->prepare("UPDATE rpos_staff SET reset_token = ?, reset_expiry = ? WHERE staff_id = ?");
            $updateStmt->bind_param('ssi', $reset_token, $reset_expiry, $staff->staff_id);
            
            if($updateStmt->execute()) {
                // Send reset email
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $reset_token;
                $to = $staff_email;
                $subject = "Password Reset Request";
                $message = "Dear " . htmlspecialchars($staff->staff_name) . ",\n\n";
                $message .= "You have requested to reset your password. Click the link below to reset your password:\n\n";
                $message .= $reset_link . "\n\n";
                $message .= "This link will expire in 1 hour.\n\n";
                $message .= "If you did not request this reset, please ignore this email.\n\n";
                $message .= "Best regards,\nKedai Ranggawulung";
                $headers = "From: noreply@kedairanggawulung.com";
                
                if(mail($to, $subject, $message, $headers)) {
                    $success = "Password reset instructions have been sent to your email";
                    error_log("Password reset email sent to: " . $staff_email);
                } else {
                    $err = "Failed to send reset email. Please try again later.";
                    error_log("Failed to send reset email to: " . $staff_email);
                }
            } else {
                $err = "Failed to process reset request. Please try again later.";
                error_log("Failed to update reset token for: " . $staff_email);
            }
            
            $updateStmt->close();
        } else {
            // Don't reveal if email exists or not
            $success = "If your email is registered, you will receive reset instructions shortly";
            error_log("Password reset attempted for non-existent email: " . $staff_email);
        }
        
        $stmt->close();
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
                            <h1 class="text-white">Reset Password</h1>
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
                                <div class="text-center">
                                    <button type="submit" name="reset" class="btn btn-primary my-4">Reset Password</button>
                                </div>
                            </form>
                            <div class="text-center">
                                <a href="index.php" class="text-light">Back to Login</a>
                            </div>
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