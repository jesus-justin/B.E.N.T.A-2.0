<?php
require 'config.php';

$errors = [];

// Redirect if already logged in as admin
if (!empty($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Throttle brute-force attempts
    if (throttle_is_limited('admin_login')) {
        $errors[] = 'Too many attempts. Please try again later.';
    }

    // Verify CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $errors[] = 'Invalid request token. Please refresh and try again.';
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $errors[] = 'Please enter both username and password.';
    } else {
        // Check admin credentials
        $stmt = $pdo->prepare("SELECT id, username, email, password, full_name, role, is_active FROM admins WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            if (!$admin['is_active']) {
                $errors[] = 'Your admin account has been deactivated.';
            } else {
                // Login successful
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_full_name'] = $admin['full_name'];
                $_SESSION['admin_role'] = $admin['role'];
                
                // Update last login
                $updateLogin = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $updateLogin->execute([$admin['id']]);

                // Reset throttle on success
                throttle_reset('admin_login');
                
                header('Location: admin_dashboard.php');
                exit;
            }
        } else {
            $errors[] = 'Invalid username/email or password.';
            throttle_hit('admin_login');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - BENTA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --admin-gradient: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            --success-color: #27ae60;
            --error-color: #e74c3c;
            --warning-color: #f39c12;
            --text-primary: #2c3e50;
            --text-secondary: #7f8c8d;
            --bg-light: #f8f9fa;
            --border-color: #e1e5e9;
            --shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 30px 60px rgba(0, 0, 0, 0.15);
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Animated background elements */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 119, 198, 0.2) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 3rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 10;
            animation: slideUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .admin-badge {
            position: absolute;
            top: -15px;
            right: -15px;
            background: var(--admin-gradient);
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 10px 20px rgba(231, 76, 60, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: var(--primary-gradient);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            animation: bounce 1s ease-out;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .logo i {
            font-size: 2rem;
            color: white;
        }
        
        h1 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 2.2rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 400;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 1.1rem;
            transition: var(--transition);
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 400;
            background: white;
            transition: var(--transition);
            font-family: inherit;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        input[type="text"]:focus + i,
        input[type="password"]:focus + i {
            color: #667eea;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .password-toggle:hover {
            color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 1rem;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: var(--transition);
        }

        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        .btn:active {
            transform: translateY(-1px);
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
            margin-left: 0.5rem;
        }

        .btn.loading .btn-loader {
            display: inline-block;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .errors {
            background: linear-gradient(135deg, #fee 0%, #fdd 100%);
            color: var(--error-color);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--error-color);
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .error-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .error-item:last-child {
            margin-bottom: 0;
        }

        .error-item i {
            margin-right: 0.5rem;
            font-size: 0.9rem;
        }
        
        .back-link {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-secondary);
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-link a:hover {
            color: #5a6fd8;
            transform: translateX(-3px);
        }
        
        .security-notice {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--warning-color);
            font-size: 0.9rem;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .security-notice strong {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .security-notice small {
            display: block;
            margin-top: 0.5rem;
            opacity: 0.8;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .login-container {
                margin: 1rem;
                padding: 2rem;
            }

            h1 {
                font-size: 1.8rem;
            }

            .admin-badge {
                top: -10px;
                right: -10px;
                padding: 0.5rem 0.75rem;
                font-size: 0.7rem;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .login-container {
                background: rgba(30, 30, 30, 0.95);
                color: white;
            }

            h1 {
                color: white;
            }

            .form-group label {
                color: #e0e0e0;
            }

            input[type="text"],
            input[type="password"] {
                background: #2a2a2a;
                border-color: #444;
                color: white;
            }

            input[type="text"]:focus,
            input[type="password"]:focus {
                border-color: #667eea;
                background: #333;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="admin-badge">
            <i class="fas fa-shield-alt"></i>
            ADMIN
        </div>
        
        <div class="logo-section">
            <div class="logo">
                <i class="fas fa-chart-line"></i>
            </div>
            <h1>BENTA Admin</h1>
            <p class="subtitle">Business Expense & Net Transaction Analyzer</p>
        </div>
        
        <div class="security-notice">
            <strong>
                <i class="fas fa-lock"></i>
                Secure Administrative Area
            </strong>
            This is a restricted area for authorized personnel only. All access attempts are logged and monitored.
            <small>
                <strong>Demo Credentials:</strong> admin@example.com / admin123
            </small>
        </div>
        
        <?php if ($errors): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <div class="error-item">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= e($error) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="adminLoginForm">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" value="<?= e($_POST['username'] ?? '') ?>" required autocomplete="username">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="passwordToggleIcon"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn" id="loginBtn">
                <span class="btn-text">Login as Admin</span>
                <div class="btn-loader"></div>
            </button>
        </form>
        
        <div class="back-link">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i>
                Back to Main Application
            </a>
        </div>
    </div>

    <script>
        // Password toggle functionality
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form submission with loading state
        document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const btnText = btn.querySelector('.btn-text');
            
            btn.classList.add('loading');
            btnText.textContent = 'Authenticating...';
            
            // Re-enable after 3 seconds if no redirect occurs
            setTimeout(() => {
                btn.classList.remove('loading');
                btnText.textContent = 'Login as Admin';
            }, 3000);
        });

        // Add focus animations
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Add enter key support for form submission
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('adminLoginForm').submit();
            }
        });
    </script>
    <script src="assets/js/dark-mode.js"></script>
</body>
</html>
