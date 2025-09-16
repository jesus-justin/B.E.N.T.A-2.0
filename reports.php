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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - BENTA</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-brand">
            <strong>BENTA</strong> - Reports
        </div>
        <nav class="topbar-nav">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="expenses.php" class="nav-link">
                <i class="fas fa-receipt"></i>
                <span>Expenses</span>
            </a>
            <a href="reports.php" class="nav-link active">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <a href="logout.php" class="nav-link logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </header>
    
    <main class="container">
        <div class="page-header">
            <h1>Financial Reports</h1>
            <p>Analyze your income and expenses over time</p>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Date Range</h2>
                <a href="export_csv.php?type=transactions&from=<?= e($from) ?>&to=<?= e($to) ?>" class="btn btn-primary btn-sm">Export CSV</a>
            </div>
            
            <form method="get" class="form-inline">
                <div class="form-group">
                    <label for="from">From Date</label>
                    <input type="date" name="from" id="from" value="<?= e($from) ?>" required>
                </div>
                <div class="form-group">
                    <label for="to">To Date</label>
                    <input type="date" name="to" id="to" value="<?= e($to) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Update Report</button>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card income">
                <h3>Total Income</h3>
                <div class="amount">₱<?= number_format($totalIncome, 2) ?></div>
                <div class="stat-trend">Period: <?= date('M d', strtotime($from)) ?> - <?= date('M d, Y', strtotime($to)) ?></div>
            </div>
            <div class="stat-card expense">
                <h3>Total Expenses</h3>
                <div class="amount">₱<?= number_format($totalExpense, 2) ?></div>
                <div class="stat-trend">Average: ₱<?= number_format($totalExpense / max(1, (strtotime($to) - strtotime($from)) / (60*60*24)), 2) ?>/day</div>
            </div>
            <div class="stat-card net">
                <h3>Net Income</h3>
                <div class="amount">₱<?= number_format($net, 2) ?></div>
                <div class="stat-trend"><?= $net >= 0 ? 'Positive' : 'Negative' ?> balance</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Monthly Breakdown</h2>
                <span class="chart-info">Income vs Expenses</span>
            </div>
            <div class="chart-container">
                <canvas id="reportChart" width="800" height="300"></canvas>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/animations.js"></script>
    <script>
        // Enhanced Chart with animations
        const labels = <?= json_encode(array_values($labels)) ?>;
        const income = <?= json_encode(array_values($incomeData)) ?>;
        const expense = <?= json_encode(array_values($expenseData)) ?>;
        
        const ctx = document.getElementById('reportChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { 
                        label: 'Income', 
                        data: income,
                        backgroundColor: 'rgba(39, 174, 96, 0.8)',
                        borderColor: 'rgba(39, 174, 96, 1)',
                        borderWidth: 2,
                        borderRadius: 5,
                        borderSkipped: false,
                    },
                    { 
                        label: 'Expenses', 
                        data: expense,
                        backgroundColor: 'rgba(231, 76, 60, 0.8)',
                        borderColor: 'rgba(231, 76, 60, 1)',
                        borderWidth: 2,
                        borderRadius: 5,
                        borderSkipped: false,
                    }
                ]
            },
            options: { 
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 14,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ₱' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            },
                            font: {
                                size: 12
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    </script>
</body>
</html>
