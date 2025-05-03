<?php
// admin/users.php

// 1. Admin Authentication Check
require_once __DIR__ . '/auth_check.php';

// 2. Include Database Connection
require_once __DIR__ . '/../config/database.php'; // Go up one level for config

// --- Handle Delete Request ---
$feedback = $_SESSION['delete_feedback'] ?? $_SESSION['edit_feedback'] ?? null; // Check for feedback from delete OR edit
unset($_SESSION['delete_feedback'], $_SESSION['edit_feedback']); // Clear messages after retrieving

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    // ... (Keep existing delete logic here) ...
    $userIdToDelete = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $loggedInAdminId = $_SESSION['user_id'] ?? null;

    if (!$userIdToDelete) {
        $feedback = ['type' => 'error', 'message' => 'Invalid user ID provided.'];
    } elseif ($userIdToDelete === $loggedInAdminId) {
        $feedback = ['type' => 'error', 'message' => 'You cannot delete your own admin account.'];
    } else {
        try {
            $sql_delete = "DELETE FROM users WHERE id = :id";
            $stmt_delete = $pdo->prepare($sql_delete);
            $stmt_delete->bindParam(':id', $userIdToDelete, PDO::PARAM_INT);
            if ($stmt_delete->execute()) {
                if ($stmt_delete->rowCount() > 0) {
                    $_SESSION['delete_feedback'] = ['type' => 'success', 'message' => 'User deleted successfully.'];
                } else {
                    $_SESSION['delete_feedback'] = ['type' => 'error', 'message' => 'User not found or already deleted.'];
                }
            } else {
                 $_SESSION['delete_feedback'] = ['type' => 'error', 'message' => 'Failed to delete user due to a database error.'];
            }
        } catch (PDOException $e) {
             error_log("Admin Delete User DB Error: " . $e->getMessage());
             $_SESSION['delete_feedback'] = ['type' => 'error', 'message' => 'An unexpected database error occurred during deletion.'];
        }
        header("Location: users.php");
        exit();
    }
}


// 3. Set Page Title
$pageTitle = "Manage Users";

// 4. Include Admin Header
require_once __DIR__ . '/includes/header.php';


// --- Fetch Users from Database ---
$users = [];
$fetchError = null;

try {
    $sql = "SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $fetchError = "Error fetching users: " . htmlspecialchars($e->getMessage());
    error_log("Admin Users Page DB Error: " . $e->getMessage());
}

// Get the logged-in admin's ID again
$loggedInAdminId = $_SESSION['user_id'] ?? null;

?>

<main class="admin-content">
    <h1 class="text-2xl font-semibold mb-6 border-b pb-2">Manage Users</h1>

    <?php if ($feedback): ?>
        <div class="px-4 py-3 rounded relative mb-6 <?php echo $feedback['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>" role="alert">
            <strong class="font-bold"><?php echo $feedback['type'] === 'success' ? 'Success!' : 'Error!'; ?></strong>
            <span class="block sm:inline"><?php echo htmlspecialchars($feedback['message']); ?></span>
        </div>
    <?php endif; ?>


    <?php if ($fetchError): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Database Error!</strong>
            <span class="block sm:inline"><?php echo $fetchError; ?></span>
        </div>
    <?php elseif (empty($users)): ?>
         <p class="text-gray-500">No users found in the database.</p>
    <?php else: ?>
        <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="py-3 px-6">ID</th>
                        <th scope="col" class="py-3 px-6">Username</th>
                        <th scope="col" class="py-3 px-6">Email</th>
                        <th scope="col" class="py-3 px-6">Role</th>
                        <th scope="col" class="py-3 px-6">Registered On</th>
                        <th scope="col" class="py-3 px-6">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <th scope="row" class="py-4 px-6 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                <?php echo $user['id']; ?>
                            </th>
                            <td class="py-4 px-6"> <?php echo htmlspecialchars($user['username']); ?> </td>
                            <td class="py-4 px-6"> <?php echo htmlspecialchars($user['email']); ?> </td>
                            <td class="py-4 px-6">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                                    <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <?php
                                    try {
                                        if (!empty($user['created_at'])) {
                                            $date = new DateTime($user['created_at']); echo $date->format('Y-m-d H:i:s');
                                        } else { echo 'N/A'; }
                                    } catch (Exception $e) { echo 'Invalid Date'; }
                                ?>
                            </td>
                            <td class="py-4 px-6 text-right space-x-2 whitespace-nowrap">
                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Edit</a>

                                <?php if ($user['id'] !== $loggedInAdminId): ?>
                                    <form action="users.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete the user \'<?php echo htmlspecialchars(addslashes($user['username'])); ?>\'? This action cannot be undone.');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="font-medium text-red-600 dark:text-red-500 hover:underline">Delete</button>
                                    </form>
                                <?php else: ?>
                                     <span class="text-gray-400 text-xs">(Cannot delete self)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php
// 5. Include Admin Footer
require_once __DIR__ . '/includes/footer.php';
?>