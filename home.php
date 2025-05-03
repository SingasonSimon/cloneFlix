<?php
// home.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// --- Include TMDb Class and Initialize ---
require_once __DIR__ . '/lib/TMDb.php';
require_once __DIR__ . '/config/database.php'; // Include DB Config

// !!! IMPORTANT: Replace 'YOUR_TMDB_API_KEY' with your actual TMDb API Key (v3 auth) !!!
$apiKey = '82837648bc7ef840a2e7b308f686cabb'; // <--- Key from user code

// Initialize variables for data
$popularMovies = null;
$popularTvShows = null;
$apiError = null;

// Instantiate the TMDb client, handle potential key error
try {
    // Check if the key is still the placeholder
    if ($apiKey === 'YOUR_TMDB_API_KEY') { // Check against placeholder
        throw new InvalidArgumentException('TMDb API Key is still the placeholder. Please configure it in home.php.');
    }
    $tmdb = new TMDb($apiKey);

    // --- Fetch Data from TMDb ---
    $popularMoviesData = $tmdb->getPopularMovies();
    $popularTvShowsData = $tmdb->getPopularTvShows();

    // Extract the 'results' array if the fetch was successful
    if ($popularMoviesData && isset($popularMoviesData['results'])) {
        $popularMovies = $popularMoviesData['results'];
    } else { $apiError = "Could not fetch popular movies."; error_log("TMDb Fetch Error: Popular Movies - " . ($apiError ?? 'Unknown error')); }
    if ($popularTvShowsData && isset($popularTvShowsData['results'])) {
        $popularTvShows = $popularTvShowsData['results'];
    } else { $apiError = ($apiError ? $apiError . " " : "") . "Could not fetch popular TV shows."; error_log("TMDb Fetch Error: Popular TV Shows - " . ($apiError ?? 'Unknown error')); }

} catch (InvalidArgumentException $e) { $apiError = $e->getMessage(); error_log($apiError);
} catch (Exception $e) { $apiError = "An unexpected error occurred while contacting TMDb."; error_log($apiError . " Exception: " . $e->getMessage()); }


// Get username from session
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
// Get user role and check if admin
$userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user';
$isAdmin = ($userRole === 'admin');

// Set page title
$pageTitle = "Home";

// --- Start HTML ---
// *** INCLUDE HEADER ***
require_once __DIR__ . '/includes/header.php';
?>

    <style type="text/tailwindcss">
        .content-row { @apply mt-10; }
        .row-title { @apply text-xl lg:text-2xl font-semibold mb-3 text-gray-200; }
        .poster-grid { @apply grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 md:gap-4; }
        .poster-card { @apply bg-gray-800 rounded shadow overflow-hidden transition-transform duration-200 ease-in-out hover:scale-105 cursor-pointer; }
        .poster-image { @apply w-full h-auto object-cover; }
        .api-error-message { @apply text-center text-red-400 bg-red-900 bg-opacity-50 p-4 rounded border border-red-700 my-4; }
    </style>

    <main class="container mx-auto p-4 md:p-8 mt-6">
        <h2 class="text-3xl font-semibold mb-6 sr-only">Browse Content</h2>

        <?php if ($apiError): ?>
            <div class="api-error-message">
                <p><strong>Error:</strong> <?php echo htmlspecialchars($apiError); ?></p>
                <p class="text-sm mt-1">Please check the configuration or try again later.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($popularMovies)): ?>
            <section class="content-row">
                <h3 class="row-title">Popular Movies</h3>
                <div class="poster-grid">
                    <?php foreach ($popularMovies as $movie): ?>
                        <?php
                            $posterUrl = isset($movie['poster_path']) ? TMDb::IMAGE_BASE_URL_W500 . $movie['poster_path'] : 'https://placehold.co/500x750/141414/444444?text=No+Image';
                            $title = htmlspecialchars($movie['title'] ?? 'Untitled Movie');
                            $movieId = $movie['id'] ?? null;
                        ?>
                        <?php if ($movieId): ?>
                            <a href="details.php?type=movie&id=<?php echo $movieId; ?>" class="poster-card" title="<?php echo $title; ?>">
                                <img src="<?php echo $posterUrl; ?>" alt="<?php echo $title; ?> Poster" class="poster-image" loading="lazy" onerror="this.onerror=null; this.src='https://placehold.co/500x750/141414/444444?text=No+Image';">
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif (!$apiError): ?>
            <p class="text-gray-500 mt-10">Could not load popular movies.</p>
        <?php endif; ?>

        <?php if (!empty($popularTvShows)): ?>
            <section class="content-row">
                <h3 class="row-title">Popular TV Shows</h3>
                 <div class="poster-grid">
                    <?php foreach ($popularTvShows as $show): ?>
                         <?php
                            $posterUrl = isset($show['poster_path']) ? TMDb::IMAGE_BASE_URL_W500 . $show['poster_path'] : 'https://placehold.co/500x750/141414/444444?text=No+Image';
                            $title = htmlspecialchars($show['name'] ?? 'Untitled Show');
                             $showId = $show['id'] ?? null;
                        ?>
                         <?php if ($showId): ?>
                             <a href="details.php?type=tv&id=<?php echo $showId; ?>" class="poster-card" title="<?php echo $title; ?>">
                                <img src="<?php echo $posterUrl; ?>" alt="<?php echo $title; ?> Poster" class="poster-image" loading="lazy" onerror="this.onerror=null; this.src='https://placehold.co/500x750/141414/444444?text=No+Image';">
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif (!$apiError): ?>
             <p class="text-gray-500 mt-10">Could not load popular TV shows.</p>
        <?php endif; ?>

    </main>

<?php
// *** INCLUDE FOOTER ***
require_once __DIR__ . '/includes/footer.php';
?>
