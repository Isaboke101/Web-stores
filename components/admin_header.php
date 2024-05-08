<header>
	<style type="text/css">
:root{
	--space: 2rem;
	--main-color: #da6285;
	--pink-color: #C0B4B4;
	--pink-opacity: #ffe6e9;
	--hunter-green: #182515;
	--armadillo: #4d413d;
	--chalet-green: #547140;
	--white-alpha-40:#fff;
	--white-alpha-25: rgba(255, 255, 255, 0.25);
	--backdrop-filter: blur(5px);
	--box-shadow: 2px 2px 5px rgba(0,0,0,0.40);
}
		/*---header section---*/
header{
	position: fixed;
	left: 0;
	right: 0;
	top: 0;
	height: 80px;
	z-index: 151;
	box-shadow: 0px 5px 10px 0px #aaa;
	padding: 0 50px;
	background-color: #fff;
	justify-content: space-between;
	align-items: center;
}
header .right{
	display: flex;
}
body {
    margin: 0; /* Remove default body margin */
}

.btn.toggle-btn{
    position: fixed;
    top: 10px; /* Adjust top position as needed */
    right: 10px; /* Adjust right position as needed */
    cursor: pointer;
	font-size: 2rem;
	padding: .5rem;
	color: var(--pink-color);
	cursor: pointer;
	transition: .6s;
	margin-left: .5rem;
}
#user-btn,.toggle-btn{
	font-size: 2rem;
	padding: .5rem;
	color: var(--armadillo);
	cursor: pointer;
	transition: .6s;
}
.menu{
	margin-left: .5rem;
	top: 10px;
	right: 80px;
	padding: .5rem;
	position: fixed;
	transition: 0.6s;
	display: none;
}
.profile-detail{
	background-color: var(--white-alpha-25);
	border: 2px solid var(--white-alpha-40);
	backdrop-filter: var(--backdrop-filter);
	box-shadow: var(--box-shadow);
	position: absolute;
	top: 125%;
	right: 2rem;
	border-radius: .5rem;
	width: 22rem;
	padding: 1.5rem .5rem;
	animation: .2s linear fadein;
	text-align: center;
	overflow: hidden;
	display: none;
}
@keyframes fadeIn{
	0%{
		transform: translateY(1rem);
	}
}
.profile-detail.active{
	display: inline-block;
}
.profile-detail p{
	padding-bottom: .7rem;
	font-size: 1.5rem;
	text-transform: capitalize;
	color: white;
}
.profile-detail .flex-btn{
	display: flex;
	justify-content: space-evenly;
}
.profile-detail .flex-btn .btn{
	margin: 0 .5rem;
}
.main-container{
	display: flex;
}
.sidebar{
	background-color: var(--white-alpha-25);
	backdrop-filter: var(--backdrop-filter);
	padding-top: 3rem;
	width: 115%;
	height: 100vh;
	position: sticky;
	--offset:var(--space);
	top: var(--offset);
	box-shadow: 0px 5px 10px 0px #aaa;
	overflow: auto;
	z-index: 1;
}
.sidebar h5{
	text-transform: uppercase;
	color: var(--main-color);
	padding: .5rem 1rem;
	margin: .5rem;
}
.profile{
	margin: .5rem auto;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
}
.sidebar .profile{
	margin-top: 2.5rem;
}
.profile .logo-img{
	border-radius: 50%;
	padding: .2rem;
	border: 2px solid var(--main-color);
}
.sidebar .profile p{
	margin-top: .5rem;
	text-transform: uppercase;
	font-weight: bolder;
	color: #000;
	font-size: 1.3rem;
}
.sidebar ul li{
	padding: 1rem;
	background-color: var(--white-alpha-25);
	border: 2px solid var(--white-alpha-40);
	backdrop-filter: var(--backdrop-filter);
	box-shadow: var(--box-shadow);
	position: relative;
	transition: .5s;
	margin: .5rem 0;
}
.sidebar ul li::before{
	position: absolute;
	content: '';
	left: 0;
	top: 0;
	height: 0%;
	width: 5px;
	background-color: var(--main-color);
	z-index: 2;
	transition: all 200ms linear;
}
.sidebar ul li::hover::before{
	height: 100%;
}
.sidebar ul li i{
	color: var(--main-color);
	font-size: 20px;
	margin-right: 2rem;
}
.sidebar ul li a{
	text-transform: uppercase;
	color: gray;
	font-size: 12px;
	font-weight: bold;
}
.social-links{
	margin-bottom: 4rem;
}
.social-links i{
	background-color: var(--white-alpha-25);
	border: 2px solid var(--white-alpha-40);
	backdrop-filter: var(--backdrop-filter);
	box-shadow: var(--box-shadow);
	cursor: pointer;
	margin: .3rem;
	width: 40px;
	height: 40px;
	line-height: 40px;
	text-align: center;
	font-size: 20px;
	transition: .5s;
	border-radius: 50%;
}
.social-links i:hover{
	background-color: var(--pink-opacity);
	border: 2px solid var(--main-color);
}
/*---media screen---*/
@media screen and (max-width:991px){
	.toggle-btn{
		display: block;
	}
	.sidebar{
		padding-top: 4rem;
		position: fixed;
		width: 0;
		transition: .5s;
		z-index: 101;
		top: 7%;
	}
	.sidebar.active{
		width: 40%;
	}
	.sidebar .profile{
		margin-top: .5rem;
	}
	section{
		width: 100%;
	}
}
	</style>
	<div class="logo">
		<img src="../images/logo.svg" width="80">
	</div>
	<div class="right">
	    <div class="btn toggle-btn" id="user-btn">
	    	<i class="fa fa-user-circle" aria-hidden="true"></i>
	    </div>
	    <div class="toggle-btn menu"><i class="fa fa-bars" aria-hidden="true"></i></div>
	</div>
	<div class="profile-detail">
		<?php  
			$select_profile = $conn->prepare("SELECT * FROM sellers WHERE id = ?");
			$select_profile->execute([$seller_id]);

			if($select_profile->rowCount() > 0){
				$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
			

		?>
		<div class="profile">
			<img src="../uploaded_files/<?= $fetch_profile['image']; ?>" class="logo-img logo" width="100" >
			<p><?= $fetch_profile['name']; ?></p>
			<div class="flex-btn">
				<a href="profile.php" class="btn" style="margin: 0 .5rem;">profile</a>
				<a href="../components/admin_logout.php" onclick="return confirm('logout from this website?');" class="btn">logout</a>
			</div>
		</div>
		<?php } ?>
	</div>
