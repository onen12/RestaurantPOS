<?php
/**
 * HOME TASTE FOODS - Cart Helper
 * Session-based cart shared across admin / cashier / customer
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = []; // key = prod_id, value = ['prod_id','prod_name','prod_price','qty']
}

function cart_add($prod_id, $prod_name, $prod_price, $qty = 1) {
    $qty = max(1, (int)$qty);
    if (isset($_SESSION['cart'][$prod_id])) {
        $_SESSION['cart'][$prod_id]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$prod_id] = [
            'prod_id'    => $prod_id,
            'prod_name'  => $prod_name,
            'prod_price' => (float)$prod_price,
            'qty'        => $qty
        ];
    }
}

function cart_update($prod_id, $qty) {
    $qty = (int)$qty;
    if ($qty <= 0) {
        unset($_SESSION['cart'][$prod_id]);
    } elseif (isset($_SESSION['cart'][$prod_id])) {
        $_SESSION['cart'][$prod_id]['qty'] = $qty;
    }
}

function cart_remove($prod_id) {
    unset($_SESSION['cart'][$prod_id]);
}

function cart_clear() {
    $_SESSION['cart'] = [];
}

function cart_count() {
    $c = 0;
    foreach ($_SESSION['cart'] as $item) {
        $c += $item['qty'];
    }
    return $c;
}

function cart_subtotal() {
    $sum = 0;
    foreach ($_SESSION['cart'] as $item) {
        $sum += $item['prod_price'] * $item['qty'];
    }
    return $sum;
}

/**
 * Packaging fee: 1000 UGX for every single plate/item quantity when Takeaway
 */
function cart_packaging_fee($order_type) {
    if (strtolower($order_type) !== 'takeaway') {
        return 0;
    }
    $plates = 0;
    foreach ($_SESSION['cart'] as $item) {
        $plates += $item['qty'];
    }
    return $plates * 1000;
}

function cart_total($order_type) {
    return cart_subtotal() + cart_packaging_fee($order_type);
}

function cart_items() {
    return $_SESSION['cart'];
}

function cart_is_empty() {
    return empty($_SESSION['cart']);
}
