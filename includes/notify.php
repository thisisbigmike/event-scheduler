<?php
// SMS and e-mail notifications.
// Every message is written to the notification_log table, whether it was
// really sent, skipped because of demo mode, or failed (e.g. no internet).
require_once __DIR__ . '/functions.php';

// Convert 08031234567 to the international format 2348031234567 that gateways expect.
function to_international($phone)
{
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) === 11 && $phone[0] === '0') {
        return '234' . substr($phone, 1);
    }
    return $phone;
}

// Send one SMS through the configured Nigerian bulk SMS gateway.
// Returns ['status' => 'sent' | 'demo' | 'failed', 'response' => text]
function send_sms($phone, $message)
{
    global $config;

    if ($config['demo_mode'] || $config['sms_api_key'] === '') {
        return ['status' => 'demo', 'response' => 'Demo mode: SMS not sent (no API key or demo_mode is on).'];
    }

    $to = to_international($phone);

    if ($config['sms_provider'] === 'termii') {
        // Termii: https://developers.termii.com/messaging-api
        $url  = 'https://api.ng.termii.com/api/sms/send';
        $body = json_encode([
            'to'      => $to,
            'from'    => $config['sms_sender_id'],
            'sms'     => $message,
            'type'    => 'plain',
            'channel' => 'generic',
            'api_key' => $config['sms_api_key'],
        ]);
    } else {
        // BulkSMSNigeria: https://www.bulksmsnigeria.com/bulk-sms-api
        $url  = 'https://www.bulksmsnigeria.com/api/v1/sms/create';
        $body = json_encode([
            'api_token' => $config['sms_api_key'],
            'from'      => $config['sms_sender_id'],
            'to'        => $to,
            'body'      => $message,
        ]);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    $error    = curl_error($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['status' => 'failed', 'response' => 'No connection to SMS gateway: ' . $error];
    }
    if ($code < 200 || $code >= 300) {
        return ['status' => 'failed', 'response' => "Gateway returned HTTP $code: $response"];
    }
    return ['status' => 'sent', 'response' => $response];
}

// Send one e-mail. Returns the same shape as send_sms().
function send_email($to, $subject, $message)
{
    global $config;

    if ($config['demo_mode'] || $config['email_from'] === '') {
        return ['status' => 'demo', 'response' => 'Demo mode: e-mail not sent (no sender configured or demo_mode is on).'];
    }
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 'failed', 'response' => 'User has no valid e-mail address.'];
    }

    if ($config['email_method'] === 'mail') {
        $headers = 'From: ' . $config['email_from_name'] . ' <' . $config['email_from'] . ">\r\n"
                 . "Content-Type: text/plain; charset=UTF-8\r\n";
        $ok = @mail($to, $subject, $message, $headers);
        return $ok ? ['status' => 'sent', 'response' => 'Accepted by mail()']
                   : ['status' => 'failed', 'response' => 'mail() failed - is sendmail/postfix installed?'];
    }
    return smtp_send($to, $subject, $message);
}

// Minimal SMTP client (SSL on port 465, AUTH LOGIN) - enough for Gmail.
function smtp_send($to, $subject, $message)
{
    global $config;

    $socket = @stream_socket_client('ssl://' . $config['smtp_host'] . ':' . $config['smtp_port'], $errno, $errstr, 15);
    if (!$socket) {
        return ['status' => 'failed', 'response' => "No connection to mail server: $errstr"];
    }

    // Send a command and check that the server answers with the expected code.
    $talk = function ($command, $expect) use ($socket) {
        if ($command !== null) {
            fwrite($socket, $command . "\r\n");
        }
        $reply = '';
        while (($line = fgets($socket, 515)) !== false) {
            $reply .= $line;
            if (isset($line[3]) && $line[3] === ' ') break; // last line of a reply
        }
        if (substr($reply, 0, 3) !== (string)$expect) {
            throw new Exception(trim($reply));
        }
    };

    $from = $config['email_from'];
    $data = 'From: ' . $config['email_from_name'] . " <$from>\r\n"
          . "To: <$to>\r\n"
          . 'Subject: ' . $subject . "\r\n"
          . 'Date: ' . date('r') . "\r\n"
          . "MIME-Version: 1.0\r\n"
          . "Content-Type: text/plain; charset=UTF-8\r\n\r\n"
          . str_replace("\n.", "\n..", $message) . "\r\n.";

    try {
        $talk(null, 220);
        $talk('EHLO localhost', 250);
        $talk('AUTH LOGIN', 334);
        $talk(base64_encode($config['smtp_user']), 334);
        $talk(base64_encode($config['smtp_pass']), 235);
        $talk("MAIL FROM:<$from>", 250);
        $talk("RCPT TO:<$to>", 250);
        $talk('DATA', 354);
        $talk($data, 250);
        $talk('QUIT', 221);
        $result = ['status' => 'sent', 'response' => 'Accepted by ' . $config['smtp_host']];
    } catch (Exception $ex) {
        $result = ['status' => 'failed', 'response' => 'SMTP error: ' . $ex->getMessage()];
    }
    fclose($socket);
    return $result;
}

function log_notification($user_id, $event_id, $channel, $recipient, $message, $result)
{
    db_exec(
        'INSERT INTO notification_log (user_id, event_id, channel, recipient, message, status, response)
         VALUES (?, ?, ?, ?, ?, ?, ?)',
        'iisssss',
        [$user_id, $event_id, $channel, $recipient, $message, $result['status'], $result['response']]
    );
}

// Send the same message to a user by SMS and e-mail and log both.
// Returns the two results so the page can show what happened.
function notify_user($user, $event_id, $subject, $message)
{
    $sms = send_sms($user['pn'], $message);
    log_notification($user['id'], $event_id, 'sms', $user['pn'], $message, $sms);

    $email = send_email($user['email'], $subject, $message);
    log_notification($user['id'], $event_id, 'email', $user['email'], $message, $email);

    return ['sms' => $sms, 'email' => $email];
}

// Send reminders for every scheduled event that starts within the next
// $hours hours and has not had its reminder yet. Used by the admin
// "Send reminders" page and by cron_reminders.php.
// Returns how many events and people were reminded.
function send_due_reminders($hours)
{
    $now   = date('Y-m-d H:i:s');
    $until = date('Y-m-d H:i:s', time() + $hours * 3600);

    $events = db_all(
        "SELECT e.*, c.country AS venue
           FROM event e JOIN country c ON c.id = e.venue_id
          WHERE e.status = 'Scheduled' AND e.reminder_sent = 0
            AND TIMESTAMP(e.event_date, e.start_time) BETWEEN ? AND ?",
        'ss',
        [$now, $until]
    );

    $count = 0;
    foreach ($events as $event) {
        $attendees = db_all(
            'SELECT r.* FROM event_attendee a JOIN register r ON r.id = a.user_id
              WHERE a.event_id = ? AND r.active = 1',
            'i',
            [$event['id']]
        );
        foreach ($attendees as $user) {
            notify_user($user, $event['id'], 'Reminder: ' . $event['fname'], reminder_message($event, $user));
            $count++;
        }
        db_exec('UPDATE event SET reminder_sent = 1 WHERE id = ?', 'i', [$event['id']]);
    }
    return ['events' => count($events), 'messages' => $count];
}
