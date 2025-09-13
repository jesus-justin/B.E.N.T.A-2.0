<?php
require 'config.php';
if (empty($_SESSION['user_id'])) header('Location: login.php');
$uid = $_SESSION['user_id'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="topbar">
        <div><strong>BENTA</strong> - Add Expense</div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="expenses.php">Expenses</a>
            <a href="reports.php">Reports</a>
            <a href="logout.php">Logout</a>
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
    
    <script src="assets/js/animations.js"></script>
</body>
</html>
