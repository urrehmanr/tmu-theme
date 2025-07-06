<?php
/**
 * TMU Theme Constants
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Theme configuration constants
define('TMU_THEME_NAME', 'TMU');
define('TMU_THEME_SLUG', 'tmu');
define('TMU_TEXT_DOMAIN', 'tmu');

// Database table names (preserving existing plugin tables)
define('TMU_MOVIES_TABLE', 'tmu_movies');
define('TMU_TV_SERIES_TABLE', 'tmu_tv_series');
define('TMU_DRAMAS_TABLE', 'tmu_dramas');
define('TMU_PEOPLE_TABLE', 'tmu_people');
define('TMU_VIDEOS_TABLE', 'tmu_videos');
define('TMU_MOVIES_CAST_TABLE', 'tmu_movies_cast');
define('TMU_MOVIES_CREW_TABLE', 'tmu_movies_crew');
define('TMU_TV_SERIES_CAST_TABLE', 'tmu_tv_series_cast');
define('TMU_TV_SERIES_CREW_TABLE', 'tmu_tv_series_crew');
define('TMU_TV_SERIES_SEASONS_TABLE', 'tmu_tv_series_seasons');
define('TMU_TV_SERIES_EPISODES_TABLE', 'tmu_tv_series_episodes');
define('TMU_DRAMAS_CAST_TABLE', 'tmu_dramas_cast');
define('TMU_DRAMAS_CREW_TABLE', 'tmu_dramas_crew');
define('TMU_DRAMAS_SEASONS_TABLE', 'tmu_dramas_seasons');
define('TMU_DRAMAS_EPISODES_TABLE', 'tmu_dramas_episodes');
define('TMU_SEO_OPTIONS_TABLE', 'tmu_seo_options');

// Post types
define('TMU_MOVIE_POST_TYPE', 'movie');
define('TMU_TV_SERIES_POST_TYPE', 'tv-series');
define('TMU_DRAMA_POST_TYPE', 'drama');
define('TMU_PEOPLE_POST_TYPE', 'people');
define('TMU_VIDEO_POST_TYPE', 'video');
define('TMU_SEASON_POST_TYPE', 'season');
define('TMU_EPISODE_POST_TYPE', 'episode');
define('TMU_DRAMA_EPISODE_POST_TYPE', 'drama-episode');

// Taxonomies
define('TMU_GENRE_TAXONOMY', 'genre');
define('TMU_COUNTRY_TAXONOMY', 'country');
define('TMU_LANGUAGE_TAXONOMY', 'language');
define('TMU_NETWORK_TAXONOMY', 'network');
define('TMU_YEAR_TAXONOMY', 'year');
define('TMU_KEYWORDS_TAXONOMY', 'keywords');
define('TMU_NATIONALITY_TAXONOMY', 'nationality');

// TMDB API configuration
define('TMU_TMDB_API_BASE_URL', 'https://api.themoviedb.org/3/');
define('TMU_TMDB_IMAGE_BASE_URL', 'https://image.tmdb.org/t/p/');
define('TMU_TMDB_POSTER_SIZES', ['w92', 'w154', 'w185', 'w342', 'w500', 'w780', 'original']);
define('TMU_TMDB_BACKDROP_SIZES', ['w300', 'w780', 'w1280', 'original']);

// Cache configuration
define('TMU_CACHE_GROUP', 'tmu_cache');
define('TMU_CACHE_EXPIRATION', 3600); // 1 hour
define('TMU_TRANSIENT_EXPIRATION', 86400); // 24 hours

// Pagination
define('TMU_POSTS_PER_PAGE', 20);
define('TMU_LOAD_MORE_POSTS', 10);

// Image sizes
define('TMU_POSTER_SIZES', [
    'small' => [185, 278],
    'medium' => [300, 450],
    'large' => [500, 750]
]);

define('TMU_BACKDROP_SIZES', [
    'small' => [533, 300],
    'medium' => [800, 450],
    'large' => [1280, 720]
]);