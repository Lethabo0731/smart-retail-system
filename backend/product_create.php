<?php
// product_create.php
require_once 'config.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Invalid method'], 405);
}
if (!validate_csrf_token(get_post('csrf_token'))) {
    json_response(['error' => 'Invalid CSRF token'], 403);
}

$name = trim(get_post('name',''));
$sku = trim(get_post('sku','')) ?: null;
$description = trim(get_post('description',''));
$price = (float) get_post('price', 0);
$stock = (int) get_post('stock', 0);
$image_url = trim(get_post('image_url',''));

if ($name === '' || $price <= 0 || $stock < 0) {
    json_response(['error' => 'Invalid product data'], 400);
}

$pdo = getPDO();
try {
    $stmt = $pdo->prepare("INSERT INTO products (sku,name,description,price,stock,image_url) VALUES (:sku,:name,:desc,:price,:stock,:img)");
    $stmt->execute([
        ':sku'=>$sku,
        ':name'=>$name,
        ':desc'=>$description,
        ':price'=>$price,
        ':stock'=>$stock,
        ':img'=>$image_url
    ]);
    json_response(['success'=>true,'product_id'=> (int)$pdo->lastInsertId()], 201);
} catch (Exception $e) {
    json_response(['error'=>'Server error'],500);
}
