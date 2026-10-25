<?php
// session.php - Session Management

session_name(SESSION_NAME);
session_start();

// Check if session exists
if (isset($_SESSION['user_id'])) {
    // Check for inactivity timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        // Session expired due to inactivity
        session_destroy();
        header('Location: ' . BASE_URL . 'login.php?session_expired=1');
        exit;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
} else {
    // No session exists - check if this is a protected page
    $current_page = basename($_SERVER['PHP_SELF']);
    $public_pages = ['login.php', 'register.php', 'index.php'];
    
    if (!in_array($current_page, $public_pages)) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

// Check user role for admin pages
if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

// Check user role for user pages
if (strpos($_SERVER['PHP_SELF'], '/user/') !== false) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

?>