<?php
// lib/TMDb.php

/**
 * A simple PHP client for interacting with The Movie Database (TMDb) API v3.
 */
class TMDb {
    // Base URL for the TMDb API v3
    private const API_BASE_URL = 'https://api.themoviedb.org/3/';
    // Base URL for TMDb images
    public const IMAGE_BASE_URL_W500 = 'https://image.tmdb.org/t/p/w500';
    public const IMAGE_BASE_URL_ORIGINAL = 'https://image.tmdb.org/t/p/original';

    private string $apiKey;

    public function __construct(string $apiKey) {
        if (empty($apiKey)) {
            throw new InvalidArgumentException('TMDb API key cannot be empty.');
        }
        $this->apiKey = $apiKey;
    }

    private function makeRequest(string $endpoint, array $params = []): ?array {
        $params['api_key'] = $this->apiKey;
        // $params['language'] = 'en-US'; // Optional language setting

        $url = self::API_BASE_URL . $endpoint . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Uncomment if needed
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Uncomment if needed

        $responseJson = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("TMDb API cURL Error for '{$endpoint}': " . $curlError);
            return null;
        }
        if ($httpCode !== 200) {
            error_log("TMDb API HTTP Error for '{$endpoint}': Code " . $httpCode . " Response: " . $responseJson);
            return null;
        }
        $responseData = json_decode($responseJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("TMDb API JSON Decode Error for '{$endpoint}': " . json_last_error_msg());
            return null;
        }
        return $responseData;
    }

    // --- Methods for fetching lists ---

    public function getPopularMovies(int $page = 1): ?array {
        return $this->makeRequest('movie/popular', ['page' => $page]);
    }

    public function getPopularTvShows(int $page = 1): ?array {
        return $this->makeRequest('tv/popular', ['page' => $page]);
    }

    public function getTrending(string $mediaType = 'all', string $timeWindow = 'week', int $page = 1): ?array {
         if (!in_array($mediaType, ['all', 'movie', 'tv', 'person'])) $mediaType = 'all';
         if (!in_array($timeWindow, ['day', 'week'])) $timeWindow = 'week';
        return $this->makeRequest("trending/{$mediaType}/{$timeWindow}", ['page' => $page]);
    }

    // --- Methods for fetching details ---

    public function getMovieDetails(int $movieId): ?array {
         return $this->makeRequest("movie/{$movieId}");
    }

    public function getTvShowDetails(int $tvId): ?array {
         return $this->makeRequest("tv/{$tvId}");
    }

    // --- Methods for fetching videos/trailers ---

    public function getMovieVideos(int $movieId): ?array {
        return $this->makeRequest("movie/{$movieId}/videos");
    }

    public function getTvShowVideos(int $tvId): ?array {
         return $this->makeRequest("tv/{$tvId}/videos");
    }

    public function findTrailerKey(?array $videoData): ?string {
        if (!$videoData || empty($videoData['results'])) return null;
        $trailerKey = null;
        // Prioritize official YouTube trailers
        foreach ($videoData['results'] as $video) {
            if (strtolower($video['site'] ?? '') === 'youtube' && strtolower($video['type'] ?? '') === 'trailer' && ($video['official'] ?? false) && !empty($video['key'])) {
                $trailerKey = $video['key']; break;
            }
        }
        // Fallback 1: First YouTube trailer
        if (!$trailerKey) {
            foreach ($videoData['results'] as $video) {
                 if (strtolower($video['site'] ?? '') === 'youtube' && strtolower($video['type'] ?? '') === 'trailer' && !empty($video['key'])) {
                     $trailerKey = $video['key']; break;
                 }
            }
        }
        // Fallback 2: First YouTube video
         if (!$trailerKey) {
            foreach ($videoData['results'] as $video) {
                 if (strtolower($video['site'] ?? '') === 'youtube' && !empty($video['key'])) {
                     $trailerKey = $video['key']; break;
                 }
            }
        }
        return $trailerKey;
    }

    // --- NEW: Methods for searching ---

    /**
     * Searches for movies based on a query string.
     *
     * @param string $query The search query.
     * @param int $page The page number to fetch.
     * @param bool $includeAdult Whether to include adult content (default: false).
     * @return array|null Search results or null on error.
     */
    public function searchMovies(string $query, int $page = 1, bool $includeAdult = false): ?array {
        if (empty(trim($query))) {
            return ['results' => [], 'page' => 1, 'total_pages' => 0, 'total_results' => 0]; // Return empty result structure for empty query
        }
        $params = [
            'query' => $query,
            'page' => $page,
            'include_adult' => $includeAdult
        ];
        return $this->makeRequest('search/movie', $params);
    }

     /**
     * Searches for TV shows based on a query string.
     *
     * @param string $query The search query.
     * @param int $page The page number to fetch.
     * @param bool $includeAdult Whether to include adult content (default: false).
     * @return array|null Search results or null on error.
     */
    public function searchTvShows(string $query, int $page = 1, bool $includeAdult = false): ?array {
         if (empty(trim($query))) {
            return ['results' => [], 'page' => 1, 'total_pages' => 0, 'total_results' => 0];
        }
        $params = [
            'query' => $query,
            'page' => $page,
            'include_adult' => $includeAdult
        ];
        return $this->makeRequest('search/tv', $params);
    }

     /**
     * Searches across multiple types (movies, TV shows, people) - Multi Search.
     *
     * @param string $query The search query.
     * @param int $page The page number to fetch.
     * @param bool $includeAdult Whether to include adult content (default: false).
     * @return array|null Search results or null on error.
     */
    public function searchMulti(string $query, int $page = 1, bool $includeAdult = false): ?array {
         if (empty(trim($query))) {
            return ['results' => [], 'page' => 1, 'total_pages' => 0, 'total_results' => 0];
        }
        $params = [
            'query' => $query,
            'page' => $page,
            'include_adult' => $includeAdult
        ];
        // Note: Multi search results contain a 'media_type' field ('movie', 'tv', 'person')
        return $this->makeRequest('search/multi', $params);
    }

}
