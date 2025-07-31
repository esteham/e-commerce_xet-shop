<?php
if (!isset($_SESSION['userSession'])) {
    exit("Unauthorized access.");
}
// BD Connection
// require_once __DIR__ . '/../../config/classes/user.php';
// $DB_con = new USER();

$error = '';
$success = '';

// Delete Subcategory
if (isset($_GET['delete'])) {
    $del_id = $_GET['delete'];

    // Check for dependent records
    $check_stmt = $DB_con->runQuery("SELECT COUNT(*) FROM products WHERE sub_cat_id = ?");
    if($check_stmt->execute([$del_id]) && $check_stmt->fetchColumn() > 0) {
        $error = "Cannot delete subcategory with existing products!";
    } else {
        $stmt = $DB_con->runQuery("DELETE FROM sub_categories WHERE id = ?");
        if ($stmt->execute([$del_id])) {
            $success = "Subcategory deleted successfully!";
        } else {
            $error = "Failed to delete subcategory.";
        }
    }
}

// Edit Subcategory
$edit_id = '';
$edit_name = '';
$edit_category_id = '';
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $DB_con->runQuery("SELECT * FROM sub_categories WHERE id = :id");
    $stmt->bindParam(':id', $edit_id);
    $stmt->execute();
    $subcategory = $stmt->fetch(PDO::FETCH_ASSOC);
    $edit_name = $subcategory['sub_cat_name'];
    $edit_category_id = $subcategory['category_id'];
}

if (isset($_POST['update_subcategory'])) {
    $new_name = $_POST['sub_cat_name'];
    $id = $_POST['edit_id'];
    $category_id = $_POST['category_id'];

    $stmt = $DB_con->runQuery("UPDATE sub_categories SET sub_cat_name = :name, category_id = :cat_id WHERE id = :id");
    $stmt->bindParam(':name', $new_name);
    $stmt->bindParam(':cat_id', $category_id);
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        $success = "Subcategory updated successfully!";
    } else {
        $error = "Error updating subcategory!";
    }
}

// Add Subcategory
if (isset($_POST['add_subcategory'])) {
    $sub_cat_name = $_POST['sub_cat_name'];
    $category_id = $_POST['category_id'];
    
    // Check if subcategory already exists
    $check_stmt = $DB_con->runQuery("SELECT COUNT(*) FROM sub_categories WHERE sub_cat_name = :name AND category_id = :cat_id");
    $check_stmt->bindParam(':name', $sub_cat_name);
    $check_stmt->bindParam(':cat_id', $category_id);
    $check_stmt->execute();
    $count = $check_stmt->fetchColumn();
    
    if ($count > 0) {
        $error = "Subcategory already exists for this category!";
    } else {
        $stmt = $DB_con->runQuery("INSERT INTO sub_categories (sub_cat_name, category_id) VALUES (:name, :cat_id)");
        $stmt->bindParam(':name', $sub_cat_name);
        $stmt->bindParam(':cat_id', $category_id);
        if ($stmt->execute()) {
            $success = "Subcategory added successfully!";
        } else {
            $error = "Error adding subcategory!";
        }
    }
}

// Fetch Categories
$cat_stmt = $DB_con->runQuery("SELECT * FROM categories ORDER BY category_name");
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Subcategories with their parent categories
$sub_cat_stmt = $DB_con->runQuery("
    SELECT sc.*, c.category_name 
    FROM sub_categories sc
    JOIN categories c ON sc.category_id = c.id
    ORDER BY c.category_name, sc.sub_cat_name
");
$sub_cat_stmt->execute();
$subcategories = $sub_cat_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subcategory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4bb543;
            --danger-color: #ff3333;
            --warning-color: #ffc107;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            border-radius: 12px 12px 0 0 !important;
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .table {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }
        
        .badge-category {
            background-color: var(--accent-color);
            color: white;
        }
        
        .badge-subcategory {
            background-color: #7209b7;
            color: white;
        }
        
        .action-btn {
            transition: all 0.2s ease;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
        }
        
        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(76, 201, 240, 0.25);
        }
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold text-primary">
                    <i class="bi bi-diagram-2 me-2"></i>Subcategory Management
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="index.php?page=categories">Categories</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Subcategories</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Toast Notifications -->
        <?php if(!empty($error)): ?>
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-danger text-white">
                    <strong class="me-auto">Error</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body"><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>
            
        <?php if(!empty($success)): ?>
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-success text-white">
                    <strong class="me-auto">Success</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body"><?= htmlspecialchars($success) ?></div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Subcategory Form Card -->
            <div class="col-lg-4">
                <div class="card animate__animated animate__fadeInLeft">
                    <div class="card-header">
                        <i class="bi bi-plus-circle me-2"></i>
                        <?= $edit_id ? 'Update Subcategory' : 'Add New Subcategory' ?>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Parent Category</label>
                                <select class="form-select" name="category_id" required>
                                    <option value="" disabled selected>Select Category</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" 
                                            <?= ($edit_id && $cat['id'] == $edit_category_id) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['category_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="sub_cat_name" class="form-label">Subcategory Name</label>
                                <input type="text" class="form-control" name="sub_cat_name" 
                                    value="<?= htmlspecialchars($edit_name) ?>" required>
                            </div>
                            
                            <?php if ($edit_id): ?>
                                <input type="hidden" name="edit_id" value="<?= $edit_id ?>">
                                <button type="submit" name="update_subcategory" class="btn btn-warning me-2">
                                    <i class="bi bi-pencil-square me-1"></i> Update
                                </button>
                                <a href="index.php?page=sub_cat" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-1"></i> Cancel
                                </a>
                            <?php else: ?>
                                <button type="submit" name="add_subcategory" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Add Subcategory
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Subcategories Table Card -->
            <div class="col-lg-8">
                <div class="card animate__animated animate__fadeInRight">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul me-2"></i>Subcategories List
                        </h5>
                        <div>
                            <span class="badge bg-primary rounded-pill">
                                <?= count($subcategories) ?> subcategories
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th width="30%">Category</th>
                                        <th width="40%">Subcategory</th>
                                        <th width="30%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($subcategories) > 0): ?>
                                        <?php foreach($subcategories as $subcat): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge badge-category p-2">
                                                        <?= htmlspecialchars($subcat['category_name']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-subcategory p-2">
                                                        <?= htmlspecialchars($subcat['sub_cat_name']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="index.php?page=sub_cat&edit=<?= $subcat['id'] ?>" 
                                                           class="btn btn-sm btn-outline-primary action-btn" 
                                                           data-bs-toggle="tooltip" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        
                                                        <a href="index.php?page=sub_cat&delete=<?= $subcat['id'] ?>" 
                                                           onclick="return confirm('Are you sure you want to delete this subcategory?')" 
                                                           class="btn btn-sm btn-outline-danger action-btn" 
                                                           data-bs-toggle="tooltip" title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">
                                                <i class="bi bi-exclamation-circle fs-1"></i>
                                                <p class="mt-2 mb-0">No subcategories found</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Auto-dismiss toasts after 5 seconds
            var toastElList = [].slice.call(document.querySelectorAll('.toast'));
            var toastList = toastElList.map(function (toastEl) {
                return new bootstrap.Toast(toastEl, {delay: 5000});
            });
            toastList.forEach(toast => toast.show());
        });
    </script>
</body>
</html>