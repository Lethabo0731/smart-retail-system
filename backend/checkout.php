<?php
// checkout.php
require_once 'config.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error'=>'Invalid method'],405);
}

if (!validate_csrf_token(get_post('csrf_token'))) {
    json_response(['error' => 'Invalid CSRF token'], 403);
}

if (empty($_SESSION['user_id'])) {
    json_response(['error'=>'Authentication required'],401);
}
$userId = (int)$_SESSION['user_id'];

$payment_method = trim(get_post('payment_method',''));
$cartRaw = get_post('cart',''); // either JSON or array

if ($cartRaw === '') {
    json_response(['error'=>'Cart is empty'],400);
}

// decode if JSON string
if (is_string($cartRaw)) {
    $cart = json_decode($cartRaw, true);
    if (!is_array($cart)) {
        json_response(['error'=>'Invalid cart format'],400);
    }
} else {
    $cart = $cartRaw;
}

// Normalize and validate cart
$items = [];
foreach ($cart as $c) {
    $pid = isset($c['product_id']) ? (int)$c['product_id'] : (int)($c['id'] ?? 0);
    $qty = isset($c['quantity']) ? (int)$c['quantity'] : (int)($c['qty'] ?? 0);
    if ($pid <= 0 || $qty <= 0) {
        json_response(['error'=>'Invalid cart item'],400);
    }
    $items[$pid] = ($items[$pid] ?? 0) + $qty;
}

$pdo = getPDO();
try {
    $pdo->beginTransaction();

    // Fetch product rows to check stock and price
    $in = implode(',', array_fill(0, count($items), '?'));
    $stmt = $pdo->prepare("SELECT id, price, stock, name FROM products WHERE id IN ($in) FOR UPDATE");
    $stmt->execute(array_keys($items));
    $products = [];
    while ($r = $stmt->fetch()) {
        $products[$r['id']] = $r;
    }

    // Ensure all products exist and have enough stock
    $total = 0.0;
    foreach ($items as $pid => $qty) {
        if (!isset($products[$pid])) {
            $pdo->rollBack();
            json_response(['error'=>"Product $pid not found"],400);
        }
        if ($products[$pid]['stock'] < $qty) {
            $pdo->rollBack();
            json_response(['error'=>"Insufficient stock for product {$products[$pid]['name']}"],409);
        }
        $total += (float)$products[$pid]['price'] * $qty;
    }

    // Create order
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, payment_method, status) VALUES (:uid, :total, :pm, :status)");
    $stmt->execute([
        ':uid' => $userId,
        ':total' => $total,
        ':pm' => $payment_method,
        ':status' => 'paid' // depending on payment gateway, might be 'pending'
    ]);
    $orderId = (int)$pdo->lastInsertId();

    // Insert order_items and decrement stock
    $insertItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, price, quantity) VALUES (:order_id, :product_id, :price, :quantity)");
    $updateStock = $pdo->prepare("UPDATE products SET stock = stock - :qty WHERE id = :id AND stock >= :qty");

    foreach ($items as $pid => $qty) {
        $price = $products[$pid]['price'];
        $insertItem->execute([':order_id'=>$orderId, ':product_id'=>$pid, ':price'=>$price, ':quantity'=>$qty]);

        $updateStock->execute([':qty'=>$qty, ':id'=>$pid]);
        if ($updateStock->rowCount() === 0) {
            // Race condition / insufficient stock
            $pdo->rollBack();
            json_response(['error'=>"Failed to update stock for product id $pid"],500);
        }
    }

    $pdo->commit();
    json_response(['success'=>true, 'order_id'=>$orderId, 'total'=>$total], 201);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['error'=>'Server error'],500);
}
