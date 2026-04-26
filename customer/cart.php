<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
check_login();

// Initialize variables
$err = '';
$success = '';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    // Validate input
    $product_id = filter_var($_POST['prod_id'], FILTER_SANITIZE_NUMBER_INT);
    $product_name = trim($_POST['prod_name'] ?? '');
    $product_price = filter_var($_POST['prod_price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $quantity = filter_var($_POST['prod_qty'], FILTER_SANITIZE_NUMBER_INT);
    
    // Validate quantity
    if($quantity <= 0) {
        $err = "Quantity must be greater than 0";
    } else {
        // Check product stock
        $stockCheck = $mysqli->prepare("SELECT prod_stock FROM rpos_products WHERE prod_id = ?");
        $stockCheck->bind_param('i', $product_id);
        $stockCheck->execute();
        $stockResult = $stockCheck->get_result();
        $stock = $stockResult->fetch_assoc();
        
        if($stock['prod_stock'] < $quantity) {
            $err = "Sorry, only " . $stock['prod_stock'] . " items available in stock";
        } else {
            // Add to cart
            $cart_item = [
                'id' => $product_id,
                'name' => $product_name,
                'price' => $product_price,
                'quantity' => $quantity
            ];
            
            $_SESSION['cart'][] = $cart_item;
            $success = "Item added to cart successfully";
            
            // Log cart addition
            error_log("Item added to cart - Product ID: $product_id, Quantity: $quantity");
        }
        $stockCheck->close();
    }
}

// Handle update cart
if (isset($_POST['update_cart'])) {
    $index = filter_var($_POST['index'], FILTER_SANITIZE_NUMBER_INT);
    $quantity = filter_var($_POST['quantity'], FILTER_SANITIZE_NUMBER_INT);
    
    if($quantity <= 0) {
        $err = "Quantity must be greater than 0";
    } elseif(isset($_SESSION['cart'][$index])) {
        // Check stock before updating
        $product_id = $_SESSION['cart'][$index]['id'];
        $stockCheck = $mysqli->prepare("SELECT prod_stock FROM rpos_products WHERE prod_id = ?");
        $stockCheck->bind_param('i', $product_id);
        $stockCheck->execute();
        $stockResult = $stockCheck->get_result();
        $stock = $stockResult->fetch_assoc();
        
        if($stock['prod_stock'] < $quantity) {
            $err = "Sorry, only " . $stock['prod_stock'] . " items available in stock";
        } else {
            $_SESSION['cart'][$index]['quantity'] = $quantity;
            $success = "Cart updated successfully";
        }
        $stockCheck->close();
    }
}

// Handle remove from cart
if (isset($_GET['remove'])) {
    $index = filter_var($_GET['remove'], FILTER_SANITIZE_NUMBER_INT);
    if(isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        $success = "Item removed from cart";
    }
}

require_once('partials/_head.php');
?>

<body>
    <!-- Sidenav -->
    <?php
    require_once('partials/_sidebar.php');
    ?>
    <!-- Main content -->
    <div class="main-content">
        <!-- Top navbar -->
        <?php
        require_once('partials/_topnav.php');
        ?>
        <!-- Header -->
        <div class="header pb-8 pt-5 pt-md-8">
            <div class="container-fluid">
                <div class="header-body">
                    <h1>Keranjang Belanja</h1>
                </div>
            </div>
        </div>
        <!-- Page content -->
        <div class="container-fluid mt--8">
            <div class="row">
                <div class="col">
                    <div class="card shadow">
                        <div class="card-header border-0">
                            <h3 class="mb-0">Isi Keranjang</h3>
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
                                        <th scope="col">Nama Produk</th>
                                        <th scope="col">Harga</th>
                                        <th scope="col">Jumlah</th>
                                        <th scope="col">Subtotal</th>
                                        <th scope="col">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total = 0;
                                    if(empty($_SESSION['cart'])): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Keranjang belanja kosong</td>
                                        </tr>
                                    <?php else:
                                        foreach ($_SESSION['cart'] as $index => $item):
                                            $subtotal = $item['price'] * $item['quantity'];
                                            $total += $subtotal;
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                            <td>
                                                <form method="post" action="cart.php" class="d-inline">
                                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                                    <div class="input-group input-group-sm" style="width: 150px;">
                                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="form-control">
                                                        <div class="input-group-append">
                                                            <button type="submit" name="update_cart" class="btn btn-primary">Update</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </td>
                                            <td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                                            <td>
                                                <a href="cart.php?remove=<?php echo $index; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to remove this item?')">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php 
                                        endforeach;
                                    endif; 
                                    ?>
                                    <tr>
                                        <td colspan="3" class="text-right"><strong>Total</strong></td>
                                        <td><strong>Rp <?php echo number_format($total, 0, ',', '.'); ?></strong></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="card-footer">
                            <?php if(!empty($_SESSION['cart'])): ?>
                                <a href="checkout.php" class="btn btn-success">Checkout</a>
                            <?php endif; ?>
                            <a href="orders.php" class="btn btn-secondary">Lanjutkan Belanja</a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <?php
            require_once('partials/_footer.php');
            ?>
        </div>
    </div>
    <!-- Argon Scripts -->
    <?php
    require_once('partials/_scripts.php');
    ?>
</body>

</html>
