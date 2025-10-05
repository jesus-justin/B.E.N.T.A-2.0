<?php
require 'config/config.php';

// Redirect based on login status
if (!empty($_SESSION['user_id'])) {
    header('Location: user/dashboard.php');
} else {
    header('Location: auth/login.php');
}
exit;
?>
