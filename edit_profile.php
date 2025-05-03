<?php
// edit_profile.php

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

// --- Initialize Variables ---
$userId = $_SESSION['user_id'];
$user = null;
$fetchError = null;
$updateFeedback = $_SESSION['update_feedback'] ?? null; // Check for feedback message
unset($_SESSION['update_feedback']); // Clear the message after displaying it once
$errors = []; // To hold validation errors during form submission

// --- Fetch Current User Data ---
try {
    $sql_fetch = "SELECT id, username, email, avatar_path FROM users WHERE id = :id";
    $stmt_fetch = $pdo->prepare($sql_fetch);
    $stmt_fetch->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt_fetch->execute();
    $user = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $fetchError = "Could not retrieve user profile information.";
        error_log("Edit Profile Error: User not found for ID {$userId} in session.");
    }
} catch (PDOException $e) {
    $fetchError = "Database error retrieving profile information.";
    error_log("Edit Profile DB Error (Fetch): " . $e->getMessage());
}

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile' && $user) {
    $newUsername = trim($_POST['username'] ?? '');
    $currentUsername = $user['username'];
    $newAvatarPath = $user['avatar_path']; // Keep current path by default

    // 1. Validate Username
    if (empty($newUsername)) {
        $errors['username'] = "Username cannot be empty.";
    } elseif (strlen($newUsername) < 3) {
        $errors['username'] = "Username must be at least 3 characters long.";
    } elseif ($newUsername !== $currentUsername) {
        try {
            $sql_check_user = "SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1";
            $stmt_check_user = $pdo->prepare($sql_check_user);
            $stmt_check_user->bindParam(':username', $newUsername, PDO::PARAM_STR);
            $stmt_check_user->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt_check_user->execute();
            if ($stmt_check_user->fetch()) {
                $errors['username'] = "Username already taken. Please choose another.";
            }
        } catch (PDOException $e) {
             $errors['database_check'] = "Error checking username availability.";
             error_log("Username check error: " . $e->getMessage());
        }
    }

    // 2. Handle File Upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $uploadDir = __DIR__ . '/public/uploads/avatars/';
        $webPathPrefix = 'public/uploads/avatars/';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 5 * 1024 * 1024; // 5 MB

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
             $errors['avatar'] = "Failed to create upload directory. Check server permissions.";
             error_log("Failed to create directory: " . $uploadDir);
        }

        if (!isset($errors['avatar'])) {
            if (!in_array($file['type'], $allowedTypes)) {
                $errors['avatar'] = "Invalid file type. Only JPG, PNG, GIF allowed.";
            } elseif ($file['size'] > $maxFileSize) {
                $errors['avatar'] = "File is too large. Maximum size is 5MB.";
            } else {
                $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
                if(!in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif'])) {
                    $errors['avatar'] = "Invalid file extension.";
                } else {
                    $uniqueFilename = uniqid('avatar_', true) . '.' . strtolower($fileExtension);
                    $destination = $uploadDir . $uniqueFilename;
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $newAvatarPath = $webPathPrefix . $uniqueFilename;
                        if ($user['avatar_path'] && $user['avatar_path'] !== $newAvatarPath && file_exists(__DIR__ . '/' . $user['avatar_path'])) {
                             @unlink(__DIR__ . '/' . $user['avatar_path']);
                        }
                    } else {
                        $errors['avatar'] = "Failed to upload avatar image. Check server configuration.";
                         error_log("File upload failed for user {$userId}. move_uploaded_file error. Destination: " . $destination);
                    }
                }
            }
        }
    } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
         $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => "File exceeds upload_max_filesize directive in php.ini.",
            UPLOAD_ERR_FORM_SIZE  => "File exceeds MAX_FILE_SIZE directive specified in HTML form.",
            UPLOAD_ERR_PARTIAL    => "File was only partially uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload.",
        ];
        $errorCode = $_FILES['avatar']['error'];
        $errors['avatar'] = $uploadErrors[$errorCode] ?? "An unknown error occurred during file upload (Error code: {$errorCode}).";
         error_log("File upload failed for user {$userId}. PHP Upload Error code: " . $errorCode);
    }


    // 3. Update Database if no errors
    if (empty($errors)) {
        try {
            $sql_update = "UPDATE users SET username = :username, avatar_path = :avatar_path WHERE id = :id";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->bindParam(':username', $newUsername, PDO::PARAM_STR);
            $stmt_update->bindParam(':avatar_path', $newAvatarPath, PDO::PARAM_STR);
            $stmt_update->bindParam(':id', $userId, PDO::PARAM_INT);

            if ($stmt_update->execute()) {
                 if ($newUsername !== $currentUsername) { $_SESSION['username'] = $newUsername; }
                 $_SESSION['update_feedback'] = ['type' => 'success', 'message' => 'Profile updated successfully.'];
                 header("Location: profile.php");
                 exit();
            } else {
                 $updateFeedback = ['type' => 'error', 'message' => 'Failed to update profile due to a database error.'];
            }
        } catch (PDOException $e) {
             error_log("Edit Profile DB Error (Update): " . $e->getMessage());
             $updateFeedback = ['type' => 'error', 'message' => 'An unexpected database error occurred during update.'];
        }
    } else {
        // Prepare feedback message if validation errors occurred
        $errorMessages = [];
        if(isset($errors['database_check'])) $errorMessages[] = $errors['database_check'];
        if(isset($errors['username'])) $errorMessages[] = "Username: " . $errors['username'];
        if(isset($errors['avatar'])) $errorMessages[] = "Avatar: " . $errors['avatar'];
        $updateFeedback = ['type' => 'error', 'message' => 'Please fix the errors: ' . implode(' ', $errorMessages)];
    }

    // Re-fetch user data if update failed or validation errors
     if ($updateFeedback || !empty($errors)) {
         try {
             $stmt_fetch->execute();
             $user = $stmt_fetch->fetch(PDO::FETCH_ASSOC);
         } catch (PDOException $e) {
              $fetchError = "Database error retrieving profile information after update attempt.";
              error_log("Edit Profile Re-Fetch DB Error: " . $e->getMessage());
              $user = null;
         }
     }
}


// Set page title
$pageTitle = "Edit Profile";

// --- Start HTML ---
require_once __DIR__ . '/includes/header.php';
?>
    <style type="text/tailwindcss">
        /* Inherit base styles from header */
        .profile-container { /* Use same container style */
            @apply container mx-auto p-6 md:p-10 mt-8 bg-gray-900 rounded-lg shadow-xl max-w-3xl;
        }
        .profile-heading {
            @apply text-2xl lg:text-3xl font-bold mb-6 text-white border-b border-gray-700 pb-4;
        }
        .profile-label { /* Use same label style */
            @apply block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2; /* Increased bottom margin */
        }
        /* Style for editable inputs */
        .profile-input {
             @apply shadow-sm appearance-none border border-gray-600 rounded w-full py-2 px-3 bg-gray-700 text-gray-100 leading-tight focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500;
        }
        .profile-input-error {
             @apply border-red-500 ring-1 ring-red-500; /* Add red border for errors */
        }
        /* Style for disabled inputs */
        .profile-input-disabled {
             @apply shadow-sm appearance-none border border-gray-700 rounded w-full py-2 px-3 bg-gray-800 text-gray-500 leading-tight focus:outline-none cursor-not-allowed;
        }
        /* Style for file input */
        .profile-file-input {
             @apply block w-full text-sm text-gray-400 border border-gray-600 rounded cursor-pointer bg-gray-700 focus:outline-none file:bg-red-600 file:border-0 file:text-white file:px-4 file:py-2 file:mr-3 hover:file:bg-red-700 transition-colors; /* Themed file button */
        }
        .profile-file-input-error {
             @apply border-red-500 ring-1 ring-red-500;
        }
        /* Style for buttons */
        .profile-save-button {
             @apply inline-block px-6 py-2 text-sm font-bold text-white bg-red-600 rounded shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-red-500 transition duration-200;
        }
         .profile-cancel-link {
             @apply inline-block align-baseline font-bold text-sm text-blue-400 hover:text-blue-300;
         }
         /* Feedback messages */
         .feedback-box { @apply px-4 py-3 rounded relative mb-6 border text-sm; }
         .feedback-success { @apply bg-green-900 bg-opacity-50 border-green-700 text-green-300; }
         .feedback-error { @apply bg-red-900 bg-opacity-50 border-red-700 text-red-300; }
         .error-box { @apply text-center text-red-400 bg-red-900 bg-opacity-50 p-4 rounded border border-red-700 my-4; }
         .field-error-text { @apply text-red-400 text-xs italic mt-2; }
    </style>

<main class="profile-container">
    <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
         <h1 class="profile-heading !mb-0 !border-b-0"><?php echo htmlspecialchars($pageTitle); ?></h1>
         <a href="profile.php" class="text-sm text-blue-400 hover:underline">&larr; Back to Profile</a>
    </div>


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
     <?php elseif (!$user): ?>
        <p class="text-gray-400 text-center">Could not load profile information to edit.</p>
     <?php else: ?>
        <form action="edit_profile.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_profile">

            <div class="mb-6 text-center">
                 <label class="profile-label text-center mb-2">Current Avatar</label>
                 <?php
                    $avatarDisplayUrl = (!empty($user['avatar_path']) && file_exists(__DIR__ . '/' . $user['avatar_path']))
                               ? $user['avatar_path']
                               : 'https://placehold.co/150x150/333333/888888?text=No+Avatar';
                 ?>
                 <img src="<?php echo $avatarDisplayUrl; ?>" alt="Current Avatar" class="w-32 h-32 rounded-full mx-auto mb-2 border-2 border-gray-600 object-cover shadow-lg">
            </div>

             <div class="mb-6">
                <label class="profile-label" for="avatar">
                    Change Avatar (Optional - JPG, PNG, GIF, max 5MB)
                </label>
                <input class="profile-file-input <?php echo isset($errors['avatar']) ? 'profile-file-input-error' : ''; ?>"
                       id="avatar" name="avatar" type="file" accept="image/jpeg, image/png, image/gif">
                 <?php if (isset($errors['avatar'])): ?>
                    <p class="field-error-text"><?php echo $errors['avatar']; ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label class="profile-label" for="username">
                    Username
                </label>
                <input class="profile-input <?php echo isset($errors['username']) ? 'profile-input-error' : ''; ?>"
                       id="username" name="username" type="text"
                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                 <?php if (isset($errors['username'])): ?>
                    <p class="field-error-text"><?php echo $errors['username']; ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-6">
                <label class="profile-label" for="email">
                    Email Address
                </label>
                <input class="profile-input-disabled"
                       id="email" type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                 <p class="text-xs text-gray-500 mt-1">Email cannot be changed.</p>
            </div>

            <div class="flex items-center justify-between mt-8">
                <button class="profile-save-button" type="submit">
                    Save Changes
                </button>
                <a href="profile.php" class="profile-cancel-link">
                    Cancel
                </a>
            </div>
        </form>
     <?php endif; ?>

</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
