<?php
require '../config/config.php';
if (empty($_SESSION['user_id'])) header('Location: ../auth/login.php');
$uid = $_SESSION['user_id'];

// Require POST and valid CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header('Location: expenses.php');
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id > 0) {
    $del = $pdo->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
    $del->execute([$id, $uid]);
}
header('Location: expenses.php'); exit;
