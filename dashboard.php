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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        .topbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .topbar a {
            color: white;
            text-decoration: none;
            margin-right: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .topbar a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        h1 {
            color: #333;
            margin-bottom: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #666;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .stat-card .amount {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .income { color: #27ae60; }
        .expense { color: #e74c3c; }
        .net { color: #3498db; }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            margin-bottom: 1rem;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 0.5rem 0.5rem 0.5rem 0;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .topbar {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
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
        <h1>Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Monthly Income</h3>
                <div class="amount income">₱<?= number_format($monthlyIncome, 2) ?></div>
            </div>
            <div class="stat-card">
                <h3>Monthly Expenses</h3>
                <div class="amount expense">₱<?= number_format($monthlyExpense, 2) ?></div>
            </div>
            <div class="stat-card">
                <h3>Net Income</h3>
                <div class="amount net">₱<?= number_format($netIncome, 2) ?></div>
            </div>
        </div>
        
        <div class="content-grid">
            <div class="card">
                <h2>Recent Transactions</h2>
                <?php if (empty($transactions)): ?>
                    <p>No transactions yet. <a href="add_transaction.php">Add your first transaction</a></p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?= e($t['trx_date']) ?></td>
                                <td><?= e($t['category_name']) ?></td>
                                <td class="income">₱<?= number_format($t['amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>Recent Expenses</h2>
                <?php if (empty($expenses)): ?>
                    <p>No expenses yet. <a href="add_expense.php">Add your first expense</a></p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expenses as $e): ?>
                            <tr>
                                <td><?= e($e['expense_date']) ?></td>
                                <td><?= e($e['category_name']) ?></td>
                                <td class="expense">₱<?= number_format($e['amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="margin-top: 2rem;">
            <a href="add_expense.php" class="btn">Add Expense</a>
            <a href="expenses.php" class="btn btn-secondary">View All Expenses</a>
            <a href="reports.php" class="btn btn-secondary">View Reports</a>
        </div>
    </main>
</body>
</html>
