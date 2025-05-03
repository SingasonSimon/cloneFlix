<?php
// login.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- DEBUG: Check if session exists before potential redirect ---
// echo "DEBUG: Session user_id is: " . ($_SESSION['user_id'] ?? 'Not Set') . "<br>";

// If user is already logged in, redirect to home page
if (isset($_SESSION['user_id'])) {
    // echo "DEBUG: Redirecting because user is already logged in.<br>";
    header("Location: home.php");
    exit();
}

// Include the database connection configuration
require_once __DIR__ . '/config/database.php'; // Use __DIR__ for reliable path
// echo "DEBUG: Database config included.<br>"; // Confirm include works

// Initialize variables
$errors = []; // Array to hold validation/login errors
$login_identifier = ''; // To repopulate username/email field on error

// Check for registration success message (optional)
$registration_success = isset($_GET['registered']) && $_GET['registered'] === 'success';

// --- Form Submission Logic ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // echo "DEBUG: POST request received.<br>"; // Confirm form submission

    // 1. Retrieve and Sanitize Input
    $login_identifier = trim($_POST['login_identifier'] ?? ''); // Can be username or email
    $password = $_POST['password'] ?? '';
    // echo "DEBUG: Identifier = " . htmlspecialchars($login_identifier) . ", Password Length = " . strlen($password) . "<br>";

    // 2. Validate Input
    if (empty($login_identifier)) {
        $errors['login_identifier'] = "Username or email is required.";
    }
    if (empty($password)) {
        $errors['password'] = "Password is required.";
    }
    // echo "DEBUG: Validation errors count = " . count($errors) . "<br>";


    // 3. If Validation Passes, Attempt Login
    if (empty($errors)) {
        // echo "DEBUG: Validation passed. Attempting database query.<br>";
        try {
            // *** ALTERNATIVE APPROACH: Use distinct placeholders ***
            $sql = "SELECT id, username, email, password_hash, role FROM users WHERE username = :identifier_user OR email = :identifier_email LIMIT 1";
            // echo "DEBUG: SQL = " . $sql . "<br>";
            $stmt = $pdo->prepare($sql);
            // echo "DEBUG: Statement prepared.<br>";

            // Pass the parameters in an array to execute(), binding both distinct placeholders
            $executeParams = [
                ':identifier_user' => $login_identifier,
                ':identifier_email' => $login_identifier
            ];
            // echo "DEBUG: Execute params = "; var_dump($executeParams); echo "<br>"; // See exactly what's being passed

            $stmt->execute($executeParams); // Use the array here
            // echo "DEBUG: Statement executed.<br>";

            // Fetch the user data
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            // echo "DEBUG: User data fetched = "; var_dump($user); echo "<br>"; // See if user was found

            // 4. Verify User and Password
            if ($user) {
                // echo "DEBUG: User found in database.<br>";
                if (password_verify($password, $user['password_hash'])) {
                    // echo "DEBUG: Password verified successfully!<br>";
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    // echo "DEBUG: Session data set. Redirecting...<br>";
                    header("Location: home.php");
                    exit();
                } else {
                    // echo "DEBUG: Password verification failed.<br>";
                    $errors['general'] = "Invalid username/email or password.";
                }
            } else {
                // echo "DEBUG: User not found in database.<br>";
                $errors['general'] = "Invalid username/email or password.";
            }

        } catch (PDOException $e) {
            // echo "DEBUG: PDO Exception caught!<br>";
            error_log("Database Login Error: " . $e->getMessage());
             // --- DEBUG: Output the specific error message to the screen ---
             echo "<p style='color: red; background: white; padding: 10px; border: 1px solid black;'><b>DEBUG DB ERROR:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
            $errors['general'] = "An error occurred during login. Please try again.";
        }
    } else {
         // echo "DEBUG: Validation failed.<br>";
    }
} else {
    // echo "DEBUG: Not a POST request.<br>";
}

// --- Start HTML ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - CloneFlix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style type="text/tailwindcss">
        /* Styles remain the same */
        body { background-color: #141414; }
        .form-container { background-color: rgba(0, 0, 0, 0.75); }
        .form-input { @apply w-full pl-4 pr-10 py-3 mb-4 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-600 focus:border-transparent; }
        .form-input-text { @apply w-full px-4 py-3 mb-4 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-600 focus:border-transparent; }
        .form-label { @apply block text-gray-400 text-sm font-medium mb-1; }
        .form-button { @apply w-full py-3 mt-4 font-semibold text-white bg-red-600 rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-200; }
        .error-message { @apply text-red-400 text-sm mt-[-0.5rem] mb-3; }
        .success-message { @apply text-green-400 text-center font-semibold mb-4 p-3 bg-green-900 bg-opacity-50 rounded border border-green-700; }
        .password-toggle-icon { @apply absolute top-0 right-0 h-full w-10 flex items-center justify-center text-gray-400 cursor-pointer hover:text-gray-200; padding-bottom: 1rem; }
    </style>
</head>
<body class="text-gray-100 flex items-center justify-center min-h-screen p-4">

    <div class="form-container p-8 md:p-12 rounded-lg shadow-lg w-full max-w-md">

        <h1 class="text-3xl font-bold text-white mb-8 text-center">Sign In</h1>

        <?php if ($registration_success): ?>
            <div class="success-message">Registration successful! You can now sign in.</div>
        <?php endif; ?>

        <?php if (isset($errors['general'])): ?>
             <p class="error-message text-center font-semibold mb-4"><?php echo $errors['general']; ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST" novalidate>
            <div>
                <label for="login_identifier" class="form-label hidden">Email or Username</label>
                <input type="text" id="login_identifier" name="login_identifier" class="form-input-text <?php echo isset($errors['login_identifier']) || isset($errors['general']) ? 'border-red-500 ring-red-500' : 'border-gray-600'; ?>" placeholder="Email or username" required value="<?php echo htmlspecialchars($login_identifier); ?>">
                 <?php if (isset($errors['login_identifier'])): ?> <p class="error-message"><?php echo $errors['login_identifier']; ?></p> <?php endif; ?>
            </div>
            <div class="relative">
                <label for="password" class="form-label hidden">Password</label>
                <input type="password" id="password" name="password" class="form-input <?php echo isset($errors['password']) || isset($errors['general']) ? 'border-red-500 ring-red-500' : 'border-gray-600'; ?>" placeholder="Password" required>
                <span class="password-toggle-icon" onclick="togglePasswordVisibility('password', 'toggle-icon-password')">
                    <svg id="toggle-icon-password" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"> <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /> <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /> </svg>
                 </span>
                 <?php if (isset($errors['password'])): ?> <p class="error-message"><?php echo $errors['password']; ?></p> <?php endif; ?>
            </div>
            <button type="submit" class="form-button">Sign In</button>
            <p class="text-center text-gray-400 mt-6 text-sm"> New to CloneFlix? <a href="register.php" class="text-white hover:underline font-semibold">Sign up now</a>. </p>
        </form>
    </div>

    <script>
        // JS remains the same
        const eyeIconPath = `<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />`;
        const eyeSlashIconPath = `<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L6.228 6.228" />`;
        function togglePasswordVisibility(inputId, iconId) { const passwordInput = document.getElementById(inputId); const toggleIcon = document.getElementById(iconId); if (!passwordInput || !toggleIcon) return; const currentType = passwordInput.getAttribute('type'); passwordInput.setAttribute('type', currentType === 'password' ? 'text' : 'password'); toggleIcon.innerHTML = currentType === 'password' ? eyeSlashIconPath : eyeIconPath; }
    </script>

</body>
</html>