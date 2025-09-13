<?php
require 'config.php';

// Check if admin is logged in
if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Get date range
$from = $_GET['from'] ?? date('Y-m-01'); // First day of current month
$to = $_GET['to'] ?? date('Y-m-d');

// System statistics
$systemStats = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM users WHERE is_active = 1) as active_users,
        (SELECT COUNT(*) FROM transactions) as total_transactions,
        (SELECT COUNT(*) FROM expenses) as total_expenses,
        (SELECT COALESCE(SUM(amount), 0) FROM transactions) as total_income,
        (SELECT COALESCE(SUM(amount), 0) FROM expenses) as total_expenses_amount
")->fetch();

// Monthly breakdown
$monthlyData = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as new_users
    FROM users 
    WHERE created_at >= '$from' AND created_at <= '$to'
    GROUP BY month
    ORDER BY month
")->fetchAll();

// Top users by activity
$topUsers = $pdo->query("
    SELECT 
        u.username,
        u.email,
        COUNT(DISTINCT t.id) as transaction_count,
        COUNT(DISTINCT e.id) as expense_count,
        COALESCE(SUM(t.amount), 0) as total_income,
        COALESCE(SUM(e.amount), 0) as total_expenses
    FROM users u
    LEFT JOIN transactions t ON u.id = t.user_id
    LEFT JOIN expenses e ON u.id = e.user_id
    WHERE u.created_at >= '$from' AND u.created_at <= '$to'
    GROUP BY u.id
    ORDER BY (transaction_count + expense_count) DESC
    LIMIT 10
")->fetchAll();

// Category usage
$categoryUsage = $pdo->query("
    SELECT 
        c.name,
        c.type,
        COUNT(DISTINCT CASE WHEN c.type = 'income' THEN t.id ELSE e.id END) as usage_count,
        COALESCE(SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE e.amount END), 0) as total_amount
    FROM categories c
    LEFT JOIN transactions t ON c.id = t.category_id AND c.type = 'income'
    LEFT JOIN expenses e ON c.id = e.category_id AND c.type = 'expense'
    GROUP BY c.id
    ORDER BY usage_count DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports - BENTA Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-badge {
            background: #e74c3c;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            margin-right: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .admin-nav a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .report-filters {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .system-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .overview-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #2c3e50;
        }
        
        .overview-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .overview-card .label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .reports-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .export-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div>
            <strong>BENTA</strong> - System Reports
            <span class="admin-badge"><?= strtoupper($_SESSION['admin_role']) ?></span>
        </div>
        <nav class="admin-nav">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_users.php">Manage Users</a>
            <a href="admin_reports.php">System Reports</a>
            <a href="admin_settings.php">Settings</a>
            <a href="admin_logout.php">Logout</a>
        </nav>
    </header>
    
    <main class="container">
        <div class="page-header">
            <h1>System Reports</h1>
            <p>Comprehensive system analytics and user activity reports</p>
        </div>
        
        <!-- Date Range Filter -->
        <div class="report-filters">
            <form method="GET" class="form-inline">
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
            
            <div class="export-actions">
                <a href="export_admin_report.php?from=<?= e($from) ?>&to=<?= e($to) ?>&type=users" class="btn btn-success">Export Users CSV</a>
                <a href="export_admin_report.php?from=<?= e($from) ?>&to=<?= e($to) ?>&type=transactions" class="btn btn-info">Export Transactions CSV</a>
                <a href="export_admin_report.php?from=<?= e($from) ?>&to=<?= e($to) ?>&type=expenses" class="btn btn-warning">Export Expenses CSV</a>
            </div>
        </div>
        
        <!-- System Overview -->
        <div class="system-overview">
            <div class="overview-card">
                <div class="number"><?= $systemStats['total_users'] ?></div>
                <div class="label">Total Users</div>
            </div>
            <div class="overview-card">
                <div class="number"><?= $systemStats['active_users'] ?></div>
                <div class="label">Active Users</div>
            </div>
            <div class="overview-card">
                <div class="number"><?= $systemStats['total_transactions'] ?></div>
                <div class="label">Total Transactions</div>
            </div>
            <div class="overview-card">
                <div class="number"><?= $systemStats['total_expenses'] ?></div>
                <div class="label">Total Expenses</div>
            </div>
            <div class="overview-card">
                <div class="number">₱<?= number_format($systemStats['total_income'], 0) ?></div>
                <div class="label">Total Income</div>
            </div>
            <div class="overview-card">
                <div class="number">₱<?= number_format($systemStats['total_expenses_amount'], 0) ?></div>
                <div class="label">Total Expenses</div>
            </div>
        </div>
        
        <div class="reports-grid">
            <!-- Top Users -->
            <div class="card">
                <div class="card-header">
                    <h2>Top Active Users</h2>
                    <span class="chart-info">By transaction count</span>
                </div>
                <?php if (empty($topUsers)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">👥</div>
                        <h3>No user activity</h3>
                        <p>No users have made transactions in this period</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Transactions</th>
                                <th>Expenses</th>
                                <th>Total Income</th>
                                <th>Total Expenses</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topUsers as $user): ?>
                            <tr>
                                <td>
                                    <strong><?= e($user['username']) ?></strong><br>
                                    <small><?= e($user['email']) ?></small>
                                </td>
                                <td><?= $user['transaction_count'] ?></td>
                                <td><?= $user['expense_count'] ?></td>
                                <td class="income">₱<?= number_format($user['total_income'], 2) ?></td>
                                <td class="expense">₱<?= number_format($user['total_expenses'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Category Usage -->
            <div class="card">
                <div class="card-header">
                    <h2>Category Usage</h2>
                    <span class="chart-info">Most used categories</span>
                </div>
                <?php if (empty($categoryUsage)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📊</div>
                        <h3>No category data</h3>
                        <p>No categories have been used yet</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Usage Count</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categoryUsage as $category): ?>
                            <tr>
                                <td><?= e($category['name']) ?></td>
                                <td>
                                    <span class="badge <?= $category['type'] == 'income' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= ucfirst($category['type']) ?>
                                    </span>
                                </td>
                                <td><?= $category['usage_count'] ?></td>
                                <td class="<?= $category['type'] == 'income' ? 'income' : 'expense' ?>">
                                    ₱<?= number_format($category['total_amount'], 2) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Monthly User Growth -->
        <?php if (!empty($monthlyData)): ?>
        <div class="card">
            <div class="card-header">
                <h2>Monthly User Growth</h2>
                <span class="chart-info">New user registrations</span>
            </div>
            <div class="chart-container">
                <canvas id="userGrowthChart" width="800" height="300"></canvas>
            </div>
        </div>
        <?php endif; ?>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/animations.js"></script>
    <script>
        <?php if (!empty($monthlyData)): ?>
        // User Growth Chart
        const userGrowthData = <?= json_encode($monthlyData) ?>;
        const labels = userGrowthData.map(item => item.month);
        const userCounts = userGrowthData.map(item => parseInt(item.new_users));
        
        const ctx = document.getElementById('userGrowthChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'New Users',
                    data: userCounts,
                    borderColor: '#2c3e50',
                    backgroundColor: 'rgba(44, 62, 80, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
