<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
check_login();

// Delete Table
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Delete related orders
    $deleteOrders = "DELETE FROM rpos_orders WHERE meja_id = ?";
    $stmtOrders = $mysqli->prepare($deleteOrders);
    $stmtOrders->bind_param('i', $id);
    $stmtOrders->execute();
    $stmtOrders->close();

    // Delete table
    $adn = "DELETE FROM meja WHERE meja_id = ?";
    $stmt = $mysqli->prepare($adn);
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        if ($stmt) {
            header("refresh:1; url=meja.php");
            $success = "Deleted";
        } else {
            $err = "Try Again Later";
        }
    } else {
        $err = "Failed to prepare the SQL statement";
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
    <div style="background-image: url(assets/img/theme/restro00.jpg); background-size: cover;" class="header pb-8 pt-5 pt-md-8">
    <span class="mask bg-gradient-dark opacity-8"></span>
      <div class="container-fluid">
        <div class="header-body">
        </div>
      </div>
    </div>
    <!-- Page content -->
    <div class="container-fluid mt--8">
      <!-- Table -->
      <div class="row">
        <div class="col">
          <div class="card shadow">
            <div class="card-header border-0">
              <a href="add_meja.php" class="btn btn-outline-success"><i class="fas fa-plus"></i> Add New Table</a>
            </div>
            <div class="table-responsive">
              <table class="table align-items-center table-flush">
                <thead class="thead-light">
                  <tr>
                    <th scope="col">Table ID</th>
                    <th scope="col">Table Number</th>
                    <th scope="col">Capacity</th>
                    <th scope="col">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $ret = "SELECT * FROM meja";
                  $stmt = $mysqli->prepare($ret);
                  $stmt->execute();
                  $res = $stmt->get_result();
                  while ($table = $res->fetch_object()) {
                  ?>
                    <tr>
                      <td><?php echo $table->meja_id; ?></td>
                      <td><?php echo $table->no_meja; ?></td>
                      <td><?php echo $table->kapasitas; ?></td>
                      <td>
                        <a href="meja.php?delete=<?php echo $table->meja_id; ?>">
                          <button class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Delete
                          </button>
                        </a>
                      </td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
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
