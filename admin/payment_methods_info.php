<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
check_login();

require_once('partials/_head.php');
require_once('partials/_analytics.php');
?>

<body>
<style>
    .payment-methods-container {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px 20px;
        border-radius: 15px;
        margin-bottom: 30px;
        color: white;
    }

    .payment-methods-container h1 {
        margin-bottom: 10px;
        font-weight: 700;
    }

    .method-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        border-top: 5px solid;
    }

    .method-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }

    .method-card.midtrans {
        border-top-color: #667eea;
    }

    .method-card.cash {
        border-top-color: #10b981;
    }

    .method-card.paypal {
        border-top-color: #0070ba;
    }

    .method-icon {
        font-size: 3rem;
        margin-bottom: 15px;
    }

    .method-card.midtrans .method-icon {
        color: #667eea;
    }

    .method-card.cash .method-icon {
        color: #10b981;
    }

    .method-card.paypal .method-icon {
        color: #0070ba;
    }

    .method-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 10px;
    }

    .method-description {
        color: #666;
        margin-bottom: 20px;
        line-height: 1.6;
    }

    .method-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 15px;
    }

    .badge-active {
        background: #d4edda;
        color: #155724;
    }

    .badge-configured {
        background: #cfe2ff;
        color: #084298;
    }

    .method-features {
        list-style: none;
        padding: 0;
        margin-bottom: 20px;
    }

    .method-features li {
        padding: 0.5rem 0;
        color: #555;
        border-bottom: 1px solid #f0f0f0;
    }

    .method-features li:last-child {
        border-bottom: none;
    }

    .method-features li:before {
        content: "✓ ";
        color: #10b981;
        font-weight: bold;
        margin-right: 10px;
    }

    .method-action {
        margin-top: 20px;
    }

    .btn-method {
        display: inline-block;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .btn-method:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .btn-midtrans {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }

    .btn-cash {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .btn-paypal {
        background: linear-gradient(135deg, #0070ba, #003087);
        color: white;
    }

    .stats-info {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 10px;
        margin-top: 20px;
    }

    .stats-info-item {
        padding: 10px 0;
        border-bottom: 1px solid #e0e0e0;
    }

    .stats-info-item:last-child {
        border-bottom: none;
    }

    .stats-label {
        color: #666;
        font-weight: 600;
    }

    .stats-value {
        color: #333;
        font-weight: 700;
        margin-top: 5px;
    }
</style>

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

    <div class="payment-methods-container">
      <div class="container-fluid">
        <h1><i class="fas fa-credit-card"></i> Payment Methods Available</h1>
        <p style="opacity: 0.95; margin: 0;">Manage and configure available payment methods for your restaurant</p>
      </div>
    </div>

    <!-- Page content -->
    <div class="container-fluid mt--7">
      <div class="row">
        <!-- Midtrans -->
        <div class="col-lg-6">
          <div class="method-card midtrans">
            <div class="method-icon">
              <i class="fas fa-shield-alt"></i>
            </div>
            <span class="method-badge badge-configured">Configured</span>
            <h3 class="method-title">Midtrans</h3>
            <p class="method-description">
              Midtrans is a payment aggregator that allows customers to pay using various methods including credit cards, bank transfers, e-wallets, and more.
            </p>
            
            <ul class="method-features">
              <li>Multiple payment methods</li>
              <li>Bank transfers (virtual account)</li>
              <li>Credit/Debit cards</li>
              <li>E-wallets (GoPay, OVO, Dana)</li>
              <li>Mobile wallets</li>
              <li>Real-time payment confirmation</li>
              <li>Webhook integration</li>
              <li>Secure transaction processing</li>
            </ul>

            <div class="stats-info">
              <div class="stats-info-item">
                <div class="stats-label">Status</div>
                <div class="stats-value" style="color: #10b981;"><i class="fas fa-check-circle"></i> Ready</div>
              </div>
              <div class="stats-info-item">
                <div class="stats-label">Requires</div>
                <div class="stats-value">Server Key & Client Key</div>
              </div>
              <div class="stats-info-item">
                <div class="stats-label">Fee</div>
                <div class="stats-value">Variable (1% - 3%)</div>
              </div>
            </div>

            <div class="method-action">
              <a href="setup/index.php" class="btn-method btn-midtrans">
                <i class="fas fa-cog"></i> Configure Midtrans
              </a>
            </div>
          </div>
        </div>

        <!-- Cash -->
        <div class="col-lg-6">
          <div class="method-card cash">
            <div class="method-icon">
              <i class="fas fa-money-bill"></i>
            </div>
            <span class="method-badge badge-active">Active</span>
            <h3 class="method-title">Cash Payment</h3>
            <p class="method-description">
              Direct cash payment at the restaurant. Staff manually records payment and marks orders as paid.
            </p>
            
            <ul class="method-features">
              <li>No transaction fees</li>
              <li>Instant settlement</li>
              <li>In-person payment</li>
              <li>Manual verification</li>
              <li>Complete audit trail</li>
              <li>No internet required</li>
            </ul>

            <div class="stats-info">
              <div class="stats-info-item">
                <div class="stats-label">Status</div>
                <div class="stats-value" style="color: #10b981;"><i class="fas fa-check-circle"></i> Active</div>
              </div>
              <div class="stats-info-item">
                <div class="stats-label">Recording</div>
                <div class="stats-value">Manual by Staff</div>
              </div>
              <div class="stats-info-item">
                <div class="stats-label">Fee</div>
                <div class="stats-value">No fees</div>
              </div>
            </div>

            <div class="method-action">
              <a href="pay_order.php" class="btn-method btn-cash">
                <i class="fas fa-arrow-right"></i> Process Payment
              </a>
            </div>
          </div>
        </div>

        <!-- PayPal -->
        <div class="col-lg-6">
          <div class="method-card paypal">
            <div class="method-icon">
              <i class="fab fa-paypal"></i>
            </div>
            <span class="method-badge badge-configured">Configured</span>
            <h3 class="method-title">PayPal</h3>
            <p class="method-description">
              PayPal payment integration for customers with PayPal accounts. Manual recording of payments.
            </p>
            
            <ul class="method-features">
              <li>PayPal account holders</li>
              <li>Credit card via PayPal</li>
              <li>Manual verification</li>
              <li>Transaction reference</li>
              <li>Global reach</li>
              <li>Secure processing</li>
            </ul>

            <div class="stats-info">
              <div class="stats-info-item">
                <div class="stats-label">Status</div>
                <div class="stats-value" style="color: #10b981;"><i class="fas fa-check-circle"></i> Ready</div>
              </div>
              <div class="stats-info-item">
                <div class="stats-label">Recording</div>
                <div class="stats-value">Manual by Staff</div>
              </div>
              <div class="stats-info-item">
                <div class="stats-label">Fee</div>
                <div class="stats-value">2.9% + $0.30</div>
              </div>
            </div>

            <div class="method-action">
              <a href="pay_order.php" class="btn-method btn-paypal">
                <i class="fas fa-arrow-right"></i> Process Payment
              </a>
            </div>
          </div>
        </div>

        <!-- Payment Statistics -->
        <div class="col-lg-6">
          <div class="card" style="border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border: none;">
            <div class="card-header" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 15px 15px 0 0; border: none; padding: 20px; font-weight: 600;">
              <i class="fas fa-chart-pie"></i> Payment Statistics
            </div>
            <div class="card-body">
              <?php
              try {
                // Get payment method statistics
                $statsQuery = "
                  SELECT 
                    payment_method,
                    COUNT(*) as count,
                    SUM(pay_amt) as total
                  FROM rpos_payments
                  GROUP BY payment_method
                  ORDER BY total DESC
                ";
                
                $stmt = $mysqli->prepare($statsQuery);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if($result->num_rows > 0):
                  while($stat = $result->fetch_object()):
              ?>
                <div style="padding: 15px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center;">
                  <div>
                    <strong><?php echo ucfirst($stat->payment_method); ?></strong>
                    <div style="font-size: 0.9rem; color: #666;"><?php echo $stat->count; ?> transactions</div>
                  </div>
                  <div style="text-align: right; font-weight: 600; font-size: 1.1rem;">
                    Rp<?php echo number_format($stat->total, 0, ',', '.'); ?>
                  </div>
                </div>
              <?php 
                  endwhile;
                else:
              ?>
                <p class="text-muted text-center py-4">No payment data available</p>
              <?php 
                endif;
                $stmt->close();
              } catch (Exception $e) {
                echo '<p class="text-danger">Error loading statistics</p>';
              }
              ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="row mt-5">
        <div class="col-12">
          <div class="card" style="border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border: none;">
            <div class="card-header" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 15px 15px 0 0; border: none; padding: 20px; font-weight: 600;">
              <i class="fas fa-link"></i> Quick Links
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-3 mb-3">
                  <a href="orders.php" class="btn btn-outline-primary btn-block" style="border-radius: 8px; padding: 15px;">
                    <i class="fas fa-shopping-bag"></i><br>View Orders
                  </a>
                </div>
                <div class="col-md-3 mb-3">
                  <a href="payments.php" class="btn btn-outline-success btn-block" style="border-radius: 8px; padding: 15px;">
                    <i class="fas fa-credit-card"></i><br>Manage Payments
                  </a>
                </div>
                <div class="col-md-3 mb-3">
                  <a href="payments_reports.php" class="btn btn-outline-info btn-block" style="border-radius: 8px; padding: 15px;">
                    <i class="fas fa-chart-bar"></i><br>Payment Reports
                  </a>
                </div>
                <div class="col-md-3 mb-3">
                  <a href="setup/index.php" class="btn btn-outline-warning btn-block" style="border-radius: 8px; padding: 15px;">
                    <i class="fas fa-cog"></i><br>Setup Midtrans
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Footer -->
  </div>
  <!-- Argon Scripts -->
  <?php
  require_once('partials/_scripts.php');
  ?>
</body>
</html>
