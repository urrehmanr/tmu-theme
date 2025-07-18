<?php
/**
 * Admin Manager
 * 
 * Main admin coordinator that manages all TMU admin interface enhancements
 * including columns, meta boxes, widgets, and bulk actions.
 * 
 * @package TMU\Admin
 * @since 1.0.0
 */

namespace TMU\Admin;

use TMU\Config\ThemeConfig;

/**
 * AdminManager class
 * 
 * Central management of all TMU admin interface components
 */
class AdminManager {
    
    /**
     * Singleton instance
     * @var AdminManager|null
     */
    private static $instance = null;
    
    /**
     * Theme configuration
     * @var ThemeConfig
     */
    private $config;
    
    /**
     * Admin components
     * @var array
     */
    private $components = [];
    
    /**
     * Get singleton instance
     * 
     * @return AdminManager
     */
    public static function getInstance(): AdminManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->config = ThemeConfig::getInstance();
        $this->initHooks();
        $this->loadComponents();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void {
        add_action('admin_init', [$this, 'initAdmin']);
        add_action('admin_menu', [$this, 'customizeAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('admin_bar_menu', [$this, 'customizeAdminBar'], 100);
        add_filter('admin_footer_text', [$this, 'customAdminFooter']);
        add_action('wp_dashboard_setup', [$this, 'addDashboardWidgets']);
    }
    
    /**
     * Load admin components
     */
    private function loadComponents(): void {
        // Load admin column managers
        if (class_exists('TMU\Admin\Columns\MovieColumns')) {
            $this->components['movie_columns'] = new Columns\MovieColumns();
        }
        
        if (class_exists('TMU\Admin\Columns\TVColumns')) {
            $this->components['tv_columns'] = new Columns\TVColumns();
        }
        
        if (class_exists('TMU\Admin\Columns\DramaColumns')) {
            $this->components['drama_columns'] = new Columns\DramaColumns();
        }
        
        if (class_exists('TMU\Admin\Columns\PeopleColumns')) {
            $this->components['people_columns'] = new Columns\PeopleColumns();
        }
        
        // Load action handlers
        if (class_exists('TMU\Admin\Actions\TMDBSync')) {
            $this->components['tmdb_sync'] = new Actions\TMDBSync();
        }
        
        if (class_exists('TMU\Admin\Actions\BulkEdit')) {
            $this->components['bulk_edit'] = new Actions\BulkEdit();
        }
        
        // Load dashboard components
        if (class_exists('TMU\Admin\Dashboard\Widgets')) {
            $this->components['widgets'] = new Dashboard\Widgets();
        }
        
        // Load enhanced meta boxes - Disabled for standalone theme
        // if (class_exists('TMU\Admin\MetaBoxes\TMDBBox')) {
        //     $this->components['tmdb_box'] = new MetaBoxes\TMDBBox();
        // }
        // 
        // if (class_exists('TMU\Admin\MetaBoxes\RelationshipBox')) {
        //     $this->components['relationship_box'] = new MetaBoxes\RelationshipBox();
        // }
        
        // Load data import tools
        if (class_exists('TMU\Admin\Actions\DataImport')) {
            $this->components['data_import'] = new Actions\DataImport();
        }
        
        // Load additional meta boxes - Disabled for standalone theme
        // if (class_exists('TMU\Admin\MetaBoxes\QuickActions')) {
        //     $this->components['quick_actions'] = new MetaBoxes\QuickActions();
        // }
        
        // Load additional dashboard components
        if (class_exists('TMU\Admin\Dashboard\QuickStats')) {
            $this->components['quick_stats'] = new Dashboard\QuickStats();
        }
        
        // Load navigation enhancements
        if (class_exists('TMU\Admin\Navigation\MenuManager')) {
            $this->components['menu_manager'] = new Navigation\MenuManager();
        }
        
        if (class_exists('TMU\Admin\Navigation\SubMenus')) {
            $this->components['sub_menus'] = new Navigation\SubMenus();
        }
        
        // Load TMDB settings
        if (class_exists('TMU\Admin\Settings\TMDBSettings')) {
            $this->components['tmdb_settings'] = Settings\TMDBSettings::getInstance();
        }
    }
    
    /**
     * Initialize admin
     */
    public function initAdmin(): void {
        // Add custom admin capabilities
        $this->addAdminCapabilities();
        
        // Initialize admin notices
        add_action('admin_notices', [$this, 'showAdminNotices']);
    }
    
    /**
     * Customize admin menu
     */
    public function customizeAdminMenu(): void {
        // Group TMU content together
        $tmu_position = 25;
        
        // Main TMU content menu (only if movies are enabled)
        if ($this->config->isFeatureEnabled('movies')) {
            add_menu_page(
                __('TMU Content', 'tmu'),
                __('TMU Content', 'tmu'),
                'edit_posts',
                'edit.php?post_type=movie',
                '',
                'dashicons-video-alt2',
                $tmu_position
            );
        }
        
        // Add quick actions submenu
        add_submenu_page(
            'edit.php?post_type=movie',
            __('Quick Actions', 'tmu'),
            __('Quick Actions', 'tmu'),
            'manage_options',
            'tmu-quick-actions',
            [$this, 'renderQuickActionsPage']
        );
        
        // Add data management submenu
        add_submenu_page(
            'edit.php?post_type=movie',
            __('Data Management', 'tmu'),
            __('Data Management', 'tmu'),
            'manage_options',
            'tmu-data-management',
            [$this, 'renderDataManagementPage']
        );
        
        // Add statistics submenu
        add_submenu_page(
            'edit.php?post_type=movie',
            __('Statistics', 'tmu'),
            __('Statistics', 'tmu'),
            'manage_options',
            'tmu-statistics',
            [$this, 'renderStatisticsPage']
        );
    }
    
    /**
     * Enqueue admin assets
     * 
     * @param string $hook_suffix Current admin page
     */
    public function enqueueAdminAssets($hook_suffix): void {
        global $post_type;
        
        // Load on TMU post type pages
        if (in_array($post_type, ['movie', 'tv', 'drama', 'people']) || 
            strpos($hook_suffix, 'tmu-') !== false) {
            
            wp_enqueue_style(
                'tmu-admin',
                TMU_ASSETS_BUILD_URL . '/css/admin.css',
                [],
                TMU_VERSION
            );
            
            wp_enqueue_script(
                'tmu-admin',
                TMU_ASSETS_BUILD_URL . '/js/admin.js',
                ['jquery'],
                TMU_VERSION,
                true
            );
            
            wp_localize_script('tmu-admin', 'tmuAdmin', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('tmu_admin_nonce'),
                'postType' => $post_type,
                'strings' => [
                    'confirm_sync' => __('Sync with TMDB? This will overwrite existing data.', 'tmu'),
                    'syncing' => __('Syncing...', 'tmu'),
                    'sync_complete' => __('Sync completed successfully!', 'tmu'),
                    'sync_error' => __('Sync failed. Please try again.', 'tmu'),
                    'bulk_processing' => __('Processing bulk action...', 'tmu'),
                    'select_items' => __('Please select items to process.', 'tmu'),
                ],
            ]);
        }
    }
    
    /**
     * Customize admin bar
     * 
     * @param \WP_Admin_Bar $wp_admin_bar
     */
    public function customizeAdminBar($wp_admin_bar): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $wp_admin_bar->add_node([
            'id' => 'tmu-quick-menu',
            'title' => '<span class="ab-icon dashicons-video-alt2"></span> TMU',
            'href' => admin_url('admin.php?page=tmu-quick-actions'),
        ]);
        
        $wp_admin_bar->add_node([
            'id' => 'tmu-sync-all',
            'parent' => 'tmu-quick-menu',
            'title' => __('Sync All TMDB', 'tmu'),
            'href' => '#',
            'meta' => ['class' => 'tmu-sync-all-link'],
        ]);
        
        $wp_admin_bar->add_node([
            'id' => 'tmu-statistics',
            'parent' => 'tmu-quick-menu',
            'title' => __('View Statistics', 'tmu'),
            'href' => admin_url('admin.php?page=tmu-statistics'),
        ]);
    }
    
