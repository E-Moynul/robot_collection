<?php
require_once 'includes/auth.php';

// Clear all session data and end the session
$_SESSION = [];
session_destroy();

header('Location: login.php');
exit;
