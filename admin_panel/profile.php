<?php 
	include '../components/connect.php';

 if (isset($_COOKIE['seller_id'])) {
 	$seller_id = $_COOKIE['seller_id'];
 }else{
 	$seller_id = '';
 	header('location:login.php');
 }

 $select_products = $conn->prepare("SELECT * FROM products WHERE seller_id = ?");
 $select_products->execute([$seller_id]);
 $total_products = $select_products->rowCount();

 $select_orders = $conn->prepare("SELECT * FROM products WHERE seller_id = ?");
 $select_orders->execute([$seller_id]);
 $total_orders = $select_orders->rowCount();


?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Nashon - seller Profile Page</title>
	<link rel="stylesheet" type="text/css" href="../css/admin_style.css">
	<!--Bootsrap link-->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

	<link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet">

	<style type="text/css">
/*--Profile section--*/
.seller-profile .details{
	background-color: var(--white-alpha-25);
	border: 2px solid var(--white-alpha-40);
	backdrop-filter: var(--backdrop-filter);
	box-shadow: var(--box-shadow);
	text-align: center;
	border-radius: .5rem;
	padding: 1rem;
}
.seller-profile .details .seller{
	margin-bottom: 2rem;
}
.seller-profile .details .seller img{
	width: 10rem;
	height: 10rem;
	border-radius: 50%;
	object-fit: cover;
	margin-bottom: .5rem;
	padding: .5rem;
	background-color: var(--main-color);
}
.seller-profile .details .seller h3{
	font-size: 1.5rem;
	margin: .5rem 0;
	text-transform: capitalize;
}
.seller-profile .details .seller span{
	font-size: 1.2rem;
	color: gray;
	display: block;
	margin-bottom: 2rem;
	text-transform: capitalize;
}
.seller-profile .details .flex{
	display: flex;
	gap: 1.5rem;
	align-items: center;
	flex-wrap: wrap;
	margin: 4rem 0;
}
.seller-profile .details .flex .box{
	flex: 1 1 26rem;
	border-radius: .5rem;
	background-color: #cccccc33;
	padding: 2rem;
}
.seller-profile .details .flex .box span{
	color: var(--main-color);
	display: block;
	margin-bottom: .5rem;
	font-size: 2.5rem;
	text-transform: capitalize;
}
.seller-profile .details .flex .box p{
	font-size: 2rem;
	color: #000;
	padding: .5rem 0;
	margin-bottom: 1rem;
}
	</style>
</head>
<body>

	<div class="main-container">
				<?php include '../components/admin_header.php'; ?>
		<section class="seller-profile">
			<div class="heading">
				<h1 style="color: #fff;">Profile Details</h1>
			</div>
			<div class="details">
				<div class="seller">
					<img src="../uploaded_files/<?= $fetch_profile['image']; ?>">
					<h3 class="name"><?= $fetch_profile['name']; ?></h3>
					<span>Seller</span>
					<a href="update.php" class="btn">Update profile</a>
				</div>
				<div class="flex">
					<div class="box">
						<span><?= $total_products; ?></span>
						<p>Total products</p>
						<a href="view_product.php" class="btn">View products</a>
					</div>
					<div class="box">
						<span><?= $total_orders; ?></span>
						<p>Total Orders placed</p>
						<a href="admin_order.php" class="btn">View orders</a>
					</div>
				</div>
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