</header>
<div class="sidebar-container" style="display: flex;">
	<div class="sidebar">
				<?php  
			$select_profile = $conn->prepare("SELECT * FROM sellers WHERE id = ?");
			$select_profile->execute([$seller_id]);

			if($select_profile->rowCount() > 0){
				$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
			

		?>
		<div class="profile">
			<img src="../uploaded_files/<?= $fetch_profile['image']; ?>" class="logo-img" width="100"><?= $fetch_profile['name']; ?></p>
		</div>
		<?php } ?>
		<h5>menu</h5>
		<div class="navbar">
			<ul>
				<li><a href="dashboard.php"><i class="fa fa-home" aria-hidden="true"></i>dashboard</a></li>
				<li><a href="add_products.php"><i class="fa fa-shopping-bag" aria-hidden="true"></i>add products</a></li>
				<li><a href="view_product.php"><i class="fa fa-bars" aria-hidden="true"></i>view products</a></li>
				<li><a href="user_accounts.php"><i class="fa fa-user-circle" aria-hidden="true"></i>accounts</a></li>
				<li><a href="../components/admin_logout.php" onclick="return confirm('logout from this website?');"><i class="fa fa-sign-out" aria-hidden="true"></i>logout</a></li>
			</ul>
		</div>
		<h5>find us</h5>
		<div class="social-links">
			<i class="lni lni-facebook" aria-hidden="true"></i>
			<i class="lni lni-instagram-fill" aria-hidden="true"></i>
			<i class="lni lni-whatsapp" aria-hidden="true"></i>
			<i class="lni lni-linkedin" aria-hidden="true"></i>
		</div>
	</div>
</div>