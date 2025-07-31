<?php
session_start();
require_once __DIR__ . '/../../config/classes/user.php';
$auth_user = new USER();

if (!isset($_SESSION['userSession'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$order_id = $_POST['order_id'] ?? null;
$return_reason = $_POST['return_reason'] ?? '';
$return_notes = $_POST['return_notes'] ?? '';

if (!$order_id || !$return_reason) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// Fetch admin details
$admin_id = $_SESSION['userSession'];

$stmt = $auth_user->runQuery("
    SELECT userEmail as admin_email, first_name, last_name
    FROM users
    WHERE id = :id
");
$stmt->execute(['id' => $admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

try {
    // Begin transaction
    $auth_user->beginTransaction();
    
    // 1. Update order status to "Returned"
    $stmt = $auth_user->runQuery("
        UPDATE orders 
        SET status = 'Returned', 
            return_reason = :reason,
            return_notes = :notes,
            return_date = NOW()
        WHERE id = :order_id
    ");
    $stmt->execute([
        'reason' => $return_reason,
        'notes' => $return_notes,
        'order_id' => $order_id
    ]);
    
    // 2. Get all products in this order to update stock
    $stmt = $auth_user->runQuery("
        SELECT product_id, quantity FROM order_items WHERE order_id = :order_id
    ");
    $stmt->execute(['order_id' => $order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Update stock for each product
    foreach ($order_items as $item) {
        $stmt = $auth_user->runQuery("
            UPDATE products 
            SET stock_amount = stock_amount + :quantity 
            WHERE id = :product_id
        ");
        $stmt->execute([
            'quantity' => $item['quantity'],
            'product_id' => $item['product_id']
        ]);
    }
    
    // 4. Get order details for email notification
    $stmt = $auth_user->runQuery("
        SELECT o.*, u.userEmail as customer_email, u.first_name, u.last_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = :order_id
    ");
    $stmt->execute(['order_id' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Send email notifications
    // Customer email
    $customer_message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .footer { margin-top: 20px; padding: 10px; text-align: center; font-size: 0.8em; color: #777; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Order Return Confirmation</h2>
            </div>
            <div class='content'>
                <p>Dear {$order['first_name']} {$order['last_name']},</p>
                <p>We have processed the return for your order <strong>#{$order_id}</strong>.</p>
                
                <h3>Return Details:</h3>
                <ul>
                    <li><strong>Reason:</strong> {$return_reason}</li>
                    <li><strong>Notes:</strong> " . (!empty($return_notes) ? $return_notes : 'No additional notes provided') . "</li>
                    <li><strong>Date Processed:</strong> " . date('F j, Y') . "</li>
                </ul>
                
                <p>If you have any questions about this return, please contact our customer service team.</p>
                <p>Thank you for shopping with us.</p>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " Your Company Name. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $auth_user->sendMail($order['customer_email'], $customer_message, "Your Order #{$order_id} Return Confirmation");

    // Admin email
    $admin_actor_message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .footer { margin-top: 20px; padding: 10px; text-align: center; font-size: 0.8em; color: #777; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Return Processed Successfully</h2>
            </div>
            <div class='content'>
                <p>Hello {$admin['first_name']},</p>
                <p>You have successfully processed the return for order <strong>#{$order_id}</strong>.</p>
                
                <h3>Return Summary:</h3>
                <ul>
                    <li><strong>Customer:</strong> {$order['first_name']} {$order['last_name']}</li>
                    <li><strong>Reason:</strong> {$return_reason}</li>
                    <li><strong>Notes:</strong> " . (!empty($return_notes) ? $return_notes : 'None') . "</li>
                    <li><strong>Date Processed:</strong> " . date('F j, Y g:i a') . "</li>
                </ul>
                
                <p>The products from this order have been automatically added back to inventory.</p>
            </div>
            <div class='footer'>
                <p>This is an automated notification. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    if (!empty($admin['admin_email'])) {
        $auth_user->sendMail($admin['admin_email'], $admin_actor_message, "Return Processed: Order #{$order_id}");
    }
    
    // Commit transaction
    $auth_user->commit();
    
    // Return JSON response instead of JavaScript
    echo json_encode([
        'status' => 'success', 
        'message' => 'Order has been marked as returned and stock updated',
        'redirect' => 'index.php?page=delivary_orders'
    ]);
    
} catch (Exception $e) {
    $auth_user->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Failed to process return: ' . $e->getMessage()]);
}