<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
check_login();

// Initialize variables
$err = '';
$success = '';

// Check if cart is empty
if(empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// Handle checkout
if (isset($_POST['checkout'])) {
    // Validate input
    $customer_id = $_SESSION['customer_id'];
    $order_code = uniqid('ORD-');
    $order_status = 'Pending';
    $total_amount = 0;
    
    // Start transaction
    $mysqli->begin_transaction();
    
    try {
        // Calculate total and validate stock
        foreach ($_SESSION['cart'] as $item) {
            $product_id = $item['id'];
            $quantity = $item['quantity'];
            $price = $item['price'];
            
            // Check stock again
            $stockCheck = $mysqli->prepare("SELECT prod_stock FROM rpos_products WHERE prod_id = ?");
            $stockCheck->bind_param('i', $product_id);
            $stockCheck->execute();
            $stockResult = $stockCheck->get_result();
            $stock = $stockResult->fetch_assoc();
            
            if($stock['prod_stock'] < $quantity) {
                throw new Exception("Sorry, only " . $stock['prod_stock'] . " items available in stock for product ID: " . $product_id);
            }
            
            $total_amount += ($price * $quantity);
            $stockCheck->close();
        }
        
        // Insert order
        $orderStmt = $mysqli->prepare("INSERT INTO rpos_orders (order_code, customer_id, order_status, total_amount) VALUES (?, ?, ?, ?)");
        $orderStmt->bind_param('siss', $order_code, $customer_id, $order_status, $total_amount);
        $orderStmt->execute();
        $orderStmt->close();
        
        // Insert order items and update stock
        foreach ($_SESSION['cart'] as $item) {
            $product_id = $item['id'];
            $quantity = $item['quantity'];
            $price = $item['price'];
            
            // Insert order item
            $itemStmt = $mysqli->prepare("INSERT INTO rpos_order_items (order_code, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $itemStmt->bind_param('siid', $order_code, $product_id, $quantity, $price);
            $itemStmt->execute();
            $itemStmt->close();
            
            // Update stock
            $updateStock = $mysqli->prepare("UPDATE rpos_products SET prod_stock = prod_stock - ? WHERE prod_id = ?");
            $updateStock->bind_param('ii', $quantity, $product_id);
            $updateStock->execute();
            $updateStock->close();
        }
        
        // Commit transaction
        $mysqli->commit();
        
        // Clear cart
        unset($_SESSION['cart']);
        
        // Log successful order
        error_log("New order created - Order Code: $order_code, Customer ID: $customer_id, Total: $total_amount");
        
        $success = "Order placed successfully! Your order code is: " . $order_code;
        header("refresh:3; url=orders.php");
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $mysqli->rollback();
        $err = $e->getMessage();
        error_log("Checkout error: " . $e->getMessage());
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
        <div class="header pb-8 pt-5 pt-md-8">
            <div class="container-fluid">
                <div class="header-body">
                    <h1>Checkout</h1>
                </div>
            </div>
        </div>
        
        <!-- Page content -->
        <div class="container-fluid mt--8">
            <div class="row">
                <div class="col">
                    <div class="card shadow">
                        <div class="card-header border-0">
                            <h3 class="mb-0">Order Summary</h3>
                        </div>
                        
                        <?php if($err): ?>
                            <div class="alert alert-danger"><?php echo $err; ?></div>
                        <?php endif; ?>
                        
                        <?php if($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-items-center table-flush">
                                    <thead class="thead-light">
                                        <tr>
                                            <th scope="col">Product</th>
                                            <th scope="col">Price</th>
                                            <th scope="col">Quantity</th>
                                            <th scope="col">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $total = 0;
                                        foreach ($_SESSION['cart'] as $item):
                                            $subtotal = $item['price'] * $item['quantity'];
                                            $total += $subtotal;
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr>
                                            <td colspan="3" class="text-right"><strong>Total</strong></td>
                                            <td><strong>Rp <?php echo number_format($total, 0, ',', '.'); ?></strong></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <form method="post" action="checkout.php">
                                <div class="form-group">
                                    <label>Payment Method</label>
                                    <select name="payment_method" class="form-control" required>
                                        <option value="">Select Payment Method</option>
                                        <option value="cash">Cash</option>
                                        <option value="card">Credit/Debit Card</option>
                                        <option value="transfer">Bank Transfer</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Special Instructions</label>
                                    <textarea name="special_instructions" class="form-control" rows="3" placeholder="Any special instructions for your order?"></textarea>
                                </div>
                                
                                <button type="submit" name="checkout" class="btn btn-success">Place Order</button>
                                <a href="cart.php" class="btn btn-secondary">Back to Cart</a>
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