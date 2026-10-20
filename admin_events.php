<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_admin();

$events = db_all(
    "SELECT e.*, c.country AS venue,
            (SELECT COUNT(*) FROM event_attendee a WHERE a.event_id = e.id) AS attendees,
            (SELECT COUNT(*) FROM event_attendee a WHERE a.event_id = e.id AND a.notified = 0) AS pending
       FROM event e JOIN country c ON c.id = e.venue_id
      ORDER BY e.event_date DESC, e.start_time"
);

$page_title = 'Events';
include 'includes/header.php';
?>
<h1>Events</h1>
<div class="actions"><a class="btn" href="admin_event_form.php">+ Schedule new event</a></div>

<section class="card">
    <table>
        <tr><th>Event</th><th>Venue</th><th>Date</th><th>Time</th><th>Status</th><th>Attendees</th><th></th></tr>
        <?php foreach ($events as $ev): ?>
        <tr>
            <td><?= e($ev['fname']) ?></td>
            <td><?= e($ev['venue']) ?></td>
            <td><?= format_date($ev['event_date']) ?></td>
            <td><?= format_time($ev['start_time']) ?> - <?= format_time($ev['end_time']) ?></td>
            <td><span class="badge badge-<?= strtolower($ev['status']) ?>"><?= e($ev['status']) ?></span></td>
            <td><?= (int)$ev['attendees'] ?>
                <?php if ($ev['pending'] && $ev['status'] === 'Scheduled'): ?>
                    <a class="badge badge-warn" href="admin_notify.php?id=<?= $ev['id'] ?>"><?= (int)$ev['pending'] ?> not notified</a>
                <?php endif; ?>
            </td>
            <td class="nowrap">
                <a href="admin_event_form.php?id=<?= $ev['id'] ?>">Edit</a> |
                <a href="admin_reports.php?event=<?= $ev['id'] ?>#attendees">Attendees</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</section>
<?php include 'includes/footer.php'; ?>
