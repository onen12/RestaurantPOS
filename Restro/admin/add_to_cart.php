<?php
session_start();
include('config/config.php');
include('config/checklogin.php');
include('config/cart.php');
check_login();

if (isset($_GET['prod_id']) && isset($_GET['prod_name']) && isset($_GET['prod_price'])) {
    $qty = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;
    cart_add($_GET['prod_id'], $_GET['prod_name'], $_GET['prod_price'], $qty);
    header("Location: cart.php");
    exit;
}
header("Location: products.php");
