<?php
/**
 * TMU Welcome Screen
 *
 * @package TMU\Admin
 * @version 1.0.0
 */

namespace TMU\Admin;

use TMU\Config\ThemeConfig;
use TMU\Migration\SettingsMigrator;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Welcome Screen Class
 */
class Welcome {
    
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
    public static function getInstance(): Welcome {
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
        add_action('admin_menu', [$this, 'addWelcomePage']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_tmu_dismiss_welcome', [$this, 'dismissWelcome']);
    }
    
    /**
     * Add welcome page
     */
    public function addWelcomePage(): void {
        add_submenu_page(
            'tmu-settings',
            __('Welcome', 'tmu'),
            __('Welcome', 'tmu'),
            'manage_options',
            'tmu-welcome',
            [$this, 'renderWelcomePage']
        );
    }
    
    /**
     * Show welcome screen
     */
    public function showWelcomeScreen(): void {
        // Check if welcome was already dismissed
        if (get_option('tmu_welcome_dismissed', false)) {
            return;
        }
        
        // Check if this is the first time activation
        if (get_transient('tmu_theme_activated')) {
            wp_redirect(admin_url('admin.php?page=tmu-welcome'));
            exit;
        }
    }
    
    /**
     * Enqueue welcome assets
     */
    public function enqueueAssets($hook): void {
        if ($hook !== 'tmu-settings_page_tmu-welcome') {
            return;
        }
        
        wp_enqueue_style(
            'tmu-welcome',
            TMU_ASSETS_URL . '/css/admin.css',
            [],
            TMU_VERSION
        );
        
        wp_enqueue_script(
            'tmu-welcome',
            TMU_ASSETS_URL . '/js/admin.js',
            ['jquery'],
            TMU_VERSION,
            true
        );
        
        wp_localize_script('tmu-welcome', 'tmuWelcome', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmu_welcome_nonce'),
        ]);
    }
    
