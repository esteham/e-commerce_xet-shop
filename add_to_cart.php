<?php
session_start();
require_once __DIR__ . '/config/classes/user.php';
$DB_con = new USER();

header('Content-Type: application/json');

if (!isset($_SESSION['userSession']) || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}


if (!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

$productId = $_POST['product_id'];
$quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;

// Check if product exists and has stock
$productStmt = $DB_con->runQuery("SELECT * FROM products WHERE id = :id");
$productStmt->execute(array(':id' => $productId));
$product = $productStmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

if ($product['stock_amount'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
    exit;
}

// Get or create cart for user
$cartStmt = $DB_con->runQuery("SELECT id FROM carts WHERE user_id = :user_id LIMIT 1");
$cartStmt->execute(array(':user_id' => $_SESSION['id']));
$cart = $cartStmt->fetch(PDO::FETCH_ASSOC);

if (!$cart) {
    $insertCart = $DB_con->runQuery("INSERT INTO carts (user_id) VALUES (:user_id)");
    $insertCart->execute(array(':user_id' => $_SESSION['id']));
    $cartId = $DB_con->lastID();
} else {
    $cartId = $cart['id'];
}

// Check if product already in cart
$itemStmt = $DB_con->runQuery("SELECT id, quantity FROM cart_items 
                              WHERE cart_id = :cart_id AND product_id = :product_id");
$itemStmt->execute(array(
    ':cart_id' => $cartId,
    ':product_id' => $productId
));
$existingItem = $itemStmt->fetch(PDO::FETCH_ASSOC);

if ($existingItem) {
    $newQuantity = $existingItem['quantity'] + $quantity;
    $updateStmt = $DB_con->runQuery("UPDATE cart_items SET quantity = :quantity 
                                    WHERE id = :id");
    $updateStmt->execute(array(
        ':quantity' => $newQuantity,
        ':id' => $existingItem['id']
    ));
} else {
    $insertStmt = $DB_con->runQuery("INSERT INTO cart_items (cart_id, product_id, quantity) 
                                   VALUES (:cart_id, :product_id, :quantity)");
    $insertStmt->execute(array(
        ':cart_id' => $cartId,
        ':product_id' => $productId,
        ':quantity' => $quantity
    ));
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

echo json_encode([
    'success' => true,
    'message' => 'Product added to cart',
    'cart' => $_SESSION['cart'],
    'total_items' => array_sum(array_column($_SESSION['cart'], 'quantity'))
]);

?>