<?php
require_once __DIR__ . '/../../../config/classes/user.php';

if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    die(json_encode(['error' => 'Invalid order ID']));
}

$orderId = intval($_GET['order_id']);

try {
    $DB_con = new USER();
    $stmt = $DB_con->runQuery("
        SELECT p.product_name, oi.quantity, oi.unit_price
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = :order_id
    ");
    $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
    $stmt->execute();
    
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database error']));
}