<?php
require '../config/config.php';
if (empty($_SESSION['user_id'])) header('Location: ../auth/login.php');
$uid = $_SESSION['user_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
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
        $ins = $pdo->prepare("INSERT INTO transactions (user_id, category_id, amount, description, trx_date) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$uid, $category, $amount, $description, $date]);
        header('Location: dashboard.php?success=transaction_added');
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
    <title>Add Transaction - BENTA</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/add-transaction.css">
</head>
<body>
    <header class="topbar">
        <div><strong>BENTA</strong> - Add Transaction</div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="expenses.php">Expenses</a>
            <a href="reports.php">Reports</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>
    
    <main class="container">
        <div class="page-header">
            <h1>Add Income Transaction</h1>
            <p>Record a new income transaction</p>
        </div>
        
        <div class="card">
            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="form" id="transactionForm">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="form-group">
                    <label for="category">
                        <i class="fas fa-tags"></i>
                        Category
                    </label>
                    <div class="input-wrapper">
                        <select name="category" id="category" required>
                            <option value="">-- Choose Category --</option>
                            <?php foreach ($cats as $c): ?>
                                <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-error" id="category-error"></div>
                </div>
                
                <div class="form-group">
                    <label for="amount">
                        <i class="fas fa-money-bill-wave"></i>
                        Amount (₱)
                    </label>
                    <div class="input-wrapper">
                        <input type="number" step="0.01" name="amount" id="amount" required placeholder="0.00" min="0.01">
                    </div>
                    <div class="field-error" id="amount-error"></div>
                </div>
                
                <div class="form-group">
                    <label for="description">
                        <i class="fas fa-align-left"></i>
                        Description
                    </label>
                    <div class="input-wrapper">
                        <input type="text" name="description" id="description" placeholder="Transaction description" maxlength="255">
                    </div>
                    <div class="field-help">Optional: Brief description of the transaction</div>
                </div>
                
                        <i class="fas fa-calendar-alt"></i>
                        Date
                    </label>
                    <div class="input-wrapper">
                        <input type="date" name="date" id="date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="field-error" id="date-error"></div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span class="btn-text">
                            <i class="fas fa-save"></i>
                            Save Transaction
                        </span>
                        <div class="btn-loader"></div>
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>
    
    <script src="../assets/js/animations.js"></script>
    <script src="../assets/js/dark-mode.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('transactionForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            
            // Real-time validation
            const categorySelect = document.getElementById('category');
            const amountInput = document.getElementById('amount');
            const dateInput = document.getElementById('date');
            const descriptionInput = document.getElementById('description');
            
            // Category validation
            categorySelect.addEventListener('change', function() {
                validateCategory();
            });
            
            // Amount validation
            amountInput.addEventListener('input', function() {
                validateAmount();
                formatAmount();
            });
            
            // Date validation
            dateInput.addEventListener('change', function() {
                validateDate();
            });
            
            // Description character counter
            descriptionInput.addEventListener('input', function() {
                updateCharacterCount();
            });
            
            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (validateForm()) {
                    submitForm();
                } else {
                    showFormErrors();
                }
            });
            
            function validateCategory() {
                const error = document.getElementById('category-error');
                if (categorySelect.value === '') {
                    showFieldError('category-error', 'Please select a category');
                    return false;
                } else {
                    clearFieldError('category-error');
                    return true;
                }
            }
            
            function validateAmount() {
                const error = document.getElementById('amount-error');
                const value = parseFloat(amountInput.value);
                
                if (isNaN(value) || value <= 0) {
                    showFieldError('amount-error', 'Please enter a valid amount greater than 0');
                    return false;
                } else if (value > 999999.99) {
                    showFieldError('amount-error', 'Amount cannot exceed ₱999,999.99');
                    return false;
                } else {
                    clearFieldError('amount-error');
                    return true;
                }
            }
            
            function validateDate() {
                const error = document.getElementById('date-error');
                const selectedDate = new Date(dateInput.value);
                const today = new Date();
                const oneYearAgo = new Date();
                oneYearAgo.setFullYear(today.getFullYear() - 1);
                
                if (selectedDate > today) {
                    showFieldError('date-error', 'Date cannot be in the future');
                    return false;
                } else if (selectedDate < oneYearAgo) {
                    showFieldError('date-error', 'Date cannot be more than 1 year ago');
                    return false;
                } else {
                    clearFieldError('date-error');
                    return true;
                }
            }
            
            function validateForm() {
                const categoryValid = validateCategory();
                const amountValid = validateAmount();
                const dateValid = validateDate();
                
                return categoryValid && amountValid && dateValid;
            }
            
            function showFieldError(fieldId, message) {
                const errorElement = document.getElementById(fieldId);
                errorElement.textContent = message;
                errorElement.style.display = 'block';
                
                // Add error class to input
                const input = errorElement.previousElementSibling.querySelector('input, select');
                if (input) {
                    input.classList.add('error');
                }
            }
            
            function clearFieldError(fieldId) {
                const errorElement = document.getElementById(fieldId);
                errorElement.textContent = '';
                errorElement.style.display = 'none';
                
                // Remove error class from input
                const input = errorElement.previousElementSibling.querySelector('input, select');
                if (input) {
                    input.classList.remove('error');
                }
            }
            
            function showFormErrors() {
                // Scroll to first error
                const firstError = document.querySelector('.field-error[style*="block"]');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                
                // Show notification
                if (window.showNotification) {
                    window.showNotification('Please fix the errors below', 'warning');
                }
            }
            
            function formatAmount() {
                const value = amountInput.value;
                if (value && !isNaN(value)) {
                    // Add thousand separators for display
                    const formatted = parseFloat(value).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    // Don't update the input value to avoid cursor issues
                }
            }
            
            function updateCharacterCount() {
                const maxLength = 255;
                const currentLength = descriptionInput.value.length;
                const remaining = maxLength - currentLength;
                
                let helpElement = descriptionInput.parentElement.nextElementSibling;
                if (helpElement && helpElement.classList.contains('field-help')) {
                    helpElement.textContent = `Optional: Brief description of the transaction (${remaining} characters remaining)`;
                    
                    if (remaining < 50) {
                        helpElement.style.color = remaining < 10 ? '#e74c3c' : '#f39c12';
                    } else {
                        helpElement.style.color = '#7f8c8d';
                    }
                }
            }
            
            function submitForm() {
                // Show loading state
                submitBtn.classList.add('loading');
                btnText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                submitBtn.disabled = true;
                
                // Add a small delay for better UX
                setTimeout(() => {
                    form.submit();
                }, 500);
            }
            
            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + Enter: Submit form
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    e.preventDefault();
                    form.dispatchEvent(new Event('submit'));
                }
                
                // Escape: Cancel and go back
                if (e.key === 'Escape') {
                    window.location.href = 'dashboard.php';
                }
            });
            
            // Auto-focus first field
            categorySelect.focus();
            
            // Add input animations
            document.querySelectorAll('input, select').forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
        });
    </script>
    

</body>
</html>
