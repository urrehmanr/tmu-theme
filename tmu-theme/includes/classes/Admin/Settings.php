<?php
/**
 * TMU Admin Settings
 *
 * @package TMU\Admin
 * @version 1.0.0
 */

namespace TMU\Admin;

use TMU\Config\ThemeConfig;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Settings Class
 */
class Settings {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Theme config
     */
    private $config;
    
    /**
     * Get instance
     */
    public static function getInstance(): Settings {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        $this->config = ThemeConfig::getInstance();
        $this->initHooks();
    }
    
    /**
     * Initialize hooks
     */
    private function initHooks(): void {
        add_action('admin_menu', [$this, 'addAdminPages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('admin_init', [$this, 'registerSettings']);
    }
    
    /**
     * Add admin pages
     */
    public function addAdminPages(): void {
        // Main settings page
        add_menu_page(
            __('TMU Settings', 'tmu'),
            __('TMU Settings', 'tmu'),
            'manage_options',
            'tmu-settings',
            [$this, 'renderSettingsPage'],
            'dashicons-video-alt2',
            30
        );
        
        // API settings submenu
        add_submenu_page(
            'tmu-settings',
            __('API Settings', 'tmu'),
            __('API Settings', 'tmu'),
            'manage_options',
            'tmu-api-settings',
            [$this, 'renderApiSettingsPage']
        );
        
        // SEO settings submenu
        add_submenu_page(
            'tmu-settings',
            __('SEO Settings', 'tmu'),
            __('SEO Settings', 'tmu'),
            'manage_options',
            'tmu-seo-settings',
            [$this, 'renderSeoSettingsPage']
        );
        
        // Migration submenu
        add_submenu_page(
            'tmu-settings',
            __('Migration', 'tmu'),
            __('Migration', 'tmu'),
            'manage_options',
            'tmu-migration',
            [$this, 'renderMigrationPage']
        );
    }
    
    /**
     * Register settings
     */
    public function registerSettings(): void {
        // API settings
        register_setting('tmu_api_settings', 'tmu_tmdb_api_key');
        register_setting('tmu_api_settings', 'tmu_cache_duration');
        
        // SEO settings
        register_setting('tmu_seo_settings', 'tmu_seo_enabled');
        
        // General settings
        register_setting('tmu_general_settings', 'tmu_enable_ratings');
        register_setting('tmu_general_settings', 'tmu_enable_comments');
        register_setting('tmu_general_settings', 'tmu_enable_ajax_search');
        register_setting('tmu_general_settings', 'tmu_images_per_page');
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueueAdminAssets($hook): void {
        if (strpos($hook, 'tmu-') === false) {
            return;
        }
        
        wp_enqueue_style(
            'tmu-admin-settings',
            TMU_ASSETS_URL . '/css/admin-settings.css',
            [],
            TMU_VERSION
        );
        
        wp_enqueue_script(
            'tmu-admin-settings',
            TMU_ASSETS_URL . '/js/admin-settings.js',
            ['jquery'],
            TMU_VERSION,
            true
        );
        
        wp_localize_script('tmu-admin-settings', 'tmuSettings', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmu_settings_nonce'),
            'apiNonce' => wp_create_nonce('tmu_api_nonce'),
        ]);
    }
    
    /**
     * Render main settings page
     */
    public function renderSettingsPage(): void {
        $options = [
            'tmu_movies' => get_option('tmu_movies', 'off'),
            'tmu_tv_series' => get_option('tmu_tv_series', 'off'),
            'tmu_dramas' => get_option('tmu_dramas', 'off'),
        ];
        
        ?>
        <div class="wrap tmu-settings bg-white">
            <h1 class="text-2xl font-bold text-tmu-dark mb-6"><?php _e('TMU Theme Settings', 'tmu'); ?></h1>
            
            <div class="tmu-settings-container flex gap-6">
                <div class="tmu-settings-content flex-1 bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h2 class="text-xl font-semibold text-tmu-dark mb-4"><?php _e('Content Types', 'tmu'); ?></h2>
                    <p class="text-gray-600 mb-6"><?php _e('Enable or disable different content types for your website.', 'tmu'); ?></p>
                    
                    <div class="tmu-setting-item flex justify-between items-center py-4 border-b border-gray-100">
                        <label for="tmu_movies" class="flex-1">
                            <strong class="text-tmu-dark"><?php _e('Movies', 'tmu'); ?></strong>
                            <p class="text-sm text-gray-600 mt-1"><?php _e('Enable movie database functionality', 'tmu'); ?></p>
                        </label>
                        <div class="tmu-toggle">
                            <input type="checkbox" 
                                   id="tmu_movies" 
                                   name="tmu_movies" 
                                   value="on" 
                                   <?php checked($options['tmu_movies'], 'on'); ?>
                                   data-setting="tmu_movies"
                                   class="sr-only">
                            <span class="tmu-toggle-slider"></span>
                        </div>
                    </div>
                    
                    <div class="tmu-setting-item flex justify-between items-center py-4 border-b border-gray-100">
                        <label for="tmu_tv_series" class="flex-1">
                            <strong class="text-tmu-dark"><?php _e('TV Series', 'tmu'); ?></strong>
                            <p class="text-sm text-gray-600 mt-1"><?php _e('Enable TV series database functionality', 'tmu'); ?></p>
                        </label>
                        <div class="tmu-toggle">
                            <input type="checkbox" 
                                   id="tmu_tv_series" 
                                   name="tmu_tv_series" 
                                   value="on" 
                                   <?php checked($options['tmu_tv_series'], 'on'); ?>
                                   data-setting="tmu_tv_series"
                                   class="sr-only">
                            <span class="tmu-toggle-slider"></span>
                        </div>
                    </div>
                    
                    <div class="tmu-setting-item flex justify-between items-center py-4">
                        <label for="tmu_dramas" class="flex-1">
                            <strong class="text-tmu-dark"><?php _e('Dramas', 'tmu'); ?></strong>
                            <p class="text-sm text-gray-600 mt-1"><?php _e('Enable drama series database functionality', 'tmu'); ?></p>
                        </label>
                        <div class="tmu-toggle">
                            <input type="checkbox" 
                                   id="tmu_dramas" 
                                   name="tmu_dramas" 
                                   value="on" 
                                   <?php checked($options['tmu_dramas'], 'on'); ?>
                                   data-setting="tmu_dramas"
                                   class="sr-only">
                            <span class="tmu-toggle-slider"></span>
                        </div>
                    </div>
                </div>
                
                <div class="tmu-settings-sidebar w-80">
                    <div class="tmu-settings-box bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-4">
                        <h3 class="text-lg font-semibold text-tmu-dark mb-4"><?php _e('Quick Actions', 'tmu'); ?></h3>
                        <div class="space-y-2">
                            <p><a href="<?php echo admin_url('admin.php?page=tmu-migration'); ?>" class="btn btn-secondary"><?php _e('Database Migration', 'tmu'); ?></a></p>
                            <p><a href="<?php echo admin_url('admin.php?page=tmu-api-settings'); ?>" class="btn btn-secondary"><?php _e('API Settings', 'tmu'); ?></a></p>
                            <p><a href="<?php echo admin_url('admin.php?page=tmu-seo-settings'); ?>" class="btn btn-secondary"><?php _e('SEO Settings', 'tmu'); ?></a></p>
                        </div>
                    </div>
                    
                    <div class="tmu-settings-box bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-lg font-semibold text-tmu-dark mb-4"><?php _e('Documentation', 'tmu'); ?></h3>
                        <div class="space-y-2 text-sm">
                            <p><a href="#" target="_blank" class="text-tmu-primary hover:text-tmu-accent"><?php _e('Theme Documentation', 'tmu'); ?></a></p>
                            <p><a href="#" target="_blank" class="text-tmu-primary hover:text-tmu-accent"><?php _e('API Documentation', 'tmu'); ?></a></p>
                            <p><a href="#" target="_blank" class="text-tmu-primary hover:text-tmu-accent"><?php _e('Support Forum', 'tmu'); ?></a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render API settings page
     */
    public function renderApiSettingsPage(): void {
        $api_key = get_option('tmu_tmdb_api_key', '');
        $cache_duration = get_option('tmu_cache_duration', 3600);
        
        ?>
        <div class="wrap tmu-settings bg-white">
            <h1 class="text-2xl font-bold text-tmu-dark mb-6"><?php _e('TMU API Settings', 'tmu'); ?></h1>
            
            <form method="post" action="options.php" class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <?php settings_fields('tmu_api_settings'); ?>
                
                <div class="space-y-6">
                    <div>
                        <label for="tmu_tmdb_api_key" class="block text-sm font-medium text-tmu-dark mb-2">
                            <?php _e('TMDB API Key', 'tmu'); ?>
                        </label>
                        <input type="text" 
                               id="tmu_tmdb_api_key"
                               name="tmu_tmdb_api_key" 
                               value="<?php echo esc_attr($api_key); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-tmu-primary focus:border-transparent" />
                        <p class="text-sm text-gray-600 mt-2">
                            <?php _e('Enter your TMDB API key to enable automatic data fetching.', 'tmu'); ?>
                            <a href="https://www.themoviedb.org/settings/api" target="_blank" class="text-tmu-primary hover:text-tmu-accent"><?php _e('Get API Key', 'tmu'); ?></a>
                        </p>
                        <div class="mt-2">
                            <button type="button" id="test-api" class="btn btn-secondary"><?php _e('Test Connection', 'tmu'); ?></button>
                            <span id="api-test-result" class="ml-2"></span>
                        </div>
                    </div>
                    
                    <div>
                        <label for="tmu_cache_duration" class="block text-sm font-medium text-tmu-dark mb-2">
                            <?php _e('Cache Duration', 'tmu'); ?>
                        </label>
                        <input type="number" 
                               id="tmu_cache_duration"
                               name="tmu_cache_duration" 
                               value="<?php echo esc_attr($cache_duration); ?>" 
                               min="300" 
                               max="86400"
                               class="w-32 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-tmu-primary focus:border-transparent" />
                        <p class="text-sm text-gray-600 mt-2"><?php _e('Cache duration in seconds (300-86400).', 'tmu'); ?></p>
                    </div>
                </div>
                
                <div class="mt-6">
                    <?php submit_button(__('Save Settings', 'tmu'), 'primary', 'submit', false, ['class' => 'btn btn-primary']); ?>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render SEO settings page
     */
    public function renderSeoSettingsPage(): void {
        $seo_enabled = get_option('tmu_seo_enabled', 'on');
        
        ?>
        <div class="wrap tmu-settings bg-white">
            <h1 class="text-2xl font-bold text-tmu-dark mb-6"><?php _e('TMU SEO Settings', 'tmu'); ?></h1>
            
            <form method="post" action="options.php" class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <?php settings_fields('tmu_seo_settings'); ?>
                
                <div class="space-y-6">
                    <div class="tmu-setting-item flex justify-between items-center py-4 border-b border-gray-100">
                        <label for="tmu_seo_enabled" class="flex-1">
                            <strong class="text-tmu-dark"><?php _e('Enable SEO Features', 'tmu'); ?></strong>
                            <p class="text-sm text-gray-600 mt-1"><?php _e('Enable enhanced SEO features for better search engine optimization.', 'tmu'); ?></p>
                        </label>
                        <div class="tmu-toggle">
                            <input type="checkbox" 
                                   id="tmu_seo_enabled" 
                                   name="tmu_seo_enabled" 
                                   value="on" 
                                   <?php checked($seo_enabled, 'on'); ?>
                                   class="sr-only">
                            <span class="tmu-toggle-slider"></span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6">
                    <?php submit_button(__('Save Settings', 'tmu'), 'primary', 'submit', false, ['class' => 'btn btn-primary']); ?>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render migration page
     */
    public function renderMigrationPage(): void {
        $migrator = new \TMU\Migration\SettingsMigrator();
        $status = $migrator->getMigrationStatus();
        
        ?>
        <div class="wrap tmu-settings bg-white">
            <h1 class="text-2xl font-bold text-tmu-dark mb-6"><?php _e('TMU Migration', 'tmu'); ?></h1>
            
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <h2 class="text-xl font-semibold text-tmu-dark mb-4"><?php _e('Migration Status', 'tmu'); ?></h2>
                
                <?php if ($status['migrated']): ?>
                    <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-4">
                        <p class="text-green-700">
                            <strong><?php _e('Migration Completed', 'tmu'); ?></strong><br>
                            <?php printf(__('Migration completed on: %s', 'tmu'), $status['migration_date']); ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-4">
                        <p class="text-yellow-700">
                            <strong><?php _e('Migration Not Completed', 'tmu'); ?></strong><br>
                            <?php _e('Plugin settings have not been migrated yet.', 'tmu'); ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <h3 class="text-lg font-semibold text-tmu-dark mb-4"><?php _e('Migration Details', 'tmu'); ?></h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Setting</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plugin Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Theme Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($status['migrated_options'] as $option => $data): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo esc_html($option); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo esc_html($data['plugin_value'] !== false ? $data['plugin_value'] : 'N/A'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo esc_html($data['theme_value'] !== false ? $data['theme_value'] : 'N/A'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($data['migrated']): ?>
                                            <span class="text-green-600">✓ Migrated</span>
                                        <?php else: ?>
                                            <span class="text-gray-400">Not migrated</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}