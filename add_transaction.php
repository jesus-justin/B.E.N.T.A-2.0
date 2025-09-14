<?php
require 'config.php';
if (empty($_SESSION['user_id'])) header('Location: login.php');
$uid = $_SESSION['user_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                
                <div class="form-group">
                    <label for="date">
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
    
    <script src="assets/js/animations.js"></script>
    <script src="assets/js/dark-mode.js"></script>
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
    
    <style>
        /* Enhanced form styles */
        .form-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group label i {
            color: var(--primary-color);
            font-size: 0.9rem;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper input,
        .input-wrapper select {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 400;
            background: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
        }
        
        .input-wrapper input:focus,
        .input-wrapper select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }
        
        .input-wrapper input.error,
        .input-wrapper select.error {
            border-color: var(--error-color);
            box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.1);
        }
        
        .field-error {
            color: var(--error-color);
            font-size: 0.8rem;
            margin-top: 0.5rem;
            display: none;
            animation: slideDown 0.3s ease-out;
        }
        
        .field-help {
            color: var(--text-secondary);
            font-size: 0.8rem;
            margin-top: 0.5rem;
            transition: color 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            min-width: 150px;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }
        
        .btn-secondary {
            background: var(--text-secondary);
            color: white;
        }
        
        .btn-secondary:hover {
            background: var(--text-primary);
            transform: translateY(-3px);
        }
        
        .btn.loading {
            pointer-events: none;
        }
        
        .btn.loading .btn-text {
            opacity: 0.7;
        }
        
        .btn-loader {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .btn.loading .btn-loader {
            display: inline-block;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Dark mode support */
        .dark-mode .input-wrapper input,
        .dark-mode .input-wrapper select {
            background: #4a5568;
            border-color: var(--border-color);
            color: var(--dark-text);
        }
        
        .dark-mode .input-wrapper input:focus,
        .dark-mode .input-wrapper select:focus {
            border-color: var(--primary-color);
            background: #2d3748;
        }
    </style>
</body>
</html>
