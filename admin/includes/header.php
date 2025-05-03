<?php
// admin/includes/header.php

// Get admin username from session for display
// We know the user is an admin because auth_check.php was included before this
$adminUsername = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Admin Dashboard'; ?> - CloneFlix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style type="text/tailwindcss">
        /* Basic admin styles */
        body {
            @apply bg-gray-100 text-gray-800;
        }
        .admin-nav-link {
             @apply px-3 py-2 rounded hover:bg-gray-700 hover:text-white transition-colors;
        }
         .admin-nav-link-active {
             @apply bg-gray-900 text-white px-3 py-2 rounded;
        }
         .admin-content {
             @apply container mx-auto p-4 md:p-8 mt-6 bg-white rounded shadow;
         }
         .admin-logout-button {
              @apply inline-block px-4 py-2 text-sm font-medium text-white bg-red-600 rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-red-500 transition duration-200;
         }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <nav class="bg-gray-800 text-gray-300 shadow-md p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <a href="../home.php" class="text-xl font-bold text-red-600 uppercase tracking-wider">CloneFlix</a>
                <span class="text-gray-500">|</span>
                <span class="font-semibold text-white">Admin Panel</span>
                 <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'admin-nav-link-active' : 'admin-nav-link'; ?>">Dashboard</a>
                 <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'admin-nav-link-active' : 'admin-nav-link'; ?>">Users</a>
                 </div>

            <div class="flex items-center space-x-4">
                 <span class="hidden md:inline">Welcome, <?php echo $adminUsername; ?>!</span>
                 <a href="../logout.php" class="admin-logout-button">Logout</a>
            </div>
        </div>
    </nav>

    <div class="flex-grow">


