<?php
// search.php

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
require_once __DIR__ . '/config/database.php'; // Include DB Config (needed for $pdo in header)

// !!! IMPORTANT: Replace 'YOUR_TMDB_API_KEY' with your actual TMDb API Key (v3 auth) !!!
$apiKey = '82837648bc7ef840a2e7b308f686cabb'; // <--- PUT YOUR API KEY HERE

// --- Get Search Query ---
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Initialize variables
$searchResults = [];
$apiError = null;
$pageTitle = "Search Results";

// --- Perform Search if Query Exists ---
if (!empty($query)) {
    $pageTitle = "Search Results for \"" . htmlspecialchars($query) . "\"";

    try {
        // *** FIX: Compare against the actual placeholder string ***
        // Check if the key is still the placeholder
        if ($apiKey === 'YOUR_TMDB_API_KEY') {
            throw new InvalidArgumentException('TMDb API Key is still the placeholder. Please configure it in search.php.');
        }

        // Instantiate TMDb client inside the try block
        $tmdb = new TMDb($apiKey);

        // Perform the search
        $searchData = $tmdb->searchMulti($query);

        // Process results
        if ($searchData && isset($searchData['results'])) {
            foreach ($searchData['results'] as $item) {
                // Filter for movies/TV shows with posters
                if (isset($item['media_type']) && ($item['media_type'] === 'movie' || $item['media_type'] === 'tv') && !empty($item['poster_path'])) {
                    $searchResults[] = $item;
                }
            }
        } else {
            // Handle cases where search didn't return expected structure
            if ($searchData && isset($searchData['results']) && empty($searchData['results'])) {
                // Valid empty result from TMDb, not an error
            } else {
                // Actual error fetching or decoding
                $apiError = "Could not perform search or decode results.";
                error_log("TMDb Search Error: Query '{$query}' - " . ($apiError ?? 'Unknown error or invalid response structure'));
            }
        }

    } catch (InvalidArgumentException $e) { // Catch API key errors
        $apiError = $e->getMessage();
        error_log($apiError);
    } catch (Exception $e) { // Catch other potential errors (e.g., cURL)
         $apiError = "An unexpected error occurred while contacting TMDb.";
         error_log($apiError . " Exception: " . $e->getMessage());
    }
} else {
    // Handle case where no query was provided
    $pageTitle = "Search";
}

// Get username from session for header
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';

// --- Start HTML ---
// Include header AFTER all PHP logic that might set $pageTitle
require_once __DIR__ . '/includes/header.php';
?>
    <style type="text/tailwindcss">
        /* Reusing styles from home.php */
        body { background-color: #141414; }
        .logout-button { @apply inline-block px-4 py-2 text-sm font-medium text-white bg-red-600 rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-black focus:ring-red-500 transition duration-200; }
        .poster-card { @apply bg-gray-800 rounded shadow overflow-hidden transition-transform duration-200 ease-in-out hover:scale-105 cursor-pointer; }
        .poster-image { @apply w-full h-auto object-cover; }
        .poster-grid { @apply grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 md:gap-4; }
        .api-error-message { @apply text-center text-red-400 bg-red-900 bg-opacity-50 p-4 rounded border border-red-700 my-4; }
        .search-input { @apply px-3 py-1 text-sm text-white bg-gray-700 border border-transparent rounded focus:outline-none focus:ring-2 focus:ring-red-600 focus:border-transparent placeholder-gray-400; }
        .results-heading { @apply text-2xl lg:text-3xl font-semibold mb-6 text-gray-200; }
        .no-results-message { @apply text-center text-gray-400 text-lg mt-10; }
    </style>
</head>
<body class="text-gray-100">

    <main class="container mx-auto p-4 md:p-8 mt-6">

        <?php if ($apiError): ?>
            <div class="api-error-message">
                <p><strong>Error:</strong> <?php echo htmlspecialchars($apiError); ?></p>
            </div>
        <?php endif; ?>

        <h2 class="results-heading">
            <?php
                if (empty($query)) {
                    echo "Please enter a search term.";
                } elseif (!$apiError && empty($searchResults)) {
                    echo "No results found for \"" . htmlspecialchars($query) . "\"";
                } elseif (!empty($searchResults)) {
                     echo "Search Results for \"" . htmlspecialchars($query) . "\"";
                }
                elseif ($apiError && empty($searchResults)) {
                     // Error message already shown above
                }
            ?>
        </h2>

        <?php if (!empty($searchResults)): ?>
            <div class="poster-grid">
                <?php foreach ($searchResults as $item): ?>
                    <?php
                        $itemType = $item['media_type'];
                        $itemId = $item['id'];
                        $itemTitle = htmlspecialchars($itemType === 'movie' ? ($item['title'] ?? 'Untitled') : ($item['name'] ?? 'Untitled'));
                        $posterUrl = TMDb::IMAGE_BASE_URL_W500 . $item['poster_path'];
                    ?>
                     <a href="details.php?type=<?php echo $itemType; ?>&id=<?php echo $itemId; ?>" class="poster-card" title="<?php echo $itemTitle; ?>">
                        <img src="<?php echo $posterUrl; ?>" alt="<?php echo $itemTitle; ?> Poster" class="poster-image" loading="lazy" onerror="this.onerror=null; this.src='https://placehold.co/500x750/141414/444444?text=No+Image';">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php elseif (!empty($query) && !$apiError && empty($searchResults)): ?>
             <p class="no-results-message">We couldn't find any matches for '<?php echo htmlspecialchars($query); ?>'. Try checking the spelling or searching for something else.</p>
        <?php endif; ?>

    </main>

<?php
// Include footer AFTER the main content
require_once __DIR__ . '/includes/footer.php';
?>
