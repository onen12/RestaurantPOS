<?php
$admin_id = $_SESSION['admin_id'];
$ret = "SELECT * FROM rpos_admin WHERE admin_id = '$admin_id'";
$stmt = $mysqli->prepare($ret);
$stmt->execute();
$res = $stmt->get_result();
while ($admin = $res->fetch_object()) {
?>
<nav class="navbar navbar-vertical fixed-left navbar-expand-md navbar-light bg-white" id="sidenav-main">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#sidenav-collapse-main" aria-controls="sidenav-main" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <a class="navbar-brand pt-0" href="dashboard.php">
      <img src="assets/img/brand/repos.png" class="navbar-brand-img" alt="HOME TASTE FOODS">
    </a>
    <ul class="nav align-items-center d-md-none">
      <li class="nav-item dropdown">
        <a class="nav-link" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <div class="media align-items-center">
            <span class="avatar avatar-sm rounded-circle">
              <img alt="Image placeholder" src="assets/img/theme/team-1-800x800.jpg">
            </span>
          </div>
        </a>
        <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-right">
          <div class="dropdown-header noti-title"><h6 class="text-overflow m-0">Welcome!</h6></div>
          <a href="change_profile.php" class="dropdown-item"><i class="ni ni-single-02"></i><span>My profile</span></a>
          <div class="dropdown-divider"></div>
          <a href="logout.php" class="dropdown-item"><i class="ni ni-user-run"></i><span>Logout</span></a>
        </div>
      </li>
    </ul>
    <div class="collapse navbar-collapse" id="sidenav-collapse-main">
      <div class="navbar-collapse-header d-md-none">
        <div class="row">
          <div class="col-6 collapse-brand">
            <a href="dashboard.php"><img src="assets/img/brand/repos.png" alt="HOME TASTE FOODS"></a>
          </div>
          <div class="col-6 collapse-close">
            <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#sidenav-collapse-main" aria-controls="sidenav-main" aria-expanded="false" aria-label="Toggle sidenav">
              <span></span><span></span>
            </button>
          </div>
        </div>
      </div>
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" href="dashboard.php"><i class="ni ni-tv-2 text-primary"></i> Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="orders.php"><i class="ni ni-cart text-primary"></i> Make Order</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart text-primary"></i> Cart</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="orders_manage.php"><i class="fas fa-clipboard-list text-primary"></i> Manage Orders</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="payments.php"><i class="ni ni-credit-card text-primary"></i> Payments</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="receipts.php"><i class="fas fa-file-invoice text-primary"></i> Receipts</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="products.php"><i class="ni ni-bullet-list-67 text-primary"></i> Products</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="customes.php"><i class="fas fa-users text-primary"></i> Customers</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="hrm.php"><i class="fas fa-user-tie text-primary"></i> HRM</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="orders_reports.php"><i class="ni ni-chart-bar-32 text-primary"></i> Orders Reports</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="payments_reports.php"><i class="ni ni-chart-pie-35 text-primary"></i> Payments Reports</a>
        </li>
      </ul>
      <hr class="my-3">
      <ul class="navbar-nav mb-md-3">
        <li class="nav-item">
          <a class="nav-link" href="change_profile.php"><i class="ni ni-single-02 text-primary"></i> My Profile</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="logout.php"><i class="ni ni-user-run text-primary"></i> Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<?php } ?>
