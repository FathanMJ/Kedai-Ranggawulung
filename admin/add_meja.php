<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
include('config/code-generator.php');

check_login();

// Initialize variables from code-generator
$beta = isset($beta) ? $beta : '0000';

// Add Table
if (isset($_POST['addTable'])) {
  // Prevent Posting Blank Values
  if (empty($_POST["meja_id"]) || empty($_POST["no_meja"]) || empty($_POST['kapasitas'])) {
    $err = "Blank Values Not Accepted";
  } else {
    $meja_id = $_POST['meja_id'];
    $no_meja = $_POST['no_meja'];
    $kapasitas = $_POST['kapasitas'];

    // Insert Captured information to a database table
    $postQuery = "INSERT INTO meja (meja_id, no_meja, kapasitas) VALUES(?, ?, ?)";
    $postStmt = $mysqli->prepare($postQuery);
    // Bind parameters
    $rc = $postStmt->bind_param('sss', $meja_id, $no_meja, $kapasitas);
    $postStmt->execute();
    // Declare a variable which will be passed to alert function
    if ($postStmt) {
      $success = "Table Added" && header("refresh:1; url=meja.php");
    } else {
      $err = "Please Try Again Or Try Later";
    }
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
              <h3>Please Fill All Fields</h3>
            </div>
            <div class="card-body">
              <form method="POST">
                <div class="form-row">
                  <div class="col-md-6">
                    <label>ID Meja</label>
                    <input type="text" name="meja_id" class="form-control" value="<?php echo $beta; ?>">
                  </div>
                  <div class="col-md-6">
                    <label>No Meja</label>
                    <input type="number" name="no_meja" class="form-control" value="">
                  </div>
                </div>
                <hr>
                <div class="form-row">
                  <div class="col-md-6">
                    <label>Kapasitas</label>
                    <input type="number" name="kapasitas" class="form-control" value="">
                  </div>
                </div>
                <br>
                <div class="form-row">
                  <div class="col-md-6">
                    <input type="submit" name="addTable" value="Add Table" class="btn btn-success" value="">
                  </div>
                </div>
              </form>
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
