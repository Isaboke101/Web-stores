<?php 
	include '../components/connect.php';

	if(isset($_POST['submit'])){

		$id = unique_id();
		$name = $_POST['name'];
		$name = filter_var($name, FILTER_SANITIZE_STRING);

		$email = $_POST['email'];
		$email = filter_var($email, FILTER_SANITIZE_STRING);

		$pass = sha1($_POST['pass']);
		$pass = filter_var($pass, FILTER_SANITIZE_STRING);

		$cpass = sha1($_POST['cpass']);
		$cpass = filter_var($cpass, FILTER_SANITIZE_STRING);

		$image = $_FILES['image']['name'];
		$image = filter_var($image, FILTER_SANITIZE_STRING);
		$ext = pathinfo($image, PATHINFO_EXTENSION);
		$rename = unique_id().'.'.$ext;
		$image_size = $_FILES['image']['size'];
		$image_tmp_name = $_FILES['image']['tmp_name'];
		$image_folder = '../uploaded_files/'.$rename;

		$select_seller = $conn->prepare("SELECT * FROM sellers WHERE email= ? ");
		$select_seller->execute([$email]);

		if ($select_seller->rowCount() > 0) {
			$warning_msg[] = 'email already exists!';
		}else{
			if ($pass != $cpass) {
				$warning_msg[] = 'confirm password not matched';
			}else{
				$insert_seller = $conn->prepare("INSERT INTO sellers(id,name, email, password, image) VALUES(?,?,?,?,?)");
				$insert_seller->execute([$id, $name, $email, $cpass, $rename]);
				move_uploaded_file($image_tmp_name, $image_folder);
				$success_msg[] = 'new seller registered! Please go loging now';
			}
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
		<form action="" method="POST" enctype="multipart/form-data" class="register">
			<h3>register now</h3>
			<div class="flex">
				<div class="col">
					<div class="input-field">
						<p>your name<span>*</span></p>
						<input type="text" name="name" placeholder="enter your name" maxlength="50" required class="box">
					</div>
					<div class="input-field">
						<p>your email<span>*</span></p>
						<input type="email" name="email" placeholder="enter your email" maxlength="50" required class="box">
					</div>
					<div class="input-field">
						<p>your password<span>*</span></p>
						<input type="password" name="pass" placeholder="enter your password" maxlength="50" required class="box">
					</div>
					<div class="input-field">
						<p>confirm password<span>*</span></p>
						<input type="password" name="cpass" placeholder="confirm your password" maxlength="50" required class="box">
					</div>
				</div>

				<div class="input-field">
					<p>your profile<span>*</span></p>
					<input type="file" name="image" accept="image/*" maxlength="50" required class="box">
				</div>
				<p>I already have an account?<a href="login.php" class="link">Login now</a></p>
				<input type="submit" name="submit" value="register now" class="btn btn-outline-primary">
				</div>
			</div>
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