    /**
     * Custom admin footer
     * 
     * @param string $text Footer text
     * @return string Modified footer text
     */
    public function customAdminFooter($text): string {
        global $post_type;
        
        if (in_array($post_type, ['movie', 'tv', 'drama', 'people'])) {
            $text = sprintf(
                __('Managing %s with TMU Theme', 'tmu'),
                ucfirst($post_type)
            );
        }
        
        return $text;
    }
    
    /**
     * Add dashboard widgets
     */
    public function addDashboardWidgets(): void {
        wp_add_dashboard_widget(
            'tmu-content-stats',
            __('TMU Content Statistics', 'tmu'),
            [$this, 'renderContentStatsWidget']
        );
        
        wp_add_dashboard_widget(
            'tmu-recent-updates',
            __('Recent TMU Updates', 'tmu'),
            [$this, 'renderRecentUpdatesWidget']
        );
    }
    
    /**
     * Render quick actions page
     */
    public function renderQuickActionsPage(): void {
        ?>
        <div class="wrap tmu-quick-actions">
            <h1><?php _e('TMU Quick Actions', 'tmu'); ?></h1>
            
            <div class="tmu-action-cards">
                <div class="tmu-action-card">
                    <div class="card-icon">
                        <span class="dashicons dashicons-update"></span>
                    </div>
                    <h3><?php _e('TMDB Sync', 'tmu'); ?></h3>
                    <p><?php _e('Sync all content with TMDB for latest information.', 'tmu'); ?></p>
                    <button id="bulk-tmdb-sync" class="button button-primary">
                        <?php _e('Start Sync', 'tmu'); ?>
                    </button>
                </div>
                
                <div class="tmu-action-card">
                    <div class="card-icon">
                        <span class="dashicons dashicons-download"></span>
                    </div>
                    <h3><?php _e('Import Content', 'tmu'); ?></h3>
                    <p><?php _e('Import new movies/shows from TMDB.', 'tmu'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=tmu-data-management'); ?>" class="button">
                        <?php _e('Import Tools', 'tmu'); ?>
                    </a>
                </div>
                
                <div class="tmu-action-card">
                    <div class="card-icon">
                        <span class="dashicons dashicons-admin-tools"></span>
                    </div>
                    <h3><?php _e('Data Health', 'tmu'); ?></h3>
                    <p><?php _e('Check and repair data integrity issues.', 'tmu'); ?></p>
                    <button id="data-health-check" class="button">
                        <?php _e('Health Check', 'tmu'); ?>
                    </button>
                </div>
                
                <div class="tmu-action-card">
                    <div class="card-icon">
                        <span class="dashicons dashicons-chart-bar"></span>
                    </div>
                    <h3><?php _e('View Statistics', 'tmu'); ?></h3>
                    <p><?php _e('See detailed statistics about your content.', 'tmu'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=tmu-statistics'); ?>" class="button">
                        <?php _e('View Stats', 'tmu'); ?>
                    </a>
                </div>
            </div>
            
            <div id="action-progress" class="tmu-progress" style="display:none;">
                <div class="progress-bar"><div class="progress-fill"></div></div>
                <div class="progress-text"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render data management page
     */
    public function renderDataManagementPage(): void {
        ?>
        <div class="wrap tmu-data-management">
            <h1><?php _e('TMU Data Management', 'tmu'); ?></h1>
            
            <div class="tmu-management-sections">
                <section class="tmu-import-section">
                    <h2><?php _e('Import Content', 'tmu'); ?></h2>
                    <p><?php _e('Import content from TMDB or other sources.', 'tmu'); ?></p>
                    <!-- Import tools will be implemented here -->
                </section>
                
                <section class="tmu-export-section">
                    <h2><?php _e('Export Content', 'tmu'); ?></h2>
                    <p><?php _e('Export your content data for backup or migration.', 'tmu'); ?></p>
                    <!-- Export tools will be implemented here -->
                </section>
                
                <section class="tmu-cleanup-section">
                    <h2><?php _e('Data Cleanup', 'tmu'); ?></h2>
                    <p><?php _e('Clean up orphaned data and optimize your database.', 'tmu'); ?></p>
                    <!-- Cleanup tools will be implemented here -->
                </section>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render statistics page
     */
    public function renderStatisticsPage(): void {
        $stats = $this->getContentStatistics();
        ?>
        <div class="wrap tmu-statistics">
            <h1><?php _e('TMU Content Statistics', 'tmu'); ?></h1>
            
            <div class="tmu-stats-grid">
                <div class="stat-card">
                    <h3><?php _e('Movies', 'tmu'); ?></h3>
                    <div class="stat-number"><?php echo number_format($stats['movies']); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3><?php _e('TV Shows', 'tmu'); ?></h3>
                    <div class="stat-number"><?php echo number_format($stats['tv_shows']); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3><?php _e('Dramas', 'tmu'); ?></h3>
                    <div class="stat-number"><?php echo number_format($stats['dramas']); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3><?php _e('People', 'tmu'); ?></h3>
                    <div class="stat-number"><?php echo number_format($stats['people']); ?></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render content stats widget
     */
    public function renderContentStatsWidget(): void {
        $stats = $this->getContentStatistics();
        ?>
        <div class="tmu-content-stats-widget">
            <ul>
                <li><strong><?php _e('Movies:', 'tmu'); ?></strong> <?php echo number_format($stats['movies']); ?></li>
                <li><strong><?php _e('TV Shows:', 'tmu'); ?></strong> <?php echo number_format($stats['tv_shows']); ?></li>
                <li><strong><?php _e('Dramas:', 'tmu'); ?></strong> <?php echo number_format($stats['dramas']); ?></li>
                <li><strong><?php _e('People:', 'tmu'); ?></strong> <?php echo number_format($stats['people']); ?></li>
            </ul>
            <p>
                <a href="<?php echo admin_url('admin.php?page=tmu-statistics'); ?>" class="button">
                    <?php _e('View Detailed Stats', 'tmu'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render recent updates widget
     */
    public function renderRecentUpdatesWidget(): void {
        $recent_posts = get_posts([
            'post_type' => ['movie', 'tv', 'drama', 'people'],
            'posts_per_page' => 5,
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);
        
        ?>
        <div class="tmu-recent-updates-widget">
            <?php if ($recent_posts): ?>
            <ul>
                <?php foreach ($recent_posts as $post): ?>
                <li>
                    <strong><?php echo esc_html($post->post_title); ?></strong>
                    <span class="post-type">(<?php echo ucfirst($post->post_type); ?>)</span>
                    <br>
                    <small><?php echo human_time_diff(strtotime($post->post_modified)); ?> <?php _e('ago', 'tmu'); ?></small>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p><?php _e('No recent updates.', 'tmu'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Get content statistics
     * 
     * @return array Content statistics
     */
    private function getContentStatistics(): array {
        return [
            'movies' => wp_count_posts('movie')->publish ?? 0,
            'tv_shows' => wp_count_posts('tv')->publish ?? 0,
            'dramas' => wp_count_posts('drama')->publish ?? 0,
            'people' => wp_count_posts('people')->publish ?? 0,
        ];
    }
    
    /**
     * Add admin capabilities
     */
    private function addAdminCapabilities(): void {
        // Add custom capabilities for TMU content management
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_tmu_content');
            $role->add_cap('sync_tmdb_data');
            $role->add_cap('import_tmu_data');
        }
    }
    
    /**
     * Show admin notices
     */
    public function showAdminNotices(): void {
        // Show notices for TMU-specific events
        if (isset($_GET['tmu_sync_complete'])) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('TMDB sync completed successfully!', 'tmu'); ?></p>
            </div>
            <?php
        }
        
        if (isset($_GET['tmu_sync_error'])) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e('TMDB sync failed. Please check your API configuration.', 'tmu'); ?></p>
            </div>
            <?php
        }
    }
    
    /**
     * Get component instance
     * 
     * @param string $component Component name
     * @return mixed|null Component instance
     */
    public function getComponent($component) {
        return $this->components[$component] ?? null;
    }
}