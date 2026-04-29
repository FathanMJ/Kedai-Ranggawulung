<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
include('config/code-generator.php');

check_login();

// Initialize variables
$err = '';
$success = '';
$alpha = $alpha ?? 'XXXX';
$beta = $beta ?? '0000';

// Add Staff
if (isset($_POST['addStaff'])) {
    // Validate input
    $staff_number = trim($_POST['staff_number'] ?? '');
    $staff_name = trim($_POST['staff_name'] ?? '');
    $staff_email = filter_var($_POST['staff_email'], FILTER_SANITIZE_EMAIL);
    $staff_password = $_POST['staff_password'];
    
    // Validation
    if(empty($staff_number) || empty($staff_name) || empty($staff_email) || empty($staff_password)) {
        $err = "All fields are required";
    } elseif(!filter_var($staff_email, FILTER_VALIDATE_EMAIL)) {
        $err = "Please enter a valid email address";
    } elseif(strlen($staff_password) < 8) {
        $err = "Password must be at least 8 characters long";
    } else {
        // Check if email already exists
        $checkEmail = $mysqli->prepare("SELECT staff_email FROM rpos_staff WHERE staff_email = ?");
        $checkEmail->bind_param('s', $staff_email);
        $checkEmail->execute();
        $result = $checkEmail->get_result();
        
        if($result->num_rows > 0) {
            $err = "Email already registered";
        } else {
            // Hash password securely
            $hashed_password = password_hash($staff_password, PASSWORD_DEFAULT);
            
            // Insert new staff
            $postQuery = "INSERT INTO rpos_staff (staff_number, staff_name, staff_email, staff_password) VALUES(?,?,?,?)";
            $postStmt = $mysqli->prepare($postQuery);
            $rc = $postStmt->bind_param('ssss', $staff_number, $staff_name, $staff_email, $hashed_password);
            
            if($postStmt->execute()) {
                // Log successful staff addition
                error_log("New staff added: " . $staff_email);
                
                $success = "Staff added successfully";
                header("refresh:2; url=hrm.php");
            } else {
                $err = "Failed to add staff. Please try again later.";
                error_log("Staff addition error: " . $postStmt->error);
            }
            
            $postStmt->close();
        }
        $checkEmail->close();
    }
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
                <div class="header-body">
                </div>
            </div>
        </div>
        
        <!-- Page content -->
        <div class="container-fluid mt--8">
            <div class="row">
                <div class="col">
                    <div class="card shadow">
                        <div class="card-header border-0">
                            <h3>Add New Staff</h3>
                        </div>
                        
                        <?php if($err): ?>
                            <div class="alert alert-danger"><?php echo $err; ?></div>
                        <?php endif; ?>
                        
                        <?php if($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <form method="POST" role="form" id="staffForm">
                                <div class="form-row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Staff Number</label>
                                            <input type="text" name="staff_number" class="form-control" 
                                                   value="<?php echo $alpha; ?>-<?php echo $beta; ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Staff Name</label>
                                            <input type="text" name="staff_name" class="form-control" 
                                                   required minlength="3" maxlength="50">
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="form-row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Staff Email</label>
                                            <input type="email" name="staff_email" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Staff Password</label>
                                            <input type="password" name="staff_password" class="form-control" 
                                                   required minlength="8">
                                        </div>
                                    </div>
                                </div>
                                
                                <br>
                                
                                <div class="form-row">
                                    <div class="col-md-6">
                                        <button type="submit" name="addStaff" class="btn btn-success">
                                            <i class="fas fa-user-plus"></i> Add Staff
                                        </button>
                                        <a href="hrm.php" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <?php require_once('partials/_footer.php'); ?>
        </div>
    </div>
    
    <!-- Argon Scripts -->
    <?php require_once('partials/_scripts.php'); ?>
    
    <script>
    document.getElementById('staffForm').addEventListener('submit', function(e) {
        var password = document.querySelector('input[name="staff_password"]').value;
        if(password.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long');
        }
    });
    </script>
</body>
</html>