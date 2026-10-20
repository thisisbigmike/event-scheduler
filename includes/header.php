<?php
// Shared page header and navigation. Set $page_title before including.
$user = current_user();
$page = basename($_SERVER['PHP_SELF']);

function nav_link($file, $label, $page)
{
    $class = $page === $file ? ' class="active"' : '';
    return "<a href=\"$file\"$class>$label</a>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title ?? 'Event Scheduler') ?> | <?= e($config['app_name']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
    <div class="brand">
        <span class="logo">IMT</span>
        <span><?= e($config['app_name']) ?></span>
    </div>
    <?php if ($user): ?>
        <nav>
            <?php if ($user['role'] === 'admin'): ?>
                <?= nav_link('admin_dashboard.php', 'Dashboard', $page) ?>
                <?= nav_link('admin_events.php', 'Events', $page) ?>
                <?= nav_link('admin_venues.php', 'Venues', $page) ?>
                <?= nav_link('admin_users.php', 'Users', $page) ?>
                <?= nav_link('admin_reports.php', 'Reports', $page) ?>
                <?= nav_link('admin_notifications.php', 'Notifications', $page) ?>
            <?php else: ?>
                <?= nav_link('user_dashboard.php', 'My Events', $page) ?>
            <?php endif; ?>
            <?= nav_link('profile.php', 'Profile', $page) ?>
            <a href="logout.php">Logout</a>
        </nav>
        <div class="who">
            <?php if ($user['photo']): ?><img src="uploads/<?= e($user['photo']) ?>" alt=""><?php endif; ?>
            <?= e($user['fname']) ?> <small>(<?= e($user['role']) ?>)</small>
        </div>
    <?php endif; ?>
</header>
<?php if ($config['demo_mode']): ?>
    <div class="demo-banner">DEMO MODE: SMS and e-mail are logged on the Notifications page instead of being sent.</div>
<?php endif; ?>
<main class="container">
<?php show_flash(); ?>
