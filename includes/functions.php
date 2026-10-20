<?php
// Event-related business rules: venue clash detection and event messages.

// ---------------------------------------------------------------------
// VENUE CLASH CHECK
// Two bookings overlap when:   existing.start < new.end  AND  existing.end > new.start
// Example: 10:00-12:00 is booked.
//   11:00-13:00 -> 10:00 < 13:00 and 12:00 > 11:00  -> CLASH
//   12:00-14:00 -> 10:00 < 14:00 but 12:00 > 12:00 is false -> no clash
// Cancelled events do not hold the venue. $ignore_id skips the event
// being edited so it does not clash with itself.
// Returns the clashing event row, or null if the venue is free.
// ---------------------------------------------------------------------
function find_venue_clash($venue_id, $date, $start, $end, $ignore_id = 0)
{
    return db_one(
        "SELECT e.id, e.fname, e.start_time, e.end_time, c.country AS venue
           FROM event e JOIN country c ON c.id = e.venue_id
          WHERE e.venue_id = ?
            AND e.event_date = ?
            AND e.status <> 'Cancelled'
            AND e.id <> ?
            AND e.start_time < ?
            AND e.end_time > ?
          LIMIT 1",
        'isiss',
        [$venue_id, $date, $ignore_id, $end, $start]
    );
}

// Validate the event form. Returns a list of error messages (empty = OK).
function validate_event($data, $is_new)
{
    $errors = [];
    if (trim($data['fname']) === '')          $errors[] = 'Event title is required.';
    if (trim($data['messageofEvent']) === '') $errors[] = 'Event description is required.';
    if (!preg_match('/^0[789][01]\d{8}$/', $data['contact'])) $errors[] = 'Contact must be an 11-digit Nigerian phone number, e.g. 08031234567.';
    if (!db_one('SELECT id FROM country WHERE id = ?', 'i', [$data['venue_id']])) $errors[] = 'Please choose a venue.';

    $date = DateTime::createFromFormat('Y-m-d', $data['event_date']);
    if (!$date || $date->format('Y-m-d') !== $data['event_date']) {
        $errors[] = 'Please enter a valid event date.';
    } elseif ($is_new && $data['event_date'] < date('Y-m-d')) {
        $errors[] = 'A new event cannot be scheduled in the past.';
    }
    if (!preg_match('/^\d{2}:\d{2}$/', $data['start_time']) || !preg_match('/^\d{2}:\d{2}$/', $data['end_time'])) {
        $errors[] = 'Please enter valid start and end times.';
    } elseif ($data['end_time'] <= $data['start_time']) {
        $errors[] = 'End time must be later than start time.';
    }
    if (!in_array($data['status'], ['Scheduled', 'Completed', 'Cancelled'], true)) $errors[] = 'Invalid status.';
    return $errors;
}

// Load an event together with its venue name.
function get_event($id)
{
    return db_one(
        'SELECT e.*, c.country AS venue, c.location
           FROM event e JOIN country c ON c.id = e.venue_id
          WHERE e.id = ?',
        'i',
        [$id]
    );
}

function format_time($time)
{
    return date('g:i A', strtotime($time));
}

function format_date($date)
{
    return date('D, j M Y', strtotime($date));
}

// Text of the "you have been added" message.
function invitation_message($event, $user)
{
    return "Dear {$user['fname']}, you have been scheduled for \"{$event['fname']}\" at {$event['venue']} on "
        . format_date($event['event_date']) . ', ' . format_time($event['start_time']) . ' - ' . format_time($event['end_time'])
        . ". Enquiries: {$event['contact']}. - IMT Enugu";
}

// Text of the reminder message.
function reminder_message($event, $user)
{
    return "REMINDER: Dear {$user['fname']}, \"{$event['fname']}\" holds at {$event['venue']} on "
        . format_date($event['event_date']) . ' at ' . format_time($event['start_time']) . '. - IMT Enugu';
}
