<?php
// Admin activates / deactivates user accounts.
require_once 'includes/auth.php';
$admin = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $user_id = (int)$_POST['user_id'];
    $active  = (int)$_POST['active'] === 1 ? 1 : 0;

    if ($user_id === (int)$admin['id']) {
        flash('error', 'You cannot deactivate your own account.');
    } else {
        db_exec('UPDATE register SET active = ? WHERE id = ?', 'ii', [$active, $user_id]);
        flash('success', $active ? 'Account activated.' : 'Account deactivated.');
    }
    redirect('admin_users.php');
}

$users = db_all('SELECT * FROM register ORDER BY active, fname');

$page_title = 'Users';
include 'includes/header.php';
?>
<h1>User accounts</h1>
<section class="card">
    <table>
        <tr><th>Photo</th><th>Name</th><th>Staff ID</th><th>Phone</th><th>E-mail</th><th>Gender</th><th>Role</th><th>Registered</th><th>Status</th><th></th></tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?php if ($u['photo']): ?><img class="thumb" src="uploads/<?= e($u['photo']) ?>" alt=""><?php endif; ?></td>
            <td><?= e($u['fname']) ?><br><small class="muted"><?= e($u['address']) ?></small></td>
            <td><?= e($u['staffid']) ?></td>
            <td><?= e($u['pn']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['gender']) ?></td>
            <td><?= e($u['role']) ?></td>
            <td><?= e(date('j M Y', strtotime($u['datereg']))) ?></td>
            <td><span class="badge badge-<?= $u['active'] ? 'sent' : 'failed' ?>"><?= $u['active'] ? 'Active' : 'Inactive' ?></span></td>
            <td>
                <?php if ($u['id'] != $admin['id']): ?>
                <form method="post" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <input type="hidden" name="active" value="<?= $u['active'] ? 0 : 1 ?>">
                    <button class="btn btn-small <?= $u['active'] ? 'btn-danger confirm' : '' ?>"><?= $u['active'] ? 'Deactivate' : 'Activate' ?></button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</section>
<?php include 'includes/footer.php'; ?>
