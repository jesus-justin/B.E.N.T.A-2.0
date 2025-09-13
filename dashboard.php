<?php
require 'config.php';

// Redirect to login if not authenticated
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$uid = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get recent transactions and expenses
$recentTransactions = $pdo->prepare("
    SELECT t.*, c.name as category_name 
    FROM transactions t 
    LEFT JOIN categories c ON c.id = t.category_id 
    WHERE t.user_id = ? 
    ORDER BY t.trx_date DESC 
    LIMIT 5
");
$recentTransactions->execute([$uid]);
$transactions = $recentTransactions->fetchAll();

$recentExpenses = $pdo->prepare("
    SELECT e.*, c.name as category_name 
    FROM expenses e 
    LEFT JOIN categories c ON c.id = e.category_id 
    WHERE e.user_id = ? 
    ORDER BY e.expense_date DESC 
    LIMIT 5
");
$recentExpenses->execute([$uid]);
$expenses = $recentExpenses->fetchAll();

// Get totals for current month
$currentMonth = date('Y-m');
$totalIncome = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = ? AND DATE_FORMAT(trx_date, '%Y-%m') = ?");
$totalIncome->execute([$uid, $currentMonth]);
$monthlyIncome = $totalIncome->fetchColumn();

$totalExpense = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?");
$totalExpense->execute([$uid, $currentMonth]);
$monthlyExpense = $totalExpense->fetchColumn();

$netIncome = $monthlyIncome - $monthlyExpense;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BENTA</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="topbar">
        <div>
            <strong>BENTA</strong> - Welcome, <?= e($username) ?>!
        </div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="expenses.php">Expenses</a>
            <a href="reports.php">Reports</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>
    
    <main class="container">
        <div class="page-header">
            <h1>Dashboard</h1>
            <p>Welcome back! Here's your financial overview</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card income">
                <h3>Monthly Income</h3>
                <div class="amount">₱<?= number_format($monthlyIncome, 2) ?></div>
                <div class="stat-trend">+12% from last month</div>
            </div>
            <div class="stat-card expense">
                <h3>Monthly Expenses</h3>
                <div class="amount">₱<?= number_format($monthlyExpense, 2) ?></div>
                <div class="stat-trend">-5% from last month</div>
            </div>
            <div class="stat-card net">
                <h3>Net Income</h3>
                <div class="amount">₱<?= number_format($netIncome, 2) ?></div>
                <div class="stat-trend"><?= $netIncome >= 0 ? 'Positive' : 'Negative' ?> balance</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="add_transaction.php" class="quick-action">
                <span class="quick-action-icon">💰</span>
                <h3>Add Income</h3>
                <p>Record new income transaction</p>
            </a>
            <a href="add_expense.php" class="quick-action">
                <span class="quick-action-icon">💸</span>
                <h3>Add Expense</h3>
                <p>Record new expense</p>
            </a>
            <a href="expenses.php" class="quick-action">
                <span class="quick-action-icon">📊</span>
                <h3>View Expenses</h3>
                <p>Manage your expenses</p>
            </a>
            <a href="reports.php" class="quick-action">
                <span class="quick-action-icon">📈</span>
                <h3>Reports</h3>
                <p>View financial reports</p>
            </a>
        </div>
        
        <div class="content-grid">
            <div class="card">
                <div class="card-header">
                    <h2>Recent Transactions</h2>
                    <a href="add_transaction.php" class="btn btn-primary btn-sm">Add New</a>
                </div>
                <?php if (empty($transactions)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">💰</div>
                        <h3>No transactions yet</h3>
                        <p>Start tracking your income by adding your first transaction</p>
                        <a href="add_transaction.php" class="btn btn-primary">Add Transaction</a>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?= e($t['trx_date']) ?></td>
                                <td><?= e($t['category_name']) ?></td>
                                <td><?= e($t['description'] ?: 'No description') ?></td>
                                <td class="income">₱<?= number_format($t['amount'], 2) ?></td>
                                <td>
                                    <a href="edit_transaction.php?id=<?= e($t['id']) ?>" class="btn btn-sm btn-info">Edit</a>
                                    <a href="delete_transaction.php?id=<?= e($t['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this transaction?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Recent Expenses</h2>
                    <a href="add_expense.php" class="btn btn-primary btn-sm">Add New</a>
                </div>
                <?php if (empty($expenses)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">💸</div>
                        <h3>No expenses yet</h3>
                        <p>Start tracking your expenses by adding your first expense</p>
                        <a href="add_expense.php" class="btn btn-primary">Add Expense</a>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Vendor</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expenses as $e): ?>
                            <tr>
                                <td><?= e($e['expense_date']) ?></td>
                                <td><?= e($e['category_name']) ?></td>
                                <td><?= e($e['vendor'] ?: 'No vendor') ?></td>
                                <td class="expense">₱<?= number_format($e['amount'], 2) ?></td>
                                <td>
                                    <a href="edit_expense.php?id=<?= e($e['id']) ?>" class="btn btn-sm btn-info">Edit</a>
                                    <a href="delete_expense.php?id=<?= e($e['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this expense?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script src="assets/js/animations.js"></script>
</body>
</html>
