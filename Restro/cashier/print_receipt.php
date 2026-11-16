<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
check_login();

$order_code = $_GET['order_code'] ?? '';
if (!$order_code) { die('Missing order code'); }

$ret = "SELECT * FROM rpos_orders WHERE order_code = ? ORDER BY created_at ASC";
$stmt = $mysqli->prepare($ret);
$stmt->bind_param('s', $order_code);
$stmt->execute();
$res = $stmt->get_result();
$items = [];
$customer = '';
$order_type = '';
$packaging = 0;
$created = '';
$status = '';
while ($row = $res->fetch_object()) {
    $items[] = $row;
    $customer = $row->customer_name;
    $order_type = $row->order_type ?? '';
    $packaging = max($packaging, (float)($row->packaging_fee ?? 0));
    $created = $row->created_at;
    $status = $row->order_status;
}
if (empty($items)) { die('Order not found'); }

$food_total = 0;
foreach ($items as $i) { $food_total += $i->prod_price * $i->prod_qty; }
$grand = $food_total + $packaging;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Receipt <?php echo htmlspecialchars($order_code); ?></title>
  <style>
    /* Thermal printer friendly: 58mm (~302px) and 58mm (~219px) */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: "Courier New", Courier, monospace;
      font-size: 11px;
      line-height: 1.35;
      color: #000;
      background: #fff;
      width: 58mm;
      max-width: 58mm;
      margin: 0 auto;
      padding: 2mm 1.5mm;
    }
    .center { text-align: center; }
    .right { text-align: right; }
    .bold { font-weight: bold; }
    .brand { font-size: 12px; font-weight: bold; letter-spacing: 0.5px; }
    .muted { font-size: 10px; }
    .line { border-top: 1px dashed #000; margin: 6px 0; }
    .dbl { border-top: 2px solid #000; margin: 6px 0; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 2px 0; vertical-align: top; }
    th { text-align: left; font-size: 10px; border-bottom: 1px solid #000; }
    .qty { width: 12%; text-align: center; }
    .price { width: 28%; text-align: right; }
    .total-row td { font-weight: bold; font-size: 11px; padding-top: 4px; }
    .no-print { margin-top: 12px; text-align: center; }
    .btn {
      display: inline-block;
      padding: 8px 16px;
      background: #28a745;
      color: #fff;
      border: none;
      border-radius: 4px;
      font-size: 12px;
      cursor: pointer;
      text-decoration: none;
      font-family: sans-serif;
    }
    .btn-secondary { background: #6c757d; margin-left: 6px; }
    @media print {
      body { width: 58mm; max-width: 58mm; padding: 2mm; }
      .no-print { display: none !important; }
      @page { margin: 0; size: 58mm auto; }
    }
    /* 58mm printers – optional narrower class */
    @media (max-width: 60mm) {
      body { width: 58mm; max-width: 58mm; font-size: 11px; }
    }
  </style>
</head>
<body onload="window.print()">
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
      <tr>
        <th>Item</th>
        <th class="qty">Qty</th>
        <th class="price">Amount</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item):
        $line = $item->prod_price * $item->prod_qty; ?>
      <tr>
        <td><?php echo htmlspecialchars($item->prod_name); ?></td>
        <td class="qty"><?php echo $item->prod_qty; ?></td>
        <td class="price"><?php echo number_format($line); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="line"></div>

  <table>
    <tr>
      <td>Subtotal</td>
      <td class="right"><?php echo number_format($food_total); ?></td>
    </tr>
    <?php if ($packaging > 0): ?>
    <tr>
      <td>Packaging</td>
      <td class="right"><?php echo number_format($packaging); ?></td>
    </tr>
    <?php endif; ?>
    <tr class="total-row">
      <td>TOTAL (UGX)</td>
      <td class="right"><?php echo number_format($grand); ?></td>
    </tr>
  </table>

  <div class="dbl"></div>

  <div class="center muted">
    Thank you for choosing<br>
    HOME TASTE FOODS
  </div>

  <div class="no-print">
    <button class="btn" onclick="window.print()">Print again</button>
    <a href="orders_manage.php" class="btn btn-secondary">Back</a>
  </div>
</body>
</html>
