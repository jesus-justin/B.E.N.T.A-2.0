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

// Get last month data for comparison
$lastMonth = date('Y-m', strtotime('-1 month'));
$lastMonthIncome = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = ? AND DATE_FORMAT(trx_date, '%Y-%m') = ?");
$lastMonthIncome->execute([$uid, $lastMonth]);
$lastMonthIncomeValue = $lastMonthIncome->fetchColumn();

$lastMonthExpense = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?");
$lastMonthExpense->execute([$uid, $lastMonth]);
$lastMonthExpenseValue = $lastMonthExpense->fetchColumn();

// Calculate percentage changes
$incomeChange = $lastMonthIncomeValue > 0 ? (($monthlyIncome - $lastMonthIncomeValue) / $lastMonthIncomeValue) * 100 : 0;
$expenseChange = $lastMonthExpenseValue > 0 ? (($monthlyExpense - $lastMonthExpenseValue) / $lastMonthExpenseValue) * 100 : 0;

// Get top expense categories
$topExpenseCategories = $pdo->prepare("
    SELECT c.name, SUM(e.amount) as total, COUNT(e.id) as count
    FROM expenses e 
    JOIN categories c ON c.id = e.category_id 
    WHERE e.user_id = ? AND DATE_FORMAT(e.expense_date, '%Y-%m') = ?
    GROUP BY c.id, c.name 
    ORDER BY total DESC 
    LIMIT 5
");
$topExpenseCategories->execute([$uid, $currentMonth]);
$expenseCategories = $topExpenseCategories->fetchAll();

// Get total transactions and expenses count
$totalTransactionsCount = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
$totalTransactionsCount->execute([$uid]);
$transactionsCount = $totalTransactionsCount->fetchColumn();

$totalExpensesCount = $pdo->prepare("SELECT COUNT(*) FROM expenses WHERE user_id = ?");
$totalExpensesCount->execute([$uid]);
$expensesCount = $totalExpensesCount->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BENTA</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
</head>
<body>
    <header class="topbar">
        <div class="topbar-brand">
            <strong>BENTA</strong> - Welcome, <?= e($username) ?>!
        </div>
        <nav class="topbar-nav">
            <a href="dashboard.php" class="nav-link active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="expenses.php" class="nav-link">
                <i class="fas fa-receipt"></i>
                <span>Expenses</span>
            </a>
            <a href="reports.php" class="nav-link">
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
            <h1>Dashboard</h1>
            <p>Welcome back! Here's your financial overview</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card income">
                <div class="stat-icon">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="stat-content">
                    <h3>Monthly Income</h3>
                    <div class="amount">₱<?= number_format($monthlyIncome, 2) ?></div>
                    <div class="stat-trend <?= $incomeChange >= 0 ? 'positive' : 'negative' ?>">
                        <i class="fas fa-<?= $incomeChange >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                        <?= abs(round($incomeChange, 1)) ?>% from last month
                    </div>
                </div>
            </div>
            <div class="stat-card expense">
                <div class="stat-icon">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="stat-content">
                    <h3>Monthly Expenses</h3>
                    <div class="amount">₱<?= number_format($monthlyExpense, 2) ?></div>
                    <div class="stat-trend <?= $expenseChange <= 0 ? 'positive' : 'negative' ?>">
                        <i class="fas fa-<?= $expenseChange <= 0 ? 'arrow-down' : 'arrow-up' ?>"></i>
                        <?= abs(round($expenseChange, 1)) ?>% from last month
                    </div>
                </div>
            </div>
            <div class="stat-card net">
                <div class="stat-icon">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <div class="stat-content">
                    <h3>Net Income</h3>
                    <div class="amount">₱<?= number_format($netIncome, 2) ?></div>
                    <div class="stat-trend <?= $netIncome >= 0 ? 'positive' : 'negative' ?>">
                        <i class="fas fa-<?= $netIncome >= 0 ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                        <?= $netIncome >= 0 ? 'Positive' : 'Negative' ?> balance
                    </div>
                </div>
            </div>
            <div class="stat-card transactions">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Records</h3>
                    <div class="amount"><?= $transactionsCount + $expensesCount ?></div>
                    <div class="stat-trend">
                        <i class="fas fa-list"></i>
                        <?= $transactionsCount ?> transactions, <?= $expensesCount ?> expenses
                    </div>
                </div>
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

        <!-- Charts and Analytics Section -->
        <?php if (!empty($expenseCategories)): ?>
        <div class="analytics-section">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-pie"></i> Expense Categories This Month</h2>
                </div>
                <div class="chart-container">
                    <canvas id="expenseChart" width="400" height="200"></canvas>
                </div>
                <div class="category-breakdown">
                    <?php foreach ($expenseCategories as $category): ?>
                    <div class="category-item">
                        <div class="category-info">
                            <span class="category-name"><?= e($category['name']) ?></span>
                            <span class="category-count"><?= $category['count'] ?> transactions</span>
                        </div>
                        <div class="category-amount">₱<?= number_format($category['total'], 2) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
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
    <script src="assets/js/dark-mode.js"></script>
    <script>
        // Enhanced dashboard functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize expense chart if data exists
            <?php if (!empty($expenseCategories)): ?>
            const ctx = document.getElementById('expenseChart').getContext('2d');
            const expenseData = <?= json_encode($expenseCategories) ?>;
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: expenseData.map(item => item.name),
                    datasets: [{
                        data: expenseData.map(item => parseFloat(item.total)),
                        backgroundColor: [
                            '#667eea',
                            '#764ba2',
                            '#f093fb',
                            '#f5576c',
                            '#4facfe',
                            '#00f2fe',
                            '#43e97b',
                            '#38f9d7'
                        ],
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return context.label + ': ₱' + value.toLocaleString() + ' (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        duration: 2000
                    }
                }
            });
            <?php endif; ?>

            // Add interactive animations to stat cards
            document.querySelectorAll('.stat-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px) scale(1.02)';
                    this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Add loading animation to quick actions
            document.querySelectorAll('.quick-action').forEach(action => {
                action.addEventListener('click', function(e) {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                    this.style.pointerEvents = 'none';
                    
                    // Reset after 2 seconds if no navigation occurs
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.pointerEvents = 'auto';
                    }, 2000);
                });
            });

            // Add real-time clock
            function updateClock() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
                const dateString = now.toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                
                // Update page title with time
                document.title = `Dashboard - BENTA (${timeString})`;
            }

            // Update clock every second
            setInterval(updateClock, 1000);
            updateClock();

            // Add smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + 1: Add Income
                if ((e.ctrlKey || e.metaKey) && e.key === '1') {
                    e.preventDefault();
                    window.location.href = 'add_transaction.php';
                }
                // Ctrl/Cmd + 2: Add Expense
                if ((e.ctrlKey || e.metaKey) && e.key === '2') {
                    e.preventDefault();
                    window.location.href = 'add_expense.php';
                }
                // Ctrl/Cmd + 3: View Reports
                if ((e.ctrlKey || e.metaKey) && e.key === '3') {
                    e.preventDefault();
                    window.location.href = 'reports.php';
                }
            });

            // Add notification for new features
            if (!localStorage.getItem('benta_dashboard_tour')) {
                setTimeout(() => {
                    showNotification('Welcome to your enhanced dashboard! Use Ctrl+1,2,3 for quick actions.', 'info');
                }, 2000);
            }
        });

        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-${type === 'info' ? 'info-circle' : type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                    <span>${message}</span>
                    <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        // Mark tour as completed
        function completeTour() {
            localStorage.setItem('benta_dashboard_tour', 'completed');
        }
    </script>


</body>
</html>
