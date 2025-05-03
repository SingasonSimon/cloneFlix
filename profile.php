<?php
// profile.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// --- Include Database Connection ---
require_once __DIR__ . '/config/database.php';

// --- Fetch User Data ---
$userId = $_SESSION['user_id'];
$user = null;
$fetchError = null;
$updateFeedback = $_SESSION['update_feedback'] ?? null; // Check for feedback message from edit page
unset($_SESSION['update_feedback']); // Clear the message after displaying it once


try {
    // Fetch avatar_path as well
    $sql = "SELECT id, username, email, role, created_at, avatar_path FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $fetchError = "Could not retrieve user profile information.";
        error_log("Profile Error: User not found for ID {$userId} in session.");
    }
} catch (PDOException $e) {
    $fetchError = "Database error retrieving profile information.";
    error_log("Profile DB Error: " . $e->getMessage());
}

// Set page title
$pageTitle = "My Profile";

// --- Start HTML ---
// Use the main site header/footer located in the root includes directory
require_once __DIR__ . '/includes/header.php';
?>
    <style type="text/tailwindcss">
        /* Inherit base styles from header */
        .profile-container {
            /* Slightly lighter background than pure black for contrast */
            @apply container mx-auto p-6 md:p-10 mt-8 bg-gray-900 rounded-lg shadow-xl max-w-3xl;
        }
        .profile-heading {
            @apply text-2xl lg:text-3xl font-bold mb-6 text-white border-b border-gray-700 pb-4;
        }
        .profile-label {
            /* Lighter label color */
            @apply block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1;
        }
        .profile-value {
            /* More padding, slightly lighter background */
            @apply text-lg text-white mb-4 p-3 bg-gray-800 border border-gray-700 rounded w-full;
        }
        .profile-edit-button {
            /* Match site's red theme, add padding and hover effect */
             @apply inline-block px-6 py-2 text-sm font-bold text-white bg-red-600 rounded shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-red-500 transition duration-200;
         }
         .feedback-box { /* Common style for feedback messages */
             @apply px-4 py-3 rounded relative mb-6 border text-sm;
         }
         .feedback-success {
             @apply bg-green-900 bg-opacity-50 border-green-700 text-green-300;
         }
         .feedback-error {
              @apply bg-red-900 bg-opacity-50 border-red-700 text-red-300;
         }
         .error-box { /* For database/fetch errors */
              @apply text-center text-red-400 bg-red-900 bg-opacity-50 p-4 rounded border border-red-700 my-4;
         }
    </style>

<main class="profile-container">
    <h1 class="profile-heading"><?php echo htmlspecialchars($pageTitle); ?></h1>

     <?php if ($updateFeedback): ?>
        <div class="feedback-box <?php echo $updateFeedback['type'] === 'success' ? 'feedback-success' : 'feedback-error'; ?>" role="alert">
            <strong class="font-bold"><?php echo $updateFeedback['type'] === 'success' ? 'Success!' : 'Error!'; ?></strong>
            <span class="block sm:inline"><?php echo htmlspecialchars($updateFeedback['message']); ?></span>
        </div>
    <?php endif; ?>


    <?php if ($fetchError): ?>
        <div class="error-box">
            <p><strong>Error:</strong> <?php echo htmlspecialchars($fetchError); ?></p>
        </div>
    <?php elseif ($user): ?>
        <div class="flex flex-col md:flex-row items-center md:items-start gap-6 md:gap-10">
             <div class="flex-shrink-0 w-full md:w-auto flex flex-col items-center">
                 <?php
                    $avatarUrl = (!empty($user['avatar_path']) && file_exists(__DIR__ . '/' . $user['avatar_path']))
                               ? $user['avatar_path']
                               : 'https://placehold.co/150x150/333333/888888?text=No+Avatar';
                 ?>
                 <img src="<?php echo $avatarUrl; ?>" alt="Profile Avatar" class="w-36 h-36 md:w-48 md:h-48 rounded-full border-4 border-gray-700 object-cover shadow-lg mb-4">
                 <div class="w-full text-center md:hidden"> <?php // Centered on mobile, hidden on md+ ?>
                     <a href="edit_profile.php" class="profile-edit-button mt-2">Edit Profile</a>
                 </div>
             </div>

             <div class="flex-grow text-left w-full">
                 <div class="mb-5"> <?php // Increased margin-bottom ?>
                    <label class="profile-label">Username</label>
                    <p class="profile-value"><?php echo htmlspecialchars($user['username']); ?></p>
                </div>
                <div class="mb-5">
                    <label class="profile-label">Email Address</label>
                    <p class="profile-value"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div class="mb-5">
                    <label class="profile-label">Account Role</label>
                    <p class="profile-value"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></p>
                </div>
                <div class="mb-5">
                    <label class="profile-label">Member Since</label>
                    <p class="profile-value">
                         <?php
                            try {
                                if (!empty($user['created_at'])) {
                                    $date = new DateTime($user['created_at']); echo $date->format('F j, Y');
                                } else { echo 'N/A'; }
                            } catch (Exception $e) { echo 'Invalid Date'; }
                        ?>
                    </p>
                </div>
                 <div class="mt-6 text-right hidden md:block">
                     <a href="edit_profile.php" class="profile-edit-button">Edit Profile</a>
                 </div>
            </div>
        </div>


    <?php else: ?>
         <p class="text-gray-400 text-center">Could not load profile information.</p>
    <?php endif; ?>

</main>

<?php
// Include a standard footer
require_once __DIR__ . '/includes/footer.php';
?>
