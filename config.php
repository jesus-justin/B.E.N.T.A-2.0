<?php
// Start session
session_start();

// Database configuration
$host = "localhost";
$user = "root";
$pass = "";
$db = "benta_db";

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper function to escape output
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// CSRF token utilities
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

function verify_csrf_token($token) {
    return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// Simple rate limiting storage (per IP + action key)
function throttle_is_limited($key, $maxAttempts = 5, $decaySeconds = 300) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $bucketKey = "throttle_{$key}_{$ip}";
    $now = time();
    if (!isset($_SESSION[$bucketKey])) {
        $_SESSION[$bucketKey] = ['count' => 0, 'reset_at' => $now + $decaySeconds];
    }
    $bucket = &$_SESSION[$bucketKey];
    if ($now > ($bucket['reset_at'] ?? 0)) {
        $bucket = ['count' => 0, 'reset_at' => $now + $decaySeconds];
    }
    return $bucket['count'] >= $maxAttempts;
}

function throttle_hit($key, $decaySeconds = 300) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $bucketKey = "throttle_{$key}_{$ip}";
    $now = time();
    if (!isset($_SESSION[$bucketKey])) {
        $_SESSION[$bucketKey] = ['count' => 0, 'reset_at' => $now + $decaySeconds];
    }
    $_SESSION[$bucketKey]['count']++;
}
?>
