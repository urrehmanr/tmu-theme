<?php
/**
 * TMDB Settings Page
 * 
 * Admin interface for TMDB API configuration and management
 * 
 * @package TMU\Admin\Settings
 * @since 1.0.0
 */

namespace TMU\Admin\Settings;

use TMU\API\TMDB\Client;
use TMU\API\TMDB\SyncService;

/**
 * TMDBSettings class
 * 
 * Provides admin interface for TMDB configuration
 */
class TMDBSettings {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'addAdminPage']);
        add_action('admin_init', [$this, 'initSettings']);
        add_action('wp_ajax_tmu_test_tmdb_api', [$this, 'handleTestApi']);
        add_action('wp_ajax_tmu_bulk_sync_tmdb', [$this, 'handleBulkSync']);
        add_action('wp_ajax_tmu_get_sync_stats', [$this, 'handleGetSyncStats']);
    }
    
    /**
     * Add admin page to menu
     */
    public function addAdminPage(): void {
        add_submenu_page(
            'edit.php?post_type=movie',
            __('TMDB Settings', 'tmu-theme'),
            __('TMDB Settings', 'tmu-theme'),
            'manage_options',
            'tmu-tmdb-settings',
            [$this, 'renderSettingsPage']
        );
    }
    
    /**
     * Initialize settings
     */
    public function initSettings(): void {
        register_setting('tmu_tmdb_settings', 'tmu_tmdb_api_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);
        
        register_setting('tmu_tmdb_settings', 'tmu_tmdb_auto_sync', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ]);
        
        register_setting('tmu_tmdb_settings', 'tmu_tmdb_sync_images', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ]);
        
        register_setting('tmu_tmdb_settings', 'tmu_tmdb_sync_videos', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ]);
        
        register_setting('tmu_tmdb_settings', 'tmu_tmdb_sync_credits', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ]);
        
        register_setting('tmu_tmdb_settings', 'tmu_tmdb_rate_limit', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 1
        ]);
    }
    
    /**
     * Render settings page
     */
    public function renderSettingsPage(): void {
        if (isset($_POST['submit'])) {
            $this->saveSettings();
        }
        
        $api_key = get_option('tmu_tmdb_api_key', '');
        $auto_sync = get_option('tmu_tmdb_auto_sync', false);
        $sync_images = get_option('tmu_tmdb_sync_images', true);
        $sync_videos = get_option('tmu_tmdb_sync_videos', false);
        $sync_credits = get_option('tmu_tmdb_sync_credits', true);
        $rate_limit = get_option('tmu_tmdb_rate_limit', 1);
        
        ?>
        <div class="wrap tmu-tmdb-settings">
            <h1><?php _e('TMDB API Settings', 'tmu-theme'); ?></h1>
            
            <div class="tmu-settings-grid">
                <div class="tmu-settings-main">
                    <form method="post" action="">
                        <?php wp_nonce_field('tmu_tmdb_settings', 'tmu_tmdb_nonce'); ?>
                        
                        <div class="tmu-settings-section">
                            <h2><?php _e('API Configuration', 'tmu-theme'); ?></h2>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="tmdb_api_key"><?php _e('TMDB API Key', 'tmu-theme'); ?></label>
                                    </th>
                                    <td>
                                        <input type="password" 
                                               name="tmdb_api_key" 
                                               id="tmdb_api_key"
                                               value="<?php echo esc_attr($api_key); ?>" 
                                               class="regular-text" 
                                               autocomplete="off" />
                                        <p class="description">
                                            <?php 
                                            printf(
                                                __('Get your API key from <a href="%s" target="_blank">TMDB API</a>', 'tmu-theme'),
                                                'https://www.themoviedb.org/settings/api'
                                            ); 
                                            ?>
                                        </p>
                                        <button type="button" id="test-api-btn" class="button button-secondary">
                                            <?php _e('Test Connection', 'tmu-theme'); ?>
                                        </button>
                                        <span id="api-test-result"></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="tmu-settings-section">
                            <h2><?php _e('Sync Options', 'tmu-theme'); ?></h2>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Auto Sync', 'tmu-theme'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" 
                                                   name="auto_sync" 
                                                   value="1" 
                                                   <?php checked($auto_sync, true); ?> />
                                            <?php _e('Enable automatic daily sync for all content', 'tmu-theme'); ?>
                                        </label>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Sync Images', 'tmu-theme'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" 
                                                   name="sync_images" 
                                                   value="1" 
                                                   <?php checked($sync_images, true); ?> />
                                            <?php _e('Download and sync posters, backdrops, and profile images', 'tmu-theme'); ?>
                                        </label>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Sync Videos', 'tmu-theme'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" 
                                                   name="sync_videos" 
                                                   value="1" 
                                                   <?php checked($sync_videos, true); ?> />
                                            <?php _e('Sync video information (trailers, clips)', 'tmu-theme'); ?>
                                        </label>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Sync Credits', 'tmu-theme'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" 
                                                   name="sync_credits" 
                                                   value="1" 
                                                   <?php checked($sync_credits, true); ?> />
                                            <?php _e('Sync cast and crew information', 'tmu-theme'); ?>
                                        </label>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="rate_limit"><?php _e('Rate Limit', 'tmu-theme'); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" 
                                               name="rate_limit" 
                                               id="rate_limit"
                                               value="<?php echo esc_attr($rate_limit); ?>" 
                                               min="1" 
                                               max="5" 
                                               class="small-text" />
                                        <span><?php _e('seconds between requests', 'tmu-theme'); ?></span>
                                        <p class="description">
                                            <?php _e('Delay between TMDB API requests to avoid rate limiting', 'tmu-theme'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="tmu-settings-section">
                            <h2><?php _e('Bulk Operations', 'tmu-theme'); ?></h2>
                            
                            <div class="tmu-bulk-actions">
                                <button type="button" id="bulk-sync-btn" class="button button-primary">
                                    <?php _e('Sync All Content', 'tmu-theme'); ?>
                                </button>
                                
                                <button type="button" id="sync-stats-btn" class="button button-secondary">
                                    <?php _e('View Sync Statistics', 'tmu-theme'); ?>
                                </button>
                                
                                <button type="button" id="clear-cache-btn" class="button button-secondary">
                                    <?php _e('Clear TMDB Cache', 'tmu-theme'); ?>
                                </button>
                            </div>
                            
                            <div id="bulk-progress" class="tmu-progress-container" style="display: none;">
                                <div class="progress-bar">
                                    <div class="progress-fill"></div>
                                </div>
                                <div class="progress-text"></div>
                                <div class="progress-log"></div>
                            </div>
                        </div>
                        
                        <p class="submit">
                            <input type="submit" 
                                   name="submit" 
                                   class="button-primary" 
                                   value="<?php esc_attr_e('Save Settings', 'tmu-theme'); ?>" />
                        </p>
                    </form>
                </div>
                
                <div class="tmu-settings-sidebar">
                    <div class="tmu-settings-widget">
                        <h3><?php _e('Sync Statistics', 'tmu-theme'); ?></h3>
                        <div id="sync-stats-display">
                            <p><?php _e('Click "View Sync Statistics" to load stats', 'tmu-theme'); ?></p>
                        </div>
                    </div>
                    
                    <div class="tmu-settings-widget">
                        <h3><?php _e('API Information', 'tmu-theme'); ?></h3>
                        <p><?php _e('TMDB API provides comprehensive movie and TV show data.', 'tmu-theme'); ?></p>
                        <ul>
                            <li><?php _e('40 requests per 10 seconds limit', 'tmu-theme'); ?></li>
                            <li><?php _e('High-quality images and metadata', 'tmu-theme'); ?></li>
                            <li><?php _e('Regular updates and new releases', 'tmu-theme'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="tmu-settings-widget">
                        <h3><?php _e('Quick Links', 'tmu-theme'); ?></h3>
                        <ul>
                            <li><a href="https://www.themoviedb.org/settings/api" target="_blank"><?php _e('Get API Key', 'tmu-theme'); ?></a></li>
                            <li><a href="https://developers.themoviedb.org/3" target="_blank"><?php _e('API Documentation', 'tmu-theme'); ?></a></li>
                            <li><a href="<?php echo admin_url('edit.php?post_type=movie'); ?>"><?php _e('Manage Movies', 'tmu-theme'); ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .tmu-tmdb-settings .tmu-settings-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-top: 1rem;
        }
        
        .tmu-settings-section {
            background: white;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .tmu-settings-section h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .tmu-bulk-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .tmu-progress-container {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: #0073aa;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            text-align: center;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .progress-log {
            max-height: 200px;
            overflow-y: auto;
            background: white;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
        }
        
        .tmu-settings-sidebar .tmu-settings-widget {
            background: white;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .tmu-settings-widget h3 {
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .tmu-settings-widget ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .tmu-settings-widget ul li {
            margin-bottom: 5px;
        }
        
        #api-test-result {
            margin-left: 10px;
            font-weight: 500;
        }
        
        #api-test-result.success {
            color: #00a32a;
        }
        
        #api-test-result.error {
            color: #d63638;
        }
        
        @media (max-width: 768px) {
            .tmu-settings-grid {
                grid-template-columns: 1fr;
            }
            
            .tmu-bulk-actions {
                flex-direction: column;
            }
            
            .tmu-bulk-actions button {
                width: 100%;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Test API connection
            $('#test-api-btn').on('click', function() {
                const $btn = $(this);
                const $result = $('#api-test-result');
                const apiKey = $('#tmdb_api_key').val();
                
                if (!apiKey) {
                    $result.removeClass('success').addClass('error').text('<?php _e('Please enter an API key', 'tmu-theme'); ?>');
                    return;
                }
                
                $btn.prop('disabled', true).text('<?php _e('Testing...', 'tmu-theme'); ?>');
                $result.removeClass('success error').text('');
                
                $.post(ajaxurl, {
                    action: 'tmu_test_tmdb_api',
                    api_key: apiKey,
                    _wpnonce: '<?php echo wp_create_nonce('tmu_test_tmdb_api'); ?>'
                }, function(response) {
                    if (response.success) {
                        $result.addClass('success').text('<?php _e('✓ Connection successful', 'tmu-theme'); ?>');
                    } else {
                        $result.addClass('error').text('✗ ' + response.data);
                    }
                }).fail(function() {
                    $result.addClass('error').text('<?php _e('✗ Network error', 'tmu-theme'); ?>');
                }).always(function() {
                    $btn.prop('disabled', false).text('<?php _e('Test Connection', 'tmu-theme'); ?>');
                });
            });
            
            // Bulk sync
            $('#bulk-sync-btn').on('click', function() {
                if (!confirm('<?php _e('This will sync all content with TMDB. This may take a while. Continue?', 'tmu-theme'); ?>')) {
                    return;
                }
                
                const $progress = $('#bulk-progress');
                const $fill = $('.progress-fill');
                const $text = $('.progress-text');
                const $log = $('.progress-log');
                
                $progress.show();
                $fill.css('width', '0%');
                $text.text('<?php _e('Starting bulk sync...', 'tmu-theme'); ?>');
                $log.html('');
                
                $.post(ajaxurl, {
                    action: 'tmu_bulk_sync_tmdb',
                    _wpnonce: '<?php echo wp_create_nonce('tmu_bulk_sync_tmdb'); ?>'
                }, function(response) {
                    if (response.success) {
                        $fill.css('width', '100%');
                        $text.text('<?php _e('Sync completed successfully!', 'tmu-theme'); ?>');
                        $log.append('<div><?php _e('Sync completed', 'tmu-theme'); ?>: ' + JSON.stringify(response.data) + '</div>');
                    } else {
                        $text.text('<?php _e('Sync failed', 'tmu-theme'); ?>');
                        $log.append('<div class="error"><?php _e('Error', 'tmu-theme'); ?>: ' + response.data + '</div>');
                    }
                }).fail(function() {
                    $text.text('<?php _e('Sync failed due to network error', 'tmu-theme'); ?>');
                    $log.append('<div class="error"><?php _e('Network error occurred', 'tmu-theme'); ?></div>');
                });
            });
            
            // Load sync statistics
            $('#sync-stats-btn').on('click', function() {
                const $display = $('#sync-stats-display');
                
                $display.html('<p><?php _e('Loading statistics...', 'tmu-theme'); ?></p>');
                
                $.post(ajaxurl, {
                    action: 'tmu_get_sync_stats',
                    _wpnonce: '<?php echo wp_create_nonce('tmu_get_sync_stats'); ?>'
                }, function(response) {
                    if (response.success) {
                        const stats = response.data;
                        let html = '<ul>';
                        html += '<li><?php _e('Total Synced', 'tmu-theme'); ?>: ' + stats.total_synced + '</li>';
                        html += '<li><?php _e('Movies', 'tmu-theme'); ?>: ' + stats.movies_synced + '</li>';
                        html += '<li><?php _e('TV Shows', 'tmu-theme'); ?>: ' + stats.tv_synced + '</li>';
                        html += '<li><?php _e('People', 'tmu-theme'); ?>: ' + stats.people_synced + '</li>';
                        if (stats.last_sync) {
                            html += '<li><?php _e('Last Sync', 'tmu-theme'); ?>: ' + stats.last_sync + '</li>';
                        }
                        html += '</ul>';
                        $display.html(html);
                    } else {
                        $display.html('<p class="error"><?php _e('Failed to load statistics', 'tmu-theme'); ?></p>');
                    }
                }).fail(function() {
                    $display.html('<p class="error"><?php _e('Network error', 'tmu-theme'); ?></p>');
                });
            });
            
            // Clear cache
            $('#clear-cache-btn').on('click', function() {
                if (!confirm('<?php _e('Clear all TMDB API cache?', 'tmu-theme'); ?>')) {
                    return;
                }
                
                // Implementation would go here
                alert('<?php _e('Cache clearing functionality would be implemented here', 'tmu-theme'); ?>');
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save settings
     */
    private function saveSettings(): void {
        if (!wp_verify_nonce($_POST['tmu_tmdb_nonce'], 'tmu_tmdb_settings')) {
            wp_die(__('Security check failed', 'tmu-theme'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'tmu-theme'));
        }
        
        update_option('tmu_tmdb_api_key', sanitize_text_field($_POST['tmdb_api_key']));
        update_option('tmu_tmdb_auto_sync', !empty($_POST['auto_sync']));
        update_option('tmu_tmdb_sync_images', !empty($_POST['sync_images']));
        update_option('tmu_tmdb_sync_videos', !empty($_POST['sync_videos']));
        update_option('tmu_tmdb_sync_credits', !empty($_POST['sync_credits']));
        update_option('tmu_tmdb_rate_limit', absint($_POST['rate_limit']));
        
        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'tmu-theme') . '</p></div>';
    }
    
    /**
     * Handle API test request
     */
    public function handleTestApi(): void {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'tmu_test_tmdb_api')) {
            wp_send_json_error(__('Security check failed', 'tmu-theme'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'tmu-theme'));
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        
        if (empty($api_key)) {
            wp_send_json_error(__('API key is required', 'tmu-theme'));
        }
        
        try {
            // Temporarily set API key for testing
            $original_key = get_option('tmu_tmdb_api_key');
            update_option('tmu_tmdb_api_key', $api_key);
            
            $client = new Client();
            $result = $client->getMovieDetails(550); // Test with Fight Club
            
            // Restore original key
            update_option('tmu_tmdb_api_key', $original_key);
            
            if (!empty($result['title'])) {
                wp_send_json_success(__('API connection successful', 'tmu-theme'));
            } else {
                wp_send_json_error(__('Invalid API response', 'tmu-theme'));
            }
            
        } catch (\Exception $e) {
            // Restore original key
            if (isset($original_key)) {
                update_option('tmu_tmdb_api_key', $original_key);
            }
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Handle bulk sync request
     */
    public function handleBulkSync(): void {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'tmu_bulk_sync_tmdb')) {
            wp_send_json_error(__('Security check failed', 'tmu-theme'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'tmu-theme'));
        }
        
        // Increase time limit for bulk operations
        set_time_limit(300);
        
        try {
            $sync_service = new SyncService();
            
            // Get all posts with TMDB IDs
            $posts = get_posts([
                'post_type' => ['movie', 'tv', 'drama', 'people'],
                'posts_per_page' => 50, // Limit for safety
                'meta_key' => 'tmdb_id',
                'meta_value' => '',
                'meta_compare' => '!='
            ]);
            
            $post_ids = wp_list_pluck($posts, 'ID');
            
            $options = [
                'sync_images' => get_option('tmu_tmdb_sync_images', true),
                'sync_videos' => get_option('tmu_tmdb_sync_videos', false),
                'sync_credits' => get_option('tmu_tmdb_sync_credits', true),
                'rate_limit' => get_option('tmu_tmdb_rate_limit', 1)
            ];
            
            $results = $sync_service->bulk_sync($post_ids, $options);
            
            wp_send_json_success($results);
            
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Handle sync statistics request
     */
    public function handleGetSyncStats(): void {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'tmu_get_sync_stats')) {
            wp_send_json_error(__('Security check failed', 'tmu-theme'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'tmu-theme'));
        }
        
        try {
            $sync_service = new SyncService();
            $stats = $sync_service->getSyncStatistics();
            
            wp_send_json_success($stats);
            
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
}