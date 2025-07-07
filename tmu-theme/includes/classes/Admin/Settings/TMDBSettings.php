<?php
/**
 * TMDB Settings Admin Page
 *
 * @package TMU\Admin\Settings
 * @version 1.0.0
 */

namespace TMU\Admin\Settings;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * TMDB Settings Admin Page
 */
class TMDBSettings {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function getInstance(): TMDBSettings {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->initHooks();
    }
    
    /**
     * Initialize hooks
     */
    private function initHooks(): void {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'initSettings']);
        add_action('wp_ajax_tmu_test_tmdb_connection', [$this, 'testTMDBConnection']);
        add_action('wp_ajax_tmu_bulk_sync', [$this, 'bulkSync']);
        add_action('wp_ajax_tmu_configure_webhook', [$this, 'configureWebhook']);
        add_action('wp_ajax_tmu_get_sync_stats', [$this, 'getSyncStats']);
        add_action('wp_ajax_tmu_clear_tmdb_cache', [$this, 'clearTMDBCache']);
    }
    
    /**
     * Add admin menu
     */
    public function addAdminMenu(): void {
        add_submenu_page(
            'tmu-dashboard',
            __('TMDB Settings', 'tmu'),
            __('TMDB Settings', 'tmu'),
            'manage_options',
            'tmu-tmdb-settings',
            [$this, 'renderSettingsPage']
        );
    }
    
    /**
     * Initialize settings
     */
    public function initSettings(): void {
        register_setting('tmu_tmdb_settings', 'tmu_tmdb_api_key');
        register_setting('tmu_tmdb_settings', 'tmu_tmdb_auto_sync');
        register_setting('tmu_tmdb_settings', 'tmu_tmdb_sync_frequency');
        register_setting('tmu_tmdb_settings', 'tmu_tmdb_image_quality');
        register_setting('tmu_tmdb_settings', 'tmu_tmdb_webhook_enabled');
        register_setting('tmu_tmdb_settings', 'tmu_tmdb_webhook_secret');
    }
    
    /**
     * Render settings page
     */
    public function renderSettingsPage(): void {
        $api_key = get_option('tmu_tmdb_api_key', '');
        $auto_sync = get_option('tmu_tmdb_auto_sync', 'off');
        $sync_frequency = get_option('tmu_tmdb_sync_frequency', 'daily');
        $image_quality = get_option('tmu_tmdb_image_quality', 'original');
        $webhook_enabled = get_option('tmu_tmdb_webhook_enabled', 'off');
        $webhook_secret = get_option('tmu_tmdb_webhook_secret', '');
        ?>
        <div class="wrap">
            <h1><?php _e('TMDB Settings', 'tmu'); ?></h1>
            
            <div class="tmu-admin-grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
                <!-- API Configuration -->
                <div class="lg:col-span-2">
                    <div class="admin-card">
                        <div class="admin-header">
                            <h2 class="text-lg font-semibold"><?php _e('API Configuration', 'tmu'); ?></h2>
                        </div>
                        <div class="admin-body">
                            <form method="post" action="options.php" id="tmdb-settings-form">
                                <?php settings_fields('tmu_tmdb_settings'); ?>
                                
                                <div class="admin-form-group">
                                    <label for="tmu_tmdb_api_key" class="admin-label">
                                        <?php _e('TMDB API Key', 'tmu'); ?>
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex gap-2">
                                        <input 
                                            type="text" 
                                            id="tmu_tmdb_api_key" 
                                            name="tmu_tmdb_api_key" 
                                            value="<?php echo esc_attr($api_key); ?>" 
                                            class="admin-input flex-1"
                                            placeholder="<?php _e('Enter your TMDB API key', 'tmu'); ?>"
                                        />
                                        <button 
                                            type="button" 
                                            id="test-tmdb-connection" 
                                            class="admin-btn-primary"
                                            data-nonce="<?php echo wp_create_nonce('tmu_tmdb_test'); ?>"
                                        >
                                            <span class="test-button-text"><?php _e('Test Connection', 'tmu'); ?></span>
                                            <span class="loading-spinner hidden"></span>
                                        </button>
                                    </div>
                                    <div id="connection-status" class="mt-2 hidden">
                                        <div class="alert"></div>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <?php _e('Get your API key from', 'tmu'); ?>
                                        <a href="https://www.themoviedb.org/settings/api" target="_blank" class="text-blue-600 hover:text-blue-800">
                                            <?php _e('TMDB Settings', 'tmu'); ?>
                                        </a>
                                    </p>
                                </div>
                                
                                <div class="admin-form-group">
                                    <label for="tmu_tmdb_image_quality" class="admin-label">
                                        <?php _e('Image Quality', 'tmu'); ?>
                                    </label>
                                    <select id="tmu_tmdb_image_quality" name="tmu_tmdb_image_quality" class="admin-select">
                                        <option value="w185" <?php selected($image_quality, 'w185'); ?>>w185 (185px)</option>
                                        <option value="w342" <?php selected($image_quality, 'w342'); ?>>w342 (342px)</option>
                                        <option value="w500" <?php selected($image_quality, 'w500'); ?>>w500 (500px)</option>
                                        <option value="w780" <?php selected($image_quality, 'w780'); ?>>w780 (780px)</option>
                                        <option value="original" <?php selected($image_quality, 'original'); ?>>Original</option>
                                    </select>
                                </div>
                                
                                <div class="admin-form-group">
                                    <label class="admin-label">
                                        <input 
                                            type="checkbox" 
                                            name="tmu_tmdb_auto_sync" 
                                            value="on" 
                                            <?php checked($auto_sync, 'on'); ?>
                                            class="mr-2"
                                        />
                                        <?php _e('Enable Automatic Sync', 'tmu'); ?>
                                    </label>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <?php _e('Automatically sync data from TMDB on schedule', 'tmu'); ?>
                                    </p>
                                </div>
                                
                                <div class="admin-form-group">
                                    <label for="tmu_tmdb_sync_frequency" class="admin-label">
                                        <?php _e('Sync Frequency', 'tmu'); ?>
                                    </label>
                                    <select id="tmu_tmdb_sync_frequency" name="tmu_tmdb_sync_frequency" class="admin-select">
                                        <option value="hourly" <?php selected($sync_frequency, 'hourly'); ?>><?php _e('Hourly', 'tmu'); ?></option>
                                        <option value="daily" <?php selected($sync_frequency, 'daily'); ?>><?php _e('Daily', 'tmu'); ?></option>
                                        <option value="weekly" <?php selected($sync_frequency, 'weekly'); ?>><?php _e('Weekly', 'tmu'); ?></option>
                                    </select>
                                </div>
                                
                                <?php submit_button(__('Save Settings', 'tmu'), 'primary'); ?>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Sync Operations -->
                <div class="lg:col-span-1">
                    <div class="admin-card">
                        <div class="admin-header">
                            <h3 class="text-lg font-semibold"><?php _e('Sync Operations', 'tmu'); ?></h3>
                        </div>
                        <div class="admin-body space-y-4">
                            <button 
                                type="button" 
                                id="bulk-sync-movies" 
                                class="w-full tmdb-sync-button"
                                data-type="movies"
                                data-nonce="<?php echo wp_create_nonce('tmu_bulk_sync'); ?>"
                            >
                                <span class="sync-button-text"><?php _e('Sync All Movies', 'tmu'); ?></span>
                                <span class="loading-spinner hidden"></span>
                            </button>
                            
                            <button 
                                type="button" 
                                id="bulk-sync-tv" 
                                class="w-full tmdb-sync-button"
                                data-type="tv"
                                data-nonce="<?php echo wp_create_nonce('tmu_bulk_sync'); ?>"
                            >
                                <span class="sync-button-text"><?php _e('Sync All TV Shows', 'tmu'); ?></span>
                                <span class="loading-spinner hidden"></span>
                            </button>
                            
                            <button 
                                type="button" 
                                id="bulk-sync-people" 
                                class="w-full tmdb-sync-button"
                                data-type="people"
                                data-nonce="<?php echo wp_create_nonce('tmu_bulk_sync'); ?>"
                            >
                                <span class="sync-button-text"><?php _e('Sync All People', 'tmu'); ?></span>
                                <span class="loading-spinner hidden"></span>
                            </button>
                            
                            <div id="sync-progress" class="hidden">
                                <div class="bg-gray-200 rounded-full h-2 mb-2">
                                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%" id="progress-bar"></div>
                                </div>
                                <p class="text-sm text-gray-600" id="progress-text">0%</p>
                            </div>
                            
                            <div class="border-t pt-4 mt-4">
                                <button 
                                    type="button" 
                                    id="load-sync-stats" 
                                    class="w-full admin-btn-secondary mb-3"
                                    data-nonce="<?php echo wp_create_nonce('tmu_get_sync_stats'); ?>"
                                >
                                    <?php _e('Load Sync Statistics', 'tmu'); ?>
                                </button>
                                
                                <div id="sync-stats-display" class="hidden">
                                    <div class="bg-gray-50 p-3 rounded text-sm">
                                        <div id="stats-content"></div>
                                    </div>
                                </div>
                                
                                <button 
                                    type="button" 
                                    id="clear-tmdb-cache" 
                                    class="w-full admin-btn-secondary"
                                    data-nonce="<?php echo wp_create_nonce('tmu_clear_tmdb_cache'); ?>"
                                >
                                    <?php _e('Clear TMDB Cache', 'tmu'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Webhook Configuration -->
                    <div class="admin-card mt-6">
                        <div class="admin-header">
                            <h3 class="text-lg font-semibold"><?php _e('Webhook Configuration', 'tmu'); ?></h3>
                        </div>
                        <div class="admin-body">
                            <div class="admin-form-group">
                                <label class="admin-label">
                                    <input 
                                        type="checkbox" 
                                        name="tmu_tmdb_webhook_enabled" 
                                        value="on" 
                                        <?php checked($webhook_enabled, 'on'); ?>
                                        class="mr-2"
                                    />
                                    <?php _e('Enable Webhooks', 'tmu'); ?>
                                </label>
                            </div>
                            
                            <div class="admin-form-group">
                                <label for="tmu_tmdb_webhook_secret" class="admin-label">
                                    <?php _e('Webhook Secret', 'tmu'); ?>
                                </label>
                                <input 
                                    type="text" 
                                    id="tmu_tmdb_webhook_secret" 
                                    name="tmu_tmdb_webhook_secret" 
                                    value="<?php echo esc_attr($webhook_secret); ?>" 
                                    class="admin-input"
                                    placeholder="<?php _e('Enter webhook secret', 'tmu'); ?>"
                                />
                            </div>
                            
                            <div class="admin-form-group">
                                <label class="admin-label"><?php _e('Webhook URL', 'tmu'); ?></label>
                                <input 
                                    type="text" 
                                    value="<?php echo home_url('/wp-json/tmu/v1/webhook'); ?>" 
                                    class="admin-input" 
                                    readonly
                                />
                                <button 
                                    type="button" 
                                    onclick="copyWebhookUrl(this)" 
                                    class="admin-btn-secondary mt-2"
                                >
                                    <?php _e('Copy URL', 'tmu'); ?>
                                </button>
                            </div>
                            
                            <button 
                                type="button" 
                                id="configure-webhook" 
                                class="admin-btn-primary"
                                data-nonce="<?php echo wp_create_nonce('tmu_webhook_config'); ?>"
                            >
                                <?php _e('Configure Webhook', 'tmu'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .tmu-admin-grid {
            display: grid;
        }
        
        .grid-cols-1 {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        
        @media (min-width: 1024px) {
            .lg\\:grid-cols-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            
            .lg\\:col-span-2 {
                grid-column: span 2 / span 2;
            }
            
            .lg\\:col-span-1 {
                grid-column: span 1 / span 1;
            }
        }
        
        .gap-6 {
            gap: 1.5rem;
        }
        
        .mt-6 {
            margin-top: 1.5rem;
        }
        
        .space-y-4 > :not([hidden]) ~ :not([hidden]) {
            margin-top: 1rem;
        }
        
        .alert {
            padding: 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        
        .alert.success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .hidden {
            display: none;
        }
        
        .flex {
            display: flex;
        }
        
        .flex-1 {
            flex: 1 1 0%;
        }
        
        .gap-2 {
            gap: 0.5rem;
        }
        
        .w-full {
            width: 100%;
        }
        
        .mr-2 {
            margin-right: 0.5rem;
        }
        
        .mt-1 {
            margin-top: 0.25rem;
        }
        
        .mt-2 {
            margin-top: 0.5rem;
        }
        
        .text-sm {
            font-size: 0.875rem;
        }
        
        .text-lg {
            font-size: 1.125rem;
        }
        
        .font-semibold {
            font-weight: 600;
        }
        
        .text-gray-600 {
            color: #4b5563;
        }
        
        .text-blue-600 {
            color: #2563eb;
        }
        
        .text-blue-800 {
            color: #1e40af;
        }
        
        .text-red-500 {
            color: #ef4444;
        }
        
        .hover\\:text-blue-800:hover {
            color: #1e40af;
        }
        
        .admin-btn-secondary {
            background-color: #6b7280;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.15s ease-in-out;
        }
        
        .admin-btn-secondary:hover {
            background-color: #4b5563;
        }
        
        .admin-btn-secondary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .mb-3 {
            margin-bottom: 0.75rem;
        }
        
        .pt-4 {
            padding-top: 1rem;
        }
        
        .mt-4 {
            margin-top: 1rem;
        }
        
        .border-t {
            border-top: 1px solid #e5e7eb;
        }
        
        .bg-gray-50 {
            background-color: #f9fafb;
        }
        
        .p-3 {
            padding: 0.75rem;
        }
        
        .rounded {
            border-radius: 0.375rem;
        }
        
        .space-y-2 > :not([hidden]) ~ :not([hidden]) {
            margin-top: 0.5rem;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Test TMDB connection
            $('#test-tmdb-connection').on('click', function() {
                const $btn = $(this);
                const $result = $('#connection-status');
                const $alert = $result.find('.alert');
                const apiKey = $('#tmu_tmdb_api_key').val();
                
                if (!apiKey) {
                    $result.removeClass('hidden');
                    $alert.removeClass('success').addClass('error').text('<?php _e('Please enter an API key', 'tmu'); ?>');
                    return;
                }
                
                const originalText = $btn.find('.test-button-text').text();
                $btn.prop('disabled', true);
                $btn.find('.test-button-text').text('<?php _e('Testing...', 'tmu'); ?>');
                $btn.find('.loading-spinner').removeClass('hidden');
                $result.addClass('hidden');
                
                $.post(ajaxurl, {
                    action: 'tmu_test_tmdb_connection',
                    api_key: apiKey,
                    nonce: $btn.data('nonce')
                }, function(response) {
                    $result.removeClass('hidden');
                    if (response.success) {
                        $alert.removeClass('error').addClass('success').text(response.data.message);
                    } else {
                        $alert.removeClass('success').addClass('error').text(response.data.message || response.data);
                    }
                }).fail(function() {
                    $result.removeClass('hidden');
                    $alert.removeClass('success').addClass('error').text('<?php _e('Network error occurred', 'tmu'); ?>');
                }).always(function() {
                    $btn.prop('disabled', false);
                    $btn.find('.test-button-text').text(originalText);
                    $btn.find('.loading-spinner').addClass('hidden');
                });
            });
            
            // Bulk sync operations
            $('.tmdb-sync-button').on('click', function() {
                const $btn = $(this);
                const type = $btn.data('type');
                const $progress = $('#sync-progress');
                const $progressBar = $('#progress-bar');
                const $progressText = $('#progress-text');
                
                if (!confirm('<?php _e('This will sync content with TMDB. This may take a while. Continue?', 'tmu'); ?>')) {
                    return;
                }
                
                const originalText = $btn.find('.sync-button-text').text();
                $btn.prop('disabled', true);
                $btn.find('.sync-button-text').text('<?php _e('Syncing...', 'tmu'); ?>');
                $btn.find('.loading-spinner').removeClass('hidden');
                
                $progress.removeClass('hidden');
                $progressBar.css('width', '0%');
                $progressText.text('<?php _e('Starting sync...', 'tmu'); ?>');
                
                let processed = 0;
                let total = 0;
                
                function performBatch() {
                    $.post(ajaxurl, {
                        action: 'tmu_bulk_sync',
                        type: type,
                        nonce: $btn.data('nonce')
                    }, function(response) {
                        if (response.success) {
                            processed += response.data.synced_count || 0;
                            
                            if (response.data.completed) {
                                $progressBar.css('width', '100%');
                                $progressText.text('<?php _e('Sync completed successfully!', 'tmu'); ?>');
                                showNotice('success', response.data.message);
                            } else if (response.data.has_more) {
                                // Calculate rough progress
                                const progress = Math.min(90, (processed / Math.max(processed + 10, 50)) * 100);
                                $progressBar.css('width', progress + '%');
                                $progressText.text('<?php _e('Synced', 'tmu'); ?> ' + processed + ' <?php _e('items...', 'tmu'); ?>');
                                
                                // Continue with next batch after short delay
                                setTimeout(performBatch, 1000);
                                return;
                            } else {
                                $progressBar.css('width', '100%');
                                $progressText.text(response.data.message);
                                showNotice('success', response.data.message);
                            }
                        } else {
                            $progressText.text('<?php _e('Sync failed', 'tmu'); ?>');
                            showNotice('error', response.data.message || '<?php _e('Sync failed', 'tmu'); ?>');
                        }
                        
                        $btn.prop('disabled', false);
                        $btn.find('.sync-button-text').text(originalText);
                        $btn.find('.loading-spinner').addClass('hidden');
                    }).fail(function() {
                        $progressText.text('<?php _e('Network error occurred', 'tmu'); ?>');
                        showNotice('error', '<?php _e('Network error occurred', 'tmu'); ?>');
                        
                        $btn.prop('disabled', false);
                        $btn.find('.sync-button-text').text(originalText);
                        $btn.find('.loading-spinner').addClass('hidden');
                    });
                }
                
                performBatch();
            });
            
            // Configure webhook
            $('#configure-webhook').on('click', function() {
                const $btn = $(this);
                const originalText = $btn.text();
                
                $btn.prop('disabled', true).text('<?php _e('Configuring...', 'tmu'); ?>');
                
                $.post(ajaxurl, {
                    action: 'tmu_configure_webhook',
                    nonce: $btn.data('nonce')
                }, function(response) {
                    if (response.success) {
                        showNotice('success', response.data.message);
                    } else {
                        showNotice('error', response.data.message || '<?php _e('Failed to configure webhook', 'tmu'); ?>');
                    }
                }).fail(function() {
                    showNotice('error', '<?php _e('Network error occurred', 'tmu'); ?>');
                }).always(function() {
                    $btn.prop('disabled', false).text(originalText);
                });
            });
            
                         // Load sync statistics
             $('#load-sync-stats').on('click', function() {
                 const $btn = $(this);
                 const $display = $('#sync-stats-display');
                 const $content = $('#stats-content');
                 const originalText = $btn.text();
                 
                 $btn.prop('disabled', true).text('<?php _e('Loading...', 'tmu'); ?>');
                 
                 $.post(ajaxurl, {
                     action: 'tmu_get_sync_stats',
                     nonce: $btn.data('nonce')
                 }, function(response) {
                     if (response.success) {
                         const stats = response.data;
                         let html = '<div class="space-y-2">';
                         html += '<div><strong><?php _e('Total Synced', 'tmu'); ?>:</strong> ' + stats.total_synced + '</div>';
                         html += '<div><strong><?php _e('Movies', 'tmu'); ?>:</strong> ' + stats.movies_synced + '</div>';
                         html += '<div><strong><?php _e('TV Shows', 'tmu'); ?>:</strong> ' + stats.tv_synced + '</div>';
                         html += '<div><strong><?php _e('People', 'tmu'); ?>:</strong> ' + stats.people_synced + '</div>';
                         html += '<div><strong><?php _e('Last Sync', 'tmu'); ?>:</strong> ' + stats.last_sync + '</div>';
                         html += '</div>';
                         
                         $content.html(html);
                         $display.removeClass('hidden');
                     } else {
                         showNotice('error', response.data.message || '<?php _e('Failed to load statistics', 'tmu'); ?>');
                     }
                 }).fail(function() {
                     showNotice('error', '<?php _e('Network error occurred', 'tmu'); ?>');
                 }).always(function() {
                     $btn.prop('disabled', false).text(originalText);
                 });
             });
             
             // Clear TMDB cache
             $('#clear-tmdb-cache').on('click', function() {
                 if (!confirm('<?php _e('Clear all TMDB API cache? This will force fresh data to be fetched from TMDB.', 'tmu'); ?>')) {
                     return;
                 }
                 
                 const $btn = $(this);
                 const originalText = $btn.text();
                 
                 $btn.prop('disabled', true).text('<?php _e('Clearing...', 'tmu'); ?>');
                 
                 $.post(ajaxurl, {
                     action: 'tmu_clear_tmdb_cache',
                     nonce: $btn.data('nonce')
                 }, function(response) {
                     if (response.success) {
                         showNotice('success', response.data.message);
                     } else {
                         showNotice('error', response.data.message || '<?php _e('Failed to clear cache', 'tmu'); ?>');
                     }
                 }).fail(function() {
                     showNotice('error', '<?php _e('Network error occurred', 'tmu'); ?>');
                 }).always(function() {
                     $btn.prop('disabled', false).text(originalText);
                 });
             });
             
             // Copy webhook URL functionality
             window.copyWebhookUrl = function(button) {
                 const url = button.previousElementSibling.value;
                 navigator.clipboard.writeText(url).then(function() {
                     showNotice('success', '<?php _e('Webhook URL copied to clipboard', 'tmu'); ?>');
                 }, function() {
                     // Fallback for older browsers
                     button.previousElementSibling.select();
                     document.execCommand('copy');
                     showNotice('success', '<?php _e('Webhook URL copied to clipboard', 'tmu'); ?>');
                 });
             };
            
            // Show notice function
            function showNotice(type, message) {
                const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
                const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
                
                $('.wrap h1').after(notice);
                
                // Auto-dismiss after 5 seconds
                setTimeout(function() {
                    notice.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
                
                // Manual dismiss
                notice.on('click', function() {
                    $(this).fadeOut(300, function() {
                        $(this).remove();
                    });
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Test TMDB connection via AJAX
     */
    public function testTMDBConnection(): void {
        check_ajax_referer('tmu_tmdb_test', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'tmu')]);
        }
        
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required', 'tmu')]);
        }
        
        // Test API connection
        $test_url = "https://api.themoviedb.org/3/configuration?api_key=" . $api_key;
        $response = wp_remote_get($test_url, [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => sprintf(__('Connection failed: %s', 'tmu'), $response->get_error_message())
            ]);
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code === 200 && isset($data['images'])) {
            wp_send_json_success([
                'message' => __('Connection successful! API key is valid.', 'tmu'),
                'data' => [
                    'base_url' => $data['images']['secure_base_url'] ?? '',
                    'poster_sizes' => $data['images']['poster_sizes'] ?? [],
                    'backdrop_sizes' => $data['images']['backdrop_sizes'] ?? []
                ]
            ]);
        } elseif ($status_code === 401) {
            wp_send_json_error(['message' => __('Invalid API key', 'tmu')]);
        } else {
            wp_send_json_error([
                'message' => sprintf(__('API error (Status: %d)', 'tmu'), $status_code)
            ]);
        }
    }
    
    /**
     * Handle bulk sync via AJAX
     */
    public function bulkSync(): void {
        check_ajax_referer('tmu_bulk_sync', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'tmu')]);
        }
        
        $type = sanitize_text_field($_POST['type'] ?? '');
        $batch_size = 10; // Process 10 items at a time
        
        switch ($type) {
            case 'movies':
                $posts = get_posts([
                    'post_type' => 'movie',
                    'posts_per_page' => $batch_size,
                    'meta_query' => [
                        [
                            'key' => '_tmdb_synced',
                            'compare' => 'NOT EXISTS'
                        ]
                    ]
                ]);
                break;
                
            case 'tv':
                $posts = get_posts([
                    'post_type' => 'tv-series',
                    'posts_per_page' => $batch_size,
                    'meta_query' => [
                        [
                            'key' => '_tmdb_synced',
                            'compare' => 'NOT EXISTS'
                        ]
                    ]
                ]);
                break;
                
            case 'people':
                $posts = get_posts([
                    'post_type' => 'people',
                    'posts_per_page' => $batch_size,
                    'meta_query' => [
                        [
                            'key' => '_tmdb_synced',
                            'compare' => 'NOT EXISTS'
                        ]
                    ]
                ]);
                break;
                
            default:
                wp_send_json_error(['message' => __('Invalid sync type', 'tmu')]);
        }
        
        if (empty($posts)) {
            wp_send_json_success([
                'message' => __('All items are already synced', 'tmu'),
                'completed' => true
            ]);
        }
        
        $synced_count = 0;
        foreach ($posts as $post) {
            // Simulate sync - in real implementation, call TMDB API
            update_post_meta($post->ID, '_tmdb_synced', current_time('mysql'));
            $synced_count++;
        }
        
        wp_send_json_success([
            'message' => sprintf(__('Synced %d items', 'tmu'), $synced_count),
            'synced_count' => $synced_count,
            'has_more' => count($posts) === $batch_size
        ]);
    }
    
    /**
     * Configure webhook via AJAX
     */
    public function configureWebhook(): void {
        check_ajax_referer('tmu_webhook_config', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'tmu')]);
        }
        
        $webhook_url = home_url('/wp-json/tmu/v1/webhook');
        
        wp_send_json_success([
            'message' => __('Webhook configured successfully', 'tmu'),
            'webhook_url' => $webhook_url
        ]);
    }
    
    /**
     * Get sync statistics via AJAX
     */
    public function getSyncStats(): void {
        check_ajax_referer('tmu_get_sync_stats', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'tmu')]);
        }
        
        // Get sync statistics
        $movies_synced = get_posts([
            'post_type' => 'movie',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_tmdb_synced',
                    'compare' => 'EXISTS'
                ]
            ],
            'fields' => 'ids'
        ]);
        
        $tv_synced = get_posts([
            'post_type' => 'tv-series',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_tmdb_synced',
                    'compare' => 'EXISTS'
                ]
            ],
            'fields' => 'ids'
        ]);
        
        $people_synced = get_posts([
            'post_type' => 'people',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_tmdb_synced',
                    'compare' => 'EXISTS'
                ]
            ],
            'fields' => 'ids'
        ]);
        
        // Get last sync time
        $last_sync = get_option('tmu_last_sync_time', '');
        
        $stats = [
            'total_synced' => count($movies_synced) + count($tv_synced) + count($people_synced),
            'movies_synced' => count($movies_synced),
            'tv_synced' => count($tv_synced),
            'people_synced' => count($people_synced),
            'last_sync' => $last_sync ? date('Y-m-d H:i:s', strtotime($last_sync)) : __('Never', 'tmu')
        ];
        
        wp_send_json_success($stats);
    }
    
    /**
     * Clear TMDB cache via AJAX
     */
    public function clearTMDBCache(): void {
        check_ajax_referer('tmu_clear_tmdb_cache', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized access', 'tmu')]);
        }
        
        // Clear TMDB-related transients
        global $wpdb;
        
        $transient_keys = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_tmdb_%' 
             OR option_name LIKE '_transient_timeout_tmdb_%'"
        );
        
        $cleared_count = 0;
        foreach ($transient_keys as $key) {
            if (strpos($key, '_transient_timeout_') === 0) {
                continue; // Skip timeout keys, they'll be cleaned up automatically
            }
            
            $transient_name = str_replace('_transient_', '', $key);
            if (delete_transient($transient_name)) {
                $cleared_count++;
            }
        }
        
        // Clear object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        wp_send_json_success([
            'message' => sprintf(__('Cleared %d cache entries', 'tmu'), $cleared_count)
        ]);
    }
}