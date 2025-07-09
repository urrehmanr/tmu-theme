<?php
/**
 * TMU Theme Template Helper Functions
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the current post type safely
 *
 * @return string Current post type
 */
function tmu_get_current_post_type(): string {
    global $post;
    
    if (is_admin() && isset($_GET['post_type'])) {
        return sanitize_text_field($_GET['post_type']);
    }
    
    if (is_admin() && isset($_GET['post'])) {
        $post_id = (int) $_GET['post'];
        return get_post_type($post_id) ?: '';
    }
    
    if (isset($post->post_type)) {
        return $post->post_type;
    }
    
    return get_post_type() ?: '';
}

/**
 * Check if current page is a TMU content type
 *
 * @return bool Whether current page is TMU content
 */
function tmu_is_tmu_content(): bool {
    $tmu_post_types = ['movie', 'tv', 'drama', 'season', 'episode', 'drama-episode', 'people', 'video'];
    return in_array(tmu_get_current_post_type(), $tmu_post_types);
}

/**
 * Get TMU post meta safely
 *
 * @param int $post_id Post ID
 * @param string $key Meta key
 * @param mixed $default Default value
 * @return mixed Meta value
 */
function tmu_get_post_meta(int $post_id, string $key, $default = '') {
    $value = get_post_meta($post_id, $key, true);
    return !empty($value) ? $value : $default;
}

/**
 * Display TMU post meta
 *
 * @param int $post_id Post ID
 * @param string $key Meta key
 * @param mixed $default Default value
 */
function tmu_the_post_meta(int $post_id, string $key, $default = ''): void {
    echo esc_html(tmu_get_post_meta($post_id, $key, $default));
}

/**
 * Get formatted release date
 *
 * @param int $post_id Post ID
 * @param string $format Date format
 * @return string Formatted date
 */
function tmu_get_release_date(int $post_id, string $format = 'F j, Y'): string {
    $date = tmu_get_post_meta($post_id, 'release_date');
    
    if (empty($date)) {
        return '';
    }
    
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : $date;
}

/**
 * Display formatted release date
 *
 * @param int $post_id Post ID
 * @param string $format Date format
 */
function tmu_the_release_date(int $post_id, string $format = 'F j, Y'): void {
    $date = tmu_get_release_date($post_id, $format);
    if (!empty($date)) {
        echo esc_html($date);
    }
}

/**
 * Get movie/TV show rating
 *
 * @param int $post_id Post ID
 * @param int $decimals Number of decimal places
 * @return string Formatted rating
 */
function tmu_get_rating(int $post_id, int $decimals = 1): string {
    $rating = tmu_get_post_meta($post_id, 'average_rating', 0);
    return $rating > 0 ? number_format((float) $rating, $decimals) : '';
}

/**
 * Display movie/TV show rating
 *
 * @param int $post_id Post ID
 * @param int $decimals Number of decimal places
 */
function tmu_the_rating(int $post_id, int $decimals = 1): void {
    $rating = tmu_get_rating($post_id, $decimals);
    if (!empty($rating)) {
        echo esc_html($rating);
    }
}

/**
 * Get formatted runtime
 *
 * @param int $post_id Post ID
 * @return string Formatted runtime
 */
function tmu_get_formatted_runtime(int $post_id): string {
    $runtime = tmu_get_post_meta($post_id, 'runtime', 0);
    return tmu_format_runtime((int) $runtime);
}

/**
 * Display formatted runtime
 *
 * @param int $post_id Post ID
 */
function tmu_the_runtime(int $post_id): void {
    $runtime = tmu_get_formatted_runtime($post_id);
    if (!empty($runtime)) {
        echo esc_html($runtime);
    }
}

/**
 * Get TMDB poster URL
 *
 * @param int $post_id Post ID
 * @param string $size Image size (w200, w500, original, etc.)
 * @return string Poster URL
 */
