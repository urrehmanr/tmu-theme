<?php
/**
 * TMU Admin Helper Functions
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add TMU admin notice
 *
 * @param string $message Notice message
 * @param string $type Notice type (success, error, warning, info)
 * @param bool $dismissible Whether notice is dismissible
 */
function tmu_add_admin_notice(string $message, string $type = 'info', bool $dismissible = true): void {
    $class = "notice notice-{$type}";
    if ($dismissible) {
        $class .= ' is-dismissible';
    }
    
    add_action('admin_notices', function() use ($message, $class) {
        echo "<div class=\"{$class}\"><p>{$message}</p></div>";
    });
}

/**
 * Get TMU admin setting
 *
 * @param string $setting Setting name
 * @param mixed $default Default value
 * @return mixed
 */
function tmu_get_admin_setting(string $setting, $default = null) {
    $settings = get_option('tmu_settings', []);
    return $settings[$setting] ?? $default;
}

/**
 * Save TMU admin setting
 *
 * @param string $setting Setting name
 * @param mixed $value Setting value
 * @return bool
 */
function tmu_save_admin_setting(string $setting, $value): bool {
    $settings = get_option('tmu_settings', []);
    $settings[$setting] = $value;
    return update_option('tmu_settings', $settings);
}

/**
 * Check if current screen is TMU admin page
 *
 * @param string $page_slug Optional specific page slug
 * @return bool
 */
function is_tmu_admin_page(string $page_slug = ''): bool {
    if (!is_admin()) {
        return false;
    }
    
    $screen = get_current_screen();
    
    if (!$screen) {
        return false;
    }
    
    $tmu_pages = [
        'toplevel_page_tmu-settings',
        'tmu_page_tmu-welcome',
        'tools_page_tmu-migration',
        'edit-movie',
        'edit-tv_series',
        'edit-drama',
        'edit-person',
        'movie',
        'tv_series',
        'drama',
        'person',
    ];
    
    if ($page_slug) {
        return $screen->id === $page_slug;
    }
    
    return in_array($screen->id, $tmu_pages) || strpos($screen->id, 'tmu') !== false;
}

/**
 * Enqueue TMU admin assets
 *
 * @param string $hook Current admin page hook
 */
function tmu_enqueue_admin_assets(string $hook): void {
    if (!is_tmu_admin_page()) {
        return;
    }
    
    // Admin CSS
    wp_enqueue_style(
        'tmu-admin-style',
        tmu_get_build_asset_url('css/admin.css'),
        [],
        tmu_get_version()
    );
    
    // Admin JavaScript
    wp_enqueue_script(
        'tmu-admin-script',
        tmu_get_build_asset_url('js/admin.js'),
        ['jquery', 'wp-api'],
        tmu_get_version(),
        true
    );
    
    // Localize admin scripts
    wp_localize_script('tmu-admin-script', 'tmuAdmin', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'restUrl' => rest_url('tmu/v1/'),
        'nonce' => wp_create_nonce('tmu_admin_nonce'),
        'restNonce' => wp_create_nonce('wp_rest'),
        'userId' => get_current_user_id(),
        'strings' => [
            'loading' => __('Loading...', 'tmu'),
            'error' => __('An error occurred. Please try again.', 'tmu'),
            'success' => __('Operation completed successfully.', 'tmu'),
            'confirmDelete' => __('Are you sure you want to delete this item?', 'tmu'),
            'confirmAction' => __('Are you sure you want to perform this action?', 'tmu'),
            'syncSuccess' => __('Data synchronized successfully!', 'tmu'),
            'syncError' => __('Error synchronizing data. Please try again.', 'tmu'),
            'tmdbIdRequired' => __('TMDB ID is required for synchronization.', 'tmu'),
            'invalidApiKey' => __('Invalid TMDB API key.', 'tmu'),
            'networkError' => __('Network error. Please check your connection.', 'tmu'),
        ],
        'settings' => [
            'tmdbApiKey' => tmu_get_option('tmu_tmdb_api_key', ''),
            'debugMode' => tmu_is_development(),
            'autoSave' => tmu_get_option('tmu_admin_autosave', true),
            'refreshInterval' => tmu_get_option('tmu_admin_refresh_interval', 30000),
        ]
    ]);
    
    // Alpine.js for enhanced interactivity in admin
    wp_enqueue_script(
        'alpinejs',
        'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js',
        [],
        '3.13.0',
        true
    );
    
    // Add defer attribute to Alpine.js
    add_filter('script_loader_tag', function($tag, $handle) {
        if ($handle === 'alpinejs') {
            return str_replace(' src', ' defer src', $tag);
        }
        return $tag;
    }, 10, 2);
}

