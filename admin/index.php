<?php
// admin/index.php

// 1. Admin Authentication Check
// Must be the very first thing required/included
require_once __DIR__ . '/auth_check.php';

// 2. Set Page Title
$pageTitle = "Admin Dashboard";

// 3. Include Admin Header
require_once __DIR__ . '/includes/header.php';

// 4. (Optional) Include Database connection if needed for stats
require_once __DIR__ . '/../config/database.php'; // Go up one level for config

// --- Fetch Stats (Example) ---
$userCount = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn(); // Fetches the first column of the first row
} catch (PDOException $e) {
    // Log error, but don't necessarily stop the page
    error_log("Admin Dashboard DB Error: " . $e->getMessage());
    // You could set an error message variable to display
}

?>

<main class="admin-content">
    <h1 class="text-2xl font-semibold mb-6 border-b pb-2">Dashboard</h1>

    <p class="mb-4">Welcome to the CloneFlix Admin Panel!</p>
    <p class="mb-6">From here you can manage users and site settings.</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-blue-100 border border-blue-300 p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold text-blue-800 mb-2">Total Registered Users</h2>
            <p class="text-3xl font-bold text-blue-900"><?php echo $userCount; ?></p>
        </div>

        <div class="bg-green-100 border border-green-300 p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold text-green-800 mb-2">Active Streams (Placeholder)</h2>
            <p class="text-3xl font-bold text-green-900">0</p> </div>

        <div class="bg-yellow-100 border border-yellow-300 p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold text-yellow-800 mb-2">Reported Issues (Placeholder)</h2>
            <p class="text-3xl font-bold text-yellow-900">0</p> </div>
    </div>

    <h2 class="text-xl font-semibold mb-4">Quick Links</h2>
    <div class="space-y-2">
        <p><a href="users.php" class="text-blue-600 hover:underline hover:text-blue-800">» Manage Users</a></p>
        <p><a href="../home.php" class="text-blue-600 hover:underline hover:text-blue-800">» View Main Site</a></p>
        </div>

</main>

<?php
// 5. Include Admin Footer
require_once __DIR__ . '/includes/footer.php';
?>

