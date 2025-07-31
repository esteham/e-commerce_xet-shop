<?php
require_once __DIR__ . '/config/classes/user.php';
$DB_con = new USER();

if (!isset($_SESSION)) {
    session_start();
}

// Display order success message if it exists
if (isset($_SESSION['order_success'])) {
    echo '<div class="alert alert-success">';
    echo htmlspecialchars($_SESSION['order_success']);
    echo '</div>';
    unset($_SESSION['order_success']);
}

// Get user's wishlist items if logged in
$wishlistItems = [];
if (isset($_SESSION['id'])) {
    $wishlistCheckQuery = "SELECT product_id FROM wishlists WHERE user_id = :id";
    $wishlistCheck = $DB_con->runQuery($wishlistCheckQuery);
    $wishlistCheck->execute(array(':id' => $_SESSION['id']));
    $wishlistItems = $wishlistCheck->fetchAll(PDO::FETCH_COLUMN);
}

// Load cart from database if user is logged in
if (isset($_SESSION['userSession']) && isset($_SESSION['id'])) {
    $cartQuery = "SELECT ci.product_id, ci.quantity, p.price, p.product_name, p.product_image 
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

<link rel="stylesheet" href="assets/css/index.css">

<style>
    .product-card {
  border: none;
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  margin-bottom: 15px;
  background: #fff;
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.card-img-top {
  width: auto;
  height: 150px;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.product-card:hover .card-img-top {
  transform: scale(1.03);
}

.card-body {
  padding: 1rem;
}

.card-title {
  font-weight: 600;
  color: #2c3e50;
  font-size: 1.1rem;
}

.card-text {
  color: #7f8c8d;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.price-tag {
  font-weight: 700;
  color: #e74c3c;
  font-size: 1.2rem;
  margin-bottom: 0.6rem;
}

.btn-order {
  background: linear-gradient(45deg, #3498db, #2ecc71);
  border: none;
  border-radius: 50px;
  padding: 7px 12px;
  font-weight: 520;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  font-size: 0.8rem;
  transition: all 0.3s ease;
  width: 100%;
}

.btn-order:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4);
}

.badge {
  font-size: 0.7rem;
  padding: 5px 10px;
  font-weight: 5200;
  letter-spacing: 0.5px;
}

.badge-success {
  background-color: #2ecc71;
}

.badge-warning {
  background-color: #f39c12;
}

.badge-danger {
  background-color: #e74c3c;
}

.product-meta {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.6rem;
  font-size: 0.8rem;
}

.rating {
  color: #f1c40f;
}

.btn-wishlist {
  background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
  border: none;
  border-radius: 50px;
  padding: 7px 12px;
  font-weight: 600;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  font-size: 0.8rem;
  transition: all 0.3s ease;
  width: 100%;
  margin-bottom: 10px;
  color: white;
}

.btn-wishlist:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
}

.btn-wishlist.added {
  background: linear-gradient(45deg, #2ecc71, #27ae60);
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
/* Sidebar Styles */
.sidebar {
  background: #f8f9fa;
  min-height: 100vh;
  transition: all 0.3s ease;
  border-right: 1px solid #dee2e6;
  position: relative;
  z-index: 1000;
}

.sidebar-container {
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
}

/* Main content area */
.main-content {
  padding: 20px;
  transition: all 0.3s;
  min-height: 100vh;
  background: #fff;
}

/* Product card styles */
.product-card {
  transition: transform 0.3s ease;
  border-radius: 10px;
  overflow: hidden;
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.btn-wishlist.added {
  color: #fff;
  background-color: #dc3545;
  border-color: #dc3545;
}

.price-tag {
  color: #0d6efd;
}

/* Mobile styles */
@media (max-width: 767.98px) {
  .sidebar {
    position: fixed;
    left: -100%;
    width: 80%;
    max-width: 300px;
    height: 100vh;
    top: 0;
    z-index: 1040;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
  }

  .sidebar.active {
    left: 0;
  }

  .sidebar-toggle {
    position: fixed;
    top: 24px;
    z-index: 1050;
  }

  .main-content {
    padding-top: 70px;
  }

  .product-card {
    margin-bottom: 20px;
  }
  .main-content {
    padding: 15px 10px;
  }

  .col-sm-6 {
    padding-left: 5px;
    padding-right: 5px;
  }
}

</style>

<div class="container-fluid px-0">
    <!-- Mobile Toggle Button -->
    <button class="d-md-none btn btn-primary sidebar-toggle" type="button">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="d-flex flex-row">
        <!-- Sidebar Area -->
        <div class="col-md-3 col-lg-2 sidebar p-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>

        <!-- Main Content Area -->
        <div class="col-md-9 col-lg-10 main-content">
            <?php
            // Check if this is a search request
            $isSearch = isset($_POST['search']) || isset($_GET['search']);
            $searchTerm = isset($_POST['search']) ? $_POST['search'] : (isset($_GET['search']) ? $_GET['search'] : '');
            
            if ($isSearch && !empty($searchTerm)) {
                echo '<div class="search-header p-3">
                        <h4>Search Results for: "'.htmlspecialchars($searchTerm).'"</h4>
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">Clear Search</a>
                    </div>';
            }
            ?>
            
            <div class="row p-3" id="product-container">
                <?php
                $query = "SELECT * FROM products WHERE 1";
                
                // Add search condition if this is a search
                if ($isSearch && !empty($searchTerm)) {
                    $searchTermLike = '%'.$searchTerm.'%';
                    $query .= " AND (product_name LIKE :search OR description LIKE :search)";
                }
                $query = "SELECT * FROM products WHERE 1";
                
                if(isset($_GET['sub_cat_id'])) {
                    $sub_cat_id = intval($_GET['sub_cat_id']);
                    $query .= " AND sub_cat_id = :sub_cat_id";
                } 
                elseif(isset($_GET['cat_id'])) {
                    $cat_id = intval($_GET['cat_id']);
                    
                    // Check if this category has any sub-categories
                    $sub_cat_check = $DB_con->runQuery("SELECT id FROM sub_categories WHERE category_id = :cat_id");
                    $sub_cat_check->execute(array(':cat_id' => $cat_id));
                    
                    // If no sub-categories exist, filter by category_id
                    if($sub_cat_check->rowCount() == 0) {
                        $query .= " AND category_id = :cat_id";
                    } 
                    // If sub-categories exist but we're at category level, don't show any products
                    else {
                        echo "<div class='col-12'><div class='alert alert-info'>Please select a sub-category to view products.</div></div>";
                        $query .= " AND 1=0"; // Force no results
                    }
                }

                $query .= " ORDER BY id DESC";

                $stmt = $DB_con->runQuery($query);

                if(isset($sub_cat_id)) {
                    $stmt->bindParam(':sub_cat_id', $sub_cat_id, PDO::PARAM_INT);
                } 
                elseif(isset($cat_id) && strpos($query, ':cat_id') !== false) {
                    $stmt->bindParam(':cat_id', $cat_id, PDO::PARAM_INT);
                }

                $stmt->execute();
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if(count($products) > 0) {
                    foreach ($products as $product) {
                        $imagePath = "admin/pages/uploads/".$product['product_image'];
                        $stock_amnt = (int)$product['stock_amount'];
                        $price = isset($product['price']) ? number_format($product['price'], 2) : '0.00';

                        if($stock_amnt == 0) {
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
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-6 mb-4">
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
                                        <h5 class="card-title">' . htmlspecialchars(strlen($product['product_name']) > 10 ? substr($product['product_name'], 0, 18) . '...' : $product['product_name']) . '</h5>
                                        <p class="card-text flex-grow-1">' . htmlspecialchars(strlen($product['description']) > 40 ? substr($product['description'], 0, 40) . '...' : $product['description']) . '</p>
                                    <div class="price-tag fs-4 fw-bold mb-3">$'.$price.'</div>

                                    <div class="mt-auto">
                                        <button class="btn btn-outline-danger btn-wishlist" 
                                            data-product-id="' . htmlspecialchars($product['id']) . '"
                                            onclick="toggleWishlist(' . $product['id'] . ')">
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
                }
                elseif(!isset($_GET['cat_id']) && !isset($_GET['sub_cat_id'])) {
                    echo '<div class="col-12"><div class="alert alert-info text-center py-4">Please select a category or sub-category to view products.</div></div>';
                }
                elseif(!(isset($_GET['cat_id']) && $sub_cat_check->rowCount() > 0)) {
                    echo '<div class="col-12"><div class="alert alert-warning text-center py-4">No products found in this category.</div></div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
//Order button
function handleOrder(productId) {
    <?php if(!isset($_SESSION['userSession'])): ?>
        $('#loginModal').modal('show');
        return;
    <?php endif; ?>

    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId + '&quantity=1&action=add'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Product has been added to the cart');
            updateCartBadge(); 
        } else {
            alert(data.message || 'There was a problem adding to the cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    });
}

function updateCartBadge() {
    fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('cartBadge').innerText = data.count;
            }
        })
        .catch(error => console.error('Badge Update Error:', error));
}

// Toggle sidebar on mobile
$(document).ready(function() {
    $('.sidebar-toggle').click(function() {
        $('.sidebar').toggleClass('active');
    });
    
    // Close sidebar when clicking outside on mobile
    $(document).click(function(e) {
        if ($(window).width() <= 767) {
            if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('.sidebar-toggle').length) {
                $('.sidebar').removeClass('active');
            }
        }
    });
});

// Handle window resize
$(window).resize(function() {
    if ($(window).width() > 767) {
        $('.sidebar').removeClass('active');
    }
});

function toggleWishlist(productId) {
    <?php if(!isset($_SESSION['userSession'])): ?>
        $('#loginModal').modal('show');
        return false;
    <?php endif; ?>

    const button = document.querySelector(`.btn-wishlist[data-product-id="${productId}"]`);
    const isAdded = button.classList.contains('added');
    
    fetch('wishlist_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId + '&action=' + (isAdded ? 'remove' : 'add')
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (isAdded) {
                button.classList.remove('added');
                button.innerHTML = '<i class="far fa-heart mr-2"></i> Add to Wishlist';
            } else {
                button.classList.add('added');
                button.innerHTML = '<i class="fas fa-heart mr-2"></i> In Wishlist';
            }
        } else {
            alert(data.message || 'Error processing your request');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    });
}
</script>

<script>
// Live search functionality
$(document).ready(function() {
    const searchInput = $('#live-search');
    const productContainer = $('#product-container');
    
    // Debounce function to limit how often the search runs
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                func.apply(context, args);
            }, wait);
        };
    }
    
    const performSearch = debounce(function(searchTerm) {
        if (searchTerm.length < 2) {
            // If search term is too short, reload the page to show all products
            if (window.location.search.includes('search=')) {
                window.location.href = 'index.php';
            }
            return;
        }
        
        // Update URL without reloading (for better UX)
        history.pushState(null, null, `index.php?search=${encodeURIComponent(searchTerm)}`);
        
        $.ajax({
            url: 'search_products.php',
            method: 'POST',
            data: { search: searchTerm },
            success: function(data) {
                productContainer.html(data);
            },
            error: function() {
                productContainer.html('<div class="col-12"><div class="alert alert-danger">Error loading search results</div></div>');
            }
        });
    }, 200);
    
    searchInput.on('input', function() {
        const searchTerm = $(this).val().trim();
        performSearch(searchTerm);
    });
    
    // Handle back/forward navigation
    window.onpopstate = function() {
        const urlParams = new URLSearchParams(window.location.search);
        const searchParam = urlParams.get('search');
        
        if (searchParam) {
            searchInput.val(searchParam);
            performSearch(searchParam);
        } else {
            searchInput.val('');
            // Reload normal products
            $.get('index.php', function(data) {
                productContainer.html($(data).find('#product-container').html());
            });
        }
    };
});
</script>

<?php include 'includes/footer.php'; ?>