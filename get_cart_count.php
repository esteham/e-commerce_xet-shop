<?php
session_start();
require_once __DIR__ . '/config/classes/user.php';
$DB_con = new USER();

header('Content-Type: application/json');

if (!isset($_SESSION['userSession']) || !isset($_SESSION['id'])) {
    echo json_encode(['success' => true, 'count' => 0]);
    exit;
}

$cartStmt = $DB_con->runQuery("SELECT id FROM carts WHERE user_id = :user_id LIMIT 1");
$cartStmt->execute([':user_id' => $_SESSION['id']]);
$cart = $cartStmt->fetch(PDO::FETCH_ASSOC);

if (!$cart) {
    echo json_encode(['success' => true, 'count' => 0]);
    exit;
}

// total quantity
$itemStmt = $DB_con->runQuery("SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = :cart_id");
$itemStmt->execute([':cart_id' => $cart['id']]);
$row = $itemStmt->fetch(PDO::FETCH_ASSOC);
$total = $row && $row['total'] ? intval($row['total']) : 0;

echo json_encode(['success' => true, 'count' => $total]);
?>
