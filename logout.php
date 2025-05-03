<?php
// logout.php

// Start the session to access session variables
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Unset all session variables
$_SESSION = array();

// 2. If session cookies are used, delete the session cookie.
// Note: This will destroy the session, not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, // Set expiry in the past
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finally, destroy the session.
session_destroy();

// 4. Redirect the user to the login page
header("Location: login.php?logged_out=true"); // Add a parameter for potential feedback
exit(); // Stop script execution

?>

