<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
check_login();

$order_code = $_GET['order_code'] ?? '';
$customer_id = $_SESSION['customer_id'] ?? '';
if (!$order_code) { header("Location: orders_reports.php"); exit; }

$ret = "SELECT * FROM rpos_orders WHERE order_code = ? AND customer_id = ? ORDER BY created_at ASC";
$stmt = $mysqli->prepare($ret);
$stmt->bind_param('ss', $order_code, $customer_id);
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
if (empty($items)) { die('Order not found or access denied'); }
$food_total = 0;
foreach ($items as $i) $food_total += $i->prod_price * $i->prod_qty;
$grand = $food_total + $packaging;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>E-Receipt <?php echo htmlspecialchars($order_code); ?></title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: "Courier New", Courier, monospace;
      font-size: 11px;
      line-height: 1.4;
      color: #000;
      background: #f0f0f0;
      padding: 12px;
    }
    .receipt {
      background: #fff;
      width: 58mm;
      max-width: 100%;
      margin: 0 auto;
      padding: 4px 5px;
      box-shadow: 0 2px 8px rgba(0,0,0,.1);
    }
    .center { text-align: center; }
    .right { text-align: right; }
    .bold { font-weight: bold; }
    .brand { font-size: 12px; font-weight: bold; }
    .muted { font-size: 11px; }
    .line { border-top: 1px dashed #000; margin: 8px 0; }
    .dbl { border-top: 2px solid #000; margin: 8px 0; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 3px 0; vertical-align: top; }
    th { text-align: left; font-size: 11px; border-bottom: 1px solid #000; }
    .qty { width: 12%; text-align: center; }
    .price { width: 30%; text-align: right; }
    .total-row td { font-weight: bold; font-size: 12px; }
    .actions { max-width: 58mm; margin: 12px auto 0; text-align: center; }
    .btn {
      display: inline-block; padding: 10px 18px; background: #28a745; color: #fff;
      border: none; border-radius: 6px; font-size: 15px; cursor: pointer;
      text-decoration: none; font-family: sans-serif;
    }
    .btn-link { background: transparent; color: #333; margin-top: 8px; display: block; }
    @media print {
      body { background: #fff; padding: 0; }
      .receipt { box-shadow: none; width: 58mm; }
      .actions { display: none !important; }
      @page { margin: 0; size: 58mm auto; }
    }
  </style>
</head>
<body>
  <div class="receipt" id="Receipt">
    <div class="center">
      <div class="brand">HOME TASTE FOODS</div>
      <div class="muted">Kansanga Ggabba Road</div>
      <div class="muted">Next to KIU Girls Hostel</div>
      <div class="muted">Tel: 0750541790 / 0788507153</div>
    </div>
    <div class="line"></div>
    <div>
      <div>Receipt #: <span class="bold"><?php echo htmlspecialchars($order_code); ?></span></div>
      <div>Date: <?php echo date('d/m/Y H:i', strtotime($created)); ?></div>
      <div>Customer: <?php echo htmlspecialchars($customer); ?></div>
      <div>Type: <span class="bold"><?php echo htmlspecialchars($order_type ?: '—'); ?></span></div>
      <div>Status: <?php echo htmlspecialchars($status); ?></div>
    </div>
    <div class="line"></div>
    <table>
      <thead>
        <tr><th>Item</th><th class="qty">Qty</th><th class="price">Amount</th></tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
          <td><?php echo htmlspecialchars($item->prod_name); ?></td>
          <td class="qty"><?php echo $item->prod_qty; ?></td>
          <td class="price"><?php echo number_format($item->prod_price * $item->prod_qty); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="line"></div>
    <table>
      <tr><td>Subtotal</td><td class="right"><?php echo number_format($food_total); ?></td></tr>
      <?php if ($packaging > 0): ?>
      <tr><td>Packaging</td><td class="right"><?php echo number_format($packaging); ?></td></tr>
      <?php endif; ?>
      <tr class="total-row"><td>TOTAL (UGX)</td><td class="right"><?php echo number_format($grand); ?></td></tr>
    </table>
    <div class="dbl"></div>
    <div class="center muted">Thank you for choosing<br>HOME TASTE FOODS</div>
  </div>
  <div class="actions">
    <button class="btn" onclick="window.print()">Save / Print E-Receipt</button>
    <a href="orders_reports.php" class="btn btn-link">Back to My Orders</a>
  </div>
</body>
</html>
