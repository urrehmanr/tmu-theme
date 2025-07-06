<?php
/**
 * TMU Template Helper Functions
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get TMU post meta
 *
 * @param int $post_id Post ID
 * @param string $key Meta key
 * @param mixed $default Default value
 * @return mixed
 */
function tmu_get_meta(int $post_id, string $key, $default = '') {
    // Try custom table first
    if (class_exists('TMU\\Database\\DataManager')) {
        $data_manager = TMU\Database\DataManager::getInstance();
        $value = $data_manager->getMeta($post_id, $key);
        
        if ($value !== null) {
            return $value;
        }
    }
    
    // Fallback to WordPress meta
    return get_post_meta($post_id, $key, true) ?: $default;
}

/**
 * Update TMU post meta
 *
 * @param int $post_id Post ID
 * @param string $key Meta key
 * @param mixed $value Meta value
 * @return bool
 */
function tmu_update_meta(int $post_id, string $key, $value): bool {
    // Try custom table first
    if (class_exists('TMU\\Database\\DataManager')) {
        $data_manager = TMU\Database\DataManager::getInstance();
        return $data_manager->updateMeta($post_id, $key, $value);
    }
    
    // Fallback to WordPress meta
    return update_post_meta($post_id, $key, $value) !== false;
}

/**
 * Get TMU template part
 *
 * @param string $template Template name
 * @param array $variables Variables to pass to template
 */
function tmu_get_template_part(string $template, array $variables = []): void {
    if (class_exists('TMU\\Frontend\\TemplateLoader')) {
        TMU\Frontend\TemplateLoader::getInstance()->getTemplatePart($template, $variables);
    } else {
        // Fallback - extract variables and load template
        if (!empty($variables)) {
            extract($variables, EXTR_SKIP);
        }
        
        $template_path = locate_template("templates/parts/{$template}.php");
        
        if ($template_path) {
            include $template_path;
        } else {
            // Try in theme directory
            $fallback_path = TMU_THEME_DIR . "/templates/parts/{$template}.php";
            if (file_exists($fallback_path)) {
                include $fallback_path;
            }
        }
    }
}

