<?php 
	include '../components/connect.php';

 if (isset($_COOKIE['seller_id'])) {
 	$seller_id = $_COOKIE['seller_id'];
 }else{
 	$seller_id = '';
 	header('location:login.php');
 }

 //delete product
 if (isset($_POST['delete'])) {
 	$p_id = $_POST['product_id'];
 	$p_id = filter_var($p_id, FILTER_SANITIZE_STRING);
 	$delete_product = $conn->prepare("DELETE FROM products WHERE id = ?");
 	$delete_product->execute([$p_id]);
 	$success_msg[] = 'product deleted successfully';
 }

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Nashon - Show Products page</title>
	<link rel="stylesheet" type="text/css" href="../css/admin_style.css">
	<!--Bootsrap link-->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

	<link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet">

	<style type="text/css">
.empty{
	background-color: var(--white-alpha-25);
	border: 2px solid var(--white-alpha-40);
	backdrop-filter: var(--backdrop-filter);
	box-shadow: var(--box-shadow);
	text-transform: capitalize;
	color: var(--main-color);
	padding: 1.5rem;
	text-align: center;
	margin: 2rem auto;
	width: auto;
	border-radius: .5rem;
}

.show-post .box-container .box{
	position: relative;
	overflow: hidden;
}
.show-post .box-container .box:hover .image{
	transform: scale(1.1);
}
.show-post .box-container .box .image{
	width: 100%;
	height: 25rem;
	object-fit: cover;
	transition: .5s;
	background-color: palegreen;
}
.show-post .box-container .box .content{
	position: relative;
	display: block;
	background-color: #fff;
	padding: 40px 10px;
	margin-top: -80px;
	border-top-right-radius: 80px;
	text-align: center;
	line-height: 1.5;
	text-transform: capitalize;
}
.show-post .box-container .box .content .shap{
	position: absolute;
	left: 0;
	top: -80px;
	height: 80px;
	background-repeat: no-repeat;
}
.show-post .box-container .box .status{
	position: absolute;
	left: 1%;
	top: 1%;
	text-transform: uppercase;
	font-size: 1rem;
	margin-bottom: 1rem;
	padding: .5rem 1rem;
	border-radius: .5rem;
	display: inline-block;
	background-color: var(--white-alpha-40);
}
.show-post .box-container .box .price{
	width: 70px;
	height: 70px;
	line-height: 70px;
	text-align: center;
	border-radius: 50%;
	position: absolute;
	right: 5%;
	top: 5%;
	font-weight: bolder;
	background-color: var(--pink-color);
}
.show-post .box-container .box .content .title{
	font-size: 1.2rem;
	text-transform: uppercase;
	text-align: center;
	margin-bottom: 1rem;
}
.show-post .box-container .box .posts-content{
	font-size: 1.3rem;
	line-height: 1.5;
	padding: 1rem 0;
}
.show-post .box-container .box .btn{
	margin: .2rem;
	padding: .2rem 1.5rem;
	width: 50%;
	text-align: center;
}
.show-post .box-container .box button{
	width: 50%;
}
.post-editor img{
	width: 100%;
}
	</style>
</head>
<body>

	<div class="main-container">
				<?php include '../components/admin_header.php'; ?>
		<section class="show-post">
			<div class="heading">
				<h1 style="color: #fff;">Your Products</h1>
			</div>
			<div class="box-container">
				<?php
					$select_products = $conn->prepare("SELECT * FROM products WHERE seller_id = ?");
					 $select_products->execute([$seller_id]);
					 if ($select_products->rowCount() > 0) {
					 	 while ($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)) {
					 	 	
					 	 
				 ?>
				 <form action="" method="POST" class="box">
				 	<input type="hidden" name="product_id" value="<?= $fetch_products['id']; ?>">
				 	<?php if ($fetch_products['image'] != ''){ ?>
				 		<img src="../uploaded_files/<?= $fetch_products['image']; ?>" class="image">
				 	<?php } ?>
				 	<div class="status" style="color: <?php if ($fetch_products['status'] == 'active') { echo 'limegreen'; } else { echo 'red'; } ?>"><?= $fetch_products['status']; ?></div>
				 	<div class="price">Ksh <?= $fetch_products['price']; ?></div>
				 	<div class="content">
				 		<div class="title"><?= $fetch_products['name']; ?></div>
				 		<div class="btn flex-btn">
				 			<a href="edit_product.php?id=<?= $fetch_products['id']; ?>"  class="btn">Edit</a>
				 			<button type="submit" name="delete" class="btn" onclick="return confirm('delete this product');">Delete</button>
				 			<a href="read_product.php?post_id=<?= $fetch_products['id']; ?>" class="btn">Read</a>
				 		</div>
				 	</div>
				 </form>
				 <?php 
					 	}
					}else{
						echo '

							<div class="empty">
							<p>no products added yet! <br> <a href="add_products.php" class="btn">Add products</a></br></p>
							</div>

						';
					}
				 ?>
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