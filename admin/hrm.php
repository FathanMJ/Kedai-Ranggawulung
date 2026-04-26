<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
check_login();

// Initialize variables
$err = '';
$success = '';

// Delete Staff
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Start transaction
    $mysqli->begin_transaction();
    
    try {
        // Check if staff exists
        $checkStmt = $mysqli->prepare("SELECT staff_id FROM rpos_staff WHERE staff_id = ?");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if($result->num_rows === 0) {
            throw new Exception("Staff member not found");
        }
        
        // Delete staff
        $deleteStmt = $mysqli->prepare("DELETE FROM rpos_staff WHERE staff_id = ?");
        $deleteStmt->bind_param('i', $id);
        $deleteStmt->execute();
        
        if($deleteStmt->affected_rows === 0) {
            throw new Exception("Failed to delete staff member");
        }
        
        // Commit transaction
        $mysqli->commit();
        
        // Log successful deletion
        error_log("Staff member deleted - ID: $id");
        
        $success = "Staff member deleted successfully";
        header("refresh:1; url=hrm.php");
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $mysqli->rollback();
        $err = $e->getMessage();
        error_log("Staff deletion error: " . $e->getMessage());
    }
    
    $checkStmt->close();
    $deleteStmt->close();
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
                            <div class="row align-items-center">
                                <div class="col">
                                    <h3 class="mb-0">Staff Management</h3>
                                </div>
                                <div class="col text-right">
                                    <a href="add_staff.php" class="btn btn-outline-success">
                                        <i class="fas fa-user-plus"></i> Add New Staff
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <?php if($err): ?>
                            <div class="alert alert-danger"><?php echo $err; ?></div>
                        <?php endif; ?>
                        
                        <?php if($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <div class="table-responsive">
                            <table class="table align-items-center table-flush">
                                <thead class="thead-light">
                                    <tr>
                                        <th scope="col">Staff Number</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Email</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $ret = "SELECT * FROM rpos_staff ORDER BY staff_name ASC";
                                    $stmt = $mysqli->prepare($ret);
                                    $stmt->execute();
                                    $res = $stmt->get_result();
                                    
                                    if($res->num_rows === 0): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No staff members found</td>
                                        </tr>
                                    <?php else:
                                        while ($staff = $res->fetch_object()):
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($staff->staff_number); ?></td>
                                            <td><?php echo htmlspecialchars($staff->staff_name); ?></td>
                                            <td><?php echo htmlspecialchars($staff->staff_email); ?></td>
                                            <td>
                                                <a href="update_staff.php?update=<?php echo $staff->staff_id; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-user-edit"></i> Update
                                                </a>
                                                <a href="hrm.php?delete=<?php echo $staff->staff_id; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Are you sure you want to delete this staff member?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    endif;
                                    $stmt->close();
                                    ?>
                                </tbody>
                            </table>
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
</body>
</html>