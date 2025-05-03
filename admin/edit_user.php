<?php
// admin/edit_user.php

// 1. Admin Authentication Check
require_once __DIR__ . '/auth_check.php';

// 2. Include Database Connection
require_once __DIR__ . '/../config/database.php';

// 3. Get User ID from URL
$userIdToEdit = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$userIdToEdit) {
    // No valid ID provided, redirect back
    $_SESSION['edit_feedback'] = ['type' => 'error', 'message' => 'Invalid or missing user ID.'];
    header("Location: users.php");
    exit();
}

// Get the logged-in admin's ID for checks
$loggedInAdminId = $_SESSION['user_id'] ?? null;

// Initialize variables
$user = null;
$fetchError = null;
$updateFeedback = null;

// --- Fetch User Data ---
try {
    $sql_fetch = "SELECT id, username, email, role FROM users WHERE id = :id";
    $stmt_fetch = $pdo->prepare($sql_fetch);
    $stmt_fetch->bindParam(':id', $userIdToEdit, PDO::PARAM_INT);
    $stmt_fetch->execute();
    $user = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // User not found
        $_SESSION['edit_feedback'] = ['type' => 'error', 'message' => 'User not found.'];
        header("Location: users.php");
        exit();
    }
} catch (PDOException $e) {
    $fetchError = "Error fetching user details: " . htmlspecialchars($e->getMessage());
    error_log("Admin Edit User Fetch DB Error: " . $e->getMessage());
    // Display error on this page instead of redirecting immediately
}


// --- Handle Form Submission (Update Role) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role') {
    $newRole = $_POST['role'] ?? null;

    // Validate the new role
    if (!$newRole || !in_array($newRole, ['user', 'admin'])) {
        $updateFeedback = ['type' => 'error', 'message' => 'Invalid role selected.'];
    }
    // Prevent admin from changing their own role
    elseif ($userIdToEdit === $loggedInAdminId) {
         $updateFeedback = ['type' => 'error', 'message' => 'You cannot change your own role.'];
    }
    // Prevent changing role if only one admin exists (optional safety check)
    // elseif ($user['role'] === 'admin' && $newRole === 'user') {
    //     // Add logic here to count admins and prevent demoting the last one
    //     // $adminCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    //     // $adminCount = $adminCountStmt->fetchColumn();
    //     // if ($adminCount <= 1) {
    //     //     $updateFeedback = ['type' => 'error', 'message' => 'Cannot remove the last administrator role.'];
    //     // }
    // }

    // Proceed with update if no errors so far
    if ($updateFeedback === null) {
        try {
            $sql_update = "UPDATE users SET role = :role WHERE id = :id";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->bindParam(':role', $newRole, PDO::PARAM_STR);
            $stmt_update->bindParam(':id', $userIdToEdit, PDO::PARAM_INT);

            if ($stmt_update->execute()) {
                $_SESSION['edit_feedback'] = ['type' => 'success', 'message' => 'User role updated successfully.'];
                header("Location: users.php"); // Redirect back to user list
                exit();
            } else {
                $updateFeedback = ['type' => 'error', 'message' => 'Failed to update user role due to a database error.'];
            }
        } catch (PDOException $e) {
            error_log("Admin Update Role DB Error: " . $e->getMessage());
            $updateFeedback = ['type' => 'error', 'message' => 'An unexpected database error occurred during update.'];
        }
    }
    // If update failed, $updateFeedback is set and will be displayed below
    // Refresh user data in case role change failed mid-way (unlikely but possible)
    if ($updateFeedback) {
         $stmt_fetch->execute(); // Re-fetch current data
         $user = $stmt_fetch->fetch(PDO::FETCH_ASSOC);
    }
}


// 5. Set Page Title (after fetching user)
$pageTitle = $user ? "Edit User: " . htmlspecialchars($user['username']) : "Edit User";

// 6. Include Admin Header
require_once __DIR__ . '/includes/header.php';

?>

<main class="admin-content">
    <div class="flex justify-between items-center mb-6 border-b pb-2">
        <h1 class="text-2xl font-semibold">
             <?php echo $pageTitle; ?>
        </h1>
        <a href="users.php" class="text-sm text-blue-600 hover:underline">&larr; Back to User List</a>
    </div>

    <?php if ($updateFeedback): ?>
        <div class="px-4 py-3 rounded relative mb-6 <?php echo $updateFeedback['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>" role="alert">
            <strong class="font-bold"><?php echo $updateFeedback['type'] === 'success' ? 'Success!' : 'Error!'; ?></strong>
            <span class="block sm:inline"><?php echo htmlspecialchars($updateFeedback['message']); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($fetchError): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Database Error!</strong>
            <span class="block sm:inline"><?php echo $fetchError; ?></span>
        </div>
     <?php elseif (!$user): ?>
        <p class="text-red-600">User data could not be loaded.</p>
     <?php else: ?>
        <form action="edit_user.php?id=<?php echo $user['id']; ?>" method="POST">
            <input type="hidden" name="action" value="update_role">

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                    Username
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-200" id="username" type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                 <p class="text-xs text-gray-500 mt-1">Username cannot be changed.</p>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                    Email
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-200" id="email" type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                 <p class="text-xs text-gray-500 mt-1">Email cannot be changed.</p>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="role">
                    Role
                </label>
                <select id="role" name="role" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500 <?php echo ($user['id'] === $loggedInAdminId) ? 'bg-gray-200 cursor-not-allowed' : ''; ?>"
                    <?php echo ($user['id'] === $loggedInAdminId) ? 'disabled' : ''; // Disable changing own role ?>>
                    <option value="user" <?php echo ($user['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                    <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
                 <?php if ($user['id'] === $loggedInAdminId): ?>
                     <p class="text-xs text-red-500 mt-1">You cannot change your own role.</p>
                 <?php endif; ?>
            </div>

            <div class="flex items-center justify-between">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline disabled:opacity-50 disabled:cursor-not-allowed" type="submit"
                    <?php echo ($user['id'] === $loggedInAdminId) ? 'disabled' : ''; // Disable button if editing self ?>>
                    Update Role
                </button>
                <a href="users.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                    Cancel
                </a>
            </div>
        </form>
     <?php endif; ?>

</main>

<?php
// 7. Include Admin Footer
require_once __DIR__ . '/includes/footer.php';
?>