<?php
// Create or edit an event and choose its attendees.
// The venue clash check runs on the server before anything is saved.
require_once 'includes/auth.php';
require_once 'includes/functions.php';
$admin = require_admin();

$id     = (int)($_GET['id'] ?? 0);
$is_new = $id === 0;
$errors = [];
$clash  = null;

if ($is_new) {
    $form = ['fname' => '', 'messageofEvent' => '', 'feedback' => '', 'status' => 'Scheduled',
             'contact' => $admin['pn'], 'venue_id' => 0, 'event_date' => '', 'start_time' => '', 'end_time' => ''];
    $chosen = [];
} else {
    $form = get_event($id);
    if (!$form) {
        flash('error', 'Event not found.');
        redirect('admin_events.php');
    }
    $form['start_time'] = substr($form['start_time'], 0, 5);
    $form['end_time']   = substr($form['end_time'], 0, 5);
    $chosen = array_column(db_all('SELECT user_id FROM event_attendee WHERE event_id = ?', 'i', [$id]), 'user_id');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $old = $form;
    foreach (['fname', 'messageofEvent', 'feedback', 'status', 'contact', 'event_date', 'start_time', 'end_time'] as $key) {
        $form[$key] = trim($_POST[$key] ?? '');
    }
    $form['venue_id'] = (int)($_POST['venue_id'] ?? 0);
    $chosen = array_map('intval', $_POST['attendees'] ?? []);

    $errors = validate_event($form, $is_new);
    if (!$chosen) $errors[] = 'Please select at least one attendee.';

    if (!$errors) {
        // Lock the venue row so two admins cannot book the same slot at the same moment.
        $conn->begin_transaction();
        db_query('SELECT id FROM country WHERE id = ? FOR UPDATE', 'i', [$form['venue_id']]);

        // A cancelled event does not need a free venue.
        if ($form['status'] !== 'Cancelled') {
            $clash = find_venue_clash($form['venue_id'], $form['event_date'], $form['start_time'], $form['end_time'], $id);
        }

        if ($clash) {
            $conn->rollback();
            $errors[] = "VENUE CLASH: {$clash['venue']} is already booked for \"{$clash['fname']}\" from "
                      . format_time($clash['start_time']) . ' to ' . format_time($clash['end_time'])
                      . ' on that day. Choose another venue or time.';
        } else {
            $values = [$form['fname'], $form['messageofEvent'], $form['feedback'], $form['status'], $form['contact'],
                       $form['venue_id'], $form['event_date'], $form['start_time'], $form['end_time']];
            if ($is_new) {
                $id = db_exec(
                    'INSERT INTO event (fname, messageofEvent, feedback, status, contact, venue_id, event_date, start_time, end_time, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    'sssssisssi',
                    array_merge($values, [$admin['id']])
                );
            } else {
                db_exec(
                    'UPDATE event SET fname = ?, messageofEvent = ?, feedback = ?, status = ?, contact = ?,
                            venue_id = ?, event_date = ?, start_time = ?, end_time = ?
                      WHERE id = ?',
                    'sssssisssi',
                    array_merge($values, [$id])
                );
                // If the venue, date or time changed, everyone must be told again.
                $moved = $old['venue_id'] != $form['venue_id'] || $old['event_date'] !== $form['event_date']
                      || $old['start_time'] !== $form['start_time'] || $old['end_time'] !== $form['end_time'];
                if ($moved) {
                    db_exec('UPDATE event_attendee SET notified = 0 WHERE event_id = ?', 'i', [$id]);
                    db_exec('UPDATE event SET reminder_sent = 0 WHERE id = ?', 'i', [$id]);
                }
                // Remove people who were un-ticked.
                $placeholders = implode(',', array_fill(0, count($chosen), '?'));
                db_exec("DELETE FROM event_attendee WHERE event_id = ? AND user_id NOT IN ($placeholders)",
                        str_repeat('i', count($chosen) + 1), array_merge([$id], $chosen));
            }
            // Add newly ticked people (INSERT IGNORE skips people already on the list).
            foreach ($chosen as $user_id) {
                db_exec('INSERT IGNORE INTO event_attendee (event_id, user_id) VALUES (?, ?)', 'ii', [$id, $user_id]);
            }
            $conn->commit();

            flash('success', $is_new ? 'Event scheduled. The venue was free.' : 'Event updated.');
            $pending = db_one('SELECT COUNT(*) n FROM event_attendee WHERE event_id = ? AND notified = 0', 'i', [$id])['n'];
            redirect($pending && $form['status'] === 'Scheduled' ? "admin_notify.php?id=$id" : 'admin_events.php');
        }
    }
}

$venues = db_all('SELECT * FROM country ORDER BY country');
$users  = db_all("SELECT id, fname, pn, staffid FROM register WHERE active = 1 AND role = 'user' ORDER BY fname");

$page_title = $is_new ? 'Schedule Event' : 'Edit Event';
include 'includes/header.php';
?>
<h1><?= $is_new ? 'Schedule a new event' : 'Edit event' ?></h1>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="card grid-2" id="event-form" data-event-id="<?= $id ?>">
    <?= csrf_field() ?>
    <label class="span-2">Event title
        <input name="fname" value="<?= e($form['fname']) ?>" required>
    </label>
    <label class="span-2">Description / message of event
        <textarea name="messageofEvent" rows="3" required><?= e($form['messageofEvent']) ?></textarea>
    </label>
    <label>Venue
        <select name="venue_id" id="venue_id" required>
            <option value="">-- select venue --</option>
            <?php foreach ($venues as $v): ?>
                <option value="<?= $v['id'] ?>" <?= $form['venue_id'] == $v['id'] ? 'selected' : '' ?>>
                    <?= e($v['country']) ?> (<?= e($v['location']) ?>, <?= (int)$v['capacity'] ?> seats)
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Date
        <input type="date" name="event_date" id="event_date" value="<?= e($form['event_date']) ?>"
               <?= $is_new ? 'min="' . date('Y-m-d') . '"' : '' ?> required>
    </label>
    <label>Start time
        <input type="time" name="start_time" id="start_time" value="<?= e($form['start_time']) ?>" required>
    </label>
    <label>End time
        <input type="time" name="end_time" id="end_time" value="<?= e($form['end_time']) ?>" required>
    </label>
    <div class="span-2" id="clash-status" aria-live="polite"></div>
    <label>Contact phone
        <input type="tel" name="contact" value="<?= e($form['contact']) ?>" required>
    </label>
    <label>Status
        <select name="status" id="status">
            <?php foreach (['Scheduled', 'Completed', 'Cancelled'] as $s): ?>
                <option <?= $form['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="span-2">Admin remarks (optional)
        <textarea name="feedback" rows="2"><?= e($form['feedback']) ?></textarea>
    </label>

    <fieldset class="span-2">
        <legend>Attendees <label class="inline"><input type="checkbox" id="select-all"> select all</label></legend>
        <div class="attendee-list">
            <?php foreach ($users as $u): ?>
                <label class="inline">
                    <input type="checkbox" name="attendees[]" value="<?= $u['id'] ?>" class="attendee"
                        <?= in_array($u['id'], $chosen) ? 'checked' : '' ?>>
                    <?= e($u['fname']) ?> <small class="muted"><?= e($u['pn']) ?></small>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <div class="span-2">
        <button type="submit" class="btn">Save event</button>
        <a href="admin_events.php" class="btn btn-secondary">Back</a>
    </div>
</form>
<?php include 'includes/footer.php'; ?>
