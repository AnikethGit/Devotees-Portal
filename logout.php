<?php
// logout.php

require_once 'config.php';
require_once 'functions.php';

// Must start session before reading or destroying it
session_name(SESSION_NAME);
session_start();

// Get current user id before destroying session
$db = getDB();
$user_id = $_SESSION['user_id'] ?? null;

// Update logout time in login logs
if ($user_id) {
    $db->query("UPDATE login_logs SET logout_time = NOW() WHERE user_id = $user_id AND logout_time IS NULL ORDER BY login_time DESC LIMIT 1");
}

// Properly destroy session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

// Redirect to login
header('Location: login.php?logged_out=1');
exit;

?>