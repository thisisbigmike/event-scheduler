<?php
require_once 'includes/auth.php';

$_SESSION = [];
session_destroy();
session_start();
flash('success', 'You have been logged out.');
redirect('index.php');
