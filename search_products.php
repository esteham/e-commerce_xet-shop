<?php
require_once __DIR__ . '/config/classes/user.php';
$DB_con = new USER();
$wishlistItems = [];
if (isset($_SESSION['id'])) {
    $wishlistCheckQuery = "SELECT product_id FROM wishlists WHERE user_id = :id";
    $wishlistCheck = $DB_con->runQuery($wishlistCheckQuery);
    $wishlistCheck->execute(array(':id' => $_SESSION['id']));
    $wishlistItems = $wishlistCheck->fetchAll(PDO::FETCH_COLUMN);
}

if (isset($_POST['search'])) {
    $searchTerm = '%' . $_POST['search'] . '%';
    
    try {
        $query = "SELECT * FROM products 
                 WHERE product_name LIKE :search OR description LIKE :search
                 ORDER BY id DESC";
        $stmt = $DB_con->runQuery($query);
        $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($products) > 0) {
            foreach ($products as $product) {
                // Use the same product card HTML structure as your index.php
                $imagePath = "admin/pages/uploads/".$product['product_image'];
                $stock_amnt = (int)$product['stock_amount'];
                $price = isset($product['price']) ? number_format($product['price'], 2) : '0.00';
                
                if ($stock_amnt == 0) {
                    $stockBadge = '<span class="badge bg-danger">Out of Stock</span>';
                    $btnDisabled = 'disabled';
                }
                elseif ($stock_amnt < 5) {
                    $stockBadge = '<span class="badge bg-warning">Low Stock</span>';
                    $btnDisabled = '';
                }
                else {
                    $stockBadge = '<span class="badge bg-success">In Stock</span>';
                    $btnDisabled = '';
                }
                
                echo '
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
                    <div class="product-card card shadow-sm h-100">
                        <img src="'.$imagePath.'" class="card-img-top p-3" alt="'.htmlspecialchars($product['product_name']).'">
                        <div class="card-body d-flex flex-column">
                            <div class="product-meta mb-2">
                                <div class="rating text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                </div>
                                '.$stockBadge.'
                            </div>
                            <h5 class="card-title">'.htmlspecialchars($product['product_name']).'</h5>
                            <p class="card-text flex-grow-1">'.htmlspecialchars($product['description']).'</p>
                            <div class="price-tag fs-4 fw-bold mb-3">$'.$price.'</div>
                            <div class="mt-auto">
                                        <button class="btn btn-outline-danger btn-wishlist '. (in_array($product['id'], $wishlistItems) ? 'added' : '') .'" 
                                            data-product-id="'.htmlspecialchars($product['id']).'"
                                            onclick="toggleWishlist(this)">
                                            <i class="'. (in_array($product['id'], $wishlistItems) ? 'fas' : 'far' ) .' fa-heart mr-2"></i>
                                            '. (in_array($product['id'], $wishlistItems) ? 'In Wishlist' : 'Add to Wishlist' ) .'
                                        </button>
                                        
                                        <button 
                                            class="btn btn-primary btn-order '.($stock_amnt == 0 ? 'disabled' : '').'" 
                                            onclick="handleOrder('.$product['id'].')">
                                            <i class="fas fa-shopping-cart mr-2"></i> Order Now
                                        </button>
                                    </div>
                        </div>
                    </div>
                </div>';
            }
        } else {
            echo '<div class="col-12"><div class="alert alert-info text-center py-4">No products found matching your search.</div></div>';
        }
    } catch (PDOException $e) {
        echo '<div class="col-12"><div class="alert alert-danger text-center py-4">Error searching products.</div></div>';
    }
}
?>
<script>
 function handleOrder(productId) {
    <?php if(!isset($_SESSION['userSession'])): ?>
        $('#loginModal').modal('show');
        return;
    <?php endif; ?>

    const encodedId = btoa(productId); // browser side encode
    window.location.href = 'place_order.php?product_id=' + encodedId;
}
</script>
