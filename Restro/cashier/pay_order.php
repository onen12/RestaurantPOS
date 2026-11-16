<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
include('config/code-generator.php');

check_login();

if (isset($_POST['pay'])) {
  //Prevent Posting Blank Values
  if (empty($_POST["pay_amt"]) || empty($_POST['pay_method'])) {
    $err = "Blank Values Not Accepted";
  } else {
    $pay_method = $_POST['pay_method'];
    $pay_amt  = $_POST['pay_amt'];
    $pay_id = $_POST['pay_id'];
    $order_code = $_GET['order_code'];
    $customer_id = $_GET['customer_id'];
    $order_status = $_GET['order_status'];

    // For Cash payments, auto-generate a code so user does not need to enter anything
    if ($pay_method === 'Cash') {
      $pay_code = empty($_POST["pay_code"]) ? ('CASH-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8))) : $_POST['pay_code'];
    } else {
      $pay_code = isset($_POST['pay_code']) ? trim($_POST['pay_code']) : '';
      if (empty($pay_code)) {
        $err = "Please enter the Mobile Money transaction code";
      }
    }

    if (!isset($err)) {
      //Insert Captured information to a database table
      $postQuery = "INSERT INTO rpos_payments (pay_id, pay_code, order_code, customer_id, pay_amt, pay_method) VALUES(?,?,?,?,?,?)";
      $upQry = "UPDATE rpos_orders SET order_status =? WHERE order_code =?";

      $postStmt = $mysqli->prepare($postQuery);
      $upStmt = $mysqli->prepare($upQry);
      //bind paramaters

      $rc = $postStmt->bind_param('ssssss', $pay_id, $pay_code, $order_code, $customer_id, $pay_amt, $pay_method);
      $rc = $upStmt->bind_param('ss', $order_status, $order_code);

      $postStmt->execute();
      $upStmt->execute();
      //declare a varible which will be passed to alert function
      if ($upStmt && $postStmt) {
        $success = "Paid" && header("refresh:1; url=receipts.php");
      } else {
        $err = "Please Try Again Or Try Later";
      }
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
    $order_code = $_GET['order_code'];
    $ret = "SELECT * FROM  rpos_orders WHERE order_code ='$order_code' ";
    $stmt = $mysqli->prepare($ret);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($order = $res->fetch_object()) {
        $total = ($order->prod_price * $order->prod_qty);
    ?>
    
    <!-- Header -->
    <div style="background-image: url(../admin/assets/img/theme/restro00.jpg); background-size: cover;" class="header  pb-8 pt-5 pt-md-8">
    <span class="mask bg-gradient-dark opacity-8"></span>
      <div class="container-fluid">
        <div class="header-body">
        </div>
      </div>
    </div><!-- For more projects: Visit codeastro.com  -->
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
              <form method="POST"  enctype="multipart/form-data">
                <div class="form-row">
                  <div class="col-md-6">
                    <label>Payment ID</label>
                    <input type="text" name="pay_id" readonly value="<?php echo $payid;?>" class="form-control">
                  </div>
                  <div class="col-md-6">
                    <label>Mobile Money Transaction Code</label>
                    <input type="text" name="pay_code" placeholder="Enter MoMo / Airtel Money confirmation code" class="form-control">
                  </div>
                </div>
                <hr>
                <div class="form-row">
                  <div class="col-md-6">
                    <label>Amount (UGX)</label>
                    <input type="text" name="pay_amt" readonly value="<?php echo $total;?>" class="form-control">
                  </div>
                  <div class="col-md-6">
                    <label>Payment Method</label>
                    <select class="form-control" name="pay_method" id="pay_method" required>
                        <option value="">-- Select Method --</option>
                        <option selected>Mobile Money</option>
                        <option>Cash</option>
                    </select>
                  </div>
                </div>
                <hr>
                <div class="form-row" id="momo-section">
                  <div class="col-md-12 text-center">
                    <h4 class="text-primary">Scan QR to Pay via Mobile Money</h4>
                    <p class="mb-2">Send <strong>UGX <?php echo $total; ?></strong> to one of these numbers using MTN MoMo or Airtel Money:</p>
                    <p class="h5"><strong>0750541790</strong> &nbsp;|&nbsp; <strong>0788507153</strong></p>
                    <p class="text-muted small">Then enter the confirmation / transaction code in the field above.</p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?php echo urlencode('HOME TASTE FOODS - Pay UGX '.$total.' via Mobile Money to 0750541790 or 0788507153. After payment enter the transaction code.'); ?>" alt="Mobile Money QR Code" class="img-thumbnail mt-2 mb-3" style="max-width:220px;">
                  </div>
                </div>
                <br>
                <div class="form-row">
                  <div class="col-md-6">
                    <input type="submit" name="pay" value="Confirm Payment" class="btn btn-success">
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div><!-- For more projects: Visit codeastro.com  -->
      <!-- Footer -->
      <?php
      require_once('partials/_footer.php');
      ?>
    </div>
  </div>
  <!-- Argon Scripts -->
  <?php
  require_once('partials/_scripts.php'); }
  ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
  var methodSelect = document.getElementById("pay_method");
  var codeInput = document.querySelector("input[name='pay_code']");
  var momoSection = document.getElementById("momo-section");
  if (!methodSelect || !codeInput) return;

  function toggleCash() {
    var isCash = methodSelect.value === "Cash";
    if (momoSection) {
      momoSection.style.display = isCash ? "none" : "block";
    }
    if (isCash) {
      codeInput.removeAttribute("required");
      codeInput.placeholder = "Optional for cash (auto-generated)";
      codeInput.value = "";
    } else {
      codeInput.setAttribute("required", "required");
      codeInput.placeholder = "Enter MoMo / Airtel Money confirmation code";
    }
  }
  methodSelect.addEventListener("change", toggleCash);
  toggleCash(); // initial
});
</script>

</body>
<!-- For more projects: Visit codeastro.com  -->
</html>