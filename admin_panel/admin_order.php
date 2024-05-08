<?php 
	include '../components/connect.php';

 if (isset($_COOKIE['seller_id'])) {
 	$seller_id = $_COOKIE['seller_id'];
 }else{
 	$seller_id = '';
 	header('location:login.php');
 }

//Update order from database

 if (isset($_POST['update_order'])) {
 	
 	$order_id = $_POST['order_id'];
 	$order_id = filter_var($order_id, FILTER_SANITIZE_STRING);

 	$update_payment = $_POST['update_payment'];
 	$update_payment = filter_var($update_payment, FILTER_SANITIZE_STRING);

 	$update_pay = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
 	$update_pay->execute([$update_payment, $order_id]);
 	$success_msg[] = 'order payment status updated';
 }

 //Delete order

 if (isset($_POST['delete_order'])) {
 	
 	$delete_id = $_POST['order_id'];
 	$delete_id = filter_var($delete_id, FILTER_SANITIZE_STRING);

 	$verify_delete = $conn->prepare("SELECT * FROM orders WHERE id = ?");
 	$verify_delete->execute([$delete_id]);

 	if ($verify_delete->rowCount() > 0) {
 		
 		$delete_order = $conn->prepare("DELETE FROM orders WHERE id = ?");
 		$delete_order->execute([$delete_id]);

 		$success_msg[] = 'order deleted';
 	}else{
 		$warning_msg[] = 'order already deleted';
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

	<div class="main-container">
				<?php include '../components/admin_header.php'; ?>
		<section class="order-container">
			<div class="heading">
				<h1 style="color: #fff;">Total Orders Placed</h1>
			</div>
			<div class="box-container">
				<?php 
					$select_order = $conn->prepare("SELECT * FROM orders WHERE seller_id = ?");
					$select_order->execute([$seller_id]);

					if ($select_order->rowCount() > 0) {
						while($fetch_order = $select_order->fetch(PDO::FETCH_ASSOC)){
					
				?>
				<div class="box">
					<div class="status" style="color: <?php if($fetch_order['status']== 'in progress'){echo "limegreen";}else{echo "red";} ?>"><?= $fetch_order['status']; ?></div>
					<div class="details">
						<p>user name: <span><?= $fetch_order['name']; ?></span></p>
						<p>user id: <span><?= $fetch_order['user_id']; ?></span></p>
						<p>Placed on: <span><?= $fetch_order['date']; ?></span></p>
						<p>User number: <span><?= $fetch_order['number']; ?></span></p>
						<p>User email: <span><?= $fetch_order['email']; ?></span></p>
						<p>Total price: <span><?= $fetch_order['price']; ?></span></p>
						<p>Payment method: <span><?= $fetch_order['method']; ?></span></p>
						<p>user address: <span><?= $fetch_order['address']; ?></span></p>
					</div>
					<form action="" method="post">
						<input type="hidden" name="order_id" value="<?= $fetch_order['id']; ?>">
						<select name="update_payment" class="box" style="width: 90%;">
							<option disabled selected><?= $fetch_order['payment_status']; ?></option>
							<option value="pending">Pending</option>
							<option value="order delivered">Order delivered</option>
						</select>
						<div class="flex-btn">
							<input type="submit" name="update_order" value="update payment" class="btn">
							<input type="submit" name="delete_ order" value="Delete order" class="btn" onclick="return confirm('delete this order');">
						</div>
					</form>
				</div>
				<?php 
						}
					}else{
						echo '
							<div class="empty">
								<p>No Order placed yet!</p>
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