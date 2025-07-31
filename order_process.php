<?php
session_start();
require_once __DIR__ . '/config/classes/user.php';
$DB_con = new USER();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $unit_price = filter_input(INPUT_POST, 'unit_price', FILTER_VALIDATE_FLOAT);
    $customer_name = htmlspecialchars(trim($_POST['customer_name']));
    $customer_phone = htmlspecialchars(trim($_POST['customer_phone']));
    $customer_address = htmlspecialchars(trim($_POST['customer_address']));
    $payment_method = htmlspecialchars(trim($_POST['payment_method']));
    
    // Validation
    if (!$product_id) $errors[] = "Invalid product";
    if (!$quantity) $errors[] = "Invalid quantity";
    if (!$unit_price) $errors[] = "Invalid price";
    if (empty($customer_name)) $errors[] = "Name is required";
    if (empty($customer_phone)) $errors[] = "Phone is required";
    if (empty($customer_address)) $errors[] = "Address is required";
    if (empty($payment_method)) $errors[] = "Payment method is required";

    if (empty($errors)) {
        try {
            $DB_con->beginTransaction();
            
            // Insert order
            $order_stmt = $DB_con->runQuery("INSERT INTO orders 
                                  (user_id, customer_name, customer_phone, customer_address, 
                                  total_amount, payment_method, order_date, status) 
                                  VALUES (:uid, :name, :phone, :address, :amount, :method, NOW(), 'Pending')");
            $total_amount = $unit_price * $quantity;
            $order_stmt->execute([
                ':uid' => $_SESSION['userSession'] ?? null,
                ':name' => $customer_name,
                ':phone' => $customer_phone,
                ':address' => $customer_address,
                ':amount' => $total_amount,
                ':method' => $payment_method
            ]);
            
            $order_id = $DB_con->lastID();
            
            // Insert order item
            $item_stmt = $DB_con->runQuery("INSERT INTO order_items 
                                  (order_id, product_id, quantity, unit_price) 
                                  VALUES (:order_id, :product_id, :quantity, :unit_price)");
            $item_stmt->execute([
                ':order_id' => $order_id,
                ':product_id' => $product_id,
                ':quantity' => $quantity,
                ':unit_price' => $unit_price
            ]);
            
            // Update stock
            $update_stmt = $DB_con->runQuery("UPDATE products SET stock_amount = stock_amount - :qty WHERE id = :id");
            $update_stmt->execute([':qty' => $quantity, ':id' => $product_id]);
            
            $DB_con->commit();
            
            $_SESSION['order_success'] = "Order #$order_id placed successfully!";
            header("Location: order_success.php?order_id=$order_id");
            exit();
            
        } catch (Exception $e) {
            $DB_con->rollBack();
            $_SESSION['order_errors'] = ["Order failed: " . $e->getMessage()];
            header("Location: place_order.php?product_id=$product_id");
            exit();
        }
    } else {
        $_SESSION['order_errors'] = $errors;
        header("Location: place_order.php?product_id=$product_id");
        exit();
    }
} else {
    header("Location: place_order.php");
    exit();
}