<?php
// Event details for an attendee, with a feedback form.
require_once 'includes/auth.php';
require_once 'includes/functions.php';
$user = require_login();

$id = (int)($_GET['id'] ?? 0);
$event = get_event($id);
// Users may only open events they are invited to (admins may open any).
$mine = db_one('SELECT * FROM event_attendee WHERE event_id = ? AND user_id = ?', 'ii', [$id, $user['id']]);
if (!$event || (!$mine && $user['role'] !== 'admin')) {
    flash('error', 'Event not found.');
    redirect('user_dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mine) {
    check_csrf();
    $feedback = trim($_POST['feedback'] ?? '');
    if ($feedback === '') {
        flash('error', 'Please type your feedback.');
    } else {
        db_exec('UPDATE event_attendee SET feedback = ?, feedback_date = NOW() WHERE id = ?', 'si', [$feedback, $mine['id']]);
        flash('success', 'Thank you. Your feedback has been sent to the administrator.');
    }
    redirect("user_event.php?id=$id");
}

$page_title = $event['fname'];
include 'includes/header.php';
?>
<h1><?= e($event['fname']) ?></h1>

<section class="card">
    <dl class="details">
        <dt>Status</dt><dd><span class="badge badge-<?= strtolower($event['status']) ?>"><?= e($event['status']) ?></span></dd>
        <dt>Venue</dt><dd><?= e($event['venue']) ?>, <?= e($event['location']) ?></dd>
        <dt>Date</dt><dd><?= format_date($event['event_date']) ?></dd>
        <dt>Time</dt><dd><?= format_time($event['start_time']) ?> - <?= format_time($event['end_time']) ?></dd>
        <dt>Contact</dt><dd><?= e($event['contact']) ?></dd>
        <dt>Details</dt><dd><?= nl2br(e($event['messageofEvent'])) ?></dd>
        <?php if ($event['feedback']): ?><dt>Remarks</dt><dd><?= nl2br(e($event['feedback'])) ?></dd><?php endif; ?>
    </dl>
</section>

<?php if ($mine): ?>
<section class="card">
    <h2>Your feedback</h2>
    <?php if ($mine['feedback']): ?>
        <p class="muted">Sent <?= e($mine['feedback_date']) ?>. You can update it below.</p>
    <?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <textarea name="feedback" rows="4" placeholder="Comments, apologies for absence, questions..." required><?= e($mine['feedback']) ?></textarea>
        <button class="btn">Send feedback</button>
    </form>
</section>
<?php endif; ?>
<p><a href="user_dashboard.php">&larr; Back to my events</a></p>
<?php include 'includes/footer.php'; ?>
