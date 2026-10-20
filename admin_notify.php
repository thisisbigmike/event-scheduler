<?php
// Send SMS + e-mail to attendees who have not been told about an event yet.
// As in the flowchart, the admin must answer their security question
// before any message goes out.
require_once 'includes/auth.php';
require_once 'includes/notify.php';
$admin = require_admin();

$id    = (int)($_GET['id'] ?? 0);
$event = get_event($id);
if (!$event) {
    flash('error', 'Event not found.');
    redirect('admin_events.php');
}

$pending = db_all(
    'SELECT r.* FROM event_attendee a JOIN register r ON r.id = a.user_id
      WHERE a.event_id = ? AND a.notified = 0 AND r.active = 1
      ORDER BY r.fname',
    'i',
    [$id]
);

$error   = '';
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $answer = strtolower(trim($_POST['seca'] ?? ''));

    if (!password_verify($answer, $admin['seca'])) {
        $error = 'Wrong answer to the security question. No messages were sent.';
    } else {
        foreach ($pending as $user) {
            $message   = invitation_message($event, $user);
            $results[] = ['user' => $user, 'message' => $message,
                          'result' => notify_user($user, $id, 'Event invitation: ' . $event['fname'], $message)];
            db_exec('UPDATE event_attendee SET notified = 1 WHERE event_id = ? AND user_id = ?', 'ii', [$id, $user['id']]);
        }
        $pending = [];
    }
}

$page_title = 'Send Notifications';
include 'includes/header.php';
?>
<h1>Notify attendees</h1>

<section class="card">
    <h2><?= e($event['fname']) ?></h2>
    <p><?= e($event['venue']) ?> &middot; <?= format_date($event['event_date']) ?> &middot;
       <?= format_time($event['start_time']) ?> - <?= format_time($event['end_time']) ?></p>
</section>

<?php if ($results): ?>
<section class="card">
    <h2>Messages sent</h2>
    <table>
        <tr><th>Attendee</th><th>Message</th><th>SMS</th><th>E-mail</th></tr>
        <?php foreach ($results as $r): ?>
        <tr>
            <td><?= e($r['user']['fname']) ?><br><small><?= e($r['user']['pn']) ?><br><?= e($r['user']['email']) ?></small></td>
            <td><?= e($r['message']) ?></td>
            <td><span class="badge badge-<?= $r['result']['sms']['status'] ?>"><?= $r['result']['sms']['status'] ?></span></td>
            <td><span class="badge badge-<?= $r['result']['email']['status'] ?>"><?= $r['result']['email']['status'] ?></span></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p><a class="btn" href="admin_events.php">Back to events</a>
       <a class="btn btn-secondary" href="admin_notifications.php">View notification log</a></p>
</section>
<?php elseif (!$pending): ?>
    <div class="alert alert-success">Everyone on this event has already been notified.</div>
    <p><a class="btn" href="admin_events.php">Back to events</a></p>
<?php else: ?>
<section class="card">
    <h2><?= count($pending) ?> attendee(s) will receive an SMS and e-mail</h2>
    <ul>
        <?php foreach ($pending as $u): ?>
            <li><?= e($u['fname']) ?> &ndash; <?= e($u['pn']) ?> &ndash; <?= e($u['email']) ?></li>
        <?php endforeach; ?>
    </ul>
    <p class="muted">Preview: <?= e(invitation_message($event, $pending[0])) ?></p>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>
        <label>Security question: <strong><?= e($admin['secq']) ?></strong>
            <input name="seca" autocomplete="off" required>
        </label>
        <button type="submit" class="btn">Confirm and send</button>
        <a href="admin_events.php" class="btn btn-secondary">Send later</a>
    </form>
</section>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
