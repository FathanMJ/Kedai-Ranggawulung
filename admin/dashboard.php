<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
check_login();

// Initialize variables
$err = '';
$success = '';

// Get dashboard statistics
try {
    // Get total customers
    $customerStmt = $mysqli->prepare("SELECT COUNT(*) as total FROM rpos_customers");
    $customerStmt->execute();
    $customerResult = $customerStmt->get_result();
    $totalCustomers = $customerResult->fetch_object()->total;
    $customerStmt->close();
    
    // Get total staff
    $staffStmt = $mysqli->prepare("SELECT COUNT(*) as total FROM rpos_staff");
    $staffStmt->execute();
    $staffResult = $staffStmt->get_result();
    $totalStaff = $staffResult->fetch_object()->total;
    $staffStmt->close();
    
    // Get total products
    $productStmt = $mysqli->prepare("SELECT COUNT(*) as total FROM rpos_products");
    $productStmt->execute();
    $productResult = $productStmt->get_result();
    $totalProducts = $productResult->fetch_object()->total;
    $productStmt->close();
    
    // Get total orders
    $orderStmt = $mysqli->prepare("SELECT COUNT(*) as total FROM rpos_orders");
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    $totalOrders = $orderResult->fetch_object()->total;
    $orderStmt->close();
    
    // Get total sales
    $salesStmt = $mysqli->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM rpos_orders WHERE order_status = 'Paid'");
    $salesStmt->execute();
    $salesResult = $salesStmt->get_result();
    $totalSales = $salesResult->fetch_object()->total;
    $salesStmt->close();
    
} catch (Exception $e) {
    $err = "Error loading dashboard data";
    error_log("Dashboard error: " . $e->getMessage());
}

require_once('partials/_head.php');
require_once('partials/_analytics.php');
?>

