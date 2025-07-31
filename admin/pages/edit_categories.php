<?php
if (!isset($_SESSION['userSession'])) {
    exit("Unauthorized access.");
}

// require_once __DIR__ . '/../../config/classes/user.php';
// $DB_con = new USER();


if(!isset($_GET['id']))
{
	die('Invalid Request');
}

$decoded_id = base64_decode(urldecode($_GET['id']));



$stmt = $DB_con->runQuery("SELECT * FROM products WHERE id = ?");
$stmt->execute([$decoded_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$product)
{
	die("Product Not Found!");
}
//Attributes Fetch
$stmtAttr = $DB_con->runQuery("SELECT * FROM attributes WHERE product_id = ?");

$stmtAttr->execute([$decoded_id]);

$attribute = $stmtAttr->fetch(PDO::FETCH_ASSOC);


//Handle Update

if(isset($_POST['update']))
{
	$productname = $_POST['product_name'];
	$description = $_POST['description'];
	$productstock = $_POST['product_stock'];
	$price = $_POST['price'];
	$category_id = $_POST['category_id'];
	$sub_cat_id = $_POST['sub_cat_id'];
	$has_attributes = isset($_POST['has_attributes']) ? 1 : 0;
	$sizes = isset($_POST['sizes']) ? implode(',', $_POST['sizes']) : '';
	$colors = $_POST['colors'];

	//Image Upload Handling
	
	$new_image = $product['product_image'];

	if(!empty($_FILES['product_image']['name']))
	{
		$imgfile = $_FILES['product_image']['name'];
		$tem_dir = $_FILES['product_image']['tmp_name'];
		$imgext = strtolower(pathinfo($imgfile, PATHINFO_EXTENSION));
		$valid_extensions = ['jpg','jpeg','png','gif'];
		$upload_dir = "pages/uploads/";
		
		if(in_array($imgext, $valid_extensions))
		{
			$new_image = rand(1000, 1000000000).".".$imgext;
			move_uploaded_file($tem_dir, $upload_dir.$new_image);
			if(file_exists($upload_dir.$product['product_image']))
			{
				unlink($upload_dir.$product['product_image']);
			}
		}
	}

	//Update Product

	$stmt = $DB_con->runQuery("UPDATE products SET product_name=?, description = ?, product_image=?, price = ?, stock_amount =?, has_attributes=?, category_id=?, sub_cat_id=? WHERE id=?");

	$stmt->execute([$productname,$description,$new_image,$price,$productstock,$has_attributes,$category_id,$sub_cat_id,$decoded_id]);

	//Update attributes

	if($has_attributes)
	{
		if($attribute)
		{
			$stmtAttr = $DB_con->runQuery("UPDATE attributes SET sizes = ?, colors = ? WHERE product_id =?");

			$stmtAttr->execute([$sizes,$colors,$decoded_id]);
		}

		else
		{
			$stmtAttr = $DB_con->runQuery("INSERT INTO attributes(product_id,sizes,colors) VALUES (?,?,?)");

			$stmtAttr->execute([$decoded_id,$sizes,$colors]);
		}
	}

	else
	{
		$stmtAttr = $DB_con->runQuery("DELETE FROM attributes WHERE product_id=?");

			$stmtAttr->execute([$decoded_id]);
	}


	if (isset($_POST['update'])) {
		// Your update logic here...

		$success = "Product updated successfully";

		// Redirect before any output
		header('refresh:5; url=index.php?page=categories');
		exit; 
	}
}

		
//Get all categories

$cats = $DB_con->runQuery("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
$sub_cats = $DB_con->runQuery("SELECT * FROM sub_categories")->fetchAll(PDO::FETCH_ASSOC);


?>

<!DOCTYPE html>
<html>
<head>
	<title>Update Products</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css" integrity="sha512-Dop/vW3iOtayerlYAqCgkVr2aTr2ErwwTYOvRFUpzl2VhCMJyjQF0Q9TjUXIo6JhuM/3i0vVEt2e/7QQmnHQqw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

<div class="container mt-5">
	<h3 class="mb-4">Edit Products</h3>

	<?php 
	if(!empty($success)) echo "<div class='alert alert-success'>$success</div>";



	?>

	<?php if(!empty($successmsg)) echo "<div class='alert alert-success'>$successmsg</div>";?>

	<form method="post" enctype="multipart/form-data">
		<div class="form-group">
			<label>Product Name:</label>
			<input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($product['product_name'])?>">
		</div>

		<div class="form-group">
			<label>Description:</label>
			<textarea name="description" class="form-control"><?= htmlspecialchars($product['description'])?></textarea>
		</div>
		

		<div class="form-group">
			<label>Stock Amount:</label>
			<input type="number" name="product_stock" class="form-control" value="<?= (int)$product['stock_amount']?>">
		</div>

		<div class="form-group">
			<label>Category:</label>
			<select name="category_id" class="form-control" required>
				<?php
					foreach ($cats as $cat) : ?>
						<option value="<?= $cat['id'];?>" <?= $cat['id'] == $product['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['category_name']); ?></option>
					<?php endforeach; ?>
			</select>
		</div>
		<div class="form-group">
			<label>Sub Category:</label>
			<select name="sub_cat_id" class="form-control" required>
				<?php
					foreach ($sub_cats as $scat) : ?>
						<option value="<?= $scat['id'];?>" <?= $scat['id'] == $product['sub_cat_id'] ? 'selected' : '' ?>><?= htmlspecialchars($scat['sub_cat_name']); ?></option>
					<?php endforeach; ?>
			</select>
		</div>

		<div class="form-group">
			<label>Currtent Image:</label>
			<img src="pages/uploads/<?= $product['product_image']?>" width="100"><br><br>
			<input type="file" name="product_image" class="form-control-file">
		</div>

		<div class="form-group">
			<label>Price:</label>
			<input type="number" name="price" class="form-control" value="<?= (int)$product['price']?>">
		</div>

		<div class="form-check">
			<input type="checkbox" name="has_attributes" class="form-check-input" id="hasAttributes" <?= $product['has_attributes'] ? 'checked' : ''?> onclick="toggleAttributes()">
			<label class="form-check-label">Has Attributes?</label>
		</div>

		<div id="attributeSection" style="display: <?= $product['has_attributes'] ? 'block' : 'none'?>;">
			<div class="form-group mt-2">
				<label>Sizes:</label>
				<?php $selectedSizes = explode(',',$attribute['sizes'] ?? ''); ?>
				<?php foreach(['L','XL','XXL'] as $size): ?>
					<label class="mr-2">
						<input type="checkbox" name="sizes[]" value="<?= $size ?>" <?= in_array($size, $selectedSizes) ? 'checked' : '' ?>> <?= $size ?>
					</label>
				<?php endforeach; ?>
			</div>

			<div class="form-group">
				<label>Add Colors:</label><br>
				<input type="color"  class="color-input" value="#000000">
				<button type="button" class="btn btn-sm btn-secondary" onclick="addColor()">Add Color</button>
				<div id="colorList" class="mt-2"></div>
				<input type="hidden" name="colors" id="colors" value="<?= $attribute['colors'] ?? '' ?>">
			</div>
		</div>
		<button type="submit" name="update" class="btn btn-success">Update Product</button>
		<a href="products.php" class="btn btn-secondary">Back</a>
	</form>
</div>
</body>
</html>

<script>
	
	let selectedColors = <?= json_encode(explode(',',$attribute['colors']?? '')) ?>;

	function toggleAttributes()
	{
		document.getElementById('attributeSection').style.display = document.getElementById('hasAttributes').checked ? 'block' : 'none';
	}

	function addColor()
	{
		const color = document.querySelector('.color-input').value;
		

		if(!selectedColors.includes(color))
		{
			selectedColors.push(color);
			updateColorList();
		}
	}

	function updateColorList()
	{
		const colorList = document.getElementById('colorList');
		const colorInput = document.getElementById('colors');
		colorList.innerHTML = '';

		selectedColors.forEach((color, index) => {

			const colorBox = document.createElement('div');			
			colorBox.style.backgroundColor = color;
			colorBox.style.width = '30px';
			colorBox.style.height = '30px';
			colorBox.style.display = 'inline-block';
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

	updateColorList();

</script>