<?php
// product_update.php
require_once 'config.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error'=>'Invalid method'],405);
}
if (!validate_csrf_token(get_post('csrf_token'))) {
    json_response(['error' => 'Invalid CSRF token'], 403);
}

$id = (int)get_post('id', 0);
if ($id <= 0) json_response(['error'=>'Invalid id'],400);

$fields = [];
$params = [':id'=>$id];

foreach (['name','sku','description','price','stock','image_url'] as $f) {
    if (isset($_POST[$f])) {
        $fields[] = "$f = :$f";
        $params[":$f"] = $f === 'price' ? (float)$_POST[$f] : ($f === 'stock' ? (int)$_POST[$f] : trim($_POST[$f]));
    }
}

if (empty($fields)) json_response(['error'=>'Nothing to update'],400);

$pdo = getPDO();
try {
    $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    json_response(['success'=>true]);
} catch (Exception $e) {
    json_response(['error'=>'Server error'],500);
}
