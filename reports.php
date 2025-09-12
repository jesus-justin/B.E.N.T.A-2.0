<?php
require 'config.php';
if (empty($_SESSION['user_id'])) header('Location: login.php');
$uid = $_SESSION['user_id'];

// date range defaults (last 6 months)
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-5 months'));
$to   = $_GET['to']   ?? date('Y-m-d');

// totals
$totalIncome = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id = ? AND trx_date BETWEEN ? AND ?");
$totalIncome->execute([$uid, $from, $to]); $totalIncome = $totalIncome->fetchColumn();

$totalExpense = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id = ? AND expense_date BETWEEN ? AND ?");
$totalExpense->execute([$uid, $from, $to]); $totalExpense = $totalExpense->fetchColumn();

$net = $totalIncome - $totalExpense;

// monthly breakdown (income)
$incomeMonthly = $pdo->prepare("
  SELECT DATE_FORMAT(trx_date, '%Y-%m') month, COALESCE(SUM(amount),0) total
  FROM transactions
  WHERE user_id = ? AND trx_date BETWEEN ? AND ?
  GROUP BY month
  ORDER BY month
");
$incomeMonthly->execute([$uid, $from, $to]);
$incomeRows = $incomeMonthly->fetchAll();

// monthly breakdown (expense)
$expenseMonthly = $pdo->prepare("
  SELECT DATE_FORMAT(expense_date, '%Y-%m') month, COALESCE(SUM(amount),0) total
  FROM expenses
  WHERE user_id = ? AND expense_date BETWEEN ? AND ?
  GROUP BY month
  ORDER BY month
");
$expenseMonthly->execute([$uid, $from, $to]);
$expenseRows = $expenseMonthly->fetchAll();

// merge months for chart labels
$months = [];
foreach ($incomeRows as $r) $months[$r['month']] = ['income'=>floatval($r['total']), 'expense'=>0];
foreach ($expenseRows as $r) {
    if (!isset($months[$r['month']])) $months[$r['month']] = ['income'=>0, 'expense'=>floatval($r['total'])];
    else $months[$r['month']]['expense'] = floatval($r['total']);
}
ksort($months);
$labels = array_keys($months);
$incomeData = array_map(fn($m)=>$m['income'],$months);
$expenseData = array_map(fn($m)=>$m['expense'],$months);
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Reports</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<header class="topbar"><a href="dashboard.php">Back</a> | <a href="export_csv.php?type=transactions&from=<?=e($from)?>&to=<?=e($to)?>">Export Transactions CSV</a></header>
<main class="container">
  <h2>Reports</h2>
  <form method="get" style="margin-bottom:12px;">
    From <input type="date" name="from" value="<?=e($from)?>"> To <input type="date" name="to" value="<?=e($to)?>">
    <button>Refresh</button>
  </form>

  <div class="card">Total Income<br><strong><?=e(number_format($totalIncome,2))?></strong></div>
  <div class="card">Total Expense<br><strong><?=e(number_format($totalExpense,2))?></strong></div>
  <div class="card">Net<br><strong><?=e(number_format($net,2))?></strong></div>

  <section style="margin-top:18px;">
    <canvas id="reportChart" width="800" height="300"></canvas>
  </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const labels = <?= json_encode(array_values($labels)) ?>;
  const income = <?= json_encode(array_values($incomeData)) ?>;
  const expense = <?= json_encode(array_values($expenseData)) ?>;
  const ctx = document.getElementById('reportChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        { label: 'Income', data: income },
        { label: 'Expense', data: expense }
      ]
    },
    options: { responsive: true }
  });
</script>
</body></html>