function tmu_get_poster_url(int $post_id, string $size = 'w500'): string {
    $poster_path = tmu_get_post_meta($post_id, 'poster_url');
    
    if (empty($poster_path)) {
        return '';
    }
    
    // If it's already a full URL, return as is
    if (filter_var($poster_path, FILTER_VALIDATE_URL)) {
        return $poster_path;
    }
    
    // If it's a TMDB path, build the URL
    if (strpos($poster_path, '/') === 0) {
        return "https://image.tmdb.org/t/p/{$size}{$poster_path}";
    }
    
    return $poster_path;
}

/**
 * Display poster image
 *
 * @param int $post_id Post ID
 * @param string $size Image size
 * @param array $attr Image attributes
 */
function tmu_the_poster(int $post_id, string $size = 'w500', array $attr = []): void {
    $poster_url = tmu_get_poster_url($post_id, $size);
    
    if (empty($poster_url)) {
        return;
    }
    
    $title = get_the_title($post_id);
    $default_attr = [
        'src' => $poster_url,
        'alt' => $title,
        'class' => 'tmu-poster',
        'loading' => 'lazy'
    ];
    
    $attr = array_merge($default_attr, $attr);
    $attr_string = '';
    
    foreach ($attr as $name => $value) {
        $attr_string .= sprintf(' %s="%s"', esc_attr($name), esc_attr($value));
    }
    
    echo '<img' . $attr_string . '>';
}

/**
 * Get TMDB backdrop URL
 *
 * @param int $post_id Post ID
 * @param string $size Image size
 * @return string Backdrop URL
 */
function tmu_get_backdrop_url(int $post_id, string $size = 'w1280'): string {
    $backdrop_path = tmu_get_post_meta($post_id, 'backdrop_url');
    
    if (empty($backdrop_path)) {
        return '';
    }
    
    // If it's already a full URL, return as is
    if (filter_var($backdrop_path, FILTER_VALIDATE_URL)) {
        return $backdrop_path;
    }
    
    // If it's a TMDB path, build the URL
    if (strpos($backdrop_path, '/') === 0) {
        return "https://image.tmdb.org/t/p/{$size}{$backdrop_path}";
    }
    
    return $backdrop_path;
}

/**
 * Get terms for a TMU post
 *
 * @param int $post_id Post ID
 * @param string $taxonomy Taxonomy name
 * @param string $field Field to return (name, slug, etc.)
 * @return array Terms
 */
function tmu_get_post_terms(int $post_id, string $taxonomy, string $field = 'name'): array {
    $terms = get_the_terms($post_id, $taxonomy);
    
    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }
    
    return wp_list_pluck($terms, $field);
}

/**
 * Display comma-separated terms
 *
 * @param int $post_id Post ID
 * @param string $taxonomy Taxonomy name
 * @param string $separator Separator string
 */
function tmu_the_terms(int $post_id, string $taxonomy, string $separator = ', '): void {
    $terms = tmu_get_post_terms($post_id, $taxonomy);
    
    if (!empty($terms)) {
        echo esc_html(implode($separator, $terms));
    }
}

/**
 * Get linked terms for a TMU post
 *
 * @param int $post_id Post ID
 * @param string $taxonomy Taxonomy name
 * @param string $separator Separator string
 * @return string Linked terms HTML
 */
function tmu_get_linked_terms(int $post_id, string $taxonomy, string $separator = ', '): string {
    $terms = get_the_terms($post_id, $taxonomy);
    
    if (is_wp_error($terms) || empty($terms)) {
        return '';
    }
    
    $term_links = [];
    foreach ($terms as $term) {
        $term_links[] = sprintf(
            '<a href="%s" class="tmu-term-link tmu-%s-link">%s</a>',
            esc_url(get_term_link($term)),
            esc_attr($taxonomy),
            esc_html($term->name)
        );
    }
    
    return implode($separator, $term_links);
}

/**
 * Display linked terms
 *
 * @param int $post_id Post ID
 * @param string $taxonomy Taxonomy name
 * @param string $separator Separator string
 */
function tmu_the_linked_terms(int $post_id, string $taxonomy, string $separator = ', '): void {
    echo tmu_get_linked_terms($post_id, $taxonomy, $separator);
}

/**
 * Get archive link for a post type
 *
 * @param string $post_type Post type
 * @return string Archive URL
 */
function tmu_get_archive_link(string $post_type): string {
    $archive_link = get_post_type_archive_link($post_type);
    return $archive_link ?: '';
}

/**
 * Get search form for TMU content
 *
 * @param array $args Search form arguments
 * @return string Search form HTML
 */
function tmu_get_search_form(array $args = []): string {
    $defaults = [
        'placeholder' => __('Search movies, TV shows...', 'tmu'),
        'button_text' => __('Search', 'tmu'),
        'show_filters' => false,
        'post_types' => ['movie', 'tv', 'drama']
    ];
    
    $args = array_merge($defaults, $args);
    
    ob_start();
    ?>
    <form role="search" method="get" class="tmu-search-form" action="<?php echo esc_url(home_url('/')); ?>">
        <div class="search-field-wrapper">
            <input type="search" 
                   class="search-field" 
                   placeholder="<?php echo esc_attr($args['placeholder']); ?>" 
                   value="<?php echo get_search_query(); ?>" 
                   name="s" />
            <?php if (!empty($args['post_types'])): ?>
                <?php foreach ($args['post_types'] as $post_type): ?>
                    <input type="hidden" name="post_type[]" value="<?php echo esc_attr($post_type); ?>" />
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button type="submit" class="search-submit">
            <?php echo esc_html($args['button_text']); ?>
        </button>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * Display search form for TMU content
 *
 * @param array $args Search form arguments
 */
function tmu_search_form(array $args = []): void {
    echo tmu_get_search_form($args);
}

/**
 * Get breadcrumb trail
 *
 * @return array Breadcrumb items
 */
function tmu_get_breadcrumbs(): array {
    $breadcrumbs = [];
    
    // Home
    $breadcrumbs[] = [
        'title' => __('Home', 'tmu'),
        'url' => home_url('/')
    ];
    
    if (is_singular()) {
        $post_type = get_post_type();
        $post_type_object = get_post_type_object($post_type);
        
        if ($post_type_object && $post_type_object->has_archive) {
            $breadcrumbs[] = [
                'title' => $post_type_object->labels->name,
                'url' => get_post_type_archive_link($post_type)
            ];
        }
        
        $breadcrumbs[] = [
            'title' => get_the_title(),
            'url' => ''
        ];
    } elseif (is_post_type_archive()) {
        $post_type_object = get_queried_object();
        $breadcrumbs[] = [
            'title' => $post_type_object->labels->name,
            'url' => ''
        ];
    } elseif (is_tax()) {
        $term = get_queried_object();
        $breadcrumbs[] = [
            'title' => $term->name,
            'url' => ''
        ];
    }
    
    return $breadcrumbs;
}

/**
 * Display breadcrumb navigation
 *
 * @param string $separator Breadcrumb separator
 */
function tmu_breadcrumbs(string $separator = ' / '): void {
    $breadcrumbs = tmu_get_breadcrumbs();
    
    if (count($breadcrumbs) <= 1) {
        return;
    }
    
    echo '<nav class="tmu-breadcrumbs" aria-label="' . esc_attr__('Breadcrumb', 'tmu') . '">';
    echo '<ol class="breadcrumb-list">';
    
    foreach ($breadcrumbs as $index => $crumb) {
        $is_last = ($index === count($breadcrumbs) - 1);
        
        echo '<li class="breadcrumb-item' . ($is_last ? ' active' : '') . '">';
        
        if (!$is_last && !empty($crumb['url'])) {
            echo '<a href="' . esc_url($crumb['url']) . '">' . esc_html($crumb['title']) . '</a>';
        } else {
            echo esc_html($crumb['title']);
        }
        
        if (!$is_last) {
            echo '<span class="separator">' . esc_html($separator) . '</span>';
        }
        
        echo '</li>';
    }
    
    echo '</ol>';
    echo '</nav>';
}