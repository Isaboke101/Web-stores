<?php 
	include '../components/connect.php';

 if (isset($_COOKIE['seller_id'])) {
 	$seller_id = $_COOKIE['seller_id'];
 }else{
 	$seller_id = '';
 	header('location:login.php');
 }

 //add products in database
 if (isset($_POST['publish'])) {
 	
 	$id = unique_id();
 	$name = $_POST['name'];
 	$name = filter_var($name, FILTER_SANITIZE_STRING);

 	$price = $_POST['price'];
 	$price = filter_var($price, FILTER_SANITIZE_STRING);

 	$description = $_POST['description'];
 	$description = filter_var($description, FILTER_SANITIZE_STRING);

 	$stock = $_POST['stock'];
 	$stock = filter_var($stock, FILTER_SANITIZE_STRING);
 	$status = 'active';

 	$image = $_FILES['image']['name'];
 	$image = filter_var($image, FILTER_SANITIZE_STRING);
 	$image_size = $_FILES['image']['size'];
 	$image_tmp_name = $_FILES['image']['tmp_name'];
 	$image_folder = '../uploaded_files/'.$image;

 	$select_image = $conn->prepare("SELECT * FROM products WHERE image = ? AND seller_id = ?");
 	$select_image->execute([$image,$seller_id]);

 	if (isset($image)) {
	 		if ($select_image->rowCount() > 0) {
	 			$warning_msg[] = 'image name repeated';
	 		}
	 		elseif ($image_size > 2000000) {
	 			$warning_msg[] = 'image size is too large';
	 		}
	 		else{
	 			move_uploaded_file($image_tmp_name, $image_folder);
	 		}
	 	}
 		else{
 			$image = '';
 		}
 		if ($select_image->rowCount() > 0 AND $image != '') {
 			$warning_msg[] = 'please rename your image';
 		}
 		else{
 			$insert_product = $conn->prepare("INSERT INTO products (id, seller_id, name, price, image, stock, product_detail, status) VALUES(?,?,?,?,?,?,?,?)");
 			$insert_product->execute([$id, $seller_id, $name, $price, $image, $stock, $description, $status]);
 			$success_msg[] = 'product inserted successfully';
 		}
 	}


//add products in database
 if (isset($_POST['draft'])) {
 	
 	$id = unique_id();
 	$name = $_POST['name'];
 	$name = filter_var($name, FILTER_SANITIZE_STRING);

 	$price = $_POST['price'];
 	$price = filter_var($price, FILTER_SANITIZE_STRING);

 	$description = $_POST['description'];
 	$description = filter_var($description, FILTER_SANITIZE_STRING);

 	$stock = $_POST['stock'];
 	$stock = filter_var($stock, FILTER_SANITIZE_STRING);
 	$status = 'deactive';

 	$image = $_FILES['image']['name'];
 	$image = filter_var($image, FILTER_SANITIZE_STRING);
 	$image_size = $_FILES['image']['size'];
 	$image_tmp_name = $_FILES['image']['tmp_name'];
 	$image_folder = '../uploaded_files/'.$image;

 	$select_image = $conn->prepare("SELECT * FROM products WHERE image = ? AND seller_id = ?");
 	$select_image->execute([$image,$seller_id]);

 	if (isset($image)) {
	 		if ($select_image->rowCount() > 0) {
	 			$warning_msg[] = 'image name repeated';
	 		}
	 		elseif ($image_size > 2000000) {
	 			$warning_msg[] = 'image size is too large';
	 		}
	 		else{
	 			move_uploaded_file($image_tmp_name, $image_folder);
	 		}
	 	}
 		else{
 			$image = '';
 		}
 		if ($select_image->rowCount() > 0 AND $image != '') {
 			$warning_msg[] = 'please rename your image';
 		}
 		else{
 			$insert_product = $conn->prepare("INSERT INTO products (id, seller_id, name, price, image, stock, product_detail, status) VALUES(?,?,?,?,?,?,?,?)");
 			$insert_product->execute([$id, $seller_id, $name, $price, $image, $stock, $description, $status]);
 			$success_msg[] = 'product saved in draft successfully';
 		}
 	}


?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Nashon - seller registration page</title>
	<link rel="stylesheet" type="text/css" href="../css/admin_style.css">
	<!--Bootsrap link-->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

	<link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet">
</head>
<body>

	<div class="main-container container">
				<?php include '../components/admin_header.php'; ?>
		<section class="post-editor">
			<div class="dashboard">
				<h1 style="color: #fff;">Add Product</h1>
			</div>
			<div class="form-container">
				<form action="" method="POST" enctype="multipart/form-data" class="seller">
					<div class="input-field">
						<p>Product Name <span>*</span></p>
						<input type="text" name="name" maxlength="100" placeholder="Add Product Name" required class="box">
					</div>
					<div class="input-field">
						<p>Product Price <span>*</span></p>
						<input type="number" name="price" maxlength="100" placeholder="Add Product Price" required class="box">
					</div>
					<div class="input-field">
						<p>Product Detail <span>*</span></p>
						<textarea name="description" required maxlength="1000" placeholder="Add Product Details" class="box"></textarea>
					</div>
					<div class="input-field">
						<p>Product Stock <span>*</span></p>
						<textarea name="stock" required maxlength="10" min="0" max="1000" placeholder="Add Product Stock" class="box"></textarea>
					</div>
					<div class="input-field">
						<p>Product image <span>*</span></p>
						<input type="file" name="image" required accept="image/*" class="box"/>
					</div>
					<div class="flex-btn">
						<input type="submit" name="publish" value="add product" class="btn"/>
						<input type="submit" name="draft" value="save as draft" class="btn"/>
					</div>
				</form>
			</div>
		</section>
	</div>






	<!-- bootstrap link -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

	<!--sweetalert cdn link-->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

	<!--Custom js link-->
	<script type="text/javascript" src="../js/admin_script.js"></script>

	<?php include '../components/alert.php'; ?>
</body>
</html>