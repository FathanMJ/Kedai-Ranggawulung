<?php
session_start();
include('admin/config/pdoconfig.php');
include('admin/config/config.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Ordering System</title>
    <style>
        body { font-family: Arial; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #007bff; color: white; }
        .success { color: green; background-color: #d4edda; padding: 10px; border-radius: 5px; }
        .error { color: red; background-color: #f8d7da; padding: 10px; border-radius: 5px; }
        .info { color: #004085; background-color: #d1ecf1; padding: 10px; border-radius: 5px; }
        button { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background-color: #0056b3; }
        .demo-form { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 15px; }
        .demo-form input, .demo-form select { padding: 8px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
        input[type='submit'] { cursor: pointer; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🧪 Test Ordering System</h1>\n";

// Test 1: Check Database Connection
echo "<div class='section'>
    <h2>1️⃣ Database Connection</h2>";
try {
    $stmt = $DB_con->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'projek'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<div class='success'>✅ Database Connected! Tables found: " . $result['count'] . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
}
echo "</div>\n";

// Test 2: Check Customers
echo "<div class='section'>
    <h2>2️⃣ Customers in Database</h2>";
try {
    $stmt = $DB_con->query("SELECT COUNT(*) as count FROM rpos_customers");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $customer_count = $result['count'];
    
    if ($customer_count > 0) {
        echo "<div class='success'>✅ Found $customer_count customer(s)</div>";
        $stmt = $DB_con->query("SELECT customer_id, customer_name, customer_email, customer_phoneno FROM rpos_customers LIMIT 10");
        echo "<table>";
        echo "<tr><th>Customer ID</th><th>Name</th><th>Email</th><th>Phone</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>" . $row['customer_id'] . "</td><td>" . $row['customer_name'] . "</td><td>" . $row['customer_email'] . "</td><td>" . $row['customer_phoneno'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ️ No customers found. You need to add customers first.</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
echo "</div>\n";

// Test 3: Check Products
echo "<div class='section'>
    <h2>3️⃣ Products in Database</h2>";
try {
    $stmt = $DB_con->query("SELECT COUNT(*) as count FROM rpos_products");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $product_count = $result['count'];
    
    if ($product_count > 0) {
        echo "<div class='success'>✅ Found $product_count product(s)</div>";
        $stmt = $DB_con->query("SELECT prod_id, prod_code, prod_name, prod_price, prod_stock FROM rpos_products LIMIT 10");
        echo "<table>";
        echo "<tr><th>Product ID</th><th>Code</th><th>Name</th><th>Price</th><th>Stock</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>" . $row['prod_id'] . "</td><td>" . $row['prod_code'] . "</td><td>" . $row['prod_name'] . "</td><td>Rp " . number_format($row['prod_price'], 0, ',', '.') . "</td><td>" . $row['prod_stock'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ️ No products found. You need to add products first.</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
echo "</div>\n";

// Test 4: Check Tables (Meja)
echo "<div class='section'>
    <h2>4️⃣ Tables (Meja) in Database</h2>";
try {
    $stmt = $DB_con->query("SELECT COUNT(*) as count FROM rpos_meja");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $table_count = $result['count'];
    
    if ($table_count > 0) {
        echo "<div class='success'>✅ Found $table_count table(s)</div>";
        $stmt = $DB_con->query("SELECT meja_id, meja_name FROM rpos_meja LIMIT 10");
        echo "<table>";
        echo "<tr><th>Table ID</th><th>Table Name</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>" . $row['meja_id'] . "</td><td>" . $row['meja_name'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ️ No tables found. You need to add tables first.</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
echo "</div>\n";

// Test 5: Check Orders
echo "<div class='section'>
    <h2>5️⃣ Orders in Database</h2>";
try {
    $stmt = $DB_con->query("SELECT COUNT(*) as count FROM rpos_orders");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $order_count = $result['count'];
    
    if ($order_count > 0) {
        echo "<div class='success'>✅ Found $order_count order(s)</div>";
        $stmt = $DB_con->query("SELECT order_id, order_code, customer_name, prod_name, prod_qty, prod_price FROM rpos_orders LIMIT 10");
        echo "<table>";
        echo "<tr><th>Order ID</th><th>Order Code</th><th>Customer</th><th>Product</th><th>Qty</th><th>Price</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>" . $row['order_id'] . "</td><td>" . $row['order_code'] . "</td><td>" . $row['customer_name'] . "</td><td>" . $row['prod_name'] . "</td><td>" . $row['prod_qty'] . "</td><td>Rp " . number_format($row['prod_price'], 0, ',', '.') . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ️ No orders found yet. Ordering system is ready but no orders placed.</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
echo "</div>\n";

// Test 6: System Readiness
echo "<div class='section'>
    <h2>📊 System Readiness for Ordering</h2>";
try {
    $stmt = $DB_con->query("SELECT COUNT(*) as count FROM rpos_customers");
    $customer_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $DB_con->query("SELECT COUNT(*) as count FROM rpos_products");
    $product_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $DB_con->query("SELECT COUNT(*) as count FROM rpos_meja");
    $table_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<table>";
    echo "<tr><th>Component</th><th>Status</th><th>Count</th></tr>";
    
    $customer_ok = $customer_count > 0 ? '✅ OK' : '❌ Missing';
    echo "<tr><td>Customers</td><td>$customer_ok</td><td>$customer_count</td></tr>";
    
    $product_ok = $product_count > 0 ? '✅ OK' : '❌ Missing';
    echo "<tr><td>Products</td><td>$product_ok</td><td>$product_count</td></tr>";
    
    $table_ok = $table_count > 0 ? '✅ OK' : '❌ Missing';
    echo "<tr><td>Tables</td><td>$table_ok</td><td>$table_count</td></tr>";
    
    echo "</table>";
    
    if ($customer_count > 0 && $product_count > 0 && $table_count > 0) {
        echo "<br><div class='success'>✅ SYSTEM READY FOR ORDERING! All components are in place.</div>";
    } else {
        echo "<br><div class='error'>❌ SYSTEM NOT READY. Missing: ";
        $missing = [];
        if ($customer_count == 0) $missing[] = "Customers";
        if ($product_count == 0) $missing[] = "Products";
        if ($table_count == 0) $missing[] = "Tables";
        echo implode(", ", $missing) . "</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
echo "</div>\n";

echo "
    <div class='section' style='text-align: center; margin-top: 30px;'>
        <h3>🔗 Quick Links</h3>
        <a href='admin/index.php'><button>Admin Login</button></a>
        <a href='customer/index.php'><button>Customer Login</button></a>
        <a href='admin/add_customer.php'><button>Add Customer</button></a>
        <a href='admin/add_product.php'><button>Add Product</button></a>
        <a href='admin/add_meja.php'><button>Add Table</button></a>
    </div>
</div>
</body>
</html>";
?>
