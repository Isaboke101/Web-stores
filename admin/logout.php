<?php
session_name('injili_admin');
session_start();
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
