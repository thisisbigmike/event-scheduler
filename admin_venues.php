<?php
// Manage venues (stored in the "country" table).
require_once 'includes/auth.php';
require_admin();

$errors = [];
$form = ['country' => '', 'location' => '', 'capacity' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    if (isset($_POST['delete'])) {
        $venue_id = (int)$_POST['delete'];
        if (db_one('SELECT id FROM event WHERE venue_id = ? LIMIT 1', 'i', [$venue_id])) {
            flash('error', 'This venue has events booked and cannot be deleted.');
        } else {
            db_exec('DELETE FROM country WHERE id = ?', 'i', [$venue_id]);
            flash('success', 'Venue deleted.');
        }
        redirect('admin_venues.php');
    }

    $form['country']  = trim($_POST['country'] ?? '');
    $form['location'] = trim($_POST['location'] ?? '');
    $form['capacity'] = (int)($_POST['capacity'] ?? 0);

    if ($form['country'] === '')  $errors[] = 'Venue name is required.';
    if ($form['location'] === '') $errors[] = 'Location is required.';
    if ($form['capacity'] < 1)    $errors[] = 'Capacity must be at least 1.';
    if (db_one('SELECT id FROM country WHERE country = ?', 's', [$form['country']])) $errors[] = 'A venue with this name already exists.';

    if (!$errors) {
        db_exec('INSERT INTO country (country, location, capacity) VALUES (?, ?, ?)', 'ssi',
                [$form['country'], $form['location'], $form['capacity']]);
        flash('success', 'Venue added.');
        redirect('admin_venues.php');
    }
}

$venues = db_all(
    "SELECT c.*, (SELECT COUNT(*) FROM event e WHERE e.venue_id = c.id AND e.status = 'Scheduled' AND e.event_date >= CURDATE()) AS upcoming
       FROM country c ORDER BY c.country"
);

$page_title = 'Venues';
include 'includes/header.php';
?>
<h1>Venues</h1>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<section class="card">
    <h2>Add venue</h2>
    <form method="post" class="grid-3">
        <?= csrf_field() ?>
        <label>Venue name <input name="country" value="<?= e($form['country']) ?>" required></label>
        <label>Location <input name="location" value="<?= e($form['location']) ?>" required></label>
        <label>Capacity <input type="number" name="capacity" min="1" value="<?= e($form['capacity']) ?>" required></label>
        <div><button class="btn">Add venue</button></div>
    </form>
</section>

<section class="card">
    <table>
        <tr><th>Venue</th><th>Location</th><th>Capacity</th><th>Upcoming bookings</th><th></th></tr>
        <?php foreach ($venues as $v): ?>
        <tr>
            <td><?= e($v['country']) ?></td><td><?= e($v['location']) ?></td>
            <td><?= (int)$v['capacity'] ?></td><td><?= (int)$v['upcoming'] ?></td>
            <td>
                <form method="post" class="inline-form">
                    <?= csrf_field() ?>
                    <button name="delete" value="<?= $v['id'] ?>" class="btn btn-small btn-danger confirm">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</section>
<?php include 'includes/footer.php'; ?>