    /**
     * Render welcome page
     */
    public function renderWelcomePage(): void {
        $migrator = new SettingsMigrator();
        $migration_status = $migrator->getMigrationStatus();
        $config_status = $this->config->getConfigurationStatus();
        
        ?>
        <div class="wrap tmu-welcome bg-white">
            <div class="max-w-4xl mx-auto py-8">
                
                <!-- Header -->
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-tmu-dark mb-2">
                        <?php _e('Welcome to TMU Theme', 'tmu'); ?>
                    </h1>
                    <p class="text-lg text-gray-600">
                        <?php _e('Thank you for choosing TMU Theme. Let\'s get you started!', 'tmu'); ?>
                    </p>
                </div>
                
                <!-- Setup Progress -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
                    <h2 class="text-xl font-semibold text-tmu-dark mb-4">
                        <?php _e('Setup Progress', 'tmu'); ?>
                    </h2>
                    
                    <div class="space-y-4">
                        <!-- Migration Status -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full <?php echo $migration_status['migrated'] ? 'bg-green-500' : 'bg-gray-300'; ?> flex items-center justify-center mr-3">
                                    <?php if ($migration_status['migrated']): ?>
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <span class="text-white font-medium">1</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h3 class="font-medium text-tmu-dark"><?php _e('Plugin Settings Migration', 'tmu'); ?></h3>
                                    <p class="text-sm text-gray-600">
                                        <?php if ($migration_status['migrated']): ?>
                                            <?php _e('Settings migrated successfully', 'tmu'); ?>
                                        <?php else: ?>
                                            <?php _e('Migrate existing plugin settings', 'tmu'); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <?php if (!$migration_status['migrated']): ?>
                                <a href="<?php echo admin_url('admin.php?page=tmu-migration'); ?>" class="btn btn-primary">
                                    <?php _e('Migrate Now', 'tmu'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- API Configuration -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full <?php echo $config_status['api_key_configured'] ? 'bg-green-500' : 'bg-gray-300'; ?> flex items-center justify-center mr-3">
                                    <?php if ($config_status['api_key_configured']): ?>
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <span class="text-white font-medium">2</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h3 class="font-medium text-tmu-dark"><?php _e('API Configuration', 'tmu'); ?></h3>
                                    <p class="text-sm text-gray-600">
                                        <?php if ($config_status['api_key_configured']): ?>
                                            <?php _e('TMDB API key configured', 'tmu'); ?>
                                        <?php else: ?>
                                            <?php _e('Configure TMDB API key for data fetching', 'tmu'); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <?php if (!$config_status['api_key_configured']): ?>
                                <a href="<?php echo admin_url('admin.php?page=tmu-api-settings'); ?>" class="btn btn-primary">
                                    <?php _e('Configure API', 'tmu'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Content Types -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full <?php echo !empty($config_status['enabled_features']) ? 'bg-green-500' : 'bg-gray-300'; ?> flex items-center justify-center mr-3">
                                    <?php if (!empty($config_status['enabled_features'])): ?>
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <span class="text-white font-medium">3</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h3 class="font-medium text-tmu-dark"><?php _e('Content Types', 'tmu'); ?></h3>
                                    <p class="text-sm text-gray-600">
                                        <?php if (!empty($config_status['enabled_features'])): ?>
                                            <?php printf(__('Enabled: %s', 'tmu'), implode(', ', $config_status['enabled_features'])); ?>
                                        <?php else: ?>
                                            <?php _e('Enable Movies, TV Series, or Dramas', 'tmu'); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <?php if (empty($config_status['enabled_features'])): ?>
                                <a href="<?php echo admin_url('admin.php?page=tmu-settings'); ?>" class="btn btn-primary">
                                    <?php _e('Enable Content Types', 'tmu'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-tmu-dark mb-3">
                            <?php _e('Settings', 'tmu'); ?>
                        </h3>
                        <p class="text-gray-600 mb-4">
                            <?php _e('Configure your theme settings and enable content types.', 'tmu'); ?>
                        </p>
                        <a href="<?php echo admin_url('admin.php?page=tmu-settings'); ?>" class="btn btn-primary">
                            <?php _e('Go to Settings', 'tmu'); ?>
                        </a>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-tmu-dark mb-3">
                            <?php _e('API Setup', 'tmu'); ?>
                        </h3>
                        <p class="text-gray-600 mb-4">
                            <?php _e('Connect to TMDB API for automatic data fetching.', 'tmu'); ?>
                        </p>
                        <a href="<?php echo admin_url('admin.php?page=tmu-api-settings'); ?>" class="btn btn-primary">
                            <?php _e('Setup API', 'tmu'); ?>
                        </a>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-tmu-dark mb-3">
                            <?php _e('Documentation', 'tmu'); ?>
                        </h3>
                        <p class="text-gray-600 mb-4">
                            <?php _e('Learn how to use all the features of TMU Theme.', 'tmu'); ?>
                        </p>
                        <a href="#" class="btn btn-secondary">
                            <?php _e('View Docs', 'tmu'); ?>
                        </a>
                    </div>
                </div>
                
                <!-- Getting Started -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-semibold text-tmu-dark mb-4">
                        <?php _e('Getting Started', 'tmu'); ?>
                    </h2>
                    
                    <div class="prose max-w-none">
                        <p class="text-gray-600 mb-4">
                            <?php _e('TMU Theme is a powerful WordPress theme for creating movie, TV series, and drama databases. Here\'s how to get started:', 'tmu'); ?>
                        </p>
                        
                        <ol class="text-gray-600 space-y-2">
                            <li><strong><?php _e('Configure API Settings:', 'tmu'); ?></strong> <?php _e('Get your TMDB API key and configure it in the API settings.', 'tmu'); ?></li>
                            <li><strong><?php _e('Enable Content Types:', 'tmu'); ?></strong> <?php _e('Choose which content types you want to use (Movies, TV Series, Dramas).', 'tmu'); ?></li>
                            <li><strong><?php _e('Import Data:', 'tmu'); ?></strong> <?php _e('Start importing your movie/TV data from TMDB.', 'tmu'); ?></li>
                            <li><strong><?php _e('Customize Theme:', 'tmu'); ?></strong> <?php _e('Customize the theme appearance to match your brand.', 'tmu'); ?></li>
                        </ol>
                    </div>
                </div>
                
                <!-- Footer Actions -->
                <div class="flex justify-between items-center mt-8">
                    <button id="dismiss-welcome" class="text-gray-500 hover:text-gray-700">
                        <?php _e('Don\'t show this again', 'tmu'); ?>
                    </button>
                    
                    <div class="space-x-4">
                        <a href="<?php echo admin_url('admin.php?page=tmu-settings'); ?>" class="btn btn-primary">
                            <?php _e('Go to Settings', 'tmu'); ?>
                        </a>
                        <a href="<?php echo admin_url(); ?>" class="btn btn-secondary">
                            <?php _e('Go to Dashboard', 'tmu'); ?>
                        </a>
                    </div>
                </div>
                
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#dismiss-welcome').on('click', function() {
                $.ajax({
                    url: tmuWelcome.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'tmu_dismiss_welcome',
                        nonce: tmuWelcome.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = '<?php echo admin_url('admin.php?page=tmu-settings'); ?>';
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Dismiss welcome screen
     */
    public function dismissWelcome(): void {
        check_ajax_referer('tmu_welcome_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        update_option('tmu_welcome_dismissed', true);
        wp_send_json_success(['message' => 'Welcome screen dismissed']);
    }
}