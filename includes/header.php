<?php
// includes/header.php (Main Site)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$loggedIn = isset($_SESSION['user_id']);
$username = $loggedIn ? ($_SESSION['username'] ?? 'User') : null;
$userRole = $loggedIn ? ($_SESSION['user_role'] ?? 'user') : null;
$isAdmin = ($userRole === 'admin');

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'CloneFlix'; ?> - CloneFlix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style type="text/tailwindcss">
        body { background-color: #141414; @apply text-gray-100; }
        .logout-button { @apply block w-full text-left px-4 py-2 text-sm font-medium text-white bg-red-600 rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-black focus:ring-red-500 transition duration-200 mt-2 md:inline-block md:w-auto md:mt-0; }
        .login-button { @apply inline-block px-4 py-2 text-sm font-medium text-white bg-red-600 rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-black focus:ring-red-500 transition duration-200; }
        .search-input { @apply px-3 py-1 w-full md:w-auto text-sm text-white bg-gray-700 border border-transparent rounded focus:outline-none focus:ring-2 focus:ring-red-600 focus:border-transparent placeholder-gray-400; }
        .nav-link { @apply block md:inline-block text-gray-300 hover:text-white px-3 py-2 rounded hover:bg-gray-700 transition-colors; }
        .nav-link-active { @apply block md:inline-block text-white bg-gray-700 px-3 py-2 rounded; }
        .admin-link { @apply block md:inline-block text-yellow-400 hover:text-yellow-300 px-3 py-2 rounded hover:bg-gray-700 transition-colors font-semibold; }
        /* Styles for mobile menu container */
        #mobile-menu {
            @apply absolute top-full right-0 mt-1 md:hidden w-48 rounded-md shadow-lg py-1 bg-gray-800 ring-1 ring-black ring-opacity-5 z-50;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <header class="bg-black bg-opacity-90 shadow-md p-4 sticky top-0 z-40">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex-shrink-0">
                 <a href="home.php" class="text-2xl font-bold text-red-600 uppercase tracking-wider">CloneFlix</a>
            </div>

            <div class="hidden md:flex md:items-center md:space-x-4">
                <?php if ($loggedIn): ?>
                    <nav class="flex space-x-4">
                         <a href="home.php" class="<?php echo $currentPage === 'home.php' ? 'nav-link-active' : 'nav-link'; ?>">Home</a>
                         <a href="watched.php" class="<?php echo $currentPage === 'watched.php' ? 'nav-link-active' : 'nav-link'; ?>">History</a>
                    </nav>
                    <form action="search.php" method="GET" class="relative">
                         <input type="search" name="query" placeholder="Search..." class="search-input pr-8" required value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>">
                         <button type="submit" class="absolute top-0 right-0 h-full px-2 text-gray-400 hover:text-white" aria-label="Search"> <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"> <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /> </svg> </button>
                     </form>
                     <?php if ($isAdmin): ?>
                         <a href="admin/" class="admin-link">Admin Panel</a>
                     <?php endif; ?>
                     <a href="profile.php" class="<?php echo $currentPage === 'profile.php' || $currentPage === 'edit_profile.php' ? 'nav-link-active' : 'nav-link'; ?>">Profile</a>
                     <a href="logout.php" class="logout-button">Logout</a>
                <?php else: ?>
                     <a href="login.php" class="login-button">Sign In</a>
                <?php endif; ?>
            </div>

             <div class="md:hidden flex items-center relative">
                 <button id="mobile-menu-button" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" aria-controls="mobile-menu" aria-expanded="false">
                     <span class="sr-only">Open main menu</span>
                     <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                     </svg>
                     <svg class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                      </svg>
                 </button>

                 <div class="hidden" id="mobile-menu">
                     <div class="px-2 pt-2 pb-3 space-y-1">
                         <?php if ($loggedIn): ?>
                             <form action="search.php" method="GET" class="relative mb-2 px-2">
                                 <input type="search" name="query" placeholder="Search..." class="search-input pr-8" required value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>">
                                 <button type="submit" class="absolute top-0 right-0 h-full px-2 text-gray-400 hover:text-white" aria-label="Search"> <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"> <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /> </svg> </button>
                             </form>
                             <a href="home.php" class="<?php echo $currentPage === 'home.php' ? 'nav-link-active' : 'nav-link'; ?>">Home</a>
                             <a href="watched.php" class="<?php echo $currentPage === 'watched.php' ? 'nav-link-active' : 'nav-link'; ?>">History</a>
                             <a href="profile.php" class="<?php echo $currentPage === 'profile.php' || $currentPage === 'edit_profile.php' ? 'nav-link-active' : 'nav-link'; ?>">Profile</a>
                             <?php if ($isAdmin): ?>
                                 <a href="admin/" class="admin-link">Admin Panel</a>
                             <?php endif; ?>
                             <a href="logout.php" class="logout-button">Logout</a>
                         <?php else: ?>
                              <a href="login.php" class="login-button block w-full text-center">Sign In</a>
                         <?php endif; ?>
                     </div>
                 </div>
             </div>
        </div>
    </header>
     <div class="flex-grow">

     <script>
        const menuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        const hamburgerIcon = menuButton.querySelector('svg:first-of-type'); // Assumes first SVG is hamburger
        const closeIcon = menuButton.querySelector('svg:last-of-type'); // Assumes last SVG is close

        menuButton.addEventListener('click', () => {
            const isExpanded = menuButton.getAttribute('aria-expanded') === 'true';
            menuButton.setAttribute('aria-expanded', !isExpanded);
            mobileMenu.classList.toggle('hidden');
            hamburgerIcon.classList.toggle('hidden'); // Toggle hamburger icon
            closeIcon.classList.toggle('hidden'); // Toggle close icon
        });

        // Optional: Close menu if clicking outside of it
        document.addEventListener('click', (event) => {
            const isClickInsideMenu = mobileMenu.contains(event.target);
            const isClickOnButton = menuButton.contains(event.target);
            // Check if menu is open AND click was outside button and menu
            if (!mobileMenu.classList.contains('hidden') && !isClickInsideMenu && !isClickOnButton) {
                 menuButton.setAttribute('aria-expanded', 'false');
                 mobileMenu.classList.add('hidden');
                 hamburgerIcon.classList.remove('hidden');
                 closeIcon.classList.add('hidden');
            }
        });
     </script>
