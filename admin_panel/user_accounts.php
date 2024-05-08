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
	<title>Nashon - Registered Users page</title>
	<link rel="stylesheet" type="text/css" href="../css/admin_style.css">
	<!--Bootsrap link-->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

	<link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet">
</head>
<body>

	<div class="main-container">
				<?php include '../components/admin_header.php'; ?>
		<section class="user-container">
			<div class="heading">
				<h1 style="color: #fff;">Registered Users</h1>
			</div>
			<div class="box-container">
				<?php 
					$select_users = $conn->prepare("SELECT * FROM users");
					$select_users->execute();

					if ($select_users->rowCount() > 0) {
						while ($fetch_user = $select_users->fetch(PDO::FETCH_ASSOC)) {
							$user_id = $fetch_users['id'];
					
				?>
				<div class="box">
					<img src="../uploaded_files/<?= $fetch_users['image']; ?>">
					<p>User id : <span><?= $user_id; ?></span></p>
					<p>User name : <span><?= $fetch_users['name']; ?></span></p>
					<p>User email : <span><?= $fetch_users['email']; ?></span></p>
				</div>
				<?php 
						}
					}else{
						echo '<div class="empty">
									<p>No user registered yet!</p>
							  </div>';
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