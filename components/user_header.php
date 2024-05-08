<header class="header">
	<section class="flex">
		<nav class="navbar navbar-expand-lg navbar-light bg-light">
			  <div class="container-fluid">
				    <a class="navbar-brand" href="#"><img src="images\logo.jpg" width="100px"></a>
				    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				      <span class="navbar-toggler-icon"></span>
				    </button>
				    <div class="collapse navbar-collapse" id="navbarSupportedContent">
				      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
				        <li class="nav-item">
				          <a class="nav-link active" aria-current="page" href="home.php">Home</a>
				        </li>
				        <li class="nav-item">
				          <a class="nav-link" href="about.php">About Us</a>
				        </li>
				        <li class="nav-item">
				          <a class="nav-link" href="menu.php">Shop</a>
				        </li>
				        <li class="nav-item">
				          <a class="nav-link" href="order.php">Order</a>
				        </li>
				        <li class="nav-item">
				          <a class="nav-link" href="contact.php">Contact Us</a>
				        </li>
				      </ul>
				      <form class="d-flex search-form form-inline my-2 my-lg-0" method="post" action="">
				        <input name="search_product" class="form-control me-2" type="text" placeholder="Search product" aria-label="Search" required maxlength="100">
				        <button class="lni lni-search-alt" id="search_product_btn" type="submit"></button>
				      </form>
				    </div>
				    <div class="icons">
							<div class="lni lni-list" id="menu-btn"></div>
							<div class="lni lni-search-alt" id="search-btn"></div>
							<a href="wishlist.php"><i class="lni lni-heart-fill"></i><sup>0</sup></a>
							<a href="cart.php"><i class="lni lni-cart"></i><sup>0</sup></a>
							<div class="fa fa-user-circle" aria-hidden="true" id="user-btn"></div>
					</div>
			  </div>
		</nav>
			<div class="profile-detail">
				<?php
					$select_profile = $conn->prepare("SELECT * FROM users WHERE id = ?");
					$select_profile->execute([$user_id]);

					if ($select_profile->rowCount() > 0) {
						$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
				 ?>
				 <img src="uploaded_files/<?= $fetch_profile['image']; ?>">
				 <h3 style="margin-bottom: 1rem;"><?= $fetch_profile['name']; ?></h3>
				 <div class="flex-btn">
				 	<a href="profile.php" class="btn">View profile</a>
				 	<a href="components.user_logout.php" onclick="return confirm('logout from this website');" class="btn">Logout</a>
				 </div>
				<?php }else{ ?>
					<h3 style="margin-bottom: 1rem;">Please Login or Register</h3>
					<div class="flex-btn">
						<a href="login.php" class="btn">Login</a>
						<a href="register.php" class="btn">Register</a>
					</div>
				<?php } ?>

		
			</div>
	</section>
</header>