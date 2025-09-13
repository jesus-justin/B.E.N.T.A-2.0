<?php
require 'config.php';

// Destroy session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>
