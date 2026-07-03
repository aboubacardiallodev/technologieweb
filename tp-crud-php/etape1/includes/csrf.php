<?php
// Simple CSRF helper
function generateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfInputField() {
    $t = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($t) . '">';
}
?>
