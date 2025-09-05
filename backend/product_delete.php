<?php
// product_delete.php
require_once 'config.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error'=>'Invalid method'],405);
}
if (!validate_csrf_token(get_post('csrf_token'))) {
    json_response(['error' => 'Invalid CSRF token'], 403);
}

$id = (int)get_post('id',0);
if ($id <= 0) json_response(['error'=>'Invalid id'],400);

$pdo = getPDO();
try {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
    $stmt->execute([':id'=>$id]);
    json_response(['success'=>true]);
} catch (Exception $e) {
    json_response(['error'=>'Server error'],500);
}
