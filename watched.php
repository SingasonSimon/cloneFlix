<?php
// watched.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Authentication Check ---
// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$userId = $_SESSION['user_id']; // Get the logged-in user's ID

// --- Include Database Connection & TMDb Class ---
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/TMDb.php';

// !!! IMPORTANT: Replace '82837648bc7ef840a2e7b308f686cabb' with your actual TMDb API Key (v3 auth) !!!
$apiKey = '82837648bc7ef840a2e7b308f686cabb'; // <--- PUT YOUR API KEY HERE

// Initialize variables
$watchedItems = []; // Array to store combined data (history + TMDb details)
$fetchError = null; // To store database fetch errors
$apiError = null;   // To store API related errors
$tmdb = null;       // To hold the TMDb client object

// --- Instantiate TMDb Client ---
// This needs to happen before fetching details
try {
    // Create the TMDb client object
    $tmdb = new TMDb($apiKey);
} catch (InvalidArgumentException $e) {
    // Catch specific error for invalid API key
    $apiError = $e->getMessage();
    error_log($apiError); // Log the error
} catch (Exception $e) {
     // Catch any other unexpected errors during TMDb client initialization
     $apiError = "An unexpected error occurred while initializing TMDb client.";
     error_log($apiError . " Exception: " . $e->getMessage());
}


// --- Fetch Watched History from Database ---
// Proceed only if the TMDb client was initialized successfully (no API key error)
if (!$apiError && $tmdb) {
    try {
        // Prepare SQL query to fetch history records for the logged-in user
        // Order by most recently watched first
        $sql_history = "SELECT item_type, item_id, watched_at
                        FROM watched_history
                        WHERE user_id = :user_id
                        ORDER BY watched_at DESC";
                        // Optional: Add LIMIT clause here for pagination later (e.g., LIMIT 20)
        $stmt_history = $pdo->prepare($sql_history);
        $stmt_history->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt_history->execute();
        // Fetch all history records for the user
        $historyRecords = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

        // --- Fetch TMDb Details for each watched item ---
        if ($historyRecords) {
            // Loop through each record fetched from the database
            foreach ($historyRecords as $record) {
                $itemDetails = null; // Reset details for each item
                try {
                    // Call the appropriate TMDb method based on item_type
                    if ($record['item_type'] === 'movie') {
                        $itemDetails = $tmdb->getMovieDetails((int)$record['item_id']);
                    } elseif ($record['item_type'] === 'tv') {
                        $itemDetails = $tmdb->getTvShowDetails((int)$record['item_id']);
                    }

                    // Process the fetched details
                    // Add details to our results array ONLY if fetched successfully AND has a poster path
                    if ($itemDetails && !empty($itemDetails['poster_path'])) {
                         // Add the original watched_at timestamp from the history record to the details array
                        $itemDetails['watched_at_timestamp'] = $record['watched_at'];
                        // Add the media type explicitly for easier use in the template
                        $itemDetails['media_type'] = $record['item_type'];
                        // Add the combined details to our final array
                        $watchedItems[] = $itemDetails;
                    } else {
                         // Log if details couldn't be fetched or poster is missing (optional)
                         error_log("Watched History: Could not fetch details for {$record['item_type']}/{$record['item_id']} or missing poster.");
                    }
                } catch (Exception $e) {
                     // Catch errors fetching individual item details (e.g., API error for one item)
                     // Log the error but allow the loop to continue processing other items
                     error_log("Watched History: Error fetching TMDb details for {$record['item_type']}/{$record['item_id']}: " . $e->getMessage());
                     // Optionally set a flag or partial error message if needed
                }
                 // Optional: Add a small delay between API calls if you encounter rate limiting issues with TMDb
                 // usleep(100000); // Example: 100 milliseconds delay
            }
        } // else: No history records found for this user

    } catch (PDOException $e) {
        // Handle errors fetching data from the watched_history table
        $fetchError = "Database error retrieving watched history.";
        error_log("Watched History DB Error: " . $e->getMessage());
    }
} // else: $apiError is set, skip fetching history


