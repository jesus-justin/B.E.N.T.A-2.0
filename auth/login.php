<?php
require '../config/config.php';

$errors = [];

// Google reCAPTCHA configuration
$recaptcha_site_key = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'; // Test key - replace with your actual site key
$recaptcha_secret_key = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'; // Test key - replace with your actual secret key

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Throttle brute-force attempts
    if (throttle_is_limited('user_login')) {
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
        // Check user credentials
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            throttle_reset('user_login');
            header('Location: ../user/dashboard.php');
            exit;
        } else {
            $errors[] = 'Invalid username/email or password.';
            throttle_hit('user_login');
        }
        } // end credential check else branch
    } // end reCAPTCHA else branch
} // end main POST handler
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BENTA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/login.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <div class="logo">
                <i class="fas fa-chart-line"></i>
            </div>
            <h1>BENTA</h1>
            <p class="subtitle">Business Expense & Net Transaction Analyzer</p>
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
        
        <form method="POST" id="loginForm">
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
                <span class="btn-text">Sign In</span>
                <div class="btn-loader"></div>
            </button>
        </form>
        
        <div class="register-link">
            <a href="register.php">
                <i class="fas fa-user-plus"></i>
                Create New Account
            </a>
        </div>

        <div class="admin-link">
            <a href="../admin/admin_login.php" class="admin-login-btn">
                <i class="fas fa-user-shield"></i>
                Admin Login
            </a>
        </div>

        <div class="features">
            <div class="feature">
                <i class="fas fa-chart-pie"></i>
                <div>Track Expenses</div>
            </div>
            <div class="feature">
                <i class="fas fa-chart-bar"></i>
                <div>Generate Reports</div>
            </div>
            <div class="feature">
                <i class="fas fa-mobile-alt"></i>
                <div>Mobile Friendly</div>
            </div>
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
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const btnText = btn.querySelector('.btn-text');
            
            btn.classList.add('loading');
            btnText.textContent = 'Signing In...';
            
            // Re-enable after 3 seconds if no redirect occurs
            setTimeout(() => {
                btn.classList.remove('loading');
                btnText.textContent = 'Sign In';
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
                document.getElementById('loginForm').submit();
            }
        });

        // Add some interactive animations
        document.querySelectorAll('.feature').forEach(feature => {
            feature.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.transition = 'transform 0.3s ease';
            });
            
            feature.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
    <script src="../assets/js/dark-mode.js"></script>
</body>
</html>
