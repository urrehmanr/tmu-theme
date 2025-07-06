<?php
/**
 * TMU Theme Helper Functions
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get TMU configuration
 *
 * @param string|null $key Configuration key
 * @return mixed Configuration value or entire config array
 */
function tmu_config(?string $key = null) {
    static $config = null;
    
    if ($config === null) {
        $config = include TMU_INCLUDES_DIR . '/config/config.php';
    }
    
    if ($key === null) {
        return $config;
    }
    
    return $config[$key] ?? null;
}

/**
 * Get movie data from database
 *
 * @param int $post_id Post ID
 * @return array|null Movie data or null if not found
 */
function tmu_get_movie_data(int $post_id): ?array {
    global $wpdb;
    
    $table = $wpdb->prefix . 'tmu_movies';
    $data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE ID = %d",
        $post_id
    ), ARRAY_A);
    
    if (!$data) {
        return null;
    }
    
    // Unserialize arrays
    if (!empty($data['star_cast'])) {
        $data['star_cast'] = maybe_unserialize($data['star_cast']);
    }
    if (!empty($data['credits'])) {
        $data['credits'] = maybe_unserialize($data['credits']);
    }
    if (!empty($data['images'])) {
        $data['images'] = maybe_unserialize($data['images']);
    }
    if (!empty($data['videos'])) {
        $data['videos'] = maybe_unserialize($data['videos']);
    }
    
    return $data;
}

/**
 * Get TV series data from database
 *
 * @param int $post_id Post ID
 * @return array|null TV data or null if not found
 */
function tmu_get_tv_data(int $post_id): ?array {
    global $wpdb;
    
    $table = $wpdb->prefix . 'tmu_tv_series';
    $data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE ID = %d",
        $post_id
    ), ARRAY_A);
    
    if (!$data) {
        return null;
    }
    
    // Unserialize arrays
    if (!empty($data['star_cast'])) {
        $data['star_cast'] = maybe_unserialize($data['star_cast']);
    }
    if (!empty($data['credits'])) {
        $data['credits'] = maybe_unserialize($data['credits']);
    }
    if (!empty($data['images'])) {
        $data['images'] = maybe_unserialize($data['images']);
    }
    if (!empty($data['videos'])) {
        $data['videos'] = maybe_unserialize($data['videos']);
    }
    if (!empty($data['seasons'])) {
        $data['seasons'] = maybe_unserialize($data['seasons']);
    }
    
    return $data;
}

/**
 * Get drama data from database
 *
 * @param int $post_id Post ID
 * @return array|null Drama data or null if not found
 */
function tmu_get_drama_data(int $post_id): ?array {
    global $wpdb;
    
    $table = $wpdb->prefix . 'tmu_dramas';
    $data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE ID = %d",
        $post_id
    ), ARRAY_A);
    
    if (!$data) {
        return null;
    }
    
    // Unserialize arrays
    if (!empty($data['star_cast'])) {
        $data['star_cast'] = maybe_unserialize($data['star_cast']);
    }
    if (!empty($data['credits'])) {
        $data['credits'] = maybe_unserialize($data['credits']);
    }
    if (!empty($data['images'])) {
        $data['images'] = maybe_unserialize($data['images']);
    }
    if (!empty($data['videos'])) {
        $data['videos'] = maybe_unserialize($data['videos']);
    }
    
    return $data;
}

/**
 * Get people data from database
 *
 * @param int $post_id Post ID
 * @return array|null People data or null if not found
 */
function tmu_get_people_data(int $post_id): ?array {
    global $wpdb;
    
    $table = $wpdb->prefix . 'tmu_people';
    $data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE ID = %d",
        $post_id
    ), ARRAY_A);
    
    if (!$data) {
        return null;
    }
    
    // Unserialize arrays
    if (!empty($data['videos'])) {
        $data['videos'] = maybe_unserialize($data['videos']);
    }
    if (!empty($data['photos'])) {
        $data['photos'] = maybe_unserialize($data['photos']);
    }
    if (!empty($data['known_for'])) {
        $data['known_for'] = maybe_unserialize($data['known_for']);
    }
    if (!empty($data['social_media_account'])) {
        $data['social_media_account'] = maybe_unserialize($data['social_media_account']);
    }
    
    return $data;
}

/**
 * Get video data from database
 *
 * @param int $post_id Post ID
 * @return array|null Video data or null if not found
 */
function tmu_get_video_data(int $post_id): ?array {
    global $wpdb;
    
    $table = $wpdb->prefix . 'tmu_videos';
    $data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE ID = %d",
        $post_id
    ), ARRAY_A);
    
    if (!$data) {
        return null;
    }
    
    // Unserialize video data
    if (!empty($data['video_data'])) {
        $data['video_data'] = maybe_unserialize($data['video_data']);
    }
    
    return $data;
}

/**
 * Get rating data for a post
 *
 * @param int $post_id Post ID
 * @return array Rating data
 */
function tmu_get_rating_data(int $post_id): array {
    $post_type = get_post_type($post_id);
    
    switch ($post_type) {
        case 'movie':
            $data = tmu_get_movie_data($post_id);
            break;
        case 'tv':
            $data = tmu_get_tv_data($post_id);
            break;
        case 'drama':
            $data = tmu_get_drama_data($post_id);
            break;
        default:
            return ['average' => 0, 'count' => 0];
    }
    
    return [
        'average' => $data['total_average_rating'] ?? 0,
        'count' => $data['total_vote_count'] ?? 0
    ];
}

/**
 * Render star rating HTML
 *
 * @param float $rating Rating value (0-10)
 * @param int $max_stars Maximum stars to display
 * @return string Star rating HTML
 */
function tmu_render_star_rating(float $rating, int $max_stars = 5): string {
    $rating = max(0, min(10, $rating)); // Clamp between 0-10
    $stars = ($rating / 10) * $max_stars; // Convert to star scale
    
    $output = '<div class="tmu-star-rating flex items-center space-x-1">';
    
    for ($i = 1; $i <= $max_stars; $i++) {
        $class = 'text-gray-300';
        
        if ($i <= floor($stars)) {
            $class = 'text-yellow-400';
        } elseif ($i <= ceil($stars)) {
            $class = 'text-yellow-400 opacity-50';
        }
        
        $output .= sprintf(
            '<svg class="w-4 h-4 %s" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>',
            $class
        );
    }
    
    $output .= sprintf(
        '<span class="text-sm text-gray-600 ml-2">%.1f</span>',
        $rating
    );
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Get linked terms for a post
 *
 * @param int $post_id Post ID
 * @param string $taxonomy Taxonomy name
 * @param string $separator Term separator
 * @return string Linked terms HTML
 */
function tmu_get_linked_terms(int $post_id, string $taxonomy, string $separator = ', '): string {
    $terms = get_the_terms($post_id, $taxonomy);
    
    if (!$terms || is_wp_error($terms)) {
        return '';
    }
    
    $linked_terms = array_map(function($term) use ($taxonomy) {
        return sprintf(
            '<a href="%s" class="text-tmu-primary-600 hover:text-tmu-primary-700 transition-colors">%s</a>',
            esc_url(get_term_link($term)),
            esc_html($term->name)
        );
    }, $terms);
    
    return implode($separator, $linked_terms);
}

/**
 * Format runtime in human readable format
 *
 * @param int $minutes Runtime in minutes
 * @return string Formatted runtime
 */
function tmu_format_runtime(int $minutes): string {
    if ($minutes < 60) {
        return sprintf(__('%d min', 'tmu'), $minutes);
    }
    
    $hours = floor($minutes / 60);
    $remaining_minutes = $minutes % 60;
    
    if ($remaining_minutes === 0) {
        return sprintf(__('%d hr', 'tmu'), $hours);
    }
    
    return sprintf(__('%d hr %d min', 'tmu'), $hours, $remaining_minutes);
}

/**
 * Calculate age from birth date
 *
 * @param string $birth_date Birth date (Y-m-d format)
 * @param string|null $death_date Death date (Y-m-d format) or null if alive
 * @return int Age in years
 */
function tmu_calculate_age(string $birth_date, ?string $death_date = null): int {
    $birth = new DateTime($birth_date);
    $end_date = $death_date ? new DateTime($death_date) : new DateTime();
    
    return $birth->diff($end_date)->y;
}

/**
 * Get TMDB image URL
 *
 * @param string $file_path TMDB file path
 * @param string $size Image size
 * @param string $type Image type (poster, backdrop, profile)
 * @return string Full image URL
 */
function tmu_get_tmdb_image_url(string $file_path, string $size = 'w500', string $type = 'poster'): string {
    if (empty($file_path)) {
        return '';
    }
    
    $base_url = 'https://image.tmdb.org/t/p/';
    $config = tmu_config('tmdb');
    
    // Validate size for image type
    $valid_sizes = $config['image_sizes'][$type] ?? ['w500'];
    if (!in_array($size, $valid_sizes)) {
        $size = 'w500';
    }
    
    return $base_url . $size . $file_path;
}

/**
 * Sanitize and format job string for database
 *
 * @param string $job Job title
 * @return string Sanitized job string
 */
function tmu_clean_job_string(string $job): string {
    // Remove special characters and convert to lowercase
    $job = strtolower($job);
    $job = preg_replace('/[^a-z0-9\s]/', '', $job);
    $job = preg_replace('/\s+/', '_', trim($job));
    
    return $job;
}

/**
 * Get breadcrumb navigation
 *
 * @param int|null $post_id Post ID or null for current post
 * @return array Breadcrumb items
 */
function tmu_get_breadcrumbs(?int $post_id = null): array {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $breadcrumbs = [
        [
            'title' => __('Home', 'tmu'),
            'url' => home_url('/'),
            'current' => false
        ]
    ];
    
    $post_type = get_post_type($post_id);
    $post_type_object = get_post_type_object($post_type);
    
    if ($post_type_object && $post_type !== 'post') {
        $breadcrumbs[] = [
            'title' => $post_type_object->labels->name,
            'url' => get_post_type_archive_link($post_type),
            'current' => false
        ];
    }
    
    // Add taxonomies if available
    $taxonomies = get_object_taxonomies($post_type, 'objects');
    foreach ($taxonomies as $taxonomy) {
        $terms = get_the_terms($post_id, $taxonomy->name);
        if ($terms && !is_wp_error($terms)) {
            $term = array_shift($terms);
            $breadcrumbs[] = [
                'title' => $term->name,
                'url' => get_term_link($term),
                'current' => false
            ];
            break; // Only add first taxonomy term
        }
    }
    
    // Add current post
    $breadcrumbs[] = [
        'title' => get_the_title($post_id),
        'url' => get_permalink($post_id),
        'current' => true
    ];
    
    return $breadcrumbs;
}

/**
 * Generate social sharing buttons
 *
 * @param string $url URL to share
 * @param string $title Title to share
 * @return string Social sharing HTML
 */
function tmu_generate_social_sharing(string $url, string $title): string {
    $encoded_url = urlencode($url);
    $encoded_title = urlencode($title);
    
    $platforms = [
        'facebook' => [
            'url' => "https://www.facebook.com/sharer/sharer.php?u={$encoded_url}",
            'icon' => 'facebook',
            'label' => __('Share on Facebook', 'tmu')
        ],
        'twitter' => [
            'url' => "https://twitter.com/intent/tweet?url={$encoded_url}&text={$encoded_title}",
            'icon' => 'twitter',
            'label' => __('Share on Twitter', 'tmu')
        ],
        'linkedin' => [
            'url' => "https://www.linkedin.com/sharing/share-offsite/?url={$encoded_url}",
            'icon' => 'linkedin',
            'label' => __('Share on LinkedIn', 'tmu')
        ],
        'reddit' => [
            'url' => "https://reddit.com/submit?url={$encoded_url}&title={$encoded_title}",
            'icon' => 'reddit',
            'label' => __('Share on Reddit', 'tmu')
        ]
    ];
    
    $output = '<div class="tmu-social-sharing flex items-center space-x-2">';
    $output .= '<span class="text-sm text-gray-600">' . __('Share:', 'tmu') . '</span>';
    
    foreach ($platforms as $platform => $data) {
        $output .= sprintf(
            '<a href="%s" target="_blank" rel="noopener" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-600 hover:bg-%s-500 hover:text-white transition-colors" title="%s">
                <span class="sr-only">%s</span>
                <i class="fab fa-%s text-sm"></i>
            </a>',
            esc_url($data['url']),
            esc_attr($platform),
            esc_attr($data['label']),
            esc_html($data['label']),
            esc_attr($data['icon'])
        );
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Generate random string
 *
 * @param int $length String length
 * @return string Random string
 */
function tmu_generate_random_string(int $length = 8): string {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random_string = '';
    
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $random_string;
}

/**
 * Check if image URL exists
 *
 * @param string $url Image URL
 * @return bool True if image exists
 */
function tmu_image_exists(string $url): bool {
    $headers = @get_headers($url);
    return $headers && strpos($headers[0], '200') !== false;
}

/**
 * Get theme option with default value
 *
 * @param string $option Option name
 * @param mixed $default Default value
 * @return mixed Option value or default
 */
function tmu_get_option(string $option, $default = null) {
    return get_option($option, $default);
}

/**
 * Set theme option
 *
 * @param string $option Option name
 * @param mixed $value Option value
 * @return bool True if successful
 */
function tmu_set_option(string $option, $value): bool {
    return update_option($option, $value);
}

/**
 * Delete theme option
 *
 * @param string $option Option name
 * @return bool True if successful
 */
function tmu_delete_option(string $option): bool {
    return delete_option($option);
}

/**
 * Get current theme version
 *
 * @return string Theme version
 */
function tmu_get_version(): string {
    return TMU_VERSION;
}

/**
 * Check if current page is TMU related
 *
 * @return bool True if TMU related page
 */
function tmu_is_tmu_page(): bool {
    if (is_admin()) {
        return false;
    }
    
    $post_types = ['movie', 'tv', 'drama', 'people', 'video', 'season', 'episode', 'drama-episode'];
    
    if (is_singular($post_types) || is_post_type_archive($post_types)) {
        return true;
    }
    
    $taxonomies = ['genre', 'country', 'language', 'by-year', 'network', 'channel', 'keyword', 'nationality'];
    
    if (is_tax($taxonomies)) {
        return true;
    }
    
    return false;
}

/**
 * Log message to TMU log file
 *
 * @param string $message Log message
 * @param string $level Log level (info, warning, error)
 * @return void
 */
function tmu_log(string $message, string $level = 'info'): void {
    if (!WP_DEBUG) {
        return;
    }
    
    $log_file = WP_CONTENT_DIR . '/uploads/tmu-logs/tmu.log';
    $log_dir = dirname($log_file);
    
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }
    
    $timestamp = current_time('Y-m-d H:i:s');
    $formatted_message = sprintf("[%s] [%s] %s\n", $timestamp, strtoupper($level), $message);
    
    error_log($formatted_message, 3, $log_file);
}