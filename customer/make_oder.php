<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
include('config/code-generator.php');

check_login();

if (isset($_POST['make'])) {
    // Prevent Posting Blank Values
    if (empty($_POST["order_code"]) || empty($_POST["customer_name"]) || empty($_POST['prod_price']) || empty($_POST['prod_qty']) || empty($_POST['meja_id'])) {
        $err = "Blank Values Not Accepted";
    } else {
        $order_id = $_POST['order_id'];
        $order_code = $_POST['order_code'];
        $customer_id = $_SESSION['customer_id'];
        $customer_name = $_POST['customer_name'];
        $prod_id = $_POST['prod_id'];
        $prod_name = $_POST['prod_name'];
        $prod_price = $_POST['prod_price'];
        $prod_qty = $_POST['prod_qty'];
        $meja_id = $_POST['meja_id'];

        // Check product stock
        $stockCheckQuery = "SELECT prod_stock FROM rpos_products WHERE prod_id = ?";
        $stockCheckStmt = $mysqli->prepare($stockCheckQuery);
        $stockCheckStmt->bind_param('s', $prod_id);
        $stockCheckStmt->execute();
        $stockCheckStmt->bind_result($prod_stock);
        $stockCheckStmt->fetch();
        $stockCheckStmt->close();

        if ($prod_stock >= $prod_qty) {
            // Insert order details into rpos_orders table
            $postQuery = "INSERT INTO rpos_orders (prod_qty, order_id, order_code, customer_id, customer_name, prod_id, prod_name, prod_price, meja_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $postStmt = $mysqli->prepare($postQuery);
            $rc = $postStmt->bind_param('sssssssss', $prod_qty, $order_id, $order_code, $customer_id, $customer_name, $prod_id, $prod_name, $prod_price, $meja_id);
            $postStmt->execute();

            if ($postStmt) {
                // Update product stock
                $updateStockQuery = "UPDATE rpos_products SET prod_stock = prod_stock - ? WHERE prod_id = ?";
                $updateStockStmt = $mysqli->prepare($updateStockQuery);
                $updateStockStmt->bind_param('is', $prod_qty, $prod_id);
                $updateStockStmt->execute();
                $updateStockStmt->close();

                $success = "Order Submitted";
                header("refresh:1; url=payments.php");
            } else {
                $err = "Please Try Again Or Try Later";
            }
        } else {
            $err = "Insufficient stock!";
        }
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
        <div style="background-image: url(../admin/assets/img/theme/restro00.jpg); background-size: cover;" class="header pb-8 pt-5 pt-md-8">
            <span class="mask bg-gradient-dark opacity-8"></span>
            <div class="container-fluid">
                <div class="header-body"></div>
            </div>
        </div>
        <!-- Page content -->
        <div class="container-fluid mt--8">
            <!-- Table -->
            <div class="row">
                <div class="col">
                    <div class="card shadow">
                        <div class="card-header border-0">
                            <h3>Please Fill All Fields</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="form-row">
                                    <div class="col-md-6">
                                        <label>Customer Name</label>
                                        <?php
                                        // Load All Customers
                                        $customer_id = $_SESSION['customer_id'];
                                        $ret = "SELECT * FROM rpos_customers WHERE customer_id = '$customer_id'";
                                        $stmt = $mysqli->prepare($ret);
                                        $stmt->execute();
                                        $res = $stmt->get_result();
                                        while ($cust = $res->fetch_object()) {
                                        ?>
                                            <input class="form-control" readonly name="customer_name" value="<?php echo $cust->customer_name; ?>">
                                        <?php } ?>
                                        <input type="hidden" name="order_id" value="<?php echo $orderid; ?>" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label>Order Code</label>
                                        <input type="text" readonly name="order_code" value="<?php echo $alpha; ?>-<?php echo $beta; ?>" class="form-control">
                                    </div>
                                </div>
                                <hr>
                                <?php
                                $prod_id = $_GET['prod_id'];
                                $ret = "SELECT * FROM rpos_products WHERE prod_id = '$prod_id'";
                                $stmt = $mysqli->prepare($ret);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                while ($prod = $res->fetch_object()) {
                                ?>
                                    <input type="hidden" name="prod_id" value="<?php echo $prod->prod_id; ?>">
                                    <input type="hidden" name="prod_name" value="<?php echo $prod->prod_name; ?>">
                                    <div class="form-row">
                                        <div class="col-md-6">
                                            <label>Product Price (RP)</label>
                                            <input type="text" readonly name="prod_price" value="<?php echo $prod->prod_price; ?>" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label>Product Quantity</label>
                                            <input type="number" name="prod_qty" class="form-control" required>
                                        </div>
                                    </div>
                                <?php } ?>
                                <br>
                                <div class="form-row">
                                    <div class="col-md-6">
                                        <label>Table ID</label>
                                        <select name="meja_id" class="form-control" required>
                                            <?php
                                            // Load all table IDs
                                            $ret = "SELECT * FROM meja";
                                            $stmt = $mysqli->prepare($ret);
                                            $stmt->execute();
                                            $res = $stmt->get_result();
                                            while ($meja = $res->fetch_object()) {
                                            ?>
                                                <option value="<?php echo $meja->meja_id; ?>"><?php echo $meja->meja_id; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <br>
                                <div class="form-row">
                                    <div class="col-md-6">
                                        <input type="submit" name="make" value="Make Order" class="btn btn-success">
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
</body>

</html>
