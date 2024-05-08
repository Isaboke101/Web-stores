<?php 
	include '../components/connect.php';

 if (isset($_COOKIE['seller_id'])) {
 	$seller_id = $_COOKIE['seller_id'];
 }else{
 	$seller_id = '';
 	header('location:login.php');
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

	<div class="main-container">
				<?php include '../components/admin_header.php'; ?>
		<section class="dashboard">
			<div class="dashboard">
				<h1 style="color: #fff;">dashboard</h1>
			</div>
			<div class="box-container">
				<div class="box">
					<h3>welcome</h3>
					<p><?= $fetch_profile['name']; ?></p>
					<a href="update.php" class="btn">Update profile</a>
				</div>
				<div class="box">
					<?php 
						$select_message = $conn->prepare("SELECT * FROM message");
						$select_message->execute();
						$number_of_msg = $select_message->rowCount();
					?>
					<h3><?= $number_of_msg; ?></h3>
					<p>unread message</p>
					<a href="admin_message.php" class="btn">See message</a>
				</div>
				<div class="box">
					<?php 
						$select_products = $conn->prepare("SELECT * FROM products WHERE seller_id = ?");
						$select_products->execute([$seller_id]);
						$number_of_products = $select_products->rowCount();
					?>
					<h3><?= $number_of_products; ?></h3>
					<p>products added</p>
					<a href="add_products.php" class="btn">Add product</a>
				</div>
				<div class="box">
					<?php 
						$select_active_products = $conn->prepare("SELECT * FROM products WHERE seller_id = ? AND status = ?");
						$select_active_products->execute([$seller_id, 'active']);
						$number_of_active_products = $select_active_products->rowCount();
					?>
					<h3><?= $number_of_active_products; ?></h3>
					<p>Total active products</p>
					<a href="view_product.php" class="btn">View active product</a>
				</div>
                <div class="box">
					<?php 
						$select_deactive_products = $conn->prepare("SELECT * FROM products WHERE seller_id = ? AND status = ?");
						$select_deactive_products->execute([$seller_id, 'deactive']);
						$number_of_deactive_products = $select_deactive_products->rowCount();
					?>
					<h3><?= $number_of_deactive_products; ?></h3>
					<p>Total deactive products</p>
					<a href="view_product.php" class="btn">View deactive product</a>
				</div>
				<div class="box">
					<?php 
						$select_users = $conn->prepare("SELECT * FROM users");
						$select_users->execute();
						$number_of_users = $select_users->rowCount();
					?>
					<h3><?= $number_of_users; ?></h3>
					<p>users account</p>
					<a href="user_accounts.php" class="btn">See Users</a>
				</div>
				<div class="box">
					<?php 
						$select_sellers = $conn->prepare("SELECT * FROM sellers");
						$select_sellers->execute();
						$number_of_sellers = $select_sellers->rowCount();
					?>
					<h3><?= $number_of_sellers; ?></h3>
					<p>Sellers account</p>
					<a href="user_accounts.php" class="btn">See sellers</a>
				</div>
				<div class="box">
					<?php 
						$select_orders = $conn->prepare("SELECT * FROM orders WHERE seller_id = ?");
						$select_orders->execute([$seller_id]);
						$number_of_orders = $select_orders->rowCount();
					?>
					<h3><?= $number_of_orders; ?></h3>
					<p>Total Orders Placed</p>
					<a href="admin_order.php" class="btn">Total Orders</a>
				</div>
				<div class="box">
					<?php 
						$select_confirm_orders = $conn->prepare("SELECT * FROM orders WHERE seller_id = ? AND status = ?");
						$select_confirm_orders->execute([$seller_id, 'in progress']);
						$number_of_confirm_orders = $select_confirm_orders->rowCount();
					?>
					<h3><?= $number_of_confirm_orders; ?></h3>
					<p>Total Confirmed Orders</p>
					<a href="admin_order.php" class="btn">Confirm Orders</a>
				</div>
				<div class="box">
					<?php 
						$select_canceled_orders = $conn->prepare("SELECT * FROM orders WHERE seller_id = ? AND status = ?");
						$select_canceled_orders->execute([$seller_id, 'canceled']);
						$number_of_canceled_orders = $select_canceled_orders->rowCount();
					?>
					<h3><?= $number_of_canceled_orders; ?></h3>
					<p>Canceled Orders</p>
					<a href="admin_order.php" class="btn">Canceled Orders</a>
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