/**
 * Add TMU admin menu pages
 */
function tmu_add_admin_menus(): void {
    // Main settings page
    add_menu_page(
        __('TMU Settings', 'tmu'),
        __('TMU', 'tmu'),
        'manage_options',
        'tmu-settings',
        'tmu_render_settings_page',
        'dashicons-video-alt3',
        30
    );
    
    // Welcome submenu
    add_submenu_page(
        'tmu-settings',
        __('Welcome', 'tmu'),
        __('Welcome', 'tmu'),
        'manage_options',
        'tmu-welcome',
        'tmu_render_welcome_page'
    );
    
    // Migration submenu under Tools
    add_management_page(
        __('TMU Migration', 'tmu'),
        __('TMU Migration', 'tmu'),
        'manage_options',
        'tmu-migration',
        'tmu_render_migration_page'
    );
}

/**
 * Render TMU settings page
 */
function tmu_render_settings_page(): void {
    if (class_exists('TMU\\Admin\\Settings')) {
        TMU\Admin\Settings::getInstance()->renderPage();
    } else {
        echo '<div class="wrap">';
        echo '<h1>' . __('TMU Settings', 'tmu') . '</h1>';
        echo '<p>' . __('Settings page is loading...', 'tmu') . '</p>';
        echo '</div>';
    }
}

/**
 * Render TMU welcome page
 */
function tmu_render_welcome_page(): void {
    if (class_exists('TMU\\Admin\\Welcome')) {
        TMU\Admin\Welcome::getInstance()->renderPage();
    } else {
        echo '<div class="wrap">';
        echo '<h1>' . __('Welcome to TMU Theme', 'tmu') . '</h1>';
        echo '<p>' . __('Welcome page is loading...', 'tmu') . '</p>';
        echo '</div>';
    }
}

/**
 * Render TMU migration page
 */
function tmu_render_migration_page(): void {
    if (class_exists('TMU\\Database\\Migration')) {
        $migration = TMU\Database\Migration::getInstance();
        $status = $migration->getMigrationStatus();
        
        echo '<div class="wrap">';
        echo '<h1>' . __('TMU Database Migration', 'tmu') . '</h1>';
        
        echo '<div class="card">';
        echo '<h2>' . __('Migration Status', 'tmu') . '</h2>';
        echo '<p><strong>' . __('Current Version:', 'tmu') . '</strong> ' . esc_html($status['installed_version']) . '</p>';
        echo '<p><strong>' . __('Target Version:', 'tmu') . '</strong> ' . esc_html($status['current_version']) . '</p>';
        
        if ($status['needs_migration']) {
            echo '<p class="notice notice-warning inline">' . __('Migration required!', 'tmu') . '</p>';
            echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
            echo '<input type="hidden" name="action" value="tmu_run_migration">';
            wp_nonce_field('tmu_migration_nonce');
            echo '<p class="submit">';
            echo '<input type="submit" name="submit" class="button-primary" value="' . __('Run Migration', 'tmu') . '">';
            echo '</p>';
            echo '</form>';
        } else {
            echo '<p class="notice notice-success inline">' . __('Database is up to date!', 'tmu') . '</p>';
        }
        
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="wrap">';
        echo '<h1>' . __('TMU Migration', 'tmu') . '</h1>';
        echo '<p>' . __('Migration system is not available.', 'tmu') . '</p>';
        echo '</div>';
    }
}

/**
 * Add custom admin columns for TMU post types
 *
 * @param array $columns Existing columns
 * @param string $post_type Post type
 * @return array
 */
