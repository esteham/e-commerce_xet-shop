<?php
if (!isset($_SESSION['userSession'])) {
    exit("Unauthorized access.");
}
// // Fetch class for user
// require_once __DIR__ . '/../../config/classes/user.php';
// $DB_con = new USER();

$errmsg = '';
$successmsg = '';
//Handle Delete Operation
$delete_id = null; // Initialize with a default value
if(isset($_GET['delete_id']))
{
	$delete_id = (int) base64_decode(urldecode($_GET['delete_id']));

	//Fetch Image File
	$stmtImg = $DB_con->runQuery("SELECT product_image FROM products WHERE id = ?");
	$stmtImg->execute([$delete_id]);
	$productImg = $stmtImg->fetchColumn();

	if($productImg && file_exists("pages/uploads/$productImg"))
	{
		unlink("pages/uploads/$productImg");
        $successmsg = "Deleted Successfully";
	}
    
}

$stmtAttr = $DB_con->runQuery("DELETE FROM attributes WHERE product_id = ?");
$stmtAttr->execute([$delete_id]);

$stmtDel = $DB_con->runQuery("DELETE FROM products WHERE id = ?");
$stmtDel->execute([$delete_id]);



//Fetch all products

$stmt = $DB_con->runQuery("SELECT * FROM products ORDER BY id DESC");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
	<title>All Products</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style type="text/css">
		.thumbnail-img
		{
			width: 80px;
			height: 80px;
			object-fit: cover;
		}

		.color-box
		{
			display: inline-block;
			width: 20px;
			height: 20px;
			border: 1px solid #000;
			margin-right: 4px;
		}
	</style>
</head>
<body>

            <?php if(!empty($errmsg)) echo "<div class='alert alert-danger'>$errmsg</div>";?>
            <?php if(!empty($successmsg)) echo "<div class='alert alert-success'>$successmsg</div>";?>
    <div class="container-fluid" style="width: 98%;">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="page-title">
                    <i class="fas fa-box-open me-2"></i> Product Management
                </h2>
                
                <div>
                    <a href="index.php?page=addnew" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> Add New Product
                    </a>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Stock</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Attributes</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($products): ?>
                            <?php foreach($products as $row): ?>
                                <?php
                                    $product_id = $row['id'];
                                    $encrypted_id = urlencode(base64_encode($product_id));
                                    
                                    $attrStmt = $DB_con->runQuery("SELECT sizes, colors FROM attributes WHERE product_id = ?");
                                    $attrStmt->execute([$product_id]);
                                    $attribute = $attrStmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    $sizes = $attribute['sizes'] ?? '';
                                    $colors = $attribute['colors'] ?? '';
                                    $colorArray = explode(',', $colors);
                                ?>
                                
                                <tr>
									<!-- Product Image -->
                                    <td>
                                        <img src="pages/uploads/<?php echo htmlspecialchars($row['product_image']); ?>" class="thumbnail-img">
                                    </td>
                                    
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['product_name']); ?></strong>
                                        <div class="text-muted small"><?php echo substr(htmlspecialchars($row['description']), 0, 50) . '...'; ?></div>
                                    </td>
                                    
									<!-- Stock Status -->
                                    <td>
                                        <?php if((int)$row['stock_amount'] == 0): ?>
                                            <span class="badge bg-danger">Out of Stock <?=$row['stock_amount']?></span>
                                        <?php elseif ((int)$row['stock_amount'] <5): ?>
                                            <span class="badge bg-warning">Low Stock <?=$row['stock_amount']?></span>
										<?php else : ?>
											<span class="badge bg-success">In Stock <?=$row['stock_amount']?></span>
                                        <?php endif; ?>
                                    </td>
                                    
									<!-- Price -->
                                    <td>
                                        <strong>$<?php echo number_format((int)$row['price'], 2); ?></strong>
                                    </td>
                                    
                                    <td>
                                        <?php if(!empty($row['category_id'])): ?>
                                            <?php 
                                                $catStmt = $DB_con->runQuery("SELECT category_name FROM categories WHERE id = ?");
                                                $catStmt->execute([$row['category_id']]);
                                                $category = $catStmt->fetchColumn();
                                            ?>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($category); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Uncategorized</span>
                                        <?php endif; ?>
                                    </td>
                                    
									<!-- Attributes -->
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php if($sizes): ?>
                                                <span class="badge bg-light text-dark">
                                                    <i class="fas fa-ruler me-1"></i> <?php echo htmlspecialchars($sizes); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if($colors): ?>
                                                <div class="d-flex align-items-center">
                                                    <?php foreach($colorArray as $color): ?>
                                                        <span class="color-box" style="background-color: <?php echo htmlspecialchars($color); ?>" title="<?php echo $color; ?>"></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
									<!-- Actions -->
                                    <td class="text-end">
                                        <div class="d-flex flex-column">
                                            <a href="index.php?page=edit_product&id=<?php echo $encrypted_id; ?>" class="btn btn-sm btn-warning action-btn">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="index.php?page=products&delete_id=<?php echo $encrypted_id; ?>" class="btn btn-sm btn-danger action-btn">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                            <a href="view_product.php?id=<?php echo $encrypted_id; ?>" class="btn btn-sm btn-info action-btn">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fas fa-box-open fa-3x"></i>
                                        <h4>No Products Found</h4>
                                        <p>You haven't added any products yet. Click the button above to add your first product.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
    </div>

    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Add confirmation for delete action
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.btn-danger');
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if(!confirm('Are you sure you want to delete this product?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>