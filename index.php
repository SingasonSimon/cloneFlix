<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netflix Clone - Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style type="text/tailwindcss">
        /* You can add custom CSS or @apply directives here if needed */
        /* For example:
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }
        */
    </style>
</head>
<body class="bg-black text-gray-100 antialiased">
    <div class="container mx-auto p-8">

        <header class="flex justify-between items-center mb-10">
             <h1 class="text-3xl font-bold text-red-600 uppercase tracking-wider">
                CloneFlix
             </h1>
             <div>
                </div>
        </header>

        <main>
            <h2 class="text-2xl font-semibold mb-4">Setup Verification</h2>

            <p class="mb-3">
                If you see this page with a black background, white/gray text,
                and a red "CloneFlix" logo, then HTML and Tailwind CSS (via CDN) are working correctly.
            </p>

            <div class="mt-6 p-4 bg-gray-800 rounded-lg shadow-md">
                <p class="font-medium">This is a styled container using Tailwind classes.</p>
            </div>

            <?php
                // Simple PHP check to ensure the server is processing PHP code
                $php_version = phpversion(); // Get current PHP version
                $message = "PHP version " . htmlspecialchars($php_version) . " is running!"; // Create message

                // Output the message using Tailwind classes for styling
                echo "<p class='mt-6 text-lg text-green-400 font-semibold'>✅ Success: " . $message . "</p>";
            ?>
        </main>

        <footer class="mt-12 text-center text-gray-500 text-sm">
            <p>&copy; <?php echo date("Y"); ?> CloneFlix. For educational purposes only.</p>
        </footer>

    </div> </body>
</html>
