<?php
require 'config.php';

// Check if admin is logged in
if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$adminId = $_SESSION['admin_id'];
$adminRole = $_SESSION['admin_role'];

// Get system statistics
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
$totalTransactions = $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
$totalExpenses = $pdo->query("SELECT COUNT(*) FROM expenses")->fetchColumn();

// Get recent users
$recentUsers = $pdo->query("
    SELECT id, username, email, is_active, created_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 10
")->fetchAll();

// Get system activity (recent transactions and expenses)
$recentActivity = $pdo->query("
    (SELECT 'transaction' as type, id, user_id, amount, trx_date as date, description as details, created_at
     FROM transactions 
     ORDER BY created_at DESC 
     LIMIT 5)
    UNION ALL
    (SELECT 'expense' as type, id, user_id, amount, expense_date as date, CONCAT(vendor, ' - ', note) as details, created_at
     FROM expenses 
     ORDER BY created_at DESC 
     LIMIT 5)
    ORDER BY created_at DESC 
    LIMIT 10
")->fetchAll();

// Get monthly statistics
$currentMonth = date('Y-m');
$monthlyStats = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth') as new_users,
        (SELECT COUNT(*) FROM transactions WHERE DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth') as new_transactions,
        (SELECT COUNT(*) FROM expenses WHERE DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth') as new_expenses,
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth') as monthly_income,
        (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth') as monthly_expenses
")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BENTA</title>
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
        
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .admin-stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #2c3e50;
        }
        
        .admin-stat-card h3 {
            color: #666;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .admin-stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .admin-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .user-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            border-radius: 3px;
        }
        
        .status-active {
            color: #27ae60;
            font-weight: bold;
        }
        
        .status-inactive {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div>
            <strong>BENTA</strong> - Admin Dashboard
            <span class="admin-badge"><?= strtoupper($adminRole) ?></span>
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
            <h1>Admin Dashboard</h1>
            <p>Welcome back, <?= e($_SESSION['admin_full_name']) ?>! System overview and management</p>
        </div>
        
        <!-- System Statistics -->
        <div class="admin-stats">
            <div class="admin-stat-card">
                <h3>Total Users</h3>
                <div class="number"><?= $totalUsers ?></div>
                <div class="stat-trend"><?= $activeUsers ?> active</div>
            </div>
            <div class="admin-stat-card">
                <h3>Transactions</h3>
                <div class="number"><?= $totalTransactions ?></div>
                <div class="stat-trend"><?= $monthlyStats['new_transactions'] ?> this month</div>
            </div>
            <div class="admin-stat-card">
                <h3>Expenses</h3>
                <div class="number"><?= $totalExpenses ?></div>
                <div class="stat-trend"><?= $monthlyStats['new_expenses'] ?> this month</div>
            </div>
            <div class="admin-stat-card">
                <h3>Monthly Volume</h3>
                <div class="number">₱<?= number_format($monthlyStats['monthly_income'] + $monthlyStats['monthly_expenses'], 0) ?></div>
                <div class="stat-trend">Total activity</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="admin_users.php" class="quick-action">
                <span class="quick-action-icon">👥</span>
                <h3>Manage Users</h3>
                <p>View, edit, and manage user accounts</p>
            </a>
            <a href="admin_reports.php" class="quick-action">
                <span class="quick-action-icon">📊</span>
                <h3>System Reports</h3>
                <p>Generate detailed system reports</p>
            </a>
            <a href="admin_settings.php" class="quick-action">
                <span class="quick-action-icon">⚙️</span>
                <h3>System Settings</h3>
                <p>Configure system parameters</p>
            </a>
            <a href="admin_backup.php" class="quick-action">
                <span class="quick-action-icon">💾</span>
                <h3>Backup System</h3>
                <p>Create and manage backups</p>
            </a>
        </div>
        
        <div class="admin-content">
            <!-- Recent Users -->
            <div class="card">
                <div class="card-header">
                    <h2>Recent Users</h2>
                    <a href="admin_users.php" class="btn btn-primary btn-sm">View All</a>
                </div>
                <?php if (empty($recentUsers)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">👥</div>
                        <h3>No users yet</h3>
                        <p>Users will appear here as they register</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $user): ?>
                            <tr>
                                <td><?= e($user['username']) ?></td>
                                <td><?= e($user['email']) ?></td>
                                <td>
                                    <span class="<?= $user['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <div class="user-actions">
                                        <a href="admin_edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                                        <a href="admin_toggle_user.php?id=<?= $user['id'] ?>" class="btn btn-sm <?= $user['is_active'] ? 'btn-warning' : 'btn-success' ?>">
                                            <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h2>Recent Activity</h2>
                    <span class="chart-info">System-wide activity</span>
                </div>
                <?php if (empty($recentActivity)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📈</div>
                        <h3>No activity yet</h3>
                        <p>User activity will appear here</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentActivity as $activity): ?>
                            <tr>
                                <td>
                                    <span class="badge <?= $activity['type'] == 'transaction' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= ucfirst($activity['type']) ?>
                                    </span>
                                </td>
                                <td class="<?= $activity['type'] == 'transaction' ? 'income' : 'expense' ?>">
                                    ₱<?= number_format($activity['amount'], 2) ?>
                                </td>
                                <td><?= date('M d', strtotime($activity['date'])) ?></td>
                                <td><?= e(substr($activity['details'], 0, 30)) ?><?= strlen($activity['details']) > 30 ? '...' : '' ?></td>
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