function tmu_add_custom_admin_columns(array $columns, string $post_type): array {
    $tmu_post_types = tmu_get_post_types();
    
    if (!in_array($post_type, $tmu_post_types)) {
        return $columns;
    }
    
    // Add rating column for movies, TV series, and dramas
    if (in_array($post_type, ['movie', 'tv_series', 'drama'])) {
        $columns['tmu_rating'] = __('Rating', 'tmu');
        $columns['tmu_release_date'] = __('Release Date', 'tmu');
        $columns['tmu_tmdb_id'] = __('TMDB ID', 'tmu');
    }
    
    // Add specific columns for people
    if ($post_type === 'person') {
        $columns['tmu_profession'] = __('Profession', 'tmu');
        $columns['tmu_birth_date'] = __('Birth Date', 'tmu');
    }
    
    return $columns;
}

/**
 * Display custom admin column content
 *
 * @param string $column Column name
 * @param int $post_id Post ID
 */
function tmu_display_custom_admin_column_content(string $column, int $post_id): void {
    switch ($column) {
        case 'tmu_rating':
            $rating = tmu_get_meta($post_id, 'average_rating', 0);
            $vote_count = tmu_get_meta($post_id, 'vote_count', 0);
            
            if ($rating > 0) {
                echo sprintf('%.1f (%d)', $rating, $vote_count);
            } else {
                echo '—';
            }
            break;
            
        case 'tmu_release_date':
            $release_date = tmu_get_meta($post_id, 'release_date', '');
            
            if ($release_date) {
                $timestamp = strtotime($release_date);
                echo $timestamp ? date('M j, Y', $timestamp) : $release_date;
            } else {
                echo '—';
            }
            break;
            
        case 'tmu_tmdb_id':
            $tmdb_id = tmu_get_meta($post_id, 'tmdb_id', '');
            
            if ($tmdb_id) {
                echo '<code>' . esc_html($tmdb_id) . '</code>';
            } else {
                echo '—';
            }
            break;
            
        case 'tmu_profession':
            $profession = tmu_get_meta($post_id, 'profession', '');
            echo $profession ? esc_html($profession) : '—';
            break;
            
        case 'tmu_birth_date':
            $birth_date = tmu_get_meta($post_id, 'date_of_birth', '');
            
            if ($birth_date) {
                $timestamp = strtotime($birth_date);
                echo $timestamp ? date('M j, Y', $timestamp) : $birth_date;
            } else {
                echo '—';
            }
            break;
    }
}

/**
 * Make custom admin columns sortable
 *
 * @param array $columns Sortable columns
 * @param string $post_type Post type
 * @return array
 */
function tmu_make_admin_columns_sortable(array $columns, string $post_type): array {
    $tmu_post_types = tmu_get_post_types();
    
    if (!in_array($post_type, $tmu_post_types)) {
        return $columns;
    }
    
    $columns['tmu_rating'] = 'tmu_rating';
    $columns['tmu_release_date'] = 'tmu_release_date';
    $columns['tmu_tmdb_id'] = 'tmu_tmdb_id';
    
    if ($post_type === 'person') {
        $columns['tmu_birth_date'] = 'tmu_birth_date';
    }
    
    return $columns;
}

/**
 * Handle custom admin column sorting
 *
 * @param WP_Query $query Query object
 */
function tmu_handle_admin_column_sorting(WP_Query $query): void {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    $orderby = $query->get('orderby');
    
    switch ($orderby) {
        case 'tmu_rating':
            $query->set('meta_key', 'average_rating');
            $query->set('orderby', 'meta_value_num');
            break;
            
        case 'tmu_release_date':
            $query->set('meta_key', 'release_date');
            $query->set('orderby', 'meta_value');
            break;
            
        case 'tmu_tmdb_id':
            $query->set('meta_key', 'tmdb_id');
            $query->set('orderby', 'meta_value_num');
            break;
            
        case 'tmu_birth_date':
            $query->set('meta_key', 'date_of_birth');
            $query->set('orderby', 'meta_value');
            break;
    }
}

/**
 * Add admin actions for TMU posts
 *
 * @param array $actions Existing actions
 * @param WP_Post $post Post object
 * @return array
 */
function tmu_add_post_row_actions(array $actions, WP_Post $post): array {
    if (!is_tmu_post_type($post->ID)) {
        return $actions;
    }
    
    $tmdb_id = tmu_get_meta($post->ID, 'tmdb_id', '');
    
    if ($tmdb_id && tmu_is_tmdb_available()) {
        $sync_url = wp_nonce_url(
            admin_url("admin-post.php?action=tmu_sync_post&post_id={$post->ID}"),
            'tmu_sync_' . $post->ID
        );
        
        $actions['tmu_sync'] = sprintf(
            '<a href="%s" class="tmu-sync-link" data-post-id="%d">%s</a>',
            $sync_url,
            $post->ID,
            __('Sync with TMDB', 'tmu')
        );
    }
    
    return $actions;
}

/**
 * Add admin bulk actions for TMU posts
 *
 * @param array $actions Existing actions
 * @return array
 */
function tmu_add_bulk_actions(array $actions): array {
    global $typenow;
    
    if (!in_array($typenow, tmu_get_post_types())) {
        return $actions;
    }
    
    if (tmu_is_tmdb_available()) {
        $actions['tmu_bulk_sync'] = __('Sync with TMDB', 'tmu');
    }
    
    $actions['tmu_clear_cache'] = __('Clear TMU Cache', 'tmu');
    
    return $actions;
}

/**
 * Handle admin bulk actions for TMU posts
 *
 * @param string $redirect_to Redirect URL
 * @param string $doaction Action name
 * @param array $post_ids Post IDs
 * @return string
 */
function tmu_handle_bulk_actions(string $redirect_to, string $doaction, array $post_ids): string {
    switch ($doaction) {
        case 'tmu_bulk_sync':
            if (tmu_is_tmdb_available() && class_exists('TMU\\API\\TMDBClient')) {
                $synced = 0;
                $client = TMU\API\TMDBClient::getInstance();
                
                foreach ($post_ids as $post_id) {
                    if ($client->syncPost($post_id)) {
                        $synced++;
                    }
                }
                
                $redirect_to = add_query_arg('tmu_synced', $synced, $redirect_to);
            }
            break;
            
        case 'tmu_clear_cache':
            foreach ($post_ids as $post_id) {
                tmu_delete_cache("post_{$post_id}");
            }
            
            $redirect_to = add_query_arg('tmu_cache_cleared', count($post_ids), $redirect_to);
            break;
    }
    
    return $redirect_to;
}

/**
 * Display admin notices for TMU bulk actions
 */
function tmu_display_bulk_action_notices(): void {
    if (isset($_GET['tmu_synced'])) {
        $synced = intval($_GET['tmu_synced']);
        $message = sprintf(
            _n('Synced %d item with TMDB.', 'Synced %d items with TMDB.', $synced, 'tmu'),
            $synced
        );
        tmu_add_admin_notice($message, 'success');
    }
    
    if (isset($_GET['tmu_cache_cleared'])) {
        $cleared = intval($_GET['tmu_cache_cleared']);
        $message = sprintf(
            _n('Cleared cache for %d item.', 'Cleared cache for %d items.', $cleared, 'tmu'),
            $cleared
        );
        tmu_add_admin_notice($message, 'success');
    }
}

// Hook admin functions
add_action('admin_enqueue_scripts', 'tmu_enqueue_admin_assets');
add_action('admin_menu', 'tmu_add_admin_menus');
add_action('admin_notices', 'tmu_display_bulk_action_notices');

// Hook custom columns
add_filter('manage_posts_columns', 'tmu_add_custom_admin_columns', 10, 2);
add_action('manage_posts_custom_column', 'tmu_display_custom_admin_column_content', 10, 2);
add_filter('manage_edit-{post_type}_sortable_columns', 'tmu_make_admin_columns_sortable', 10, 2);
add_action('pre_get_posts', 'tmu_handle_admin_column_sorting');

// Hook post actions
add_filter('post_row_actions', 'tmu_add_post_row_actions', 10, 2);
add_filter('bulk_actions-edit-movie', 'tmu_add_bulk_actions');
add_filter('bulk_actions-edit-tv_series', 'tmu_add_bulk_actions');
add_filter('bulk_actions-edit-drama', 'tmu_add_bulk_actions');
add_filter('bulk_actions-edit-person', 'tmu_add_bulk_actions');
add_filter('handle_bulk_actions-edit-movie', 'tmu_handle_bulk_actions', 10, 3);
add_filter('handle_bulk_actions-edit-tv_series', 'tmu_handle_bulk_actions', 10, 3);
add_filter('handle_bulk_actions-edit-drama', 'tmu_handle_bulk_actions', 10, 3);
add_filter('handle_bulk_actions-edit-person', 'tmu_handle_bulk_actions', 10, 3);