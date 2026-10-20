<?php
// View own profile and change password.
require_once 'includes/auth.php';
$user = require_login();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $current = $_POST['current'] ?? '';
    $new     = $_POST['new'] ?? '';

    if (!password_verify($current, $user['pass'])) $errors[] = 'Current password is wrong.';
    if (strlen($new) < 6)                          $errors[] = 'New password must be at least 6 characters.';
    if ($new !== ($_POST['new2'] ?? ''))           $errors[] = 'The new passwords do not match.';

    if (!$errors) {
        db_exec('UPDATE register SET pass = ? WHERE id = ?', 'si', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        flash('success', 'Password changed.');
        redirect('profile.php');
    }
}

$page_title = 'Profile';
include 'includes/header.php';
?>
<h1>My profile</h1>
<section class="card profile">
    <?php if ($user['photo']): ?><img src="uploads/<?= e($user['photo']) ?>" alt="Passport photo"><?php endif; ?>
    <dl class="details">
        <dt>Name</dt><dd><?= e($user['fname']) ?></dd>
        <dt>Staff ID</dt><dd><?= e($user['staffid']) ?></dd>
        <dt>Phone</dt><dd><?= e($user['pn']) ?></dd>
        <dt>E-mail</dt><dd><?= e($user['email']) ?></dd>
        <dt>Gender</dt><dd><?= e($user['gender']) ?></dd>
        <dt>Address</dt><dd><?= e($user['address']) ?></dd>
        <dt>Role</dt><dd><?= e($user['role']) ?></dd>
        <dt>Registered</dt><dd><?= e($user['datereg']) ?></dd>
    </dl>
</section>

<section class="card">
    <h2>Change password</h2>
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" class="grid-3">
        <?= csrf_field() ?>
        <label>Current password <input type="password" name="current" required></label>
        <label>New password <input type="password" name="new" minlength="6" required></label>
        <label>Confirm new password <input type="password" name="new2" minlength="6" required></label>
        <div><button class="btn">Change password</button></div>
    </form>
</section>
<?php include 'includes/footer.php'; ?>
