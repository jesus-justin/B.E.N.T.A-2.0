<?php
require 'config.php';
if (empty($_SESSION['user_id'])) header('Location: login.php');
$uid = $_SESSION['user_id'];
$errors = [];

$id = intval($_GET['id'] ?? 0);
if (!$id) header('Location: dashboard.php');

// Get transaction details
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $uid]);
$transaction = $stmt->fetch();

if (!$transaction) header('Location: dashboard.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request token.';
    }
    $category = intval($_POST['category'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $date = $_POST['date'] ?? date('Y-m-d');

    if ($category <= 0) $errors[] = 'Choose a category.';
    if ($amount <= 0) $errors[] = 'Amount must be positive.';

    if (empty($errors)) {
        $upd = $pdo->prepare("UPDATE transactions SET category_id = ?, amount = ?, description = ?, trx_date = ? WHERE id = ? AND user_id = ?");
        $upd->execute([$category, $amount, $description, $date, $id, $uid]);
        header('Location: dashboard.php?success=transaction_updated');
        exit;
    }
}

$cats = $pdo->query("SELECT * FROM categories WHERE type='income' ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaction - BENTA</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="topbar">
        <div><strong>BENTA</strong> - Edit Transaction</div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="expenses.php">Expenses</a>
            <a href="reports.php">Reports</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>
    
    <main class="container">
        <div class="page-header">
            <h1>Edit Transaction</h1>
            <p>Update transaction details</p>
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
                            <option value="<?= e($c['id']) ?>" <?= $c['id'] == $transaction['category_id'] ? 'selected' : '' ?>>
                                <?= e($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="amount">Amount (₱)</label>
                    <input type="number" step="0.01" name="amount" id="amount" value="<?= e($transaction['amount']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" name="description" id="description" value="<?= e($transaction['description']) ?>" placeholder="Transaction description">
                </div>
                
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" value="<?= e($transaction['trx_date']) ?>" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-text">Update Transaction</span>
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