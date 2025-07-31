<?php
session_start();
require_once __DIR__ . '/config/classes/user.php';
$DB_con = new USER();

header('Content-Type: application/json');

if (!isset($_SESSION['userSession'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if (!isset($_POST['action']) || !isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $_POST['action'];
$productId = $_POST['product_id'];

// Get user's cart
$cartStmt = $DB_con->runQuery("SELECT id FROM carts WHERE user_id = :user_id LIMIT 1");
$cartStmt->execute(array(':user_id' => $_SESSION['id']));
$cart = $cartStmt->fetch(PDO::FETCH_ASSOC);

if (!$cart) {
    echo json_encode(['success' => false, 'message' => 'Cart not found']);
    exit;
}

$cartId = $cart['id'];

switch ($action) {
    case 'update':
        $quantity = max(1, intval($_POST['quantity']));
        
        // Check stock
        $productStmt = $DB_con->runQuery("SELECT stock_amount FROM products WHERE id = :id");
        $productStmt->execute(array(':id' => $productId));
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product['stock_amount'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
            exit;
        }
        
        $updateStmt = $DB_con->runQuery("UPDATE cart_items SET quantity = :quantity 
                                      WHERE cart_id = :cart_id AND product_id = :product_id");
        $updateStmt->execute(array(
            ':quantity' => $quantity,
            ':cart_id' => $cartId,
            ':product_id' => $productId
        ));
        break;
        
    case 'remove':
        $deleteStmt = $DB_con->runQuery("DELETE FROM cart_items 
                                       WHERE cart_id = :cart_id AND product_id = :product_id");
        $deleteStmt->execute(array(
            ':cart_id' => $cartId,
            ':product_id' => $productId
        ));
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

// Update session cart
$cartQuery = "SELECT ci.product_id, ci.quantity, p.price, p.product_name, p.product_image 
             FROM cart_items ci
             JOIN carts c ON ci.cart_id = c.id
             JOIN products p ON ci.product_id = p.id
             WHERE c.user_id = :user_id";
$cartStmt = $DB_con->runQuery($cartQuery);
$cartStmt->execute(array(':user_id' => $_SESSION['id']));
$_SESSION['cart'] = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true]);
?>