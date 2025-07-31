<?php
session_start();
require_once __DIR__ . '/config/classes/user.php';
$DB_con = new USER();

if (!isset($_GET['order_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['userSession'];

$stmt = $DB_con->runQuery("SELECT userEmail FROM users WHERE id = :uid");
$stmt->execute([':uid'=>$user_id]);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC);
$email = $userRow['userEmail'];

$orderId = $_GET['order_id'];

// Verify order belongs to user
if (isset($_SESSION['userSession'])) {
    $orderStmt = $DB_con->runQuery("SELECT o.*, COUNT(oi.id) as item_count 
                                  FROM orders o
                                  JOIN order_items oi ON o.id = oi.order_id
                                  WHERE o.id = :order_id AND o.user_id = :user_id
                                  GROUP BY o.id");
    $orderStmt->execute(array(
        ':order_id' => $orderId,
        ':user_id' => $_SESSION['id']
    ));
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
}

if (!$order) {
    $_SESSION['error'] = 'Order not found or access denied';
    header('Location: index.php');
    
    exit;
}

// Store success message for display on other pages
$_SESSION['order_success'] = "Your order #$orderId has been placed successfully!";

require_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/order_success.css">
</head>
<body>
<div class="container py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="confirmation-card text-center p-1 p-md-5">
                <div class="confirmation-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="fw-bold mb-3" style="color: var(--primary);">Order Confirmed!</h1>
                <p class="confirmation-text mb-4"><?= htmlspecialchars($_SESSION['order_success']) ?></p>
                
                <div class="order-details">
                    <div class="order-number">
                        Order #<?= $orderId ?>
                    </div>
                    <p class="mb-0">We've sent a confirmation email to your registered address.</p>
                </div>
                
                <div class="whats-next text-start">
                    <h4 class="fw-bold mb-4">What happens next?</h4>
                    
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h5>Order Processing</h5>
                            <p>We're preparing your items for shipment. You'll receive an update within 24 hours.</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h5>Shipping</h5>
                            <p>Your order will be shipped within 1-2 business days with tracking information.</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h5>Delivery</h5>
                            <p>Expected delivery in 3-5 business days. Our delivery partner will contact you.</p>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex flex-column flex-md-row justify-content-center gap-3 mt-5">
                    <a href="products.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                    </a>
                    <a href="generate_invoice.php?order_id=<?= $orderId ?>" class="btn btn-success">
                        <i class="fas fa-file-invoice me-2"></i> Download Invoice
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
unset($_SESSION['order_success']);
require_once 'includes/footer.php'; 
?>

<script type="text/javascript">
		
		$(document).ready(function(){

			$.ajax({

				type: 'POST',
				url: 'send_order_email.php',
				data: {

						email: '<?php echo $email; ?>'
				},

				success: function(res)
				{
					console.log("Email status:", res);
				},

				error: function(xhr, status, error)
				{
					console.error("Email send failed:", error);
				}
			});
		});
	</script>
