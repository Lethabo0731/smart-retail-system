<?php
// order_delete.php
require_once 'config.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error'=>'Invalid method'],405);
if (!validate_csrf_token(get_post('csrf_token'))) json_response(['error'=>'Invalid CSRF token'],403);

$id = (int)get_post('id',0);
if ($id <= 0) json_response(['error'=>'Invalid id'],400);

$pdo = getPDO();
try {
    $pdo->beginTransaction();
    // If you want to restore stock when deleting orders, implement logic here:
    $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = :id");
    $stmt->execute([':id'=>$id]);
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        $upd = $pdo->prepare("UPDATE products SET stock = stock + :qty WHERE id = :pid");
        $upd->execute([':qty'=>$r['quantity'], ':pid'=>$r['product_id']]);
    }

    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = :id");
    $stmt->execute([':id'=>$id]);
    $pdo->commit();

    json_response(['success'=>true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['error'=>'Server error'],500);
}
