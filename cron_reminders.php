<?php
// Command-line script for automatic reminders. Example cron entry (every 30 min):
//   */30 * * * * php /var/www/html/event-scheduler/cron_reminders.php
if (PHP_SAPI !== 'cli') {
    die('Run this script from the command line.');
}
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/notify.php';

$result = send_due_reminders((int)$config['reminder_hours']);
echo date('Y-m-d H:i') . " - reminders sent for {$result['events']} event(s), {$result['messages']} attendee(s)\n";
