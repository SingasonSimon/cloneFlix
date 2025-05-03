<?php
// admin/auth_check.php

// Start session if not already started (required to check session variables)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Check if user is logged in (session user_id exists)
// 2. Check if user role is set in session
// 3. Check if the role is actually 'admin'
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    // User is not logged in or is not an admin.
    // Redirect them to the main login page (or home page).
    // Optionally, you could redirect to a specific "access denied" page.
    header("Location: ../login.php?error=admin_required"); // Redirect back out of admin folder
    exit(); // Stop script execution
}

// If the script reaches this point, the user is logged in and has the 'admin' role.
// The rest of the admin page code can now execute safely.

// Optional: You might want to refresh user role from DB here in case it changed
// since login, but for simplicity, we'll rely on the session role for now.

?>
