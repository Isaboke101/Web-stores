<?php 
	include '../components/connect.php';

	 if (isset($_COOKIE['seller_id'])) {
	 	$seller_id = $_COOKIE['seller_id'];
	 }else{
	 	$seller_id = '';
	 	header('location:login.php');
	 }

	 $get_id = $_GET['post_id'];

	 //delete product

	 if (isset($_POST['delete'])) {
	 	$p_id = $_POST['product_id'];
	 	$p_id = filter_var($p_id, FILTER_SANITIZE_STRING);

	 	$delete_image = $conn->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
	 	$delete_image->execute([$p_id, $seller_id]);

	 	$fetch_delete_image = $delete_image->fetch(PDO::FETCH_ASSOC);
	 	if ($fetch_delete_image[''] != '') {
	 	 	unlink('../uploaded_files/'.fetch_delete_image['image']);
	 	 } 
	 	 $delete_product = $conn->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
	 	 $delete_product->execute([$p_id, $seller_id]);
	 	 header("location:view_product.php");
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

	.read-post{
		display: flex;
		flex-direction: column;
	}
	.read-post form{
		width: 100%;
		padding: 2rem;
		position: relative;
	}
	.read-post form .status{
		border-radius: .5rem;
		padding: .5rem 2rem;
		font-size: 1.1rem;
		display: inline-block;
		text-transform: uppercase;
	}
	.read-post form .price{
		position: absolute;
		top: 5%;
		right: 5%;
		font-weight: bolder;
		font-size: 2rem;
	}
	.read-post form .image{
		width: 100%;
		border-radius: .5rem;
		margin-top: 1.5rem;
	}

	.read-post form .title{
		font-size: 2.5rem;
		color: var(--main-color);
		margin-top: 1.5rem;
		text-transform: uppercase;
		text-align: center;
	}
	.read-post form .content{
		line-height: 2;
		font-size: 1.2rem;
		color: gray;
		padding: 1rem 0;
	}
	.read-post form .flex-btn{
		justify-content: space-between;
	}
	.read-post form .flex-btn .btn{
		width: 33%;
		text-align: center;
	}
	.read-post form .flex-btn a{
		height: 50px;
		margin-top: .8rem;
	}
	</style>
</head>
<body>

	<div class="main-container">
				<?php include '../components/admin_header.php'; ?>
		<section class="read-post">
			<div class="dashboard">
				<h1 style="color: #fff;">Product Details</h1>
			</div>
			<div class="box-container">
				<?php
					$select_product = $conn->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
					$select_product->execute([$get_id, $seller_id]);
					if($select_product->rowCount() > 0){
						while ($fetch_product = $select_product->fetch(PDO::FETCH_ASSOC)) {
							
						
				 ?>
				 <form action="" method="text" class="box">
				 	<input type="hidden" name="product_id" value="<?= $fetch_product['id']; ?>">
				 	<div class="status" style="color: <?php if ($fetch_product['status'] == 'active') {echo "limegreen";}else{echo "coral";} ?>"><?= $fetch_product['status']; ?></div>
				 	<?php if ($fetch_product['image'] != '') { ?>
				 		<img src="../uploaded_files/<?= $fetch_product['image']; ?>" class="image">
				 	<?php } ?>
				 	<div class="price">Ksh <?= $fetch_product['price']; ?></div>
				 	<div class="title"><?= $fetch_product['name']; ?></div>
				 	<div class="content"><?= $fetch_product['product_detail']; ?></div>
				 	<div class="flex-btn"></div>
				 		<a href="edit_product.php?id=<?= $fetch_product['id']; ?>" class="btn">Edit</a>
				 		<button type="submit" name="delete" class="btn" onclick="return confirm('delete this product');">Delete</button>
				 		<a href="view_product.php?post_id=<?= $fetch_product['id']; ?>" class="btn">Go Back</a>
				 </form>
				 <?php
				 		}
					}else{

						echo '

							<div class="empty">
							<p>no products added yet! <br> <a href="add_products.php" class="btn" style="margin-top: 1.5rem;">Add products</a></br></p>
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