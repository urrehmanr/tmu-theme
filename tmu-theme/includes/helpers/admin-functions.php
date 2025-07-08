<?php
/**
 * TMU Theme Admin Helper Functions
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin notice
 *
 * @param string $message Notice message
 * @param string $type Notice type (success, warning, error, info)
 * @param bool $dismissible Whether notice is dismissible
 */
function tmu_add_admin_notice(string $message, string $type = 'info', bool $dismissible = true): void {
    $class = "notice notice-{$type}";
    if ($dismissible) {
        $class .= ' is-dismissible';
    }
    
    add_action('admin_notices', function() use ($message, $class) {
        printf('<div class="%s"><p>%s</p></div>', esc_attr($class), wp_kses_post($message));
    });
}

/**
 * Check if current screen is TMU admin page
 *
 * @param string $page_slug Optional specific page slug to check
 * @return bool Whether current screen is TMU admin page
 */
function tmu_is_admin_page(string $page_slug = ''): bool {
    if (!function_exists('get_current_screen')) {
        return false;
    }
    
    $screen = get_current_screen();
    
    if (!$screen) {
        return false;
    }
    
    // Check for TMU post types
    $tmu_post_types = ['movie', 'tv', 'drama', 'season', 'episode', 'drama-episode', 'people', 'video'];
    if (in_array($screen->post_type, $tmu_post_types)) {
        return true;
    }
    
    // Check for TMU taxonomies
    $tmu_taxonomies = ['genre', 'country', 'language', 'by-year', 'production-company', 'network', 'profession'];
    if (in_array($screen->taxonomy, $tmu_taxonomies)) {
        return true;
    }
    
    // Check for specific TMU admin pages
    if (!empty($page_slug)) {
        return $screen->id === $page_slug;
    }
    
    // Check for general TMU admin pages
    return strpos($screen->id, 'tmu') !== false || strpos($screen->base, 'tmu') !== false;
}

/**
 * Get TMU admin menu position
 *
 * @return int Menu position
 */
function tmu_get_admin_menu_position(): int {
    return apply_filters('tmu_admin_menu_position', 25);
}

/**
 * Add TMU admin menu separator
 *
 * @param int $position Menu position
 */
function tmu_add_admin_menu_separator(int $position): void {
    global $menu;
    $menu[$position] = [
        '',
        'read',
        'separator-tmu',
        '',
        'wp-menu-separator tmu-separator'
    ];
}

/**
 * Enqueue TMU admin scripts and styles
 *
 * @param string $hook Current admin page hook
 */
function tmu_enqueue_admin_assets(string $hook): void {
    // Only load on TMU admin pages
    if (!tmu_is_admin_page()) {
        return;
    }
    
    $version = tmu_get_version();
    
    // Admin styles
    wp_enqueue_style(
        'tmu-admin',
        tmu_asset_url('css/admin.css'),
        [],
        $version
    );
    
    // Admin scripts
    wp_enqueue_script(
        'tmu-admin',
        tmu_asset_url('js/admin.js'),
        ['jquery', 'wp-api'],
        $version,
        true
    );
    
    // Localize script
    wp_localize_script('tmu-admin', 'tmuAdmin', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'restUrl' => rest_url('tmu/v1/'),
        'nonce' => tmu_create_nonce('admin'),
        'restNonce' => wp_create_nonce('wp_rest'),
        'strings' => [
            'loading' => __('Loading...', 'tmu'),
            'error' => __('An error occurred. Please try again.', 'tmu'),
            'success' => __('Operation completed successfully.', 'tmu'),
            'confirm' => __('Are you sure?', 'tmu'),
            'cancel' => __('Cancel', 'tmu'),
            'save' => __('Save', 'tmu'),
            'delete' => __('Delete', 'tmu'),
        ]
    ]);
}

/**
 * Get admin page URL
 *
 * @param string $page Page slug
 * @param array $args URL arguments
 * @return string Admin page URL
 */
function tmu_get_admin_page_url(string $page, array $args = []): string {
    $url = admin_url("admin.php?page={$page}");
    
    if (!empty($args)) {
        $url = add_query_arg($args, $url);
    }
    
    return $url;
}

