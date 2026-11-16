<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
include('config/code-generator.php');
include('config/cart.php');
check_login();

// Handle cart actions
if (isset($_GET['remove'])) {
    cart_remove($_GET['remove']);
    header("Location: cart.php");
    exit;
}
if (isset($_GET['clear'])) {
    cart_clear();
    header("Location: cart.php");
    exit;
}
if (isset($_POST['update_cart'])) {
    foreach ($_POST['qty'] as $pid => $q) {
        cart_update($pid, $q);
    }
    header("Location: cart.php");
    exit;
}

// Place order (checkout)
if (isset($_POST['place_order'])) {
    if (cart_is_empty()) {
        $err = "Cart is empty";
    } elseif (empty($_POST['order_type']) || empty($_POST['customer_name'])) {
        $err = "Please select Order Type and Customer";
    } else {
        $order_type   = $_POST['order_type']; // Dine-in or Takeaway
        $customer_id  = $_POST['customer_id'];
        $customer_name= $_POST['customer_name'];
        $order_code   = $alpha . '-' . $beta;
        $order_status = 'Pending';
        $packaging    = cart_packaging_fee($order_type);
        $ok = true;

        foreach (cart_items() as $item) {
            $order_id = bin2hex(random_bytes(5));
            $prod_qty = $item['qty'];
            $prod_id  = $item['prod_id'];
            $prod_name= $item['prod_name'];
            $prod_price = $item['prod_price'];

            // We store packaging_fee only on the first row of the order group to avoid double counting
            // For simplicity we store order_type and packaging on every row (same value)
            $q = "INSERT INTO rpos_orders (order_id, order_code, customer_id, customer_name, prod_id, prod_name, prod_price, prod_qty, order_status, order_type, packaging_fee) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
            $stmt = $mysqli->prepare($q);
            if (!$stmt) {
                // columns may not exist yet – fallback without new columns
                $q = "INSERT INTO rpos_orders (order_id, order_code, customer_id, customer_name, prod_id, prod_name, prod_price, prod_qty, order_status) VALUES (?,?,?,?,?,?,?,?,?)";
                $stmt = $mysqli->prepare($q);
                $stmt->bind_param('sssssssss', $order_id, $order_code, $customer_id, $customer_name, $prod_id, $prod_name, $prod_price, $prod_qty, $order_status);
            } else {
                $pkg = ($item === reset($_SESSION['cart'])) ? $packaging : 0; // store full packaging only once
                $stmt->bind_param('ssssssssssd', $order_id, $order_code, $customer_id, $customer_name, $prod_id, $prod_name, $prod_price, $prod_qty, $order_status, $order_type, $pkg);
            }
            if (!$stmt->execute()) {
                $ok = false;
                $err = "Failed to save order item: " . $mysqli->error;
                break;
            }
        }

        if ($ok) {
            cart_clear();
            $success = "Order placed successfully!";
            header("refresh:1; url=orders.php");
        }
    }
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
        <div class="card-header border-0 d-flex justify-content-between align-items-center">
          <h3 class="mb-0">Shopping Cart (<?php echo cart_count(); ?> items)</h3>
          <div>
            <a href="products.php" class="btn btn-sm btn-outline-primary">Continue Ordering</a>
            <?php if (!cart_is_empty()): ?>
            <a href="cart.php?clear=1" class="btn btn-sm btn-outline-danger" onclick="return confirm('Clear entire cart?')">Clear Cart</a>
            <?php endif; ?>
          </div>
        </div>
        <div class="card-body">
          <?php if (isset($err)): ?><div class="alert alert-danger"><?php echo $err; ?></div><?php endif; ?>
          <?php if (isset($success)): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

          <?php if (cart_is_empty()): ?>
            <p class="text-muted text-center py-5">Your cart is empty. <a href="products.php">Add some food</a></p>
          <?php else: ?>
          <form method="POST">
            <div class="table-responsive">
              <table class="table align-items-center">
                <thead class="thead-light">
                  <tr>
                    <th>Item</th>
                    <th>Unit Price</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach (cart_items() as $item): ?>
                  <tr>
                    <td><strong><?php echo htmlspecialchars($item['prod_name']); ?></strong></td>
                    <td>UGX <?php echo number_format($item['prod_price']); ?></td>
                    <td style="width:100px">
                      <input type="number" name="qty[<?php echo $item['prod_id']; ?>]" value="<?php echo $item['qty']; ?>" min="1" class="form-control form-control-sm">
                    </td>
                    <td>UGX <?php echo number_format($item['prod_price'] * $item['qty']); ?></td>
                    <td><a href="cart.php?remove=<?php echo $item['prod_id']; ?>" class="btn btn-sm btn-danger">&times;</a></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <button type="submit" name="update_cart" class="btn btn-sm btn-secondary mb-4">Update Quantities</button>
          </form>

          <hr>
          <form method="POST">
            <div class="row">
              <div class="col-md-4">
                <label>Customer <span class="text-danger">*</span></label>
                <select name="customer_name" id="customer_select" class="form-control" required>
                  <option value="">-- Select Customer --</option>
                  <?php
                  $ret = "SELECT * FROM rpos_customers ORDER BY customer_name ASC";
                  $stmt = $mysqli->prepare($ret);
                  $stmt->execute();
                  $res = $stmt->get_result();
                  while ($c = $res->fetch_object()) {
                    echo '<option value="'.htmlspecialchars($c->customer_name).'" data-id="'.$c->customer_id.'">'.htmlspecialchars($c->customer_name).'</option>';
                  }
                  ?>
                </select>
                <input type="hidden" name="customer_id" id="customer_id" value="">
              </div>
              <div class="col-md-4">
                <label>Order Type <span class="text-danger">*</span></label>
                <select name="order_type" id="order_type" class="form-control" required>
                  <option value="">-- Choose --</option>
                  <option value="Dine-in">Dine-in (no packaging fee)</option>
                  <option value="Takeaway">Takeaway (+ UGX 1,000 per plate)</option>
                </select>
              </div>
              <div class="col-md-4">
                <label>Summary</label>
                <div class="p-3 bg-light rounded">
                  <div>Food: <strong>UGX <span id="subtotal"><?php echo number_format(cart_subtotal()); ?></span></strong></div>
                  <div>Packaging: <strong>UGX <span id="packaging">0</span></strong></div>
                  <div class="h4 mt-2">Total: <strong class="text-success">UGX <span id="grandtotal"><?php echo number_format(cart_subtotal()); ?></span></strong></div>
                </div>
              </div>
            </div>
            <div class="mt-4">
              <button type="submit" name="place_order" class="btn btn-success btn-lg">Place Order</button>
            </div>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php require_once('partials/_footer.php'); ?>
</div>
</div>
<?php require_once('partials/_scripts.php'); ?>
<script>
document.getElementById('customer_select')?.addEventListener('change', function() {
  var opt = this.options[this.selectedIndex];
  document.getElementById('customer_id').value = opt.getAttribute('data-id') || '';
});
var subtotal = <?php echo cart_subtotal(); ?>;
var totalPlates = <?php echo cart_count(); ?>;
document.getElementById('order_type')?.addEventListener('change', function() {
  var pkg = (this.value === 'Takeaway') ? (totalPlates * 1000) : 0;
  document.getElementById('packaging').textContent = pkg.toLocaleString();
  document.getElementById('grandtotal').textContent = (subtotal + pkg).toLocaleString();
});
</script>
</body>
</html>
