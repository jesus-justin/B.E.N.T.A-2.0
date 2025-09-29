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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - BENTA</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/expenses.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-brand">
            <strong>BENTA</strong> - Expenses
        </div>
        <nav class="topbar-nav">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="expenses.php" class="nav-link active">
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
            <h1>Expenses</h1>
            <p>Manage and track your expenses</p>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Search Expenses</h2>
                <a href="add_expense.php" class="btn btn-primary btn-sm">Add New Expense</a>
            </div>
            
            <form method="get" class="form-inline">
                <div class="form-group">
                    <input name="q" placeholder="Search vendor or note" value="<?= e($_GET['q'] ?? '') ?>" class="search-input">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($_GET['q'])): ?>
                    <a href="expenses.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Expense List</h2>
                <span class="expense-count"><?= count($rows) ?> expenses</span>
            </div>
            
            <?php if (empty($rows)): ?>
                <div class="empty-state">
                    <div class="empty-icon">💸</div>
                    <h3>No expenses found</h3>
                    <p><?= !empty($_GET['q']) ? 'Try adjusting your search criteria' : 'Start tracking your expenses by adding your first expense' ?></p>
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
                            <th>Note</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= e($r['expense_date']) ?></td>
                            <td><?= e($r['category']) ?></td>
                            <td><?= e($r['vendor'] ?: 'No vendor') ?></td>
                            <td class="expense">₱<?= number_format($r['amount'], 2) ?></td>
                            <td><?= e($r['note'] ?: 'No note') ?></td>
                            <td>
                                <a href="edit_expense.php?id=<?= e($r['id']) ?>" class="btn btn-sm btn-info">Edit</a>
                                <a href="delete_expense.php?id=<?= e($r['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this expense?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
    
    <script src="assets/js/animations.js"></script>
</body>
</html>
