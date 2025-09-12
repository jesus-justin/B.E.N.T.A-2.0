<?php
require 'config.php';
if (empty($_SESSION['user_id'])) header('Location: login.php');

$uid = $_SESSION['user_id'];
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: transactions.php'); exit; }

// load transaction
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
stmt->execute([$id, $uid]);
$trx = $stmt->fetch();
if (!$trx) { header('Location: transactions.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = intval($_POST['category'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $note = trim($_POST['note'] ?? '');
    $date = $_POST['date'] ?? date('Y-m-d');

    if ($category <= 0) $errors[] = 'Choose a category.';
    if ($amount <= 0) $errors[] = 'Amount must be positive.';

    if (empty($errors)) {
        $upd = $pdo->prepare("UPDATE transactions SET category_id=?, amount=?, note=?, trx_date=? WHERE id=? AND user_id=?");
        $upd->execute([$category, $amount, $note, $date, $id, $uid]);
        header('Location: transactions.php'); exit;
    }
}

// categories
$cats = $pdo->query("SELECT id,name FROM categories WHERE type='income' ORDER BY name")->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Edit Transaction</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
  <main class="container">
    <h2>Edit Transaction</h2>
    <?php if ($errors): foreach($errors as $er) echo "<div class='errors'>".e($er)."</div>"; endforeach;?>
    <form method="post">
      <label>Category
        <select name="category">
          <option value="">--choose--</option>
          <?php foreach($cats as $c): ?>
            <option value="<?=e($c['id'])?>" <?= $c['id']==$trx['category_id'] ? 'selected':'';?>><?=e($c['name'])?></option>
          <?php endforeach;?>
        </select>
      </label>
      <label>Amount <input type="number" step="0.01" name="amount" value="<?=e($trx['amount'])?>" required></label>
      <label>Date <input type="date" name="date" value="<?=e($trx['trx_date'])?>"></label>
      <label>Note <input type="text" name="note" value="<?=e($trx['note'])?>"></label>
      <button>Save</button>
      <a href="transactions.php">Cancel</a>
    </form>
  </main>
</body></html>
