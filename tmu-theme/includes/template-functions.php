<?php
/**
 * Template Functions
 * 
 * Helper functions for TMU theme templates
 * 
 * @package TMU
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get movie data for template display
 * 
 * @param int $post_id Post ID
 * @return array Movie data
 */
function tmu_get_movie_data(int $post_id): array {
    $data_mapper = new \TMU\API\TMDB\DataMapper();
    return $data_mapper->getMappedData($post_id, 'movie');
}

/**
 * Get TV show data for template display
 * 
 * @param int $post_id Post ID
 * @return array TV show data
 */
function tmu_get_tv_data(int $post_id): array {
    $data_mapper = new \TMU\API\TMDB\DataMapper();
    return $data_mapper->getMappedData($post_id, 'tv');
}

/**
 * Get drama data for template display
 * 
 * @param int $post_id Post ID
 * @return array Drama data
 */
function tmu_get_drama_data(int $post_id): array {
    $data_mapper = new \TMU\API\TMDB\DataMapper();
    return $data_mapper->getMappedData($post_id, 'drama');
}

/**
 * Get person data for template display
 * 
 * @param int $post_id Post ID
 * @return array Person data
 */
function tmu_get_person_data(int $post_id): array {
    $data_mapper = new \TMU\API\TMDB\DataMapper();
    return $data_mapper->getMappedData($post_id, 'people');
}

/**
 * Render rating stars
 * 
 * @param float $rating Rating value (0-10)
 * @param int $max_stars Maximum number of stars
 * @return string HTML for rating stars
 */
