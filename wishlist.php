<?php
require_once 'includes/header.php';
$DB_con = new USER();

// Check if the user is logged in
if (!isset($_SESSION['userSession'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['userSession'];

// Remove from Wishlist
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $productId = $_GET['remove'];
    $removeStmt = $DB_con->runQuery("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");
    $removeStmt->execute([$userId, $productId]);
    // header('Location: wishlist.php?removed=true');
    //exit;
}

//  Add to Wishlist
if (isset($_GET['add']) && is_numeric($_GET['add'])) {
    $productId = $_GET['add'];
    $checkStmt = $DB_con->runQuery("SELECT * FROM wishlists WHERE user_id = ? AND product_id = ?");
    $checkStmt->execute([$userId, $productId]);
    
    if ($checkStmt->rowCount() == 0) {
        $insertStmt = $DB_con->runQuery("INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)");
        $insertStmt->execute([$userId, $productId]);
    }
    header('Location: wishlist.php');
    exit;
}

// Load user's wishlist
$stmt = $DB_con->runQuery("
    SELECT products.* 
    FROM wishlists 
    JOIN products ON wishlists.product_id = products.id
    WHERE wishlists.user_id = ?
    ORDER BY wishlists.added_at DESC
");
$stmt->execute([$userId]);
$wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Wishlist</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="assets/css/wishlist.css">
</head>
<body>

<div class="wishlist-container">
    <div class="wishlist-header">
        <h1><i class="fas fa-heart"></i> My Wishlist</h1>
    </div>

    <?php if (empty($wishlistItems)): ?>
        <div class="empty-wishlist">
            <p>Your wishlist is currently empty.</p>
            <a href="index.php" class="btn btn-primary">Browse Products</a>
        </div>
    <?php else: ?>
        <div class="wishlist-items">
            <?php foreach ($wishlistItems as $item): ?>
                <div class="wishlist-item">
                    <?php
                        $imagePath = "admin/pages/uploads/".htmlspecialchars($item['product_image'] ?? 'default_product.jpg');
                        ?>
                        <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-image">
                    <h3><?= htmlspecialchars($item['product_name']) ?></h3>
                    <p>৳<?= number_format($item['price'], 2) ?></p>

                    <a href="checkout.php?product_id=<?= $item['id'] ?>" 
                       class="btn-order <?= ($item['stock_amount'] == 0 ? 'disabled' : '') ?>">
                        <i class="fas fa-shopping-cart"></i> Order Now
                    </a>

                    <div class="item-actions" style="margin-top:10px;">
                        <a href="product.php?id=<?= $item['id'] ?>" title="View Details">
                            <i class="fas fa-info-circle"></i> Details
                        </a>&nbsp;&nbsp;
                        <a href="wishlist.php?remove=<?= $item['id'] ?>" class="remove-btn" title="Remove from Wishlist">
                            <i class="fas fa-trash"></i> Remove
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
