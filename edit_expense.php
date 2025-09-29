<?php
require 'config.php';
if (empty($_SESSION['user_id'])) header('Location: login.php');
$uid = $_SESSION['user_id'];
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) header('Location: expenses.php');

$stmt = $pdo->prepare("SELECT * FROM expenses WHERE id=? AND user_id=?");
$stmt->execute([$id, $uid]);
$e = $stmt->fetch();
if (!$e) header('Location: expenses.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = intval($_POST['category'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $vendor = trim($_POST['vendor'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $date = $_POST['date'] ?? date('Y-m-d');

    if ($category <= 0) $errors[] = 'Choose category';
    if ($amount <= 0) $errors[] = 'Amount must be positive';

    if (empty($errors)) {
        $upd = $pdo->prepare("UPDATE expenses SET category_id=?, amount=?, vendor=?, note=?, expense_date=? WHERE id=? AND user_id=?");
        $upd->execute([$category, $amount, $vendor, $note, $date, $id, $uid]);
        header('Location: expenses.php'); exit;
    }
}

$cats = $pdo->query("SELECT * FROM categories WHERE type='expense' ORDER BY name")->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Edit Expense</title><link rel="stylesheet" href="assets/css/style.css"><link rel="stylesheet" href="assets/css/edit-expense.css"></head>
<body>
<main class="container">
  <h2>Edit Expense</h2>
  <?php if ($errors) foreach($errors as $er) echo "<div class='errors'>".e($er)."</div>"; ?>
  <form method="post">
    <label>Category
      <select name="category">
        <?php foreach($cats as $c): ?><option value="<?=e($c['id'])?>" <?= $c['id']==$e['category_id'] ? 'selected':''; ?>><?=e($c['name'])?></option><?php endforeach;?>
      </select>
    </label>
    <label>Amount <input type="number" step="0.01" name="amount" value="<?=e($e['amount'])?>" required></label>
    <label>Vendor <input type="text" name="vendor" value="<?=e($e['vendor'])?>"></label>
    <label>Date <input type="date" name="date" value="<?=e($e['expense_date'])?>"></label>
    <label>Note <input type="text" name="note" value="<?=e($e['note'])?>"></label>
    <button>Save</button> <a href="expenses.php">Cancel</a>
  </form>
</main>
</body></html>