<body>
<style>
    .dashboard-stats-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px 20px;
        border-radius: 15px;
        margin-bottom: 30px;
    }

    .dashboard-header-title {
        color: white;
        margin-bottom: 5px;
    }

    .stat-card-modern {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        transition: all 0.3s ease;
        border-left: 5px solid #667eea;
    }

    .stat-card-modern:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }

    .stat-card-modern.customers {
        border-left-color: #ef4444;
    }

    .stat-card-modern.staff {
        border-left-color: #f59e0b;
    }

    .stat-card-modern.products {
        border-left-color: #3b82f6;
    }

    .stat-card-modern.sales {
        border-left-color: #10b981;
    }

    .stat-value-modern {
        font-size: 2.5rem;
        font-weight: 700;
        color: #333;
        margin: 10px 0;
    }

    .stat-label-modern {
        color: #666;
        font-size: 0.95rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .card-header-modern {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 20px;
        border-radius: 12px 12px 0 0;
    }

    .card-header-modern h3 {
        margin: 0;
        font-weight: 600;
    }

    .card-shadow {
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        border: none;
    }

    .table-modern tbody tr {
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    .table-modern tbody tr:hover {
        background: #f9f9f9;
    }

    .badge-paid {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .badge-pending {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
    }

    .btn-modern {
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
</style>
<!-- For more projects: Visit codeastro.com  -->
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
    <div class="dashboard-stats-section">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <h1 class="dashboard-header-title"><i class="fas fa-chart-line"></i> Dashboard</h1>
            <p style="color: white; opacity: 0.9; margin: 0;">Selamat datang kembali, Admin</p>
          </div>
        </div>
      </div>
    </div>

    <div class="container-fluid">
      <?php if($err): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="fas fa-exclamation-circle"></i> <?php echo $err; ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      <?php endif; ?>

      <!-- Card stats -->
      <div class="row">
        <div class="col-xl-3 col-lg-6">
          <div class="stat-card-modern customers">
            <div class="row align-items-center">
              <div class="col">
                <div class="stat-label-modern">Total Customers</div>
                <div class="stat-value-modern"><?php echo $totalCustomers; ?></div>
              </div>
              <div class="col-auto">
                <i class="fas fa-users" style="font-size: 2.5rem; color: #ef4444; opacity: 0.2;"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-lg-6">
          <div class="stat-card-modern staff">
            <div class="row align-items-center">
              <div class="col">
                <div class="stat-label-modern">Total Staff</div>
                <div class="stat-value-modern"><?php echo $totalStaff; ?></div>
              </div>
              <div class="col-auto">
                <i class="fas fa-user-tie" style="font-size: 2.5rem; color: #f59e0b; opacity: 0.2;"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-lg-6">
          <div class="stat-card-modern products">
            <div class="row align-items-center">
              <div class="col">
                <div class="stat-label-modern">Total Products</div>
                <div class="stat-value-modern"><?php echo $totalProducts; ?></div>
              </div>
              <div class="col-auto">
                <i class="fas fa-coffee" style="font-size: 2.5rem; color: #3b82f6; opacity: 0.2;"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-lg-6">
          <div class="stat-card-modern sales">
            <div class="row align-items-center">
              <div class="col">
                <div class="stat-label-modern">Total Sales</div>
                <div class="stat-value-modern">Rp <?php echo number_format($totalSales, 0, ',', '.'); ?></div>
              </div>
              <div class="col-auto">
                <i class="fas fa-money-bill-alt" style="font-size: 2.5rem; color: #10b981; opacity: 0.2;"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Page content -->
    <div class="container-fluid mt--7">
      <div class="row mt-5">
        <div class="col-xl-12 mb-5 mb-xl-0">
          <div class="card card-shadow">
            <div class="card-header card-header-modern">
              <div class="row align-items-center">
                <div class="col">
                  <h3 class="mb-0"><i class="fas fa-shopping-bag"></i> Recent Orders</h3>
                </div>
                <div class="col text-right">
                  <a href="orders_reports.php" class="btn btn-sm btn-light btn-modern">
                    <i class="fas fa-arrow-right"></i> View All
                  </a>
                </div>
              </div>
            </div>
            <div class="table-responsive">
              <!-- Projects table -->
              <table class="table align-items-center table-flush table-modern">
                <thead class="thead-light">
                  <tr style="background: #f8f9fa;">
                    <th scope="col"><i class="fas fa-barcode"></i> Order Code</th>
                    <th scope="col"><i class="fas fa-user"></i> Customer</th>
                    <th scope="col"><i class="fas fa-box"></i> Product</th>
                    <th scope="col"><i class="fas fa-money-bill"></i> Total</th>
                    <th scope="col"><i class="fas fa-check-circle"></i> Status</th>
                    <th scope="col"><i class="fas fa-calendar"></i> Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  try {
                    $query = "SELECT o.order_code, c.customer_name, p.prod_name, o.total_amount, o.order_status, o.created_at 
                            FROM rpos_orders o 
                            JOIN rpos_customers c ON o.customer_id = c.customer_id 
                            JOIN rpos_order_items oi ON o.order_code = oi.order_code 
                            JOIN rpos_products p ON oi.product_id = p.prod_id 
                            ORDER BY o.created_at DESC LIMIT 10";
                    
                    $stmt = $mysqli->prepare($query);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if($result->num_rows === 0): ?>
                      <tr>
                        <td colspan="6" class="text-center">No orders found</td>
                      </tr>
                    <?php else:
                      while($order = $result->fetch_object()): ?>
                        <tr>
                          <td><code><?php echo htmlspecialchars($order->order_code); ?></code></td>
                          <td><?php echo htmlspecialchars($order->customer_name); ?></td>
                          <td><?php echo htmlspecialchars($order->prod_name); ?></td>
                          <td><strong>Rp <?php echo number_format($order->total_amount, 0, ',', '.'); ?></strong></td>
                          <td>
                            <span class="badge <?php echo $order->order_status === 'Paid' ? 'badge-paid' : 'badge-pending'; ?>" style="padding: 0.5rem 1rem; border-radius: 50px;">
                              <?php echo htmlspecialchars($order->order_status); ?>
                            </span>
                          </td>
                          <td><small><?php echo date('d/m/Y H:i', strtotime($order->created_at)); ?></small></td>
                        </tr>
                      <?php endwhile;
                    endif;
                    $stmt->close();
                    
                  } catch (Exception $e) {
                    error_log("Error loading recent orders: " . $e->getMessage());
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="row mt-5">
        <div class="col-xl-12">
          <div class="card card-shadow">
            <div class="card-header card-header-modern">
              <div class="row align-items-center">
                <div class="col">
                  <h3 class="mb-0"><i class="fas fa-credit-card"></i> Recent Payments</h3>
                </div>
                <div class="col text-right">
                  <a href="payments_reports.php" class="btn btn-sm btn-light btn-modern">
                    <i class="fas fa-arrow-right"></i> View All
                  </a>
                </div>
              </div>
            </div>
            <div class="table-responsive">
              <!-- Projects table -->
              <table class="table align-items-center table-flush table-modern">
                <thead class="thead-light">
                  <tr style="background: #f8f9fa;">
                    <th scope="col"><i class="fas fa-hashtag"></i> Code</th>
                    <th scope="col"><i class="fas fa-money-bill"></i> Amount</th>
                    <th scope="col"><i class="fas fa-link"></i> Order Code</th>
                    <th scope="col"><i class="fas fa-calendar"></i> Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $ret = "SELECT * FROM rpos_payments ORDER BY `rpos_payments`.`created_at` DESC LIMIT 7 ";
                  $stmt = $mysqli->prepare($ret);
                  $stmt->execute();
                  $res = $stmt->get_result();
                  if($res->num_rows === 0): ?>
                    <tr>
                      <td colspan="4" class="text-center text-muted py-4">No payments found</td>
                    </tr>
                  <?php else:
                    while ($payment = $res->fetch_object()) {
                  ?>
                    <tr>
                      <td><code><?php echo htmlspecialchars($payment->pay_code); ?></code></td>
                      <td><strong>Rp<?php echo number_format($payment->pay_amt, 0, ',', '.'); ?></strong></td>
                      <td><code><?php echo htmlspecialchars($payment->order_code); ?></code></td>
                      <td><small><?php echo date('d/m/Y H:i', strtotime($payment->created_at)); ?></small></td>
                    </tr>
                  <?php 
                    }
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
    </div>
  </div>
  <!-- Argon Scripts -->
  <?php
  require_once('partials/_scripts.php');
  ?>
</body>
<!-- For more projects: Visit codeastro.com  -->
</html>