<?php
if (!isset($_SESSION['userSession'])) {
    exit("Unauthorized access.");
}
require_once __DIR__ . '/../../config/classes/user.php';

$user = new USER();
$conn = $user->getConnection();

if(!$user->is_logged_in()) {
    header("location: login.php");
    exit();
}

// CSRF Protection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// DELETE Stock only deletes stock entries, not product)
if (isset($_POST['delete_product']) && verifyCsrfToken()) {
    $delete_id = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);
    if ($delete_id) {
        try {
            $conn->beginTransaction();
            
            // Only delete related stock entries
            $stmt = $conn->prepare("DELETE FROM stocks WHERE product_id = ?");
            $stmt->execute([$delete_id]);
            
            // Reset the product's stock amount to 0 instead of deleting it
            $stmt = $conn->prepare("UPDATE products SET stock_amount = 0 WHERE id = ?");
            $stmt->execute([$delete_id]);
            
            $conn->commit();
            $_SESSION['flash'] = ['type' => 'success', 'message' => "Stock entries deleted successfully and product stock reset to 0."];
        } catch (PDOException $e) {
            $conn->rollback();
            $_SESSION['flash'] = ['type' => 'danger', 'message' => "Error deleting stock: " . $e->getMessage()];
        }
    }
    echo "<script>window.location.href='index.php?page=stock';</script>";
    exit();
}

// EDIT Product
if (isset($_POST['edit_product']) && verifyCsrfToken()) {
    $edit_id = filter_input(INPUT_POST, 'edit_id', FILTER_VALIDATE_INT);
    $new_name = trim(filter_input(INPUT_POST, 'edit_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $new_stock = filter_input(INPUT_POST, 'edit_stock', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

    if ($edit_id && $new_name && $new_stock !== false) {
        try {
            $stmt = $conn->prepare("UPDATE products SET product_name=?, stock_amount=? WHERE id=?");
            $stmt->execute([$new_name, $new_stock, $edit_id]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => "Product updated successfully."];
        } catch (PDOException $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => "Error updating product: " . $e->getMessage()];
        }
    } else {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => "Invalid input data."];
    }
    echo "<script>window.location.href='index.php?page=stock';</script>";
    exit();
}

// ADD STOCK
if(isset($_POST['submit_stock']) && verifyCsrfToken()) {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $stock_in = filter_input(INPUT_POST, 'stock_in', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($product_id && $stock_in) {
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("INSERT INTO stocks (product_id, stock_in, added_by, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$product_id, $stock_in, $_SESSION['user_session']]);

            $updateStmt = $conn->prepare("UPDATE products SET stock_amount = stock_amount + ?, last_updated = NOW() WHERE id = ?");
            $updateStmt->execute([$stock_in, $product_id]);

            $pstmt = $conn->prepare("SELECT product_name FROM products WHERE id = ?");
            $pstmt->execute([$product_id]);
            $product = $pstmt->fetch(PDO::FETCH_ASSOC);

            $userStmt = $conn->prepare("SELECT userEmail FROM users WHERE user_type IN('manager','user') AND userEmail != ?");
            $userStmt->execute([$_SESSION['user_session']]);
            $emails = $userStmt->fetchAll(PDO::FETCH_COLUMN);

            $subject = "New Stock Update: {$product['product_name']}";
            $message = "Stock of '{$product['product_name']}' has been updated. New stock added: {$stock_in}";

            foreach($emails as $email) {
                $user->sendMail($email, $message, $subject);
            }

            $conn->commit();
            $_SESSION['flash'] = ['type' => 'success', 'message' => "Stock updated successfully and notification sent"];
        } catch (PDOException $e) {
            $conn->rollback();
            $_SESSION['flash'] = ['type' => 'danger', 'message' => "Error: " . $e->getMessage()];
        }
    } else {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => "Invalid product or stock amount"];
    }
    echo "<script>window.location.href='index.php?page=stock';</script>";
    exit();
}

// Flash message handling
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Fetch all products with pagination
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$limit = 10;
$offset = ($page - 1) * $limit;

