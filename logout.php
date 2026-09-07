<?php
// Initialize session
session_start();

// Unset all session variables (including user email/name)
$_SESSION = array();

// If session cookie exists, destroy it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session data on the server
session_destroy();

// Redirect back to login page
header("Location: login.php");
exit;
?>