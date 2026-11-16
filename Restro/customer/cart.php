<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
include('config/code-generator.php');
include('config/cart.php');
check_login();

$customer_id = $_SESSION['customer_id'] ?? '';
$customer_name = $_SESSION['customer_name'] ?? '';

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

if (isset($_POST['place_order'])) {
    if (cart_is_empty()) {
        $err = "Cart is empty";
    } elseif (empty($_POST['order_type'])) {
        $err = "Please select Order Type (Dine-in or Takeaway)";
    } else {
        $order_type   = $_POST['order_type'];
        $order_code   = $alpha . '-' . $beta;
        $order_status = 'Pending';
        $packaging    = cart_packaging_fee($order_type);
        $ok = true;
        $first = true;

        foreach (cart_items() as $item) {
            $order_id = bin2hex(random_bytes(5));
            $prod_qty = $item['qty'];
            $prod_id  = $item['prod_id'];
            $prod_name= $item['prod_name'];
            $prod_price = $item['prod_price'];
            $pkg = $first ? $packaging : 0;
            $first = false;

            $q = "INSERT INTO rpos_orders (order_id, order_code, customer_id, customer_name, prod_id, prod_name, prod_price, prod_qty, order_status, order_type, packaging_fee) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
            $stmt = $mysqli->prepare($q);
            if ($stmt) {
                $stmt->bind_param('ssssssssssd', $order_id, $order_code, $customer_id, $customer_name, $prod_id, $prod_name, $prod_price, $prod_qty, $order_status, $order_type, $pkg);
            } else {
                $q = "INSERT INTO rpos_orders (order_id, order_code, customer_id, customer_name, prod_id, prod_name, prod_price, prod_qty, order_status) VALUES (?,?,?,?,?,?,?,?,?)";
                $stmt = $mysqli->prepare($q);
                $stmt->bind_param('sssssssss', $order_id, $order_code, $customer_id, $customer_name, $prod_id, $prod_name, $prod_price, $prod_qty, $order_status);
            }
            if (!$stmt->execute()) {
                $ok = false;
                $err = "Could not place order. Please try again.";
                break;
            }
        }
        if ($ok) {
            cart_clear();
            $success = "Order placed! You can track it under My Orders.";
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
        <div class="card-header border-0 d-flex justify-content-between">
          <h3 class="mb-0">My Cart (<?php echo cart_count(); ?> items)</h3>
          <a href="orders.php" class="btn btn-sm btn-outline-primary">My Orders</a>
        </div>
        <div class="card-body">
          <?php if (isset($err)): ?><div class="alert alert-danger"><?php echo $err; ?></div><?php endif; ?>
          <?php if (isset($success)): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

          <?php if (cart_is_empty()): ?>
            <p class="text-center text-muted py-5">Cart is empty. <a href="orders.php">Browse menu</a></p>
          <?php else: ?>
          <form method="POST">
            <table class="table">
              <thead><tr><th>Item</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead>
              <tbody>
              <?php foreach (cart_items() as $item): ?>
                <tr>
                  <td><?php echo htmlspecialchars($item['prod_name']); ?></td>
                  <td>UGX <?php echo number_format($item['prod_price']); ?></td>
                  <td><input type="number" name="qty[<?php echo $item['prod_id']; ?>]" value="<?php echo $item['qty']; ?>" min="1" class="form-control form-control-sm" style="width:80px"></td>
                  <td>UGX <?php echo number_format($item['prod_price']*$item['qty']); ?></td>
                  <td><a href="cart.php?remove=<?php echo $item['prod_id']; ?>" class="text-danger">&times;</a></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
            <button type="submit" name="update_cart" class="btn btn-sm btn-secondary">Update</button>
          </form>
          <hr>
          <form method="POST">
            <div class="row">
              <div class="col-md-6">
                <label>Order Type <span class="text-danger">*</span></label>
                <select name="order_type" id="order_type" class="form-control" required>
                  <option value="">-- Choose Dine-in or Takeaway --</option>
                  <option value="Dine-in">Dine-in (no packaging fee)</option>
                  <option value="Takeaway">Takeaway (+ UGX 1,000 per plate)</option>
                </select>
                <p class="small text-muted mt-2">Takeaway adds UGX 1,000 packaging for every plate/item.</p>
              </div>
              <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                  <div>Food subtotal: <strong>UGX <span id="subtotal"><?php echo number_format(cart_subtotal()); ?></span></strong></div>
                  <div>Packaging: <strong>UGX <span id="packaging">0</span></strong></div>
                  <div class="h4 mt-2">Total: <strong class="text-success">UGX <span id="grandtotal"><?php echo number_format(cart_subtotal()); ?></span></strong></div>
                </div>
              </div>
            </div>
            <button type="submit" name="place_order" class="btn btn-success btn-lg mt-3">Place Order</button>
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
