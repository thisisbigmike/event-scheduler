<?php
// Sessions, login checks, CSRF protection and small output helpers.
require_once __DIR__ . '/db.php';

// Session cookie: hidden from JavaScript and not sent with requests from other sites
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

// Escape text before printing it in HTML (protection against XSS).
function e($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

// The logged-in user, re-read from the database on every request so that
// a deactivated account is logged out immediately.
function current_user()
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $user = db_one('SELECT * FROM register WHERE id = ? AND active = 1', 'i', [$_SESSION['user_id']]);
            if (!$user) {
                $_SESSION = [];
            }
        }
    }
    return $user;
}

function require_login()
{
    if (!current_user()) {
        flash('error', 'Please log in to continue.');
        header('Location: index.php');
        exit;
    }
    return current_user();
}

function require_admin()
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        flash('error', 'That page is for administrators only.');
        header('Location: user_dashboard.php');
        exit;
    }
    return $user;
}

// ---------- Flash messages (shown once on the next page) ----------
function flash($type, $message)
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function show_flash()
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    foreach ($_SESSION['flash'] as $f) {
        echo '<div class="alert alert-' . e($f['type']) . '">' . e($f['message']) . '</div>';
    }
    unset($_SESSION['flash']);
}

// ---------- CSRF protection for every form that changes data ----------
function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function check_csrf()
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        die('Invalid form submission. Please go back and try again.');
    }
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}
