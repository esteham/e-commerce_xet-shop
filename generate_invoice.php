<?php
session_start();
require_once __DIR__ . '/config/classes/user.php';
$DB_con = new USER();

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id <= 0) {
    header("Location: orders.php");
    exit();
}

try {
    // Get order details
    $order_stmt = $DB_con->runQuery("SELECT * FROM orders WHERE id = :id");
    $order_stmt->execute([':id' => $order_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header("Location: orders.php");
        exit();
    }
    
    // Get order items
    $items_stmt = $DB_con->runQuery("
        SELECT oi.*, p.product_name, p.product_image 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = :id
    ");
    $items_stmt->execute([':id' => $order_id]);
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error loading order details");
}

$status_stmt = $DB_con->runQuery("SELECT payment_method FROM orders WHERE id = :id");
$status_stmt->execute([':id' => $order_id]);
$status = $status_stmt->fetchColumn();
$badgeClass = $status === "Paid" ? "bg-success" : "bg-danger";

require_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= $order_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/generate_invoice.css">
    <style>
        @media print {
          footer,
          .no-print {
            display: none !important;
          }
        }
    </style>

</head>
<body>
<div class="invoice-container">
    <div class="invoice-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="invoice-title">INVOICE</h3>
                <div class="invoice-number">#<?= $order_id ?></div>
            </div>
            <div class="text-end">
                <p class="mb-1"><strong>Date:</strong> <?= date('F j, Y', strtotime($order['order_date'])) ?></p>
                <p class="mb-0">
                    <strong>Status:</strong>
                    <span class="badge <?= $badgeClass; ?>"><?= htmlspecialchars($status); ?></span>
                </p>
            </div>
        </div>
    </div>
    
    <div class="invoice-body">
        <div class="row invoice-section">
            <div class="col-md-6">
                <h4 class="section-title">Bill To</h4>
                <div class="customer-info">
                    <p><span class="info-label">Name:</span> <?= htmlspecialchars($order['customer_name']) ?></p>
                    <p><span class="info-label">Phone:</span> <?= htmlspecialchars($order['customer_phone']) ?></p>
                    <p><span class="info-label">Address:</span> <?= htmlspecialchars($order['customer_address']) ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <h4 class="section-title">Payment Method</h4>
                <div class="payment-info">
                    <p><i class="fas fa-<?= $order['payment_method'] == 'Cash on Delivery' ? 'truck' : 'credit-card' ?> me-2"></i> 
                    <?= htmlspecialchars($order['payment_method']) ?></p>
                </div>
            </div>
        </div>
        
        <div class="invoice-section">
            <h4 class="section-title">Order Details</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-end">Price</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td data-label="Product">
                                <div class="d-flex align-items-center">
                                    <img src="admin/pages/uploads/<?= htmlspecialchars($item['product_image']) ?>" 
                                         class="product-img" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                    <span class="product-name"><?= htmlspecialchars($item['product_name']) ?></span>
                                </div>
                            </td>
                            <td data-label="Price" class="text-end">$<?= number_format($item['unit_price'], 2) ?></td>
                            <td data-label="Quantity" class="text-center"><?= $item['quantity'] ?></td>
                            <td data-label="Total" class="text-end">$<?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="3" class="text-end"><strong>Grand Total</strong></td>
                            <td class="text-end"><strong>$<?= number_format($order['total_amount'], 2) ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="thank-you no-print">
            <h5 class="mb-3">Thank you for your order!</h5>
            <p class="mb-0">Your order has been processed and will be shipped soon. For any questions, please contact our support team.</p>
        </div>
        
        <div class="d-flex justify-content-center gap-3 mt-5 no-print">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print me-2"></i> Print Invoice
            </button>
            <a href="index.php" class="btn-back">
                <i class="fas fa-arrow-left me-2"></i> Back to Home
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>