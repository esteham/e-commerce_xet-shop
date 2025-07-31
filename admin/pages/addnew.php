<?php

if (!isset($_SESSION['userSession'])) {
    exit("Unauthorized access.");
}
// require_once __DIR__ . '/../../config/classes/user.php';
// $DB_con = new USER();

$errmsg = '';
$successmsg = '';

//Fetch Categories
$cat_stmt = $DB_con->runQuery("SELECT * FROM categories");
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);


//Fetch Subcategories
/* fetch_subcategories.php */

if(isset($_POST['btnsave']))
{
	$productname = $_POST['product_name'];
	$description = $_POST['description'];
	$productstock = $_POST['product_stock'];
	$price = $_POST['price'];
	$category_id = $_POST['category_id'];
	$has_attributes = isset($_POST['has_attributes']) ? 1 : 0;
	$sizes = isset($_POST['sizes']) ? implode(',', $_POST['sizes']) : '';
	$colors = isset($_POST['colors']) ? $_POST['colors'] : '';

	//Image uploads

	$imgfile = $_FILES['product_image']['name'];
	$tmp_dir = $_FILES['product_image']['tmp_name'];
	$imgsize = $_FILES['product_image']['size'];

	if(empty($productname) || empty($description) || empty($imgfile) || empty($productstock))
	{
		$errmsg = "All fields are required!";
	}

	else
	{
		$upload_dir = "pages/uploads/";
		$imgext = strtolower(pathinfo($imgfile, PATHINFO_EXTENSION));
		$valid_extensions = ['jpg','jpeg','png','gif','webp','jfif','pdf','docx','doc','pptx','ppt','xlsx','xls'];
		$productpic = rand(1000, 1000000000).".".$imgext;

		if(in_array($imgext, $valid_extensions) && $imgsize < 5000000)
		{
			move_uploaded_file($tmp_dir, $upload_dir.$productpic);
		}

		else
		{
			$errmsg = "Invalid image file or size";
		}
	}

	if(empty($errmsg))
	{
		$stmt = $DB_con->runQuery("INSERT INTO products (product_name,description,product_image,price,stock_amount,has_attributes,category_id) VALUES (:pname,:pdesc,:ppic,:pprice,:pstock,:hasattr,:cat_id)");

		$stmt->bindParam(':pname',$productname);
		$stmt->bindParam(':pdesc',$description);
		$stmt->bindParam(':ppic',$productpic);
		$stmt->bindParam(':pprice',$price);
		$stmt->bindParam(':pstock',$productstock);
		$stmt->bindParam(':hasattr',$has_attributes);
		$stmt->bindParam(':cat_id',$category_id);

		if($stmt->execute())
		{
			$lastProductId = $DB_con->lastID();

			if($has_attributes)
			{
				$attr_stmt = $DB_con->runQuery("INSERT INTO attributes (product_id,sizes,colors) VALUES (:pid,:sizes,:colors)");

				$attr_stmt->bindParam(':pid',$lastProductId);
				$attr_stmt->bindParam(':sizes',$sizes);
				$attr_stmt->bindParam(':colors',$colors);
				$attr_stmt->execute();
			}

			// fatch all registerd users
			// Fetch all registered users (with ID + Email in one query)
			$stmt = $DB_con->runQuery("SELECT id, userEmail FROM users WHERE is_active = 1");
			try {
				$stmt->execute();
				$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

				// Send email + notification to each user (only once)
				foreach ($users as $user) {
					$email = $user['userEmail'];
					$user_id = $user['id'];

					// Send email (only once)
					$message = 
					" 
						<div style='font-family: Arial, sans-serif;'> 
							<h2>New Product Alert!</h2> 
							<p>We have added a new product to our store:</p> 
								<div style='border: 1px solid #ddd; padding: 15px; max-width: 300px;'> 
									<img src='https://eshop.xetroot.com/admin/pages/uploads/$productpic'
									alt='$productname' style='width: 100%; height: auto;'> 
									<h3 style='margin-top: 10px;'>$productname</h3> 
									<p style='color: #555;'>$description</p> 
									<p><strong>Price:</strong> $$price</p> 
									<a href='https://eshop.xetroot.com/index.php?page=product&id=$lastProductId' 
									style='display: inline-block; padding: 10px 15px; background-color: #007bff;
									color: #fff; text-decoration: none; border-radius: 5px;'>View Product</a> 
							</div> 
						</div>
					";
					$subject = "New Product Alert";
					$DB_con->sendMail($email, $message, $subject);

					// Insert notification (only once)
					$notif_stmt = $DB_con->runQuery("INSERT INTO notifications (user_id, product_id, message) VALUES (:uid, :pid, :msg)");
					$notif_stmt->execute([
						':uid' => $user_id,
						':pid' => $lastProductId,
						':msg' => "New product added: " . $productname
					]);
				}
			
			} catch (PDOException $e) {
				$errmsg = "Error executing query: " . $e->getMessage();
			}

			$successmsg = "New Product Inserted Successfully";
			echo "<script>window.location.href='index.php?page=addnew';</script>";
    		exit;
		}

		else
		{
			$errmsg = "Error while inserting";
		}
	}
}

?>

<!DOCTYPE html>
<html>
<head>
	<title>Add New Products</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container" style="width: 98%;">
	<h3 class="mb-4">Add Products</h3>

	<?php if(!empty($errmsg)) echo "<div class='alert alert-danger'>$errmsg</div>";?>

	<?php if(!empty($successmsg)) echo "<div class='alert alert-success'>$successmsg</div>";?>

	<form method="post" enctype="multipart/form-data">
		<div class="form-group">
			<label>Product Name:</label>
			<input type="text" name="product_name" class="form-control" required>
		</div>

		<div class="form-group">
			<label>Description:</label>
			<textarea name="description" class="form-control" rows="3" required></textarea>
		</div>

		<div class="form-group">
			<label>Product Image:</label>
			<input type="file" name="product_image" class="form-control" required>
		</div>

		<div class="form-group">
			<label>Price:</label>
			<input type="number" name="price" class="form-control" required>
		</div>

		<div class="form-group">
			<label>Stock Amount:</label>
			<input type="number" name="product_stock" class="form-control" required>
		</div>

		<div class="form-group">
				<label>Category:</label>
					<select name="category_id" id="category_id" class="form-control" required>
						<option value="">Select Category</option>
						<?php foreach ($categories as $cat) : ?>
							<option value="<?= $cat['id'];?>"><?= htmlspecialchars($cat['category_name']) ?></option>
						<?php endforeach; ?>
					</select>
		</div>
		<div class="form-group">
				<label>Sub Category:</label>
					<select name="sub_cat_id" id="sub_cat_id" class="form-control">
						<option value="">Select Sub Category</option>
						<!-- Subcategories will be loaded here dynamically -->
					</select>
		</div>
		


		<div class="form-check mb-3 mt-3">
			<input type="checkbox" name="has_attributes" class="form-check-input" id="hasAttributes" onchange="toggleAttributes()">
			<label class="form-check-label">Has Attributes?</label>
		</div>

		<div id="attributeSection" style="display: none;">
			<div class="form-group">
				<label>Sizes:</label>
				<label class="checkbox-inline mr-2"><input type="checkbox" name="sizes[]" value="L">S</label>
				<label class="checkbox-inline mr-2"><input type="checkbox" name="sizes[]" value="L">L</label>
				<label class="checkbox-inline mr-2"><input type="checkbox" name="sizes[]" value="XL">XL</label>
				<label class="checkbox-inline mr-2"><input type="checkbox" name="sizes[]" value="XXL">XXL</label>
			</div>

			<div class="form-group">
				<label>Colors:</label>
				<input type="color"  class="color-input">
				<button type="button" class="btn btn-sm btn-secondary" onclick="addColor()">Add Color</button>
				<div id="colorList" class="mt-2"></div>
				<input type="hidden" name="colors" id="colors">
			</div>
		</div>
		<button type="submit" name="btnsave" class="btn btn-success">Save</button>
	</form>
</div>
</body>
</html>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript">
	
	let selectedColors = [];

	function toggleAttributes()
	{
		const attrSection = document.getElementById('attributeSection');
		attrSection.style.display = document.getElementById('hasAttributes').checked ? 'block' : 'none';
	}

	function addColor()
	{
		const colorInput = document.querySelector('.color-input');
		const color = colorInput.value;

		if(!selectedColors.includes(color))
		{
			selectedColors.push(color);
			updateColorList();
		}
	}

	//ColorList is updated to show selected colors
	function updateColorList()
	{
		const colorList = document.getElementById('colorList');
		const colorInput = document.getElementById('colors');
		colorList.innerHTML = '';

		selectedColors.forEach((color, index) => {

			const colorBox = document.createElement('div');
			colorBox.style.display = 'inline-block';
			colorBox.style.backgroundColor = color;
			colorBox.style.width = '30px';
			colorBox.style.height = '30px';
			colorBox.style.marginRight = '5px';
			colorBox.style.border = '1px solid #000';
			colorBox.title = color;
			colorBox.onclick = () => {

				selectedColors.splice(index, 1);
				updateColorList();
			};

			colorList.appendChild(colorBox);

		});

		colorInput.value = selectedColors.join(',')

	}
	
	$(document).ready(function(){
		$('#category_id').on('change', function(){
			var category_id = $(this).val();

			if(category_id != '') {
				$.ajax({
					url: 'pages/ajax/fetch_subcategories.php',
					type: 'POST',
					data: {category_id: category_id},
					success: function(response){
						$('#sub_cat_id').html('<option value="">Select Sub Category</option>' + response);
					}
				});

			} else {
				$('#sub_cat_id').html('<option value="">Select Sub Category</option>');
			}
		});
	});
</script>
