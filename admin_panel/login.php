<?php 
	include '../components/connect.php';

	if(isset($_POST['submit'])){



		$email = $_POST['email'];
		$email = filter_var($email, FILTER_SANITIZE_STRING);

		$pass = sha1($_POST['pass']);
		$pass = filter_var($pass, FILTER_SANITIZE_STRING);

		$select_seller = $conn->prepare("SELECT * FROM sellers WHERE email= ? AND password = ?");
		$select_seller->execute([$email, $pass]);
		$row = $select_seller->fetch(PDO::FETCH_ASSOC);

		if ($select_seller->rowCount() > 0) {
				setcookie('seller_id', $row['id'], time() + 60*60*24*30, '/');
				header('location:dashboard.php');
		}else{
			$warning_msg[] = 'incorrect email or password';
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

	<div class="container form-container">
		<form action="" method="POST" enctype="multipart/form-data" class="login">
			<h3>Login Now</h3>

			<div class="input-field">
				<p>your email<span>*</span></p>
				<input type="email" name="email" placeholder="enter your email" maxlength="50" required class="box">
			</div>

			<div class="input-field">
				<p>your password<span>*</span></p>
				<input type="password" name="pass" placeholder="enter your password" maxlength="50" required class="box">
			</div>
			<p>Do not have an account?<a href="register.php" class="link">Register now</a></p>
			<input type="submit" name="submit" value="login now" class="btn btn-outline-primary">
		</form>
	</div>








	<!-- bootstrap link -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

	<!--sweetalert cdn link-->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

	<!--Custom js link-->
	<script type="text/javascript" src="../js/script.js"></script>

	<?php include '../components/alert.php'; ?>
</body>
</html>