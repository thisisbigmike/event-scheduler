<?php
// AJAX endpoint used by the event form (assets/js/app.js) to warn the admin
// about a venue clash while they are still filling in the form.
// The final check is always repeated on the server when the form is saved.
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_admin();

header('Content-Type: application/json');

$venue_id = (int)($_GET['venue_id'] ?? 0);
$date     = $_GET['event_date'] ?? '';
$start    = $_GET['start_time'] ?? '';
$end      = $_GET['end_time'] ?? '';
$ignore   = (int)($_GET['event_id'] ?? 0);

if (!$venue_id || !$date || !$start || !$end) {
    echo json_encode(['ready' => false]);
    exit;
}
if ($end <= $start) {
    echo json_encode(['ready' => true, 'ok' => false, 'message' => 'End time must be later than start time.']);
    exit;
}

$clash = find_venue_clash($venue_id, $date, $start, $end, $ignore);
if ($clash) {
    echo json_encode([
        'ready'   => true,
        'ok'      => false,
        'message' => "Venue clash: {$clash['venue']} is booked for \"{$clash['fname']}\" ("
                   . format_time($clash['start_time']) . ' - ' . format_time($clash['end_time']) . ').',
    ]);
} else {
    echo json_encode(['ready' => true, 'ok' => true, 'message' => 'Venue is free at this time.']);
}
