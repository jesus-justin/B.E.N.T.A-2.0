<?php
require '../config/config.php';

$errors = [];

// Google reCAPTCHA configuration
$recaptcha_site_key = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'; // Test key - replace with your actual site key
$recaptcha_secret_key = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'; // Test key - replace with your actual secret key

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
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    
    if (empty($username) || empty($password)) {
        $errors[] = 'Please enter both username and password.';
    } elseif (empty($recaptcha_response)) {
        $errors[] = 'Please complete the reCAPTCHA verification.';
    } else {
        // Verify reCAPTCHA
        $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
        $recaptcha_data = [
            'secret' => $recaptcha_secret_key,
            'response' => $recaptcha_response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];
        
        $recaptcha_options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($recaptcha_data)
            ]
        ];
        
        $recaptcha_context = stream_context_create($recaptcha_options);
        $recaptcha_result = file_get_contents($recaptcha_url, false, $recaptcha_context);
        $recaptcha_json = json_decode($recaptcha_result);
        
        if (!$recaptcha_json->success) {
            $errors[] = 'reCAPTCHA verification failed. Please try again.';
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
                session_regenerate_id(true);
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-login.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
            
            <div class="form-group">
                <label for="recaptcha">Security Verification</label>
                <div class="recaptcha-wrapper">
                    <div class="g-recaptcha" data-sitekey="<?= $recaptcha_site_key ?>"></div>
                </div>
            </div>
            
            <button type="submit" class="btn" id="loginBtn">
                <span class="btn-text">Login as Admin</span>
                <div class="btn-loader"></div>
            </button>
        </form>
        
        <div class="back-link">
            <a href="../index.php">
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
    <script src="../assets/js/dark-mode.js"></script>
</body>
</html>
