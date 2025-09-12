<?php
require 'config.php';
if (empty($_SESSION['user_id'])) header('Location: login.php');
$uid = $_SESSION['user_id'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = intval($_POST['category'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $vendor = trim($_POST['vendor'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $date = $_POST['date'] ?? date('Y-m-d');

    if ($category <= 0) $errors[] = 'Choose a category.';
    if ($amount <= 0) $errors[] = 'Amount must be positive.';

    if (empty($errors)) {
        $ins = $pdo->prepare("INSERT INTO expenses (user_id, category_id, amount, vendor, note, expense_date) VALUES (?, ?, ?, ?, ?, ?)");
        $ins->execute([$uid, $category, $amount, $vendor, $note, $date]);
        header('Location: expenses.php'); exit;
    }
}

$cats = $pdo->query("SELECT * FROM categories WHERE type='expense' ORDER BY name")->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Add Expense</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<main class="container">
  <h2>Add Expense</h2>
  <?php if ($errors) foreach($errors as $er) echo "<div class='errors'>".e($er)."</div>"; ?>
  <form method="post">
    <label>Category
      <select name="category">
        <option value="">-- choose --</option>
        <?php foreach($cats as $c): ?><option value="<?=e($c['id'])?>"><?=e($c['name'])?></option><?php endforeach;?>
      </select>
    </label>
    <label>Amount <input type="number" step="0.01" name="amount" required></label>
    <label>Vendor <input type="text" name="vendor"></label>
    <label>Date <input type="date" name="date" value="<?=date('Y-m-d')?>"></label>
    <label>Note <input type="text" name="note"></label>
    <button>Save</button> <a href="expenses.php">Cancel</a>
  </form>
</main>
</body></html>