$totalStmt = $user->runQuery("SELECT COUNT(*) FROM products");
$totalStmt->execute();
$totalProducts = $totalStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

$stmt = $user->runQuery("SELECT id, product_name, stock_amount, last_updated FROM products ORDER BY last_updated DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

function verifyCsrfToken() {
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .card-body {
            padding: 20px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .table thead th {
            background-color: var(--primary-color);
            color: white;
        }
        
        .action-btns .btn {
            margin-right: 5px;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-box i {
            position: absolute;
            top: 12px;
            left: 12px;
            color: #6c757d;
        }
        
        .search-box input {
            padding-left: 35px;
            border-radius: 20px;
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .pagination .page-link {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">
                            <i class="bi bi-box-seam"></i> Stock Management System
                        </h4>
                        
                        <?php if(isset($flash)): ?>
                            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($flash['message']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>   
                        <div class="tab-content p-3 border border-top-0 rounded-bottom" id="myTabContent">
                        <h2 class="h5 mb-3">Add Stock</h2> 
                            <div class="tab-pane fade show active" id="add-stock" role="tabpanel">
                                <form method="post" class="row g-3">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    
                                    <div class="col-md-6">
                                        <label for="productSelect" class="form-label">Select Product</label>
                                        <select name="product_id" id="productSelect" class="form-select" required>
                                            <option value="">Select a product...</option>
                                            <?php foreach($products as $p): ?>
                                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['product_name']) ?> (Current: <?= $p['stock_amount'] ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="stockAmount" class="form-label">Stock Amount</label>
                                        <div class="input-group">
                                            <input type="number" name="stock_in" id="stockAmount" class="form-control" required min="1" placeholder="Enter amount">
                                            <span class="input-group-text">units</span>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="submit" name="submit_stock" class="btn btn-primary w-100">
                                            <i class="bi bi-plus-circle"></i> Add Stock
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="tab-content p-3 border border-top-0 rounded-bottom" id="">
                            
                            <h2 class="h5 mb-3 mt-4">Manage Stock</h2>   
                                <div class="table-responsive">
                                    <table class="table table-hover" id="productsTable">
                                        <thead>
                                            <tr>
                                                <th>Product Name</th>
                                                <th>Current Stock</th>
                                                <th>Last Updated</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($products as $product): ?>
                                                <tr>
                                                    <form method="post">
                                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                        <input type="hidden" name="edit_id" value="<?= $product['id'] ?>">
                                                        
                                                        <td>
                                                            <input type="text" name="edit_name" value="<?= htmlspecialchars($product['product_name']) ?>" class="form-control form-control-sm" required>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="edit_stock" value="<?= $product['stock_amount'] ?>" class="form-control form-control-sm" required min="0">
                                                        </td>
                                                        <td>
                                                            <?= $product['last_updated'] ? date('M d, Y H:i', strtotime($product['last_updated'])) : 'N/A' ?>
                                                        </td>
                                                        <td class="action-btns">
                                                            <button type="submit" name="edit_product" class="btn btn-sm btn-warning" title="Save changes">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                            
                                                            <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                                <input type="hidden" name="delete_id" value="<?= $product['id'] ?>">
                                                                <button type="submit" name="delete_product" class="btn btn-sm btn-danger" title="Delete product">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </form>
                                                            
                                                            <a href="product_history.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-info" title="View history">
                                                                <i class="bi bi-clock-history"></i>
                                                            </a>
                                                        </td>
                                                    </form>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <?php if ($totalPages > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#productsTable').DataTable({
                searching: true,
                paging: false,
                info: false,
                responsive: true
            });
            
            // Search functionality
            $('#searchInput').keyup(function() {
                $('#productsTable').DataTable().search($(this).val()).draw();
            });
            
            // Auto-focus search input when manage tab is shown
            $('#manage-tab').on('shown.bs.tab', function() {
                $('#searchInput').focus();
            });
            
            // Show success/error messages
            if ($('.alert').length) {
                setTimeout(function() {
                    $('.alert').fadeOut('slow');
                }, 3000);
            }
        });
    </script>
</body>
</html>