<?php
// Login page: users log in with their phone number and password.
require_once 'includes/auth.php';

if ($u = current_user()) {
    redirect($u['role'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php');
}

$error = '';
$pn = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $pn   = trim($_POST['pn'] ?? '');
    $pass = $_POST['pass'] ?? '';

    $account = db_one('SELECT * FROM register WHERE pn = ?', 's', [$pn]);

    if (!$account || !password_verify($pass, $account['pass'])) {
        $error = 'Wrong phone number or password.';
    } elseif (!$account['active']) {
        $error = 'Your account has not been activated yet (or has been deactivated). Please contact the administrator.';
    } else {
        session_regenerate_id(true); // new session id after login (stops session fixation)
        $_SESSION['user_id'] = $account['id'];
        redirect($account['role'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php');
    }
}

$page_title = 'Login';
include 'includes/header.php';
?>
<div class="auth-box">
    <h1>Event Scheduling Management System</h1>
    <p class="muted">Institute of Management and Technology, Enugu</p>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="post" class="card">
        <?= csrf_field() ?>
        <h2>Login</h2>
        <label>Phone number
            <input type="tel" name="pn" value="<?= e($pn) ?>" placeholder="08030000001" required autofocus>
        </label>
        <label>Password
            <input type="password" name="pass" required>
        </label>
        <button type="submit" class="btn">Login</button>
        <p class="muted">No account? <a href="register.php">Register here</a></p>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