function tmu_render_rating(float $rating, int $max_stars = 5): string {
    $rating_normalized = $rating / 2; // Convert 10-point to 5-point scale
    $full_stars = floor($rating_normalized);
    $half_star = ($rating_normalized - $full_stars) >= 0.5;
    $empty_stars = $max_stars - $full_stars - ($half_star ? 1 : 0);
    
    $output = '<div class="tmu-rating-stars">';
    
    // Full stars
    for ($i = 0; $i < $full_stars; $i++) {
        $output .= '<span class="star star-full">★</span>';
    }
    
    // Half star
    if ($half_star) {
        $output .= '<span class="star star-half">☆</span>';
    }
    
    // Empty stars
    for ($i = 0; $i < $empty_stars; $i++) {
        $output .= '<span class="star star-empty">☆</span>';
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Get TMDB image URL
 * 
 * @param string $file_path TMDB file path
 * @param string $size Image size
 * @return string Full image URL
 */
function tmu_get_image_url(string $file_path, string $size = 'w500'): string {
    if (empty($file_path)) {
        return '';
    }
    
    $image_service = new \TMU\API\TMDB\ImageSyncService();
    return $image_service->getImageUrl($file_path, $size);
}

/**
 * Format runtime to hours and minutes
 * 
 * @param int $runtime Runtime in minutes
 * @return string Formatted runtime
 */
function tmu_format_runtime(int $runtime): string {
    if ($runtime <= 0) {
        return '';
    }
    
    $hours = floor($runtime / 60);
    $minutes = $runtime % 60;
    
    $output = '';
    if ($hours > 0) {
        $output .= $hours . 'h ';
    }
    if ($minutes > 0) {
        $output .= $minutes . 'm';
    }
    
    return trim($output);
}

/**
 * Format currency value
 * 
 * @param int $amount Amount in dollars
 * @return string Formatted currency
 */
function tmu_format_currency(int $amount): string {
    if ($amount <= 0) {
        return '';
    }
    
    if ($amount >= 1000000000) {
        return '$' . round($amount / 1000000000, 1) . 'B';
    } elseif ($amount >= 1000000) {
        return '$' . round($amount / 1000000, 1) . 'M';
    } elseif ($amount >= 1000) {
        return '$' . round($amount / 1000, 1) . 'K';
    }
    
    return '$' . number_format($amount);
}

/**
 * Get genre links
 * 
 * @param int $post_id Post ID
 * @return array Array of genre links
 */
function tmu_get_genre_links(int $post_id): array {
    $genres = get_the_terms($post_id, 'genre');
    $links = [];
    
    if ($genres && !is_wp_error($genres)) {
        foreach ($genres as $genre) {
            $links[] = [
                'name' => $genre->name,
                'url' => get_term_link($genre),
                'slug' => $genre->slug
            ];
        }
    }
    
    return $links;
}

/**
 * Get country links
 * 
 * @param int $post_id Post ID
 * @return array Array of country links
 */
function tmu_get_country_links(int $post_id): array {
    $countries = get_the_terms($post_id, 'country');
    $links = [];
    
    if ($countries && !is_wp_error($countries)) {
        foreach ($countries as $country) {
            $links[] = [
                'name' => $country->name,
                'url' => get_term_link($country),
                'slug' => $country->slug
            ];
        }
    }
    
    return $links;
}

/**
 * Truncate text with word boundary
 * 
 * @param string $text Text to truncate
 * @param int $limit Character limit
 * @param string $ellipsis Ellipsis string
 * @return string Truncated text
 */
function tmu_truncate_text(string $text, int $limit = 150, string $ellipsis = '...'): string {
    if (strlen($text) <= $limit) {
        return $text;
    }
    
    $truncated = substr($text, 0, $limit);
    $last_space = strrpos($truncated, ' ');
    
    if ($last_space !== false) {
        $truncated = substr($truncated, 0, $last_space);
    }
    
    return $truncated . $ellipsis;
}

/**
 * Get related posts
 * 
 * @param int $post_id Current post ID
 * @param string $post_type Post type
 * @param int $limit Number of posts to return
 * @return array Array of related posts
 */
function tmu_get_related_posts(int $post_id, string $post_type, int $limit = 6): array {
    $genres = wp_get_post_terms($post_id, 'genre', ['fields' => 'ids']);
    
    if (empty($genres)) {
        return [];
    }
    
    $args = [
        'post_type' => $post_type,
        'posts_per_page' => $limit,
        'post__not_in' => [$post_id],
        'tax_query' => [
            [
                'taxonomy' => 'genre',
                'field' => 'term_id',
                'terms' => $genres,
                'operator' => 'IN'
            ]
        ],
        'meta_query' => [
            [
                'key' => 'vote_average',
                'value' => 0,
                'compare' => '>'
            ]
        ],
        'orderby' => 'meta_value_num',
        'meta_key' => 'vote_average',
        'order' => 'DESC'
    ];
    
    return get_posts($args);
}

/**
 * Get cast and crew for a post
 * 
 * @param int $post_id Post ID
 * @return array Cast and crew data
 */
function tmu_get_cast_crew(int $post_id): array {
    $storage = new \TMU\Fields\Storage\CustomTableStorage();
    $credits_json = $storage->get($post_id, 'credits');
    
    if (empty($credits_json)) {
        return ['cast' => [], 'crew' => []];
    }
    
    $credits = json_decode($credits_json, true);
    
    return [
        'cast' => $credits['cast'] ?? [],
        'crew' => $credits['crew'] ?? []
    ];
}

/**
 * Get video trailer URL
 * 
 * @param int $post_id Post ID
 * @return string Trailer URL
 */
function tmu_get_trailer_url(int $post_id): string {
    $videos = get_posts([
        'post_type' => 'video',
        'post_parent' => $post_id,
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => 'content_type',
                'value' => 'trailer',
                'compare' => '='
            ]
        ]
    ]);
    
    if (empty($videos)) {
        return '';
    }
    
    $storage = new \TMU\Fields\Storage\CustomTableStorage();
    $source = $storage->get($videos[0]->ID, 'source');
    
    if ($source) {
        return 'https://www.youtube.com/watch?v=' . $source;
    }
    
    return '';
}

/**
 * Check if content is featured
 * 
 * @param int $post_id Post ID
 * @return bool True if featured
 */
function tmu_is_featured(int $post_id): bool {
    return (bool) get_post_meta($post_id, '_tmu_featured', true);
}

/**
 * Get content age rating
 * 
 * @param int $post_id Post ID
 * @return string Age rating
 */
function tmu_get_age_rating(int $post_id): string {
    $storage = new \TMU\Fields\Storage\CustomTableStorage();
    $adult = $storage->get($post_id, 'adult');
    
    return $adult ? 'R' : 'PG-13';
}

/**
 * Get content status with styling
 * 
 * @param string $status Status string
 * @return string HTML with status styling
 */
