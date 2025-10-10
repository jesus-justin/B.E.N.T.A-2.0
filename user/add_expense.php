<?php
require '../config/config.php';
if (empty($_SESSION['user_id'])) header('Location: ../auth/login.php');
$uid = $_SESSION['user_id'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request token.';
    }
    $category = intval($_POST['category'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $vendor = trim($_POST['vendor'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $date = $_POST['date'] ?? date('Y-m-d');

    if ($category <= 0) $errors[] = 'Choose a category.';
    if ($amount <= 0) $errors[] = 'Amount must be positive.';

    if (empty($errors)) {
        $ins = $pdo->prepare("INSERT INTO expenses (user_id, category_id, amount, vendor, note, expense_date) VALUES (?, ?, ?, ?, ?, ?)");
        $ins->execute([$uid, $category, $amount, $vendor, $note, $date]);
        header('Location: expenses.php'); exit;
    }
}

$cats = $pdo->query("SELECT * FROM categories WHERE type='expense' ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense - BENTA</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/add-expense.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-brand">
            <strong>BENTA</strong> - Add Expense
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
            <h1>Add Expense</h1>
            <p>Record a new expense transaction</p>
        </div>
        
        <div class="card">
            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="form-group">
                    <label for="category">Category</label>
                    <select name="category" id="category" required>
                        <option value="">-- Choose Category --</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="amount">Amount (₱)</label>
                    <input type="number" step="0.01" name="amount" id="amount" required placeholder="0.00">
                </div>
                
                <div class="form-group">
                    <label for="vendor">Vendor</label>
                    <input type="text" name="vendor" id="vendor" placeholder="Vendor name">
                </div>
                
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="note">Note</label>
                    <input type="text" name="note" id="note" placeholder="Additional notes">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-text">Save Expense</span>
                        <div class="btn-loader"></div>
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>
    
    <script src="../assets/js/animations.js"></script>
</body>
</html>
