<?php
require 'config.php';
if (empty($_SESSION['user_id'])) header('Location: login.php');
$uid = $_SESSION['user_id'];
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $del = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $del->execute([$id, $uid]);
}
header('Location: transactions.php');
exit;