function tmu_get_status_badge(string $status): string {
    $class = 'tmu-status-badge';
    
    switch (strtolower($status)) {
        case 'released':
        case 'ended':
            $class .= ' status-released';
            break;
        case 'returning series':
        case 'in production':
            $class .= ' status-active';
            break;
        case 'canceled':
        case 'cancelled':
            $class .= ' status-canceled';
            break;
        default:
            $class .= ' status-default';
    }
    
    return '<span class="' . esc_attr($class) . '">' . esc_html($status) . '</span>';
}

/**
 * Get breadcrumb navigation
 * 
 * @param int $post_id Post ID
 * @return array Breadcrumb items
 */
function tmu_get_breadcrumbs(int $post_id = null): array {
    $breadcrumbs = [];
    
    // Home
    $breadcrumbs[] = [
        'title' => __('Home', 'tmu-theme'),
        'url' => home_url('/')
    ];
    
    if (is_single() && $post_id) {
        $post_type = get_post_type($post_id);
        $post_type_object = get_post_type_object($post_type);
        
        // Post type archive
        if ($post_type_object && $post_type_object->has_archive) {
            $breadcrumbs[] = [
                'title' => $post_type_object->labels->name,
                'url' => get_post_type_archive_link($post_type)
            ];
        }
        
        // Current post
        $breadcrumbs[] = [
            'title' => get_the_title($post_id),
            'url' => get_permalink($post_id),
            'current' => true
        ];
        
    } elseif (is_post_type_archive()) {
        $post_type_object = get_queried_object();
        $breadcrumbs[] = [
            'title' => $post_type_object->labels->name,
            'url' => get_post_type_archive_link($post_type_object->name),
            'current' => true
        ];
        
    } elseif (is_tax()) {
        $term = get_queried_object();
        $taxonomy = get_taxonomy($term->taxonomy);
        
        $breadcrumbs[] = [
            'title' => $taxonomy->labels->name,
            'url' => get_term_link($term),
            'current' => true
        ];
    }
    
    return $breadcrumbs;
}

/**
 * Render breadcrumb navigation
 * 
 * @param int $post_id Post ID
 * @return string Breadcrumb HTML
 */
function tmu_render_breadcrumbs(int $post_id = null): string {
    $breadcrumbs = tmu_get_breadcrumbs($post_id);
    
    if (empty($breadcrumbs)) {
        return '';
    }
    
    $output = '<nav class="tmu-breadcrumbs" aria-label="' . esc_attr__('Breadcrumb', 'tmu-theme') . '">';
    $output .= '<ol class="breadcrumb-list">';
    
    foreach ($breadcrumbs as $index => $crumb) {
        $is_last = $index === count($breadcrumbs) - 1;
        $output .= '<li class="breadcrumb-item' . ($is_last ? ' current' : '') . '">';
        
        if (!$is_last) {
            $output .= '<a href="' . esc_url($crumb['url']) . '">' . esc_html($crumb['title']) . '</a>';
            $output .= '<span class="separator">→</span>';
        } else {
            $output .= '<span>' . esc_html($crumb['title']) . '</span>';
        }
        
        $output .= '</li>';
    }
    
    $output .= '</ol>';
    $output .= '</nav>';
    
    return $output;
}

/**
 * Get fallback menu for navigation
 * 
 * @return string Fallback menu HTML
 */
function tmu_fallback_menu(): string {
    $output = '<ul class="tmu-nav-menu fallback-menu">';
    $output .= '<li><a href="' . esc_url(home_url('/')) . '">' . __('Home', 'tmu-theme') . '</a></li>';
    
    if (post_type_exists('movie')) {
        $output .= '<li><a href="' . esc_url(get_post_type_archive_link('movie')) . '">' . __('Movies', 'tmu-theme') . '</a></li>';
    }
    
    if (post_type_exists('tv')) {
        $output .= '<li><a href="' . esc_url(get_post_type_archive_link('tv')) . '">' . __('TV Shows', 'tmu-theme') . '</a></li>';
    }
    
    if (post_type_exists('drama')) {
        $output .= '<li><a href="' . esc_url(get_post_type_archive_link('drama')) . '">' . __('Dramas', 'tmu-theme') . '</a></li>';
    }
    
    if (post_type_exists('people')) {
        $output .= '<li><a href="' . esc_url(get_post_type_archive_link('people')) . '">' . __('People', 'tmu-theme') . '</a></li>';
    }
    
    $output .= '</ul>';
    
    return $output;
}