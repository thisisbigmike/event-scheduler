<?php
// Notification log: every SMS / e-mail the system has sent or tried to send.
require_once 'includes/auth.php';
require_admin();

$logs = db_all(
    'SELECT n.*, r.fname AS user_name, e.fname AS event_name
       FROM notification_log n
       LEFT JOIN register r ON r.id = n.user_id
       LEFT JOIN event e ON e.id = n.event_id
      ORDER BY n.id DESC
      LIMIT 200'
);

$page_title = 'Notifications';
include 'includes/header.php';
?>
<h1>Notification log</h1>
<p class="muted">
    <span class="badge badge-sent">sent</span> delivered to the gateway &middot;
    <span class="badge badge-demo">demo</span> demo mode, not sent &middot;
    <span class="badge badge-failed">failed</span> gateway or internet error (message kept here)
</p>
<section class="card">
    <?php if (!$logs): ?>
        <p class="muted">No notifications yet. Schedule an event and notify the attendees.</p>
    <?php else: ?>
    <table>
        <tr><th>Time</th><th>Channel</th><th>To</th><th>Event</th><th>Message</th><th>Status</th></tr>
        <?php foreach ($logs as $n): ?>
        <tr>
            <td class="nowrap"><?= e($n['created_at']) ?></td>
            <td><?= strtoupper(e($n['channel'])) ?></td>
            <td><?= e($n['user_name']) ?><br><small><?= e($n['recipient']) ?></small></td>
            <td><?= e($n['event_name']) ?></td>
            <td><?= e($n['message']) ?></td>
            <td><span class="badge badge-<?= e($n['status']) ?>" title="<?= e($n['response']) ?>"><?= e($n['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</section>
<?php include 'includes/footer.php'; ?>
