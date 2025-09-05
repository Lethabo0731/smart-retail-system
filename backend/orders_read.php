<?php
// orders_read.php
require_once 'config.php';
require_once 'helpers.php';

$pdo = getPDO();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($id) {
    $stmt = $pdo->prepare("SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = :id");
    $stmt->execute([':id'=>$id]);
    $order = $stmt->fetch();
    if (!$order) json_response(['error'=>'Not found'],404);

    $stmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = :oid");
    $stmt->execute([':oid'=> $id]);
    $items = $stmt->fetchAll();

    $order['items'] = $items;
    json_response($order);
}

// list recent orders
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(50, max(10, (int)($_GET['per_page'] ?? 20)));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS o.id,o.user_id,o.total_amount,o.status,o.created_at,u.full_name FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit',$perPage,PDO::PARAM_INT);
$stmt->bindValue(':offset',$offset,PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();
$total = (int)$pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
json_response(['data'=>$orders,'pagination'=>['page'=>$page,'per_page'=>$perPage,'total'=>$total]]);
