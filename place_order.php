<?php
session_start();
require_once __DIR__ . '/config/classes/user.php';
$DB_con = new USER();

// If the request does not come via AJAX (direct browser request), redirect to index.php
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    header('Location: index.php');
    exit;
}

header('Content-Type: application/json');

if (!isset($_SESSION['userSession'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if (empty($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
    exit;
}

// Parse customer information
parse_str($_POST['shipping_info'], $shippingInfo);

$customer_name = htmlspecialchars(trim($shippingInfo['customer_name'] ?? ''));
$customer_phone = htmlspecialchars(trim($shippingInfo['customer_phone'] ?? ''));
$customer_address = htmlspecialchars(trim($shippingInfo['customer_address'] ?? ''));
$payment_method = htmlspecialchars(trim($shippingInfo['payment_method'] ?? ''));


// Validations
$errors = [];
if (empty($customer_name)) $errors[] = "Name is required";
if (empty($customer_phone)) $errors[] = "Phone is required";
if (empty($customer_address)) $errors[] = "Address is required";
if (empty($payment_method)) $errors[] = "Payment method is required";

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
    exit;
}

try {
    $DB_con->beginTransaction();
    $global_gid = $DB_con->generateGlobalOrderId();


    // Total Amount calculation
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    // Insert Order
    $orderStmt = $DB_con->runQuery("INSERT INTO orders 
        (user_id, customer_name, customer_phone, customer_address, total_amount, payment_method, order_date, status,tracking_id) 
        VALUES (:user_id, :name, :phone, :address, :amount, :method, NOW(), 'Pending', :tracking_id)");
    $orderStmt->execute([
        ':user_id' => $_SESSION['userSession'],
        ':name' => $customer_name,
        ':phone' => $customer_phone,
        ':address' => $customer_address,
        ':amount' => $total,
        ':method' => $payment_method,
        ':tracking_id' => $global_gid
    ]);

    $orderId = $DB_con->lastId();

    // Insert each cart item into order_items and decrease stock
    foreach ($_SESSION['cart'] as $item) {
        //Order item insert
        $itemStmt = $DB_con->runQuery("INSERT INTO order_items 
            (order_id, product_id, quantity, unit_price) 
            VALUES (:order_id, :product_id, :quantity, :price)");
        $itemStmt->execute([
            ':order_id' => $orderId,
            ':product_id' => $item['product_id'],
            ':quantity' => $item['quantity'],
            ':price' => $item['price']
        ]);

        // Decrease product stock
        $updateStmt = $DB_con->runQuery("UPDATE products 
            SET stock_amount = stock_amount - :qty 
            WHERE id = :id");
        $updateStmt->execute([
            ':qty' => $item['quantity'],
            ':id' => $item['product_id']
        ]);
    }

    // Clear cart
    $cartStmt = $DB_con->runQuery("SELECT id FROM carts WHERE user_id = :user_id LIMIT 1");
    $cartStmt->execute([':user_id' => $_SESSION['userSession']]);
    $cart = $cartStmt->fetch(PDO::FETCH_ASSOC);

    if ($cart) {
        $clearStmt = $DB_con->runQuery("DELETE FROM cart_items WHERE cart_id = :cart_id");
        $clearStmt->execute([':cart_id' => $cart['id']]);
    }

    $_SESSION['cart'] = [];

    $DB_con->commit();

    echo json_encode(['success' => true, 'order_id' => $orderId]);

} catch (Exception $e) {
    $DB_con->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error placing order: ' . $e->getMessage()]);
}
?>
