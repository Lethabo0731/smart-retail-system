<?php
// order_update.php
require_once 'config.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error'=>'Invalid method'],405);
if (!validate_csrf_token(get_post('csrf_token'))) json_response(['error'=>'Invalid CSRF token'],403);

$id = (int)get_post('id',0);
$status = trim(get_post('status',''));
if ($id <= 0 || $status === '') json_response(['error'=>'Invalid data'],400);

$pdo = getPDO();
try {
    $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
    $stmt->execute([':status'=>$status, ':id'=>$id]);
    json_response(['success'=>true]);
} catch (Exception $e) {
    json_response(['error'=>'Server error'],500);
}