/**
 * Display TMU rating
 *
 * @param int $post_id Post ID
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function tmu_rating(int $post_id, bool $echo = true) {
    $average_rating = tmu_get_meta($post_id, 'average_rating', 0);
    $vote_count = tmu_get_meta($post_id, 'vote_count', 0);
    
    $output = sprintf(
        '<div class="tmu-rating flex items-center space-x-2" data-rating="%.1f" data-count="%d">',
        $average_rating,
        $vote_count
    );
    
    // Star display
    $output .= '<div class="stars flex space-x-1">';
    for ($i = 1; $i <= 10; $i++) {
        $class = $i <= round($average_rating) ? 'text-yellow-400' : 'text-gray-300';
        $output .= "<span class=\"star {$class}\">★</span>";
    }
    $output .= '</div>';
    
    $output .= sprintf(
        '<span class="rating-text text-sm text-gray-600">%.1f (%s)</span>',
        $average_rating,
        sprintf(_n('%d vote', '%d votes', $vote_count, 'tmu'), $vote_count)
    );
    
    $output .= '</div>';
    
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Display TMU breadcrumbs
 *
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function tmu_breadcrumbs(bool $echo = true) {
    $output = '<nav class="tmu-breadcrumbs text-sm" aria-label="Breadcrumb">';
    $output .= '<ol class="flex items-center space-x-2">';
    
    // Home link
    $output .= '<li>';
    $output .= '<a href="' . home_url() . '" class="text-blue-600 hover:text-blue-800">' . __('Home', 'tmu') . '</a>';
    $output .= '</li>';
    
    if (is_singular()) {
        $post_type = get_post_type();
        $post_type_object = get_post_type_object($post_type);
        
        if ($post_type_object && $post_type !== 'post') {
            $output .= '<li class="flex items-center space-x-2">';
            $output .= '<span class="text-gray-400">/</span>';
            $output .= '<a href="' . get_post_type_archive_link($post_type) . '" class="text-blue-600 hover:text-blue-800">';
            $output .= $post_type_object->labels->name;
            $output .= '</a>';
            $output .= '</li>';
        }
        
        $output .= '<li class="flex items-center space-x-2">';
        $output .= '<span class="text-gray-400">/</span>';
        $output .= '<span class="text-gray-600">' . get_the_title() . '</span>';
        $output .= '</li>';
    } elseif (is_archive()) {
        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            $output .= '<li class="flex items-center space-x-2">';
            $output .= '<span class="text-gray-400">/</span>';
            $output .= '<span class="text-gray-600">' . $term->name . '</span>';
            $output .= '</li>';
        } elseif (is_post_type_archive()) {
            $post_type_object = get_queried_object();
            $output .= '<li class="flex items-center space-x-2">';
            $output .= '<span class="text-gray-400">/</span>';
            $output .= '<span class="text-gray-600">' . $post_type_object->labels->name . '</span>';
            $output .= '</li>';
        }
    }
    
    $output .= '</ol>';
    $output .= '</nav>';
    
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Display TMU poster image
 *
 * @param int $post_id Post ID
 * @param string $size Image size
 * @param array $attributes Additional attributes
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function tmu_poster(int $post_id, string $size = 'tmu-poster-medium', array $attributes = [], bool $echo = true) {
    $poster_url = tmu_get_meta($post_id, 'poster_url', '');
    $poster_path = tmu_get_meta($post_id, 'poster_path', '');
    
    // Try featured image first
    if (has_post_thumbnail($post_id)) {
        $output = get_the_post_thumbnail($post_id, $size, $attributes);
    } elseif ($poster_url) {
        // Use external poster URL
        $default_attrs = [
            'src' => $poster_url,
            'alt' => get_the_title($post_id),
            'class' => 'tmu-poster'
        ];
        
        $attrs = array_merge($default_attrs, $attributes);
        $output = '<img';
        foreach ($attrs as $key => $value) {
            $output .= ' ' . $key . '="' . esc_attr($value) . '"';
        }
        $output .= '>';
    } else {
        // Default placeholder
        $output = '<div class="tmu-poster-placeholder bg-gray-200 flex items-center justify-center">';
        $output .= '<span class="text-gray-500">' . __('No Image', 'tmu') . '</span>';
        $output .= '</div>';
    }
    
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Display TMU backdrop image
 *
 * @param int $post_id Post ID
 * @param string $size Image size
 * @param array $attributes Additional attributes
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function tmu_backdrop(int $post_id, string $size = 'tmu-backdrop-large', array $attributes = [], bool $echo = true) {
    $backdrop_url = tmu_get_meta($post_id, 'backdrop_url', '');
    $backdrop_path = tmu_get_meta($post_id, 'backdrop_path', '');
    
    if ($backdrop_url) {
        $default_attrs = [
            'src' => $backdrop_url,
            'alt' => get_the_title($post_id) . ' backdrop',
            'class' => 'tmu-backdrop'
        ];
        
        $attrs = array_merge($default_attrs, $attributes);
        $output = '<img';
        foreach ($attrs as $key => $value) {
            $output .= ' ' . $key . '="' . esc_attr($value) . '"';
        }
        $output .= '>';
    } else {
        $output = '';
    }
    
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Display TMU release date
 *
 * @param int $post_id Post ID
 * @param string $format Date format
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function tmu_release_date(int $post_id, string $format = 'F j, Y', bool $echo = true) {
    $release_date = tmu_get_meta($post_id, 'release_date', '');
    
    if (empty($release_date)) {
        return '';
    }
    
    $timestamp = strtotime($release_date);
    $output = $timestamp ? date($format, $timestamp) : $release_date;
    
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Display TMU runtime
 *
 * @param int $post_id Post ID
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function tmu_runtime(int $post_id, bool $echo = true) {
    $runtime = tmu_get_meta($post_id, 'runtime', 0);
    
    if (empty($runtime)) {
        return '';
    }
    
    $output = tmu_format_duration($runtime);
    
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Display TMU genres
 *
 * @param int $post_id Post ID
 * @param string $separator Separator between genres
 * @param bool $links Whether to show as links
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function tmu_genres(int $post_id, string $separator = ', ', bool $links = true, bool $echo = true) {
    $genres = get_the_terms($post_id, 'genre');
    
    if (!$genres || is_wp_error($genres)) {
        return '';
    }
    
    $genre_list = [];
    
    foreach ($genres as $genre) {
        if ($links) {
            $genre_list[] = '<a href="' . get_term_link($genre) . '" class="text-blue-600 hover:text-blue-800">' . $genre->name . '</a>';
        } else {
            $genre_list[] = $genre->name;
        }
    }
    
    $output = implode($separator, $genre_list);
    
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Display TMU cast
 *
 * @param int $post_id Post ID
 * @param int $limit Number of cast members to show
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function tmu_cast(int $post_id, int $limit = 5, bool $echo = true) {
    if (!class_exists('TMU\\Database\\DataManager')) {
        return '';
    }
    
    $data_manager = TMU\Database\DataManager::getInstance();
    $cast = $data_manager->getCast($post_id, $limit);
    
    if (empty($cast)) {
        return '';
    }
    
    $output = '<div class="tmu-cast">';
    $output .= '<h3 class="text-lg font-semibold mb-2">' . __('Cast', 'tmu') . '</h3>';
    $output .= '<ul class="space-y-2">';
    
    foreach ($cast as $member) {
        $output .= '<li class="flex items-center space-x-3">';
        
        // Profile image
        if (!empty($member['profile_path'])) {
            $output .= '<img src="' . esc_url($member['profile_path']) . '" alt="' . esc_attr($member['name']) . '" class="w-12 h-12 rounded-full object-cover">';
        } else {
            $output .= '<div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center">';
            $output .= '<span class="text-gray-500 text-xs">' . __('No Photo', 'tmu') . '</span>';
            $output .= '</div>';
        }
        
        // Name and character
        $output .= '<div>';
        $output .= '<div class="font-medium">' . esc_html($member['name']) . '</div>';
        if (!empty($member['character_name'])) {
            $output .= '<div class="text-sm text-gray-600">as ' . esc_html($member['character_name']) . '</div>';
        }
        $output .= '</div>';
        
        $output .= '</li>';
    }
    
    $output .= '</ul>';
    $output .= '</div>';
    
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Display TMU pagination
 *
 * @param WP_Query $query Optional query object
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function tmu_pagination($query = null, bool $echo = true) {
    global $wp_query;
    
    if (!$query) {
        $query = $wp_query;
    }
    
    $output = paginate_links([
        'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
        'format' => '?paged=%#%',
        'current' => max(1, get_query_var('paged')),
        'total' => $query->max_num_pages,
        'type' => 'array',
        'prev_text' => __('&laquo; Previous', 'tmu'),
        'next_text' => __('Next &raquo;', 'tmu'),
    ]);
    
    if (!$output) {
        return '';
    }
    
    $pagination = '<nav class="tmu-pagination" aria-label="Pagination">';
    $pagination .= '<ul class="flex space-x-2 justify-center">';
    
    foreach ($output as $link) {
        $pagination .= '<li>';
        if (strpos($link, 'current') !== false) {
            $pagination .= str_replace('page-numbers', 'page-numbers bg-blue-600 text-white px-3 py-2 rounded', $link);
        } else {
            $pagination .= str_replace('page-numbers', 'page-numbers text-blue-600 hover:bg-blue-100 px-3 py-2 rounded border', $link);
        }
        $pagination .= '</li>';
    }
    
    $pagination .= '</ul>';
    $pagination .= '</nav>';
    
    if ($echo) {
        echo $pagination;
    } else {
        return $pagination;
    }
}

/**
 * Check if current post is TMU post type
 *
 * @param int $post_id Optional post ID
 * @return bool
 */
function is_tmu_post_type(int $post_id = null): bool {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $post_type = get_post_type($post_id);
    $tmu_post_types = tmu_get_post_types();
    
    return in_array($post_type, $tmu_post_types);
}

/**
 * Get TMU archive title
 *
 * @return string
 */
function tmu_get_archive_title(): string {
    if (is_post_type_archive()) {
        $post_type_object = get_queried_object();
        return $post_type_object->labels->name;
    } elseif (is_tax()) {
        $term = get_queried_object();
        return $term->name;
    }
    
    return '';
}

/**
 * Get TMU archive description
 *
 * @return string
 */
function tmu_get_archive_description(): string {
    if (is_post_type_archive()) {
        $post_type_object = get_queried_object();
        return $post_type_object->description ?: '';
    } elseif (is_tax()) {
        $term = get_queried_object();
        return $term->description ?: '';
    }
    
    return '';
}