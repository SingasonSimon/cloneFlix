<?php
// register.php

// Start session (if not already started) - needed for potential future flash messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the database connection configuration
require_once __DIR__ . '/config/database.php'; // Use __DIR__ for reliable path

// Initialize variables
$errors = []; // Array to hold validation errors
$success_message = ''; // To display a success message
$username = ''; // To repopulate form field on error
$email = '';    // To repopulate form field on error

// --- Form Submission Logic ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Retrieve and Sanitize Input
    // Use trim() to remove whitespace from beginning and end
    $username = trim($_POST['username'] ?? ''); // Null coalescing operator ?? ''
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; // Don't trim password initially
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 2. Validate Input

    // Username validation
    if (empty($username)) {
        $errors['username'] = "Username is required.";
    } elseif (strlen($username) < 3) {
        $errors['username'] = "Username must be at least 3 characters long.";
    } // Add more checks? (e.g., alphanumeric: !preg_match('/^[a-zA-Z0-9_]+$/', $username))

    // Email validation
    if (empty($email)) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }

    // Password validation
    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters long.";
    } // Add more complexity checks? (e.g., require number, uppercase, etc.)

    // Confirm Password validation
    if (empty($confirm_password)) {
        $errors['confirm_password'] = "Please confirm your password.";
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
        $errors['password'] = "Passwords do not match."; // Also mark the first password field
    }

    // 3. If Validation Passes, Check Database and Insert
    if (empty($errors)) {
        try {
            // Check if username or email already exists using a prepared statement
            $sql_check = "SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt_check->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt_check->execute();

            if ($stmt_check->fetch()) {
                // User already exists
                $errors['general'] = "Username or email already taken. Please choose another or <a href='login.php' class='font-bold text-white hover:underline'>login</a>.";
            } else {
                // User does not exist, proceed with insertion

                // 4. Hash the password securely
                $password_hash = password_hash($password, PASSWORD_DEFAULT); // Use default strong hashing algorithm

                // 5. Insert the new user into the database
                $sql_insert = "INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)";
                $stmt_insert = $pdo->prepare($sql_insert);

                $role = 'user'; // Default role

                // Bind parameters
                $stmt_insert->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt_insert->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt_insert->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);
                $stmt_insert->bindParam(':role', $role, PDO::PARAM_STR);

                // Execute the insertion
                if ($stmt_insert->execute()) {
                    $success_message = "Registration successful! You can now <a href='login.php' class='font-bold text-white hover:underline'>sign in</a>.";
                    // Clear form fields on success
                    $username = '';
                    $email = '';
                    // Optionally redirect after success (e.g., using header function)
                    // header("Location: login.php?registered=success");
                    // exit();
                } else {
                    $errors['general'] = "Registration failed due to a server error. Please try again later.";
                }
            }
        } catch (PDOException $e) {
            // Handle potential database errors during check or insert
            error_log("Database Error: " . $e->getMessage()); // Log the actual error for the admin
            $errors['general'] = "An unexpected error occurred. Please try again."; // Generic message for user
        }
    }
     // If validation fails, $errors array will be populated and shown in the form below.
     // Input values ($username, $email) are kept to repopulate the form.
}

// --- Start HTML ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CloneFlix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style type="text/tailwindcss">
        /* Custom styles can be added here if needed */
        body {
            background-color: #141414; /* Netflix dark background */
        }
        .form-container {
            background-color: rgba(0, 0, 0, 0.75); /* Semi-transparent black */
        }
        .form-input {
            @apply w-full pl-4 pr-10 py-3 mb-4 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-600 focus:border-transparent;
        }
        /* Add specific style for text input without icon */
        .form-input-text {
             @apply w-full px-4 py-3 mb-4 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-600 focus:border-transparent;
        }
        .form-label {
            @apply block text-gray-400 text-sm font-medium mb-1;
        }
        .form-button {
            @apply w-full py-3 mt-4 font-semibold text-white bg-red-600 rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-200;
        }
        .error-message {
            @apply text-red-400 text-sm mt-[-0.5rem] mb-3; /* Adjusted margin for better spacing */
        }
        .success-message {
             @apply text-green-400 text-center font-semibold mb-4 p-3 bg-green-900 bg-opacity-50 rounded border border-green-700;
        }
        .password-toggle-icon {
            @apply absolute top-0 right-0 h-full w-10 flex items-center justify-center text-gray-400 cursor-pointer hover:text-gray-200;
            padding-bottom: 1rem; /* Align with input padding */
        }
    </style>
</head>
<body class="text-gray-100 flex items-center justify-center min-h-screen p-4">

    <div class="form-container p-8 md:p-12 rounded-lg shadow-lg w-full max-w-md">

        <h1 class="text-3xl font-bold text-white mb-8 text-center">Sign Up</h1>

        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <?php echo $success_message; /* Already contains HTML link, no need for htmlspecialchars */ ?>
            </div>
        <?php endif; ?>

        <?php if (isset($errors['general']) && empty($success_message)): ?>
             <p class="error-message text-center font-semibold mb-4"><?php echo $errors['general']; /* May contain HTML link */ ?></p>
        <?php endif; ?>


        <form action="register.php" method="POST" novalidate>
            <div>
                <label for="username" class="form-label hidden">Username</label>
                <input type="text" id="username" name="username" class="form-input-text <?php echo isset($errors['username']) ? 'border-red-500 ring-red-500' : 'border-gray-600'; ?>" placeholder="Username" required
                       value="<?php echo htmlspecialchars($username); // Repopulate username ?>">
                 <?php if (isset($errors['username'])): ?>
                    <p class="error-message"><?php echo $errors['username']; ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label for="email" class="form-label hidden">Email Address</label>
                <input type="email" id="email" name="email" class="form-input-text <?php echo isset($errors['email']) ? 'border-red-500 ring-red-500' : 'border-gray-600'; ?>" placeholder="Email address" required
                       value="<?php echo htmlspecialchars($email); // Repopulate email ?>">
                 <?php if (isset($errors['email'])): ?>
                    <p class="error-message"><?php echo $errors['email']; ?></p>
                <?php endif; ?>
            </div>

            <div class="relative">
                <label for="password" class="form-label hidden">Password</label>
                <input type="password" id="password" name="password" class="form-input <?php echo isset($errors['password']) ? 'border-red-500 ring-red-500' : 'border-gray-600'; ?>" placeholder="Password (min. 6 characters)" required>
                <span class="password-toggle-icon" onclick="togglePasswordVisibility('password', 'toggle-icon-password')">
                    <svg id="toggle-icon-password" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                 </span>
                 <?php if (isset($errors['password'])): ?>
                    <p class="error-message"><?php echo $errors['password']; ?></p>
                <?php endif; ?>
            </div>

            <div class="relative">
                <label for="confirm_password" class="form-label hidden">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input <?php echo isset($errors['confirm_password']) ? 'border-red-500 ring-red-500' : 'border-gray-600'; ?>" placeholder="Confirm Password" required>
                 <span class="password-toggle-icon" onclick="togglePasswordVisibility('confirm_password', 'toggle-icon-confirm-password')">
                    <svg id="toggle-icon-confirm-password" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                       <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                     </svg>
                 </span>
                 <?php if (isset($errors['confirm_password'])): ?>
                    <p class="error-message"><?php echo $errors['confirm_password']; ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="form-button" <?php echo !empty($success_message) ? 'disabled' : ''; ?>>
                <?php echo !empty($success_message) ? 'Registered!' : 'Sign Up'; ?>
            </button>

            <?php if (empty($success_message)): // Only show if not successful ?>
                <p class="text-center text-gray-400 mt-6 text-sm">
                    Already have an account?
                    <a href="login.php" class="text-white hover:underline font-semibold">Sign in now</a>.
                </p>
            <?php endif; ?>

        </form>
    </div>

    <script>
        const eyeIconPath = `
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        `;
        const eyeSlashIconPath = `
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L6.228 6.228" />
        `;

        function togglePasswordVisibility(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            if (!passwordInput || !toggleIcon) return;
            const currentType = passwordInput.getAttribute('type');
            passwordInput.setAttribute('type', currentType === 'password' ? 'text' : 'password');
            toggleIcon.innerHTML = currentType === 'password' ? eyeSlashIconPath : eyeIconPath;
        }
    </script>

</body>
</html>