/**
 * Add meta box to TMU post types
 *
 * @param string $id Meta box ID
 * @param string $title Meta box title
 * @param callable $callback Callback function
 * @param string|array $screen Post type(s) or screen
 * @param string $context Meta box context
 * @param string $priority Meta box priority
 * @param array $callback_args Callback arguments
 */
function tmu_add_meta_box(string $id, string $title, callable $callback, $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = []): void {
    if ($screen === null) {
        $screen = ['movie', 'tv', 'drama', 'season', 'episode', 'drama-episode', 'people', 'video'];
    }
    
    add_meta_box($id, $title, $callback, $screen, $context, $priority, $callback_args);
}

/**
 * Save TMU meta box data
 *
 * @param int $post_id Post ID
 * @param array $meta_fields Meta fields to save
 * @param string $nonce_action Nonce action
 */
function tmu_save_meta_box(int $post_id, array $meta_fields, string $nonce_action = 'tmu_meta_box'): void {
    // Check nonce
    if (!isset($_POST['tmu_meta_box_nonce']) || !tmu_verify_nonce($_POST['tmu_meta_box_nonce'], $nonce_action)) {
        return;
    }
    
    // Check user permissions
    if (!tmu_current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Save meta fields
    foreach ($meta_fields as $field_key => $field_config) {
        if (isset($_POST[$field_key])) {
            $value = $_POST[$field_key];
            
            // Sanitize based on field type
            if (isset($field_config['type'])) {
                $value = tmu_sanitize_input($value, $field_config['type']);
            } else {
                $value = tmu_sanitize_input($value);
            }
            
            update_post_meta($post_id, $field_key, $value);
        }
    }
}

/**
 * Display admin field
 *
 * @param array $field Field configuration
 * @param mixed $value Field value
 */
function tmu_display_admin_field(array $field, $value = ''): void {
    $field_id = $field['id'] ?? '';
    $field_name = $field['name'] ?? $field_id;
    $field_type = $field['type'] ?? 'text';
    $field_label = $field['label'] ?? '';
    $field_description = $field['description'] ?? '';
    $field_placeholder = $field['placeholder'] ?? '';
    $field_options = $field['options'] ?? [];
    $field_class = $field['class'] ?? '';
    $field_required = $field['required'] ?? false;
    
    echo '<div class="tmu-field-wrapper tmu-field-' . esc_attr($field_type) . '">';
    
    // Label
    if (!empty($field_label)) {
        echo '<label for="' . esc_attr($field_id) . '" class="tmu-field-label">';
        echo esc_html($field_label);
        if ($field_required) {
            echo ' <span class="required">*</span>';
        }
        echo '</label>';
    }
    
    // Field
    echo '<div class="tmu-field-input">';
    
    switch ($field_type) {
        case 'text':
        case 'email':
        case 'url':
        case 'number':
            echo '<input type="' . esc_attr($field_type) . '" ';
            echo 'id="' . esc_attr($field_id) . '" ';
            echo 'name="' . esc_attr($field_name) . '" ';
            echo 'value="' . esc_attr($value) . '" ';
            echo 'class="regular-text ' . esc_attr($field_class) . '" ';
            if (!empty($field_placeholder)) {
                echo 'placeholder="' . esc_attr($field_placeholder) . '" ';
            }
            if ($field_required) {
                echo 'required ';
            }
            echo '/>';
            break;
            
        case 'textarea':
            echo '<textarea ';
            echo 'id="' . esc_attr($field_id) . '" ';
            echo 'name="' . esc_attr($field_name) . '" ';
            echo 'class="large-text ' . esc_attr($field_class) . '" ';
            echo 'rows="5" ';
            if (!empty($field_placeholder)) {
                echo 'placeholder="' . esc_attr($field_placeholder) . '" ';
            }
            if ($field_required) {
                echo 'required ';
            }
            echo '>' . esc_textarea($value) . '</textarea>';
            break;
            
        case 'select':
            echo '<select ';
            echo 'id="' . esc_attr($field_id) . '" ';
            echo 'name="' . esc_attr($field_name) . '" ';
            echo 'class="regular-text ' . esc_attr($field_class) . '" ';
            if ($field_required) {
                echo 'required ';
            }
            echo '>';
            
            if (!empty($field_placeholder)) {
                echo '<option value="">' . esc_html($field_placeholder) . '</option>';
            }
            
            foreach ($field_options as $option_value => $option_label) {
                echo '<option value="' . esc_attr($option_value) . '"';
                if ($value == $option_value) {
                    echo ' selected';
                }
                echo '>' . esc_html($option_label) . '</option>';
            }
            echo '</select>';
            break;
            
        case 'checkbox':
            echo '<label>';
            echo '<input type="checkbox" ';
            echo 'id="' . esc_attr($field_id) . '" ';
            echo 'name="' . esc_attr($field_name) . '" ';
            echo 'value="1" ';
            echo 'class="' . esc_attr($field_class) . '" ';
            if ($value) {
                echo 'checked ';
            }
            echo '/>';
            if (!empty($field_description)) {
                echo ' ' . esc_html($field_description);
            }
            echo '</label>';
            break;
            
        case 'radio':
            foreach ($field_options as $option_value => $option_label) {
                echo '<label class="tmu-radio-option">';
                echo '<input type="radio" ';
                echo 'name="' . esc_attr($field_name) . '" ';
                echo 'value="' . esc_attr($option_value) . '" ';
                echo 'class="' . esc_attr($field_class) . '" ';
                if ($value == $option_value) {
                    echo 'checked ';
                }
                echo '/>';
                echo ' ' . esc_html($option_label);
                echo '</label><br>';
            }
            break;
            
        case 'image':
            echo '<div class="tmu-image-field">';
            echo '<input type="hidden" ';
            echo 'id="' . esc_attr($field_id) . '" ';
            echo 'name="' . esc_attr($field_name) . '" ';
            echo 'value="' . esc_attr($value) . '" ';
            echo 'class="' . esc_attr($field_class) . '" />';
            
            echo '<div class="image-preview">';
            if (!empty($value)) {
                echo '<img src="' . esc_url($value) . '" alt="" style="max-width: 150px; height: auto;" />';
            }
            echo '</div>';
            
            echo '<button type="button" class="button tmu-select-image" data-target="' . esc_attr($field_id) . '">';
            echo esc_html__('Select Image', 'tmu');
            echo '</button>';
            
            if (!empty($value)) {
                echo ' <button type="button" class="button tmu-remove-image" data-target="' . esc_attr($field_id) . '">';
                echo esc_html__('Remove', 'tmu');
                echo '</button>';
            }
            echo '</div>';
            break;
    }
    
    echo '</div>';
    
    // Description
    if (!empty($field_description) && $field_type !== 'checkbox') {
        echo '<p class="description">' . esc_html($field_description) . '</p>';
    }
    
    echo '</div>';
}

/**
 * Get admin table columns for TMU post types
 *
 * @param string $post_type Post type
 * @return array Table columns
 */
function tmu_get_admin_columns(string $post_type): array {
    $columns = [];
    
    switch ($post_type) {
        case 'movie':
            $columns = [
                'cb' => '<input type="checkbox" />',
                'title' => __('Title', 'tmu'),
                'poster' => __('Poster', 'tmu'),
                'rating' => __('Rating', 'tmu'),
                'runtime' => __('Runtime', 'tmu'),
                'release_date' => __('Release Date', 'tmu'),
                'genres' => __('Genres', 'tmu'),
                'tmdb_id' => __('TMDB ID', 'tmu'),
                'date' => __('Date', 'tmu'),
            ];
            break;
            
        case 'tv':
            $columns = [
                'cb' => '<input type="checkbox" />',
                'title' => __('Title', 'tmu'),
                'poster' => __('Poster', 'tmu'),
                'rating' => __('Rating', 'tmu'),
                'seasons' => __('Seasons', 'tmu'),
                'episodes' => __('Episodes', 'tmu'),
                'status' => __('Status', 'tmu'),
                'genres' => __('Genres', 'tmu'),
                'tmdb_id' => __('TMDB ID', 'tmu'),
                'date' => __('Date', 'tmu'),
            ];
            break;
            
        case 'people':
            $columns = [
                'cb' => '<input type="checkbox" />',
                'title' => __('Name', 'tmu'),
                'profile' => __('Profile', 'tmu'),
                'profession' => __('Profession', 'tmu'),
                'birth_date' => __('Birth Date', 'tmu'),
                'tmdb_id' => __('TMDB ID', 'tmu'),
                'date' => __('Date', 'tmu'),
            ];
            break;
    }
    
    return apply_filters("tmu_{$post_type}_admin_columns", $columns);
}

/**
 * Display custom admin column content
 *
 * @param string $column Column name
 * @param int $post_id Post ID
 */
function tmu_display_admin_column_content(string $column, int $post_id): void {
    switch ($column) {
        case 'poster':
        case 'profile':
            $image_url = tmu_get_poster_url($post_id, 'w200');
            if (!empty($image_url)) {
                echo '<img src="' . esc_url($image_url) . '" alt="" style="width: 50px; height: auto;" />';
            } else {
                echo '—';
            }
            break;
            
        case 'rating':
            $rating = tmu_get_post_meta($post_id, 'average_rating');
            $vote_count = tmu_get_post_meta($post_id, 'vote_count');
            if (!empty($rating)) {
                echo number_format((float) $rating, 1) . '/10';
                if (!empty($vote_count)) {
                    echo ' (' . number_format((int) $vote_count) . ')';
                }
            } else {
                echo '—';
            }
            break;
            
        case 'runtime':
            $runtime = tmu_get_post_meta($post_id, 'runtime');
            if (!empty($runtime)) {
                echo tmu_format_runtime((int) $runtime);
            } else {
                echo '—';
            }
            break;
            
        case 'release_date':
        case 'birth_date':
            $date = tmu_get_post_meta($post_id, $column);
            if (!empty($date)) {
                $timestamp = strtotime($date);
                echo $timestamp ? date('M j, Y', $timestamp) : $date;
            } else {
                echo '—';
            }
            break;
            
        case 'seasons':
            $seasons = tmu_get_post_meta($post_id, 'number_of_seasons');
            echo !empty($seasons) ? (int) $seasons : '—';
            break;
            
        case 'episodes':
            $episodes = tmu_get_post_meta($post_id, 'number_of_episodes');
            echo !empty($episodes) ? (int) $episodes : '—';
            break;
            
        case 'status':
            $status = tmu_get_post_meta($post_id, 'status');
            if (!empty($status)) {
                echo '<span class="tmu-status status-' . esc_attr($status) . '">' . esc_html(ucfirst($status)) . '</span>';
            } else {
                echo '—';
            }
            break;
            
        case 'genres':
            $genres = tmu_get_post_terms($post_id, 'genre');
            echo !empty($genres) ? esc_html(implode(', ', $genres)) : '—';
            break;
            
        case 'profession':
            $professions = tmu_get_post_terms($post_id, 'profession');
            echo !empty($professions) ? esc_html(implode(', ', $professions)) : '—';
            break;
            
        case 'tmdb_id':
            $tmdb_id = tmu_get_post_meta($post_id, 'tmdb_id');
            if (!empty($tmdb_id)) {
                echo '<code>' . esc_html($tmdb_id) . '</code>';
            } else {
                echo '—';
            }
            break;
    }
}

/**
 * Make admin columns sortable
 *
 * @param array $columns Sortable columns
 * @return array Modified sortable columns
 */
function tmu_make_admin_columns_sortable(array $columns): array {
    $tmu_sortable = [
        'rating' => 'average_rating',
        'runtime' => 'runtime',
        'release_date' => 'release_date',
        'birth_date' => 'birth_date',
        'seasons' => 'number_of_seasons',
        'episodes' => 'number_of_episodes',
        'tmdb_id' => 'tmdb_id',
    ];
    
    return array_merge($columns, $tmu_sortable);
}

/**
 * Handle sorting for custom admin columns
 *
 * @param WP_Query $query Query object
 */
function tmu_handle_admin_sorting(WP_Query $query): void {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    $orderby = $query->get('orderby');
    
    $meta_fields = [
        'average_rating',
        'runtime',
        'release_date',
        'birth_date',
        'number_of_seasons',
        'number_of_episodes',
        'tmdb_id'
    ];
    
    if (in_array($orderby, $meta_fields)) {
        $query->set('meta_key', $orderby);
        
        // Determine sort type based on field
        if (in_array($orderby, ['average_rating', 'runtime', 'number_of_seasons', 'number_of_episodes', 'tmdb_id'])) {
            $query->set('orderby', 'meta_value_num');
        } else {
            $query->set('orderby', 'meta_value');
        }
    }
}