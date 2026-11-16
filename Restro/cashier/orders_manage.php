<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
check_login();

// Update status
if (isset($_POST['update_status'])) {
    $code = $_POST['order_code'];
    $status = $_POST['new_status'];
    $stmt = $mysqli->prepare("UPDATE rpos_orders SET order_status = ? WHERE order_code = ?");
    $stmt->bind_param('ss', $status, $code);
    $stmt->execute();
    $success = "Order $code updated to $status";
}

require_once('partials/_head.php');
?>
<body>
<?php require_once('partials/_sidebar.php'); ?>
<div class="main-content">
<?php require_once('partials/_topnav.php'); ?>
<div style="background-image: url(../admin/assets/img/theme/restro00.jpg); background-size: cover;" class="header pb-8 pt-5 pt-md-8">
  <span class="mask bg-gradient-dark opacity-8"></span>
  <div class="container-fluid"><div class="header-body"></div></div>
</div>
<div class="container-fluid mt--8">
  <div class="row">
    <div class="col">
      <div class="card shadow">
        <div class="card-header border-0">
          <h3 class="mb-0">Manage Orders</h3>
          <p class="text-muted mb-0">Update status: Pending → Preparing → Ready for Pickup/Delivery → Completed</p>
        </div>
        <div class="card-body">
          <?php if (isset($success)): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
          <div class="table-responsive">
            <table class="table align-items-center table-flush">
              <thead class="thead-light">
                <tr>
                  <th>Order #</th>
                  <th>Customer</th>
                  <th>Type</th>
                  <th>Items</th>
                  <th>Total (UGX)</th>
                  <th>Status</th>
                  <th>Action</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
<?php
// Group by order_code
$sql = "SELECT order_code, customer_name, 
               MAX(order_type) as order_type,
               MAX(order_status) as order_status,
               MAX(created_at) as created_at,
               SUM(prod_price * prod_qty) as food_total,
               MAX(packaging_fee) as packaging_fee,
               string_agg(prod_name || ' x' || prod_qty, ', ') as items
        FROM rpos_orders 
        GROUP BY order_code, customer_name
        ORDER BY created_at DESC
        LIMIT 100";
$res = $mysqli->query($sql);
if ($res) {
  while ($row = $res->fetch_object()) {
    $total = $row->food_total + (float)$row->packaging_fee;
    $status = $row->order_status ?: 'Pending';
    $badge = 'badge-secondary';
    if ($status == 'Pending') $badge = 'badge-warning';
    if ($status == 'Preparing') $badge = 'badge-info';
    if (strpos($status, 'Ready') !== false) $badge = 'badge-primary';
    if ($status == 'Completed') $badge = 'badge-success';
?>
                <tr>
                  <td><a href="order_view.php?order_code=<?php echo urlencode($row->order_code); ?>"><strong><?php echo htmlspecialchars($row->order_code); ?></strong></a></td>
                  <td><?php echo htmlspecialchars($row->customer_name); ?></td>
                  <td><?php echo htmlspecialchars($row->order_type ?: '—'); ?></td>
                  <td class="small"><?php echo htmlspecialchars($row->items); ?></td>
                  <td><strong><?php echo number_format($total); ?></strong>
                    <?php if ($row->packaging_fee > 0): ?><br><small class="text-muted">incl. packaging <?php echo number_format($row->packaging_fee); ?></small><?php endif; ?>
                  </td>
                  <td><span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                  <td>
                    <form method="POST" class="form-inline">
                      <input type="hidden" name="order_code" value="<?php echo htmlspecialchars($row->order_code); ?>">
                      <select name="new_status" class="form-control form-control-sm mr-1">
                        <option value="Pending" <?php if($status=='Pending') echo 'selected'; ?>>Pending</option>
                        <option value="Preparing" <?php if($status=='Preparing') echo 'selected'; ?>>Preparing</option>
                        <option value="Ready for Pickup/Delivery" <?php if(strpos($status,'Ready')!==false) echo 'selected'; ?>>Ready for Pickup/Delivery</option>
                        <option value="Completed" <?php if($status=='Completed') echo 'selected'; ?>>Completed</option>
                      </select>
                      <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                    </form>
                  </td>
                  <td class="small"><?php echo date('d M Y H:i', strtotime($row->created_at)); ?></td>
                </tr>
<?php } } else { echo "<tr><td colspan='8'>Run the upgrade SQL first (see DATABASE FILE/upgrade_to_ordering.sql)</td></tr>"; } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php require_once('partials/_footer.php'); ?>
</div>
</div>
<?php require_once('partials/_scripts.php'); ?>
</body>
</html>
