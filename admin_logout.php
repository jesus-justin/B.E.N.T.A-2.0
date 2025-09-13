<?php
require 'config.php';

// Destroy admin session
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_full_name']);
unset($_SESSION['admin_role']);

// Redirect to admin login
header('Location: admin_login.php');
exit;
?>
