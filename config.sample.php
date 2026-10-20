<?php
// Copy this file to config.php and fill in your own values.
//   cp config.sample.php config.php
// config.php is listed in .gitignore so your API keys never get committed.

return [
    // ---------- Database ----------
    'db_host' => 'localhost',
    'db_user' => 'eventuser',
    'db_pass' => 'event123',
    'db_name' => 'eventdb',

    'timezone' => 'Africa/Lagos',
    'app_name' => 'IMT Event Scheduler',

    // ---------- Notifications ----------
    // true  = never contact a gateway; messages are only saved in the
    //         Notification Log and shown on screen (use this for the defence
    //         if there is no internet or no SMS credit).
    // false = really send SMS / e-mail using the settings below.
    'demo_mode' => true,

    // SMS gateway (Nigerian bulk SMS). Supported: 'termii', 'bulksmsnigeria'
    'sms_provider'  => 'termii',
    'sms_api_key'   => '',          // paste your API key / token here
    'sms_sender_id' => 'IMTEvents', // sender ID approved on your gateway account

    // E-mail: 'smtp' (e.g. Gmail with an App Password) or 'mail' (PHP mail())
    'email_method'    => 'smtp',
    'email_from'      => '',        // e.g. yourname@gmail.com
    'email_from_name' => 'IMT Event Scheduler',
    'smtp_host'       => 'smtp.gmail.com',
    'smtp_port'       => 465,       // 465 = SSL
    'smtp_user'       => '',
    'smtp_pass'       => '',        // Gmail App Password (not your normal password)

    // Reminders are sent for events starting within this many hours.
    'reminder_hours' => 24,
];
