<?php
// Google reCAPTCHA Configuration
// ------------------------------------------------------------
// YES you can use it locally. Add these domains when you register:
//   localhost
//   127.0.0.1
// (Optionally your LAN IP e.g. 192.168.1.25 if testing from phone)
// For production add your real domain (without protocol, no ports).
// ------------------------------------------------------------
// This file now:
//  1. Auto-detects if you're on a local dev host and uses Google's public test keys.
//  2. Lets you override with environment variables RECAPTCHA_SITE_KEY / RECAPTCHA_SECRET_KEY.
//  3. Provides an optional dev bypass (set RECAPTCHA_DEV_BYPASS=1 in your env *only* locally).
//  4. Falls back safely if keys are missing.
//
// Get real keys here: https://www.google.com/recaptcha/admin/create (choose reCAPTCHA v2 Checkbox)

// Helper: determine if current host is local / private network.
$__rc_host = $_SERVER['HTTP_HOST'] ?? '';
$__rc_is_local = in_array($__rc_host, ['localhost','127.0.0.1','::1'])
    || preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $__rc_host);

// Environment overrides (recommended for production so keys aren't committed)
$__rc_env_site   = getenv('RECAPTCHA_SITE_KEY');
$__rc_env_secret = getenv('RECAPTCHA_SECRET_KEY');
$__rc_dev_bypass = getenv('RECAPTCHA_DEV_BYPASS') === '1'; // NEVER set this in production.

if (!defined('RECAPTCHA_SITE_KEY') || !defined('RECAPTCHA_SECRET_KEY')) {
    if ($__rc_env_site && $__rc_env_secret) {
        // Use explicit environment variables if provided.
        define('RECAPTCHA_SITE_KEY', $__rc_env_site);
        define('RECAPTCHA_SECRET_KEY', $__rc_env_secret);
    } elseif ($__rc_is_local) {
        // Google's official public test keys (always validate but not tied to a domain).
        define('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI');
        define('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe');
    } else {
        // Placeholder: forces you to supply real keys via env or editing below.
        define('RECAPTCHA_SITE_KEY', 'your_production_site_key_here');
        define('RECAPTCHA_SECRET_KEY', 'your_production_secret_key_here');
    }
}

// (Optional) Hard-code production keys here instead of placeholders if you prefer version control storage.
// NOTE: Remove the placeholders above when you do.
// Example:
// if (!__rc_is_local) {
//     define('RECAPTCHA_SITE_KEY', 'prod_site_key');
//     define('RECAPTCHA_SECRET_KEY', 'prod_secret_key');
// }

/**
 * Verify reCAPTCHA response
 * @param string $response The reCAPTCHA response token
 * @return bool True if verification successful, false otherwise
 */
function verify_recaptcha($response) {
    global $__rc_dev_bypass, $__rc_is_local; // use detection from outer scope

    // Optional dev bypass (only active when explicitly enabled AND local)
    if ($__rc_dev_bypass && $__rc_is_local) {
        return true; // Treat as passed for rapid local testing.
    }

    if (empty($response)) {
        return false;
    }

    if (RECAPTCHA_SECRET_KEY === 'your_production_secret_key_here') {
        // Misconfigured production keys; fail closed.
        return false;
    }

    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($recaptcha_data),
            'timeout' => 10,
        ]
    ];

    $context = stream_context_create($options);
    $result = @file_get_contents($recaptcha_url, false, $context);
    if ($result === false) {
        return false; // network / API failure => treat as failed (secure default)
    }

    $json = json_decode($result, false);
    if (!isset($json->success) || $json->success !== true) {
        return false;
    }

    // (Optional) Could inspect $json->score or $json->{'error-codes'} for logging.
    return true;
}
?>