// Set the page title for the HTML head
$pageTitle = "My Watched History";

// --- Start HTML Output ---
// Include the main site header (make sure the path is correct)
require_once __DIR__ . '/includes/header.php';
?>
    <style type="text/tailwindcss">
        /* Reusing styles from home.php where applicable */
        .poster-card { @apply bg-gray-800 rounded shadow overflow-hidden transition-transform duration-200 ease-in-out hover:scale-105 cursor-pointer; }
        .poster-image { @apply w-full h-auto object-cover; }
        .poster-grid { @apply grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 md:gap-4; }
        .page-heading { @apply text-2xl lg:text-3xl font-semibold mb-6 text-gray-200 border-b border-gray-700 pb-3; }
        .error-box { @apply text-center text-red-400 bg-red-900 bg-opacity-50 p-4 rounded border border-red-700 my-4; }
        .info-box { @apply text-center text-gray-400 text-lg mt-10; }
    </style>

<main class="container mx-auto p-4 md:p-8 mt-6">
    <h1 class="page-heading"><?php echo htmlspecialchars($pageTitle); ?></h1>

    <?php if ($apiError): ?>
        <div class="error-box">
            <p><strong>API Error:</strong> <?php echo htmlspecialchars($apiError); ?></p>
        </div>
    <?php endif; ?>
    <?php if ($fetchError): ?>
        <div class="error-box">
            <p><strong>Database Error:</strong> <?php echo htmlspecialchars($fetchError); ?></p>
        </div>
    <?php endif; ?>


    <?php if (!$apiError && !$fetchError): // Only proceed if no major errors occurred ?>
        <?php if (empty($watchedItems)): ?>
            <p class="info-box">You haven't watched any trailers yet. Go browse!</p>
        <?php else: ?>
            <div class="poster-grid">
                <?php foreach ($watchedItems as $item): ?>
                    <?php
                        // Extract necessary details from the $item array (which contains TMDb details + watched_at)
                        $itemType = $item['media_type']; // We added this key earlier
                        $itemId = $item['id'];
                        // Use 'title' for movies, 'name' for TV shows
                        $itemTitle = htmlspecialchars($itemType === 'movie' ? ($item['title'] ?? 'Untitled') : ($item['name'] ?? 'Untitled'));
                        $posterPath = $item['poster_path']; // Already checked this exists

                        // Construct the poster URL
                        $posterUrl = TMDb::IMAGE_BASE_URL_W500 . $posterPath;

                         // Format the watched date from the timestamp we added
                         $watchedDateStr = 'Watched recently'; // Default text
                         try {
                             if (!empty($item['watched_at_timestamp'])) {
                                 $watchedDate = new DateTime($item['watched_at_timestamp']);
                                 // Format the date nicely
                                 $watchedDateStr = 'Watched: ' . $watchedDate->format('M d, Y');
                             }
                         } catch (Exception $e) { /* Ignore date format error, use default */ }

                    ?>
                     <a href="details.php?type=<?php echo $itemType; ?>&id=<?php echo $itemId; ?>" class="poster-card group relative" title="<?php echo $itemTitle . ' - ' . $watchedDateStr; ?>"> <?php // Add watched date to hover title attribute ?>
                        <img src="<?php echo $posterUrl; ?>" alt="<?php echo $itemTitle; ?> Poster" class="poster-image" loading="lazy" onerror="this.onerror=null; this.src='https://placehold.co/500x750/141414/444444?text=No+Image';">
                         <div class="absolute bottom-0 left-0 right-0 p-1 bg-black bg-opacity-60 text-center text-xs text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                             <?php echo $watchedDateStr; ?>
                         </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; // End if empty($watchedItems) ?>
    <?php endif; // End if !$apiError && !$fetchError ?>

</main>

<?php
// Include the main site footer (make sure the path is correct)
require_once __DIR__ . '/includes/footer.php';
?>
