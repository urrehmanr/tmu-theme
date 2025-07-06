<?php
/**
 * TMU Data Helper Utility
 *
 * @package TMU
 * @version 1.0.0
 */

namespace TMU\Utils;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Data Helper Class
 * 
 * Provides utility methods for data access and manipulation
 */
class DataHelper {
    
    /**
     * Get movie data from database
     *
     * @param int $post_id Post ID
     * @return array|null Movie data or null if not found
     */
    public static function getMovieData(int $post_id): ?array {
        return tmu_get_movie_data($post_id);
    }
    
    /**
     * Get TV series data from database
     *
     * @param int $post_id Post ID
     * @return array|null TV data or null if not found
     */
    public static function getTVData(int $post_id): ?array {
        return tmu_get_tv_data($post_id);
    }
    
    /**
     * Get drama data from database
     *
     * @param int $post_id Post ID
     * @return array|null Drama data or null if not found
     */
    public static function getDramaData(int $post_id): ?array {
        return tmu_get_drama_data($post_id);
    }
    
    /**
     * Get people data from database
     *
     * @param int $post_id Post ID
     * @return array|null People data or null if not found
     */
    public static function getPeopleData(int $post_id): ?array {
        return tmu_get_people_data($post_id);
    }
    
    /**
     * Get video data from database
     *
     * @param int $post_id Post ID
     * @return array|null Video data or null if not found
     */
    public static function getVideoData(int $post_id): ?array {
        return tmu_get_video_data($post_id);
    }
}