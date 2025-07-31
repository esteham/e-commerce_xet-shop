<?php
session_start();
require_once __DIR__ . '/config/classes/user.php';
$AUTH_user = new USER();

header('Content-Type: application/json');

if (!isset($_SESSION['userSession'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['tracking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Tracking ID required']);
    exit();
}

$tracking_id = $_GET['tracking_id'];
$user_id = $_SESSION['userSession'];

try {
    $stmt = $AUTH_user->runQuery("
        SELECT return_reason, return_notes, return_date 
        FROM orders 
        WHERE tracking_id = ? AND user_id = ?
    ");
    $stmt->execute([$tracking_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        echo json_encode([
            'success' => true,
            'return_reason' => $order['return_reason'],
            'return_notes' => $order['return_notes'],
            'return_date' => $order['return_date']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>