<?php
// Send reminders for events starting soon. The same function runs
// automatically from cron_reminders.php if a cron job is set up.
require_once 'includes/auth.php';
require_once 'includes/notify.php';
require_admin();

$hours  = (int)$config['reminder_hours'];
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $result = send_due_reminders($hours);
}

$due = db_all(
    "SELECT e.*, c.country AS venue,
            (SELECT COUNT(*) FROM event_attendee a WHERE a.event_id = e.id) AS attendees
       FROM event e JOIN country c ON c.id = e.venue_id
      WHERE e.status = 'Scheduled' AND e.reminder_sent = 0
        AND TIMESTAMP(e.event_date, e.start_time) BETWEEN NOW() AND NOW() + INTERVAL ? HOUR
      ORDER BY e.event_date, e.start_time",
    'i',
    [$hours]
);

$page_title = 'Reminders';
include 'includes/header.php';
?>
<h1>Event reminders</h1>

<?php if ($result): ?>
    <div class="alert alert-success">
        Reminders sent for <?= $result['events'] ?> event(s) to <?= $result['messages'] ?> attendee(s).
        <a href="admin_notifications.php">View notification log</a>
    </div>
<?php endif; ?>

<section class="card">
    <h2>Events starting in the next <?= $hours ?> hours (reminder not yet sent)</h2>
    <?php if (!$due): ?>
        <p class="muted">No reminders are due right now.</p>
    <?php else: ?>
        <table>
            <tr><th>Event</th><th>Venue</th><th>Date</th><th>Start</th><th>Attendees</th></tr>
            <?php foreach ($due as $ev): ?>
            <tr>
                <td><?= e($ev['fname']) ?></td><td><?= e($ev['venue']) ?></td>
                <td><?= format_date($ev['event_date']) ?></td><td><?= format_time($ev['start_time']) ?></td>
                <td><?= (int)$ev['attendees'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <form method="post">
            <?= csrf_field() ?>
            <button type="submit" class="btn">Send reminders now</button>
        </form>
    <?php endif; ?>
    <p class="muted">Automatic reminders: add a cron job that runs <code>php cron_reminders.php</code> every 30 minutes (see README).</p>
</section>
<?php include 'includes/footer.php'; ?>
