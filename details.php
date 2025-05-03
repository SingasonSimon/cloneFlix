<?php
// details.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION['user_id']; // Get user ID early for tracking

// --- Include Database & TMDb Class ---
require_once __DIR__ . '/config/database.php'; // Include DB connection
require_once __DIR__ . '/lib/TMDb.php';

// !!! IMPORTANT: Replace '82837648bc7ef840a2e7b308f686cabb' with your actual TMDb API Key (v3 auth) !!!
$apiKey = '82837648bc7ef840a2e7b308f686cabb'; // <--- Make sure your actual key is here

// --- Get Type and ID from URL ---
$type = $_GET['type'] ?? null; // 'movie' or 'tv'
$id = isset($_GET['id']) ? (int)$_GET['id'] : null; // Get the ID as an integer

// Validate type and ID
if ((!$type || !in_array($type, ['movie', 'tv'])) || !$id || $id <= 0) {
    header("Location: home.php");
    exit();
}

// --- Fetch Data from TMDb ---
$details = null;
$trailerKey = null;
$apiError = null;
$pageTitle = "Details"; // Default title
$videos = null; // Initialize videos variable

try {
    $tmdb = new TMDb($apiKey);

    // Fetch details based on type
    if ($type === 'movie') {
        $details = $tmdb->getMovieDetails($id);
        $videos = $tmdb->getMovieVideos($id);
        $pageTitle = $details['title'] ?? 'Movie Details';
    } elseif ($type === 'tv') {
        $details = $tmdb->getTvShowDetails($id);
        $videos = $tmdb->getTvShowVideos($id);
        $pageTitle = $details['name'] ?? 'TV Show Details';
    }

    // Find the trailer key
    if ($videos) {
        $trailerKey = $tmdb->findTrailerKey($videos);
    }

    // Check if details were actually fetched
    if (!$details) {
        $apiError = "Could not fetch details for the requested item.";
        error_log("TMDb Fetch Error: Details not found for type '{$type}', id '{$id}'.");
    } else {
        // --- *** ADD WATCH HISTORY RECORD *** ---
        // If details were fetched successfully, record the view
        // This block is executed only if $details is not null/false
        try {
            // Use INSERT IGNORE to avoid errors if the user views the same item again
            // The unique key `user_item_unique` (`user_id`, `item_type`, `item_id`) handles this
            $sql_watch = "INSERT IGNORE INTO watched_history (user_id, item_type, item_id) VALUES (:user_id, :item_type, :item_id)";
            $stmt_watch = $pdo->prepare($sql_watch);
            $stmt_watch->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt_watch->bindParam(':item_type', $type, PDO::PARAM_STR);
            $stmt_watch->bindParam(':item_id', $id, PDO::PARAM_INT);
            $stmt_watch->execute();
            // No user feedback needed here, just log errors if they happen
        } catch (PDOException $e) {
            // Log error but don't stop the page from loading
            error_log("Watch History DB Error for user {$userId}, item {$type}/{$id}: " . $e->getMessage());
        }
        // --- *** END WATCH HISTORY RECORD *** ---
    }


} catch (InvalidArgumentException $e) {
    $apiError = "TMDb API Key is missing or invalid.";
    error_log($apiError . " Exception: " . $e->getMessage());
} catch (Exception $e) {
     $apiError = "An unexpected error occurred while contacting TMDb.";
     error_log($apiError . " Exception: " . $e->getMessage());
}

// Helper function to format date
function formatDate($dateString) {
    if (empty($dateString)) return 'N/A';
    try {
        $date = new DateTime($dateString);
        return $date->format('M d, Y'); // e.g., Jan 01, 2024
    } catch (Exception $e) {
        return 'N/A';
    }
}

// --- Start HTML ---
// Use main site header (assuming it exists in includes/)
require_once __DIR__ . '/includes/header.php';
?>
    <style type="text/tailwindcss">
        /* Styles from your provided code */
        body { background-color: #141414; }
        .logout-button { @apply inline-block px-4 py-2 text-sm font-medium text-white bg-red-600 rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-black focus:ring-red-500 transition duration-200; }
        .back-button { @apply inline-block px-4 py-2 text-sm font-medium text-gray-300 bg-gray-700 rounded hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-black focus:ring-gray-500 transition duration-200 mr-4; }
        .api-error-message { @apply text-center text-red-400 bg-red-900 bg-opacity-50 p-4 rounded border border-red-700 my-4; }
        .backdrop-container { @apply relative w-full h-[60vh] md:h-[75vh] overflow-hidden; }
        .backdrop-image { @apply absolute top-0 left-0 w-full h-full object-cover object-center; }
        .backdrop-overlay { @apply absolute inset-0 bg-gradient-to-t from-black via-black/70 to-transparent; }
        .details-content { @apply relative z-10 container mx-auto px-4 md:px-8 pb-16 -mt-24 md:-mt-48; }
        .details-poster { @apply w-32 md:w-48 rounded shadow-lg float-left mr-4 md:mr-6 mb-4; }
        .details-title { @apply text-3xl md:text-5xl font-bold text-white mb-2 pt-4; }
        .details-meta { @apply text-sm text-gray-400 mb-4 flex items-center space-x-3; }
        .details-rating { @apply text-green-400 font-semibold; }
        .details-overview { @apply text-gray-300 text-base md:text-lg leading-relaxed clear-both; }
        .trailer-container { @apply mt-8 aspect-video w-full max-w-4xl mx-auto; }
        .trailer-placeholder { @apply mt-8 text-center text-gray-500; }
    </style>
</head> <?php // Head tag was missing in provided code, added here ?>
<body class="text-gray-100"> <?php // Body tag was missing, added here ?>

    <?php // Header require_once was moved up before <style> for $pageTitle ?>

    <main>
        <?php if ($apiError): ?>
            <div class="container mx-auto p-8">
                <div class="api-error-message">
                    <p><strong>Error:</strong> <?php echo htmlspecialchars($apiError); ?></p>
                </div>
                 <p class="text-center mt-4"><a href="home.php" class="text-red-500 hover:underline">Return to Home</a></p>
            </div>
        <?php elseif ($details): ?>
            <?php
                // Extract common details
                $title = htmlspecialchars($type === 'movie' ? ($details['title'] ?? 'N/A') : ($details['name'] ?? 'N/A'));
                $overview = htmlspecialchars($details['overview'] ?? 'No overview available.');
                $rating = isset($details['vote_average']) ? round($details['vote_average'], 1) : 'N/A';
                $releaseDate = formatDate($type === 'movie' ? ($details['release_date'] ?? null) : ($details['first_air_date'] ?? null));
                $posterPath = $details['poster_path'] ?? null;
                $backdropPath = $details['backdrop_path'] ?? null;

                $posterUrl = $posterPath ? TMDb::IMAGE_BASE_URL_W500 . $posterPath : 'https://placehold.co/500x750/141414/444444?text=No+Poster';
                $backdropUrl = $backdropPath ? TMDb::IMAGE_BASE_URL_ORIGINAL . $backdropPath : null;
            ?>

            <div class="backdrop-container">
                <?php if ($backdropUrl): ?>
                    <img src="<?php echo $backdropUrl; ?>" alt="<?php echo $title; ?> backdrop" class="backdrop-image">
                <?php endif; ?>
                <div class="backdrop-overlay"></div>
            </div>

             <div class="details-content">
                 <img src="<?php echo $posterUrl; ?>" alt="<?php echo $title; ?> poster" class="details-poster">

                <h2 class="details-title"><?php echo $title; ?></h2>

                <div class="details-meta">
                    <?php if ($rating !== 'N/A' && $rating > 0): // Show rating only if valid ?>
                        <span class="details-rating">Rating: <?php echo $rating; ?> / 10</span>
                        <span>•</span>
                    <?php endif; ?>
                    <span><?php echo $releaseDate; ?></span>
                </div>

                <p class="details-overview"><?php echo $overview; ?></p>

                <?php if ($trailerKey): ?>
                    <div class="trailer-container">
                        <iframe class="w-full h-full"
                                src="https://www.youtube.com/embed/<?php echo $trailerKey; ?>?autoplay=0&rel=0&showinfo=0&modestbranding=1"
                                title="YouTube video player"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen>
                        </iframe>
                    </div>
                <?php else: ?>
                    <p class="trailer-placeholder">No trailer available.</p>
                <?php endif; ?>

            </div>

        <?php else: ?>
            <div class="container mx-auto p-8">
                <p class="text-center text-gray-500">Details for this item could not be loaded.</p>
                <p class="text-center mt-4"><a href="home.php" class="text-red-500 hover:underline">Return to Home</a></p>
            </div>
        <?php endif; ?>
    </main>

<?php
// Use main site footer (assuming it exists in includes/)
require_once __DIR__ . '/includes/footer.php';
?>
