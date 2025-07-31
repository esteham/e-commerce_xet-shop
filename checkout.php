<?php
session_start();
require_once __DIR__ . '/config/classes/user.php';
$DB_con = new USER();

if (!isset($_SESSION['userSession'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

// Load user details
$userStmt = $DB_con->runQuery("SELECT * FROM users WHERE id = :id");
$userStmt->execute([':id' => $_SESSION['userSession']]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8">
            <h2 class="mb-4">Checkout</h2>
            
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Shipping Information</h5>
                    <form id="checkoutForm">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="customer_name" class="form-control" 
                                value="<?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="customer_email" class="form-control" 
                                value="<?= htmlspecialchars($user['userEmail'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="customer_address" class="form-control" rows="3" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" name="customer_city" class="form-control" 
                                    value="<?= htmlspecialchars($user['city'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="customer_postal" class="form-control" 
                                    value="<?= htmlspecialchars($user['zip_code'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="customer_phone" class="form-control" 
                                value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="">-- Select Payment Method --</option>
                                <option value="COD">Cash on Delivery</option>
                                <option value="bkash">bKash</option>
                                <option value="nagad">Nagad</option>
                                <option value="paypal">PayPal</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Order Summary</h5>
                    <ul class="list-group list-group-flush mb-3">
                        <?php 
                        $total = 0;
                        foreach($_SESSION['cart'] as $item): 
                            $subtotal = $item['price'] * $item['quantity'];
                            $total += $subtotal;
                        ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= htmlspecialchars($item['product_name']) ?> × <?= $item['quantity'] ?></span>
                            <span>$<?= number_format($subtotal, 2) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="d-flex justify-content-between fw-bold mb-3">
                        <span>Total:</span>
                        <span>$<?= number_format($total, 2) ?></span>
                    </div>
                    <button id="placeOrderBtn" class="btn btn-primary w-100">Place Order</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- jQuery CDN -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    $('#placeOrderBtn').click(function() {
        if ($('#checkoutForm')[0].checkValidity()) {
            $.post('place_order.php', {
                shipping_info: $('#checkoutForm').serialize()
            }, function(data) {
                if (data.success) {
                    window.location.href = 'order_success.php?order_id=' + data.order_id;
                } else {
                    alert(data.message);
                }
            }, 'json');
        } else {
            $('#checkoutForm')[0].reportValidity();
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
