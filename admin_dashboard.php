<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_admin();

$stats = [
    'Active users'     => db_one("SELECT COUNT(*) n FROM register WHERE active = 1 AND role = 'user'")['n'],
    'Awaiting activation' => db_one('SELECT COUNT(*) n FROM register WHERE active = 0')['n'],
    'Upcoming events'  => db_one("SELECT COUNT(*) n FROM event WHERE status = 'Scheduled' AND event_date >= CURDATE()")['n'],
    'Venues'           => db_one('SELECT COUNT(*) n FROM country')['n'],
];

$upcoming = db_all(
    "SELECT e.*, c.country AS venue,
            (SELECT COUNT(*) FROM event_attendee a WHERE a.event_id = e.id) AS attendees
       FROM event e JOIN country c ON c.id = e.venue_id
      WHERE e.status = 'Scheduled' AND e.event_date >= CURDATE()
      ORDER BY e.event_date, e.start_time
      LIMIT 10"
);

$pending = db_all('SELECT id, fname, pn, staffid, datereg FROM register WHERE active = 0 ORDER BY datereg DESC');

$page_title = 'Admin Dashboard';
include 'includes/header.php';
?>
<h1>Admin Dashboard</h1>

<div class="stats">
    <?php foreach ($stats as $label => $n): ?>
        <div class="stat"><strong><?= (int)$n ?></strong><span><?= e($label) ?></span></div>
    <?php endforeach; ?>
</div>

<div class="actions">
    <a class="btn" href="admin_event_form.php">+ Schedule new event</a>
    <a class="btn btn-secondary" href="admin_reminders.php">Send due reminders</a>
</div>

<section class="card">
    <h2>Upcoming events</h2>
    <?php if (!$upcoming): ?>
        <p class="muted">No upcoming events.</p>
    <?php else: ?>
    <table>
        <tr><th>Event</th><th>Venue</th><th>Date</th><th>Time</th><th>Attendees</th><th></th></tr>
        <?php foreach ($upcoming as $ev): ?>
        <tr>
            <td><?= e($ev['fname']) ?></td>
            <td><?= e($ev['venue']) ?></td>
            <td><?= format_date($ev['event_date']) ?></td>
            <td><?= format_time($ev['start_time']) ?> - <?= format_time($ev['end_time']) ?></td>
            <td><?= (int)$ev['attendees'] ?></td>
            <td><a href="admin_event_form.php?id=<?= $ev['id'] ?>">Edit</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</section>

<?php if ($pending): ?>
<section class="card">
    <h2>Accounts awaiting activation</h2>
    <table>
        <tr><th>Name</th><th>Phone</th><th>Staff ID</th><th>Registered</th></tr>
        <?php foreach ($pending as $p): ?>
        <tr><td><?= e($p['fname']) ?></td><td><?= e($p['pn']) ?></td><td><?= e($p['staffid']) ?></td><td><?= e($p['datereg']) ?></td></tr>
        <?php endforeach; ?>
    </table>
    <p><a href="admin_users.php">Manage users &rarr;</a></p>
</section>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
