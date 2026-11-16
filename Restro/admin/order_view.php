<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
check_login();

$order_code = $_GET['order_code'] ?? '';
if (!$order_code) { header("Location: orders_manage.php"); exit; }

// Update status from this page
if (isset($_POST['update_status'])) {
    $status = $_POST['new_status'];
    $stmt = $mysqli->prepare("UPDATE rpos_orders SET order_status = ? WHERE order_code = ?");
    $stmt->bind_param('ss', $status, $order_code);
    $stmt->execute();
    $success = "Status updated to $status";
}

$ret = "SELECT * FROM rpos_orders WHERE order_code = ? ORDER BY created_at ASC";
$stmt = $mysqli->prepare($ret);
$stmt->bind_param('s', $order_code);
$stmt->execute();
$res = $stmt->get_result();
$items = [];
$customer = $order_type = $status = $created = '';
$packaging = 0;
while ($row = $res->fetch_object()) {
    $items[] = $row;
    $customer = $row->customer_name;
    $order_type = $row->order_type ?? '';
    $status = $row->order_status;
    $created = $row->created_at;
    $packaging = max($packaging, (float)($row->packaging_fee ?? 0));
}
if (empty($items)) { die('Order not found'); }
$food_total = 0;
foreach ($items as $i) $food_total += $i->prod_price * $i->prod_qty;
$grand = $food_total + $packaging;

require_once('partials/_head.php');
?>
<body>
<?php require_once('partials/_sidebar.php'); ?>
<div class="main-content">
<?php require_once('partials/_topnav.php'); ?>
<div style="background-image: url(assets/img/theme/restro00.jpg); background-size: cover;" class="header pb-8 pt-5 pt-md-8">
  <span class="mask bg-gradient-dark opacity-8"></span>
  <div class="container-fluid"><div class="header-body"></div></div>
</div>
<div class="container-fluid mt--8">
  <div class="row">
    <div class="col">
      <div class="card shadow">
        <div class="card-header border-0 d-flex flex-wrap justify-content-between align-items-center">
          <div>
            <h3 class="mb-0">Order #<?php echo htmlspecialchars($order_code); ?></h3>
            <p class="mb-0 text-muted">Customer: <strong><?php echo htmlspecialchars($customer); ?></strong> · Type: <strong><?php echo htmlspecialchars($order_type ?: '—'); ?></strong></p>
          </div>
          <div class="mt-2 mt-md-0">
            <a href="print_receipt.php?order_code=<?php echo urlencode($order_code); ?>" class="btn btn-success" target="_blank">
              <i class="fas fa-print"></i> Print Receipt
            </a>
            <a href="orders_manage.php" class="btn btn-secondary">Back</a>
          </div>
        </div>
        <div class="card-body">
          <?php if (isset($success)): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead class="thead-light">
                <tr><th>Item</th><th class="text-center">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Line Total</th></tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                  <td><?php echo htmlspecialchars($item->prod_name); ?></td>
                  <td class="text-center"><?php echo $item->prod_qty; ?></td>
                  <td class="text-right">UGX <?php echo number_format($item->prod_price); ?></td>
                  <td class="text-right">UGX <?php echo number_format($item->prod_price * $item->prod_qty); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                  <td colspan="3" class="text-right"><strong>Food Subtotal</strong></td>
                  <td class="text-right"><strong>UGX <?php echo number_format($food_total); ?></strong></td>
                </tr>
                <?php if ($packaging > 0): ?>
                <tr>
                  <td colspan="3" class="text-right">Packaging (Takeaway)</td>
                  <td class="text-right">UGX <?php echo number_format($packaging); ?></td>
                </tr>
                <?php endif; ?>
                <tr class="table-success">
                  <td colspan="3" class="text-right"><h4 class="mb-0">Total</h4></td>
                  <td class="text-right"><h4 class="mb-0">UGX <?php echo number_format($grand); ?></h4></td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="row mt-4">
            <div class="col-md-6">
              <p><strong>Status:</strong> <span class="badge badge-primary"><?php echo htmlspecialchars($status); ?></span></p>
              <p><strong>Placed:</strong> <?php echo date('d M Y, H:i', strtotime($created)); ?></p>
            </div>
            <div class="col-md-6">
              <form method="POST" class="form-inline">
                <label class="mr-2">Update status:</label>
                <select name="new_status" class="form-control form-control-sm mr-2">
                  <option value="Pending" <?php if($status=='Pending') echo 'selected'; ?>>Pending</option>
                  <option value="Preparing" <?php if($status=='Preparing') echo 'selected'; ?>>Preparing</option>
                  <option value="Ready for Pickup/Delivery" <?php if(strpos($status,'Ready')!==false) echo 'selected'; ?>>Ready for Pickup/Delivery</option>
                  <option value="Completed" <?php if($status=='Completed') echo 'selected'; ?>>Completed</option>
                </select>
                <button type="submit" name="update_status" class="btn btn-sm btn-primary">Save</button>
              </form>
            </div>
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
