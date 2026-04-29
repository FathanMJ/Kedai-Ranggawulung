<?php
session_start();
include('config/config.php');
require_once('config/code-generator.php');

// Initialize variables
$err = '';
$success = '';
$cus_id = isset($cus_id) ? $cus_id : 'CUS-' . uniqid();

// Handle account creation
if (isset($_POST['addCustomer'])) {
    // Validate input
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phoneno = preg_replace('/\D+/', '', $_POST['customer_phoneno'] ?? '');
    $customer_email = filter_var($_POST['customer_email'], FILTER_SANITIZE_EMAIL);
    $customer_password = $_POST['customer_password'];
    $customer_id = $_POST['customer_id'];
    
    // Validation
    if(empty($customer_name) || empty($customer_phoneno) || empty($customer_email) || empty($customer_password)) {
        $err = "All fields are required";
    } elseif(!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $err = "Please enter a valid email address";
    } elseif(strlen($customer_password) < 8) {
        $err = "Password must be at least 8 characters long";
    } elseif(!preg_match("/^[0-9]{10,15}$/", $customer_phoneno)) {
        $err = "Please enter a valid phone number";
    } else {
        // Check if email already exists
        $checkEmail = $mysqli->prepare("SELECT customer_email FROM rpos_customers WHERE customer_email = ?");
        $checkEmail->bind_param('s', $customer_email);
        $checkEmail->execute();
        $result = $checkEmail->get_result();
        
        if($result->num_rows > 0) {
            $err = "Email already registered";
        } else {
            // Hash password securely
            $hashed_password = password_hash($customer_password, PASSWORD_DEFAULT);
            
            // Insert new customer
            $postQuery = "INSERT INTO rpos_customers (customer_id, customer_name, customer_phoneno, customer_email, customer_password) VALUES(?,?,?,?,?)";
            $postStmt = $mysqli->prepare($postQuery);
            $rc = $postStmt->bind_param('sssss', $customer_id, $customer_name, $customer_phoneno, $customer_email, $hashed_password);
            
            if($postStmt->execute()) {
                // Log successful registration
                error_log("New customer registration: " . $customer_email);
                
                $success = "Account created successfully! Please login.";
                header("refresh:2; url=index.php");
            } else {
                $err = "Registration failed. Please try again later.";
                error_log("Registration error: " . $postStmt->error);
            }
            
            $postStmt->close();
        }
        $checkEmail->close();
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
                            
                            <form method="post" role="form" id="registrationForm">
                                <div class="form-group mb-3">
                                    <div class="input-group input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input class="form-control" required name="customer_name" placeholder="Full Name" type="text" minlength="3" maxlength="50">
                                        <input type="hidden" name="customer_id" value="<?php echo $cus_id; ?>">
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <div class="input-group input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        </div>
                                        <input class="form-control" required name="customer_phoneno" placeholder="Phone Number" type="tel" pattern="[0-9]{10,15}">
                                    </div>
                                </div>
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
                                        <input class="form-control" required name="customer_password" placeholder="Password" type="password" minlength="8">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="text-left">
                                        <button type="submit" name="addCustomer" class="btn btn-primary my-4">Create Account</button>
                                        <a href="index.php" class="btn btn-success pull-right">Log In</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Argon Scripts -->
    <?php require_once('partials/_scripts.php'); ?>
    
    <script>
    document.getElementById('registrationForm').addEventListener('submit', function(e) {
        var password = document.querySelector('input[name="customer_password"]').value;
        if(password.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long');
        }
    });
    </script>
</body>
</html>