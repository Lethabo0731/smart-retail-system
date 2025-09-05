<?php
// product_read.php
require_once 'config.php';
require_once 'helpers.php';

$pdo = getPDO();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if ($id) {
    $stmt = $pdo->prepare("SELECT id,sku,name,description,price,stock,image_url,created_at,updated_at FROM products WHERE id = :id");
    $stmt->execute([':id'=>$id]);
    $product = $stmt->fetch();
    if (!$product) json_response(['error'=>'Not found'],404);
    json_response($product);
}

// list with simple pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(50, max(5, (int)($_GET['per_page'] ?? 12)));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS id,sku,name,description,price,stock,image_url FROM products ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$total = (int)$pdo->query("SELECT FOUND_ROWS()")->fetchColumn();

json_response(['data'=>$products, 'pagination'=>['page'=>$page,'per_page'=>$perPage,'total'=>$total]]);
