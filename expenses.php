<?php
require 'config.php';
if (empty($_SESSION['user_id'])) header('Location: login.php');
$uid = $_SESSION['user_id'];

// optional filter by month
$where = "WHERE e.user_id = ?";
$params = [$uid];
if (!empty($_GET['q'])) {
    $q = "%".trim($_GET['q'])."%";
    $where .= " AND (e.vendor LIKE ? OR e.note LIKE ?)";
    $params[] = $q; $params[] = $q;
}

$stmt = $pdo->prepare("SELECT e.*, c.name category FROM expenses e LEFT JOIN categories c ON c.id=e.category_id $where ORDER BY expense_date DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Expenses</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<header class="topbar"><a href="dashboard.php">Back</a> | <a href="add_expense.php">Add Expense</a></header>
<main class="container">
  <h2>Expenses</h2>
  <form method="get" style="margin-bottom:12px;">
    <input name="q" placeholder="Search vendor or note" value="<?=e($_GET['q'] ?? '')?>">
    <button>Search</button>
  </form>
  <table class="data-table">
    <thead><tr><th>Date</th><th>Category</th><th>Vendor</th><th>Amount</th><th>Note</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
      <tr>
        <td><?=e($r['expense_date'])?></td>
        <td><?=e($r['category'])?></td>
        <td><?=e($r['vendor'])?></td>
        <td><?=e(number_format($r['amount'],2))?></td>
        <td><?=e($r['note'])?></td>
        <td>
          <a href="edit_expense.php?id=<?=e($r['id'])?>">Edit</a> |
          <a href="delete_expense.php?id=<?=e($r['id'])?>" onclick="return confirm('Delete this expense?')">Delete</a>
        </td>
      </tr>
      <?php endforeach;?>
    </tbody>
  </table>
</main>
</body></html>
