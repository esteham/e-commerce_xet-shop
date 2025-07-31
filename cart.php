<?php
session_start();
require_once __DIR__ . '/config/classes/user.php';
$DB_con = new USER();

if (isset($_SESSION['userSession'])) {
    // Load cart from database
    $cartQuery = "SELECT ci.product_id, ci.quantity, p.price, p.product_name, p.product_image, p.stock_amount
                 FROM cart_items ci
                 JOIN carts c ON ci.cart_id = c.id
                 JOIN products p ON ci.product_id = p.id
                 WHERE c.user_id = :user_id";
    $cartStmt = $DB_con->runQuery($cartQuery);
    $cartStmt->execute(array(':user_id' => $_SESSION['id']));
    $_SESSION['cart'] = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once 'includes/header.php';
?>

<div class="container py-5">
    <h2 class="mb-4">Your Shopping Cart</h2>
    
    <?php if(empty($_SESSION['cart'])): ?>
        <div class="alert alert-info">
            Your cart is empty. <a href="index.php">Continue shopping</a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = 0;
                                foreach($_SESSION['cart'] as $item): 
                                    $subtotal = $item['price'] * $item['quantity'];
                                    $total += $subtotal;
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="admin/pages/uploads/<?= htmlspecialchars($item['product_image']) ?>" 
                                                 alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                                 width="60" class="me-3">
                                            <div><?= htmlspecialchars($item['product_name']) ?></div>
                                        </div>
                                    </td>
                                    <td>$<?= number_format($item['price'], 2) ?></td>
                                    <td>
                                        <input type="number" class="form-control quantity-input" 
                                               value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock_amount'] ?>"
                                               data-product-id="<?= $item['product_id'] ?>"
                                               style="width: 70px;">
                                    </td>
                                    <td>$<?= number_format($subtotal, 2) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger remove-item" 
                                                data-product-id="<?= $item['product_id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>$<?= number_format($total, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Shipping:</span>
                            <span>Free</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold mb-3">
                            <span>Total:</span>
                            <span>$<?= number_format($total, 2) ?></span>
                        </div>
                        <a href="checkout.php" class="btn btn-primary w-100">Proceed to Checkout</a>
                        <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">Continue Shopping</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Update quantity
    $('.quantity-input').change(function() {
        const productId = $(this).data('product-id');
        const newQuantity = $(this).val();
        
        $.post('update_cart.php', {
            product_id: productId,
            quantity: newQuantity,
            action: 'update'
        }, function(data) {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        }, 'json');
    });
    
    // Remove item
    $('.remove-item').click(function() {
        const productId = $(this).data('product-id');
        
        $.post('update_cart.php', {
            product_id: productId,
            action: 'remove'
        }, function(data) {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        }, 'json');
    });
});
</script>

<?php include 'includes/footer.php'; ?>