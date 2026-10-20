<?php
// A user's own events: upcoming and past.
require_once 'includes/auth.php';
require_once 'includes/functions.php';
$user = require_login();

$events = db_all(
    "SELECT e.*, c.country AS venue, c.location, a.feedback AS my_feedback
       FROM event_attendee a
       JOIN event e   ON e.id = a.event_id
       JOIN country c ON c.id = e.venue_id
      WHERE a.user_id = ?
      ORDER BY e.event_date DESC, e.start_time",
    'i',
    [$user['id']]
);

$today = date('Y-m-d');
$upcoming = array_filter($events, fn($ev) => $ev['event_date'] >= $today && $ev['status'] === 'Scheduled');
$past     = array_filter($events, fn($ev) => !($ev['event_date'] >= $today && $ev['status'] === 'Scheduled'));
// Soonest event first
usort($upcoming, fn($a, $b) => strcmp($a['event_date'] . $a['start_time'], $b['event_date'] . $b['start_time']));

$page_title = 'My Events';
include 'includes/header.php';
?>
<h1>Welcome, <?= e($user['fname']) ?></h1>

<section class="card">
    <h2>Upcoming events</h2>
    <?php if (!$upcoming): ?>
        <p class="muted">You have no upcoming events.</p>
    <?php endif; ?>
    <div class="event-cards">
        <?php foreach ($upcoming as $ev): ?>
            <a class="event-card" href="user_event.php?id=<?= $ev['id'] ?>">
                <span class="date"><?= date('j', strtotime($ev['event_date'])) ?><small><?= date('M', strtotime($ev['event_date'])) ?></small></span>
                <span>
                    <strong><?= e($ev['fname']) ?></strong><br>
                    <?= e($ev['venue']) ?> &middot; <?= format_time($ev['start_time']) ?> - <?= format_time($ev['end_time']) ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="card">
    <h2>Past and cancelled events</h2>
    <?php if (!$past): ?>
        <p class="muted">Nothing here yet.</p>
    <?php else: ?>
    <table>
        <tr><th>Event</th><th>Venue</th><th>Date</th><th>Status</th><th>Your feedback</th></tr>
        <?php foreach ($past as $ev): ?>
        <tr>
            <td><a href="user_event.php?id=<?= $ev['id'] ?>"><?= e($ev['fname']) ?></a></td>
            <td><?= e($ev['venue']) ?></td>
            <td><?= format_date($ev['event_date']) ?></td>
            <td><span class="badge badge-<?= strtolower($ev['status']) ?>"><?= e($ev['status']) ?></span></td>
            <td><?= $ev['my_feedback'] ? e($ev['my_feedback']) : '<a href="user_event.php?id=' . $ev['id'] . '">Give feedback</a>' ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</section>
<?php include 'includes/footer.php'; ?>
