<?php
// Reports: (1) events filtered by venue and date range, (2) attendees of an event.
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_admin();

$venue_id = (int)($_GET['venue_id'] ?? 0);
$from     = $_GET['from'] ?? date('Y-m-01');
$to       = $_GET['to'] ?? date('Y-m-d', strtotime('+1 month'));
$event_id = (int)($_GET['event'] ?? 0);

// ---- Report 1: events by venue / date ----
$sql = "SELECT e.*, c.country AS venue,
               (SELECT COUNT(*) FROM event_attendee a WHERE a.event_id = e.id) AS attendees
          FROM event e JOIN country c ON c.id = e.venue_id
         WHERE e.event_date BETWEEN ? AND ?";
$types  = 'ss';
$params = [$from, $to];
if ($venue_id) {
    $sql .= ' AND e.venue_id = ?';
    $types .= 'i';
    $params[] = $venue_id;
}
$sql .= ' ORDER BY e.event_date, e.start_time';
$events = db_all($sql, $types, $params);

// Summary: how many events each venue hosts in the period
$summary = db_all(
    "SELECT c.country AS venue, COUNT(e.id) AS total,
            SUM(e.status = 'Scheduled') AS scheduled, SUM(e.status = 'Completed') AS completed, SUM(e.status = 'Cancelled') AS cancelled
       FROM country c LEFT JOIN event e ON e.venue_id = c.id AND e.event_date BETWEEN ? AND ?
      GROUP BY c.id ORDER BY total DESC, c.country",
    'ss',
    [$from, $to]
);

// ---- Report 2: attendees of one event ----
$all_events = db_all('SELECT id, fname, event_date FROM event ORDER BY event_date DESC');
$selected   = $event_id ? get_event($event_id) : null;
$attendees  = $selected ? db_all(
    'SELECT r.fname, r.pn, r.email, r.staffid, a.notified, a.feedback, a.feedback_date
       FROM event_attendee a JOIN register r ON r.id = a.user_id
      WHERE a.event_id = ? ORDER BY r.fname',
    'i',
    [$event_id]
) : [];

$venues = db_all('SELECT id, country FROM country ORDER BY country');

$page_title = 'Reports';
include 'includes/header.php';
?>
<h1>Reports <button class="btn btn-secondary btn-small no-print" onclick="window.print()">Print</button></h1>

<section class="card">
    <h2>Events by venue and date</h2>
    <form method="get" class="grid-4 no-print">
        <label>Venue
            <select name="venue_id">
                <option value="0">All venues</option>
                <?php foreach ($venues as $v): ?>
                    <option value="<?= $v['id'] ?>" <?= $venue_id == $v['id'] ? 'selected' : '' ?>><?= e($v['country']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>From <input type="date" name="from" value="<?= e($from) ?>"></label>
        <label>To <input type="date" name="to" value="<?= e($to) ?>"></label>
        <div><button class="btn">Show report</button></div>
    </form>
    <p class="muted">Period: <?= format_date($from) ?> to <?= format_date($to) ?> &middot; <?= count($events) ?> event(s)</p>
    <table>
        <tr><th>Date</th><th>Time</th><th>Event</th><th>Venue</th><th>Status</th><th>Attendees</th></tr>
        <?php foreach ($events as $ev): ?>
        <tr>
            <td><?= format_date($ev['event_date']) ?></td>
            <td><?= format_time($ev['start_time']) ?> - <?= format_time($ev['end_time']) ?></td>
            <td><a href="?event=<?= $ev['id'] ?>&venue_id=<?= $venue_id ?>&from=<?= e($from) ?>&to=<?= e($to) ?>#attendees"><?= e($ev['fname']) ?></a></td>
            <td><?= e($ev['venue']) ?></td>
            <td><?= e($ev['status']) ?></td>
            <td><?= (int)$ev['attendees'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h3>Venue usage summary</h3>
    <table>
        <tr><th>Venue</th><th>Total</th><th>Scheduled</th><th>Completed</th><th>Cancelled</th></tr>
        <?php foreach ($summary as $s): ?>
        <tr><td><?= e($s['venue']) ?></td><td><?= (int)$s['total'] ?></td><td><?= (int)$s['scheduled'] ?></td>
            <td><?= (int)$s['completed'] ?></td><td><?= (int)$s['cancelled'] ?></td></tr>
        <?php endforeach; ?>
    </table>
</section>

<section class="card" id="attendees">
    <h2>Attendees report</h2>
    <form method="get" class="grid-4 no-print">
        <label class="span-2">Event
            <select name="event">
                <option value="0">-- choose event --</option>
                <?php foreach ($all_events as $ev): ?>
                    <option value="<?= $ev['id'] ?>" <?= $event_id == $ev['id'] ? 'selected' : '' ?>>
                        <?= e($ev['fname']) ?> (<?= e($ev['event_date']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <div><button class="btn">Show attendees</button></div>
    </form>

    <?php if ($selected): ?>
        <p><strong><?= e($selected['fname']) ?></strong> &middot; <?= e($selected['venue']) ?> &middot;
           <?= format_date($selected['event_date']) ?>, <?= format_time($selected['start_time']) ?> - <?= format_time($selected['end_time']) ?>
           &middot; <?= count($attendees) ?> attendee(s)</p>
        <table>
            <tr><th>#</th><th>Name</th><th>Staff ID</th><th>Phone</th><th>E-mail</th><th>Notified</th><th>Feedback</th></tr>
            <?php foreach ($attendees as $i => $a): ?>
            <tr>
                <td><?= $i + 1 ?></td><td><?= e($a['fname']) ?></td><td><?= e($a['staffid']) ?></td>
                <td><?= e($a['pn']) ?></td><td><?= e($a['email']) ?></td>
                <td><?= $a['notified'] ? 'Yes' : 'No' ?></td>
                <td><?= $a['feedback'] ? e($a['feedback']) . '<br><small class="muted">' . e($a['feedback_date']) . '</small>' : '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</section>
<?php include 'includes/footer.php'; ?>
