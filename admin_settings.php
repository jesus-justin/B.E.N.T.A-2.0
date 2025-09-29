<?php
require 'config.php';

// Check if admin is logged in
if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$adminRole = $_SESSION['admin_role'];
$success = '';
$errors = [];

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $errors[] = 'All password fields are required.';
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = 'New passwords do not match.';
    } elseif (strlen($newPassword) < 6) {
        $errors[] = 'New password must be at least 6 characters long.';
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        
        if (password_verify($currentPassword, $admin['password'])) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $updateStmt->execute([$hashedPassword, $_SESSION['admin_id']]);
            $success = 'Password updated successfully!';
        } else {
            $errors[] = 'Current password is incorrect.';
        }
    }
}

// Get system information
$systemInfo = [
    'PHP Version' => PHP_VERSION,
    'Database' => 'MySQL',
    'Server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'Total Users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'Total Admins' => $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn(),
    'Database Size' => 'N/A' // Could be calculated with additional queries
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - BENTA Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin-settings.css">

</head>
<body>
    <header class="admin-header">
        <div>
            <strong>BENTA</strong> - System Settings
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
            <h1>System Settings</h1>
            <p>Manage system configuration and admin settings</p>
        </div>
        
        <div class="settings-grid">
            <!-- Change Password -->
            <div class="card">
                <div class="card-header">
                    <h2>Change Password</h2>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= e($success) ?></div>
                <?php endif; ?>
                
                <?php if ($errors): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <div><?= e($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="form">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" name="current_password" id="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" name="new_password" id="new_password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required minlength="6">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
            
            <!-- System Information -->
            <div class="card">
                <div class="card-header">
                    <h2>System Information</h2>
                </div>
                
                <table class="info-table">
                    <?php foreach ($systemInfo as $key => $value): ?>
                    <tr>
                        <th><?= e($key) ?></th>
                        <td><?= e($value) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        
        <!-- Database Management -->
        <div class="card">
            <div class="card-header">
                <h2>Database Management</h2>
            </div>
            
            <div class="quick-actions">
                <a href="admin_backup.php" class="quick-action">
                    <span class="quick-action-icon">💾</span>
                    <h3>Backup Database</h3>
                    <p>Create a backup of the entire database</p>
                </a>
                <a href="admin_cleanup.php" class="quick-action">
                    <span class="quick-action-icon">🧹</span>
                    <h3>Cleanup System</h3>
                    <p>Remove old logs and temporary data</p>
                </a>
                <a href="admin_optimize.php" class="quick-action">
                    <span class="quick-action-icon">⚡</span>
                    <h3>Optimize Database</h3>
                    <p>Optimize database tables for better performance</p>
                </a>
            </div>
        </div>
        
        <!-- Danger Zone -->
        <?php if ($adminRole === 'super_admin'): ?>
        <div class="danger-zone">
            <h3>⚠️ Danger Zone</h3>
            <p>These actions are irreversible and should be used with extreme caution.</p>
            
            <div class="quick-actions">
                <a href="admin_reset_system.php" class="quick-action" style="border-color: var(--error-color);" onclick="return confirm('This will reset the entire system! Are you absolutely sure?')">
                    <span class="quick-action-icon">🔥</span>
                    <h3>Reset System</h3>
                    <p>Reset all data (users, transactions, expenses)</p>
                </a>
                <a href="admin_export_all.php" class="quick-action">
                    <span class="quick-action-icon">📤</span>
                    <h3>Export All Data</h3>
                    <p>Export complete system data</p>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </main>
    
    <script src="assets/js/animations.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
