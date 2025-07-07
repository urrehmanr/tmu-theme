<?php
/**
 * Menu Manager
 * 
 * Manages admin menu customization and organization for TMU content types.
 * Provides centralized menu management with proper grouping and ordering.
 * 
 * @package TMU\Admin\Navigation
 * @since 1.0.0
 */

namespace TMU\Admin\Navigation;

/**
 * MenuManager class
 * 
 * Handles WordPress admin menu customization and enhancement
 */
class MenuManager {
    
    /**
     * TMU menu position
     * @var int
     */
    private const TMU_MENU_POSITION = 25;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->initHooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void {
        add_action('admin_menu', [$this, 'customizeMenus'], 20);
        add_action('admin_menu', [$this, 'reorderMenuItems'], 30);
        add_filter('custom_menu_order', '__return_true');
        add_filter('menu_order', [$this, 'customMenuOrder']);
    }
    
    /**
     * Customize admin menus
     */
    public function customizeMenus(): void {
        global $menu, $submenu;
        
        // Remove default menu items if they exist separately
        $this->removeDefaultMenuItems();
        
        // Create main TMU menu
        $this->createMainTMUMenu();
        
        // Organize TMU submenus
        $this->organizeTMUSubmenus();
        
        // Add separator after TMU content
        $this->addMenuSeparator();
    }
    
    /**
     * Remove default menu items that will be reorganized
     */
    private function removeDefaultMenuItems(): void {
        // Don't remove the individual post type menus - we'll reorganize them
        // This allows flexibility for users who prefer separate menus
    }
    
    /**
     * Create main TMU content menu
     */
    private function createMainTMUMenu(): void {
        add_menu_page(
            __('TMU Content Hub', 'tmu-theme'),
            __('TMU Content', 'tmu-theme'),
            'edit_posts',
            'tmu-content-hub',
            [$this, 'renderContentHub'],
            'dashicons-video-alt2',
            self::TMU_MENU_POSITION
        );
    }
    
    /**
     * Organize TMU submenus
     */
    private function organizeTMUSubmenus(): void {
        // Movies submenu
        add_submenu_page(
            'tmu-content-hub',
            __('Movies', 'tmu-theme'),
            __('Movies', 'tmu-theme'),
            'edit_posts',
            'edit.php?post_type=movie'
        );
        
        // TV Shows submenu
        add_submenu_page(
            'tmu-content-hub',
            __('TV Shows', 'tmu-theme'),
            __('TV Shows', 'tmu-theme'),
            'edit_posts',
            'edit.php?post_type=tv'
        );
        
        // Dramas submenu
        add_submenu_page(
            'tmu-content-hub',
            __('Dramas', 'tmu-theme'),
            __('Dramas', 'tmu-theme'),
            'edit_posts',
            'edit.php?post_type=drama'
        );
        
        // People submenu
        add_submenu_page(
            'tmu-content-hub',
            __('People', 'tmu-theme'),
            __('People', 'tmu-theme'),
            'edit_posts',
            'edit.php?post_type=people'
        );
        
        // Taxonomies submenu
        add_submenu_page(
            'tmu-content-hub',
            __('Categories & Tags', 'tmu-theme'),
            __('Categories & Tags', 'tmu-theme'),
            'manage_categories',
            'tmu-taxonomies',
            [$this, 'renderTaxonomiesPage']
        );
        
        // Tools & Actions submenu
        add_submenu_page(
            'tmu-content-hub',
            __('Tools & Actions', 'tmu-theme'),
            __('Tools & Actions', 'tmu-theme'),
            'manage_options',
            'tmu-tools',
            [$this, 'renderToolsPage']
        );
        
        // Settings submenu
        add_submenu_page(
            'tmu-content-hub',
            __('TMU Settings', 'tmu-theme'),
            __('Settings', 'tmu-theme'),
            'manage_options',
            'tmu-settings',
            [$this, 'renderSettingsPage']
        );
    }
    
    /**
     * Add menu separator
     */
    private function addMenuSeparator(): void {
        global $menu;
        $menu[self::TMU_MENU_POSITION + 5] = [
            '',
            'read',
            'separator-tmu',
            '',
            'wp-menu-separator'
        ];
    }
    
    /**
     * Reorder menu items
     */
    public function reorderMenuItems(): void {
        global $menu;
        
        // Move TMU-related items to be grouped together
        $tmu_items = [];
        
        foreach ($menu as $key => $item) {
            if (isset($item[2]) && strpos($item[2], 'post_type=') !== false) {
                $post_type = str_replace(['edit.php?post_type=', 'post-new.php?post_type='], '', $item[2]);
                if (in_array($post_type, ['movie', 'tv', 'drama', 'people'])) {
                    // Don't show individual menus if we have the hub
                    if (current_user_can('manage_options')) {
                        unset($menu[$key]);
                    }
                }
            }
        }
    }
    
    /**
     * Custom menu order
     * 
     * @param array $menu_order Current menu order
     * @return array Modified menu order
     */
    public function customMenuOrder(array $menu_order): array {
        // Define desired order
        $tmu_order = [
            'index.php', // Dashboard
            'separator1',
            'tmu-content-hub', // TMU Content Hub
            'separator-tmu',
            'edit.php', // Posts
            'upload.php', // Media
            'edit.php?post_type=page', // Pages
            'edit-comments.php', // Comments
            'separator2',
        ];
        
        // Merge with remaining items
        $remaining = array_diff($menu_order, $tmu_order);
        return array_merge($tmu_order, $remaining);
    }
    
    /**
     * Render content hub page
     */
    public function renderContentHub(): void {
        $stats = $this->getContentStats();
        ?>
        <div class="wrap tmu-content-hub">
            <h1><?php _e('TMU Content Hub', 'tmu-theme'); ?></h1>
            <p class="description">
                <?php _e('Manage all your movies, TV shows, dramas, and people from this central hub.', 'tmu-theme'); ?>
            </p>
            
            <div class="tmu-hub-stats">
                <h2><?php _e('Content Overview', 'tmu-theme'); ?></h2>
                <div class="tmu-stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">🎬</div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['movies']); ?></h3>
                            <p><?php _e('Movies', 'tmu-theme'); ?></p>
                            <a href="<?php echo admin_url('edit.php?post_type=movie'); ?>" class="button">
                                <?php _e('Manage Movies', 'tmu-theme'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📺</div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['tv_shows']); ?></h3>
                            <p><?php _e('TV Shows', 'tmu-theme'); ?></p>
                            <a href="<?php echo admin_url('edit.php?post_type=tv'); ?>" class="button">
                                <?php _e('Manage TV Shows', 'tmu-theme'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🎭</div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['dramas']); ?></h3>
                            <p><?php _e('Dramas', 'tmu-theme'); ?></p>
                            <a href="<?php echo admin_url('edit.php?post_type=drama'); ?>" class="button">
                                <?php _e('Manage Dramas', 'tmu-theme'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['people']); ?></h3>
                            <p><?php _e('People', 'tmu-theme'); ?></p>
                            <a href="<?php echo admin_url('edit.php?post_type=people'); ?>" class="button">
                                <?php _e('Manage People', 'tmu-theme'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="tmu-hub-actions">
                <h2><?php _e('Quick Actions', 'tmu-theme'); ?></h2>
                <div class="tmu-actions-grid">
                    <div class="action-card">
                        <h3><?php _e('TMDB Sync', 'tmu-theme'); ?></h3>
                        <p><?php _e('Synchronize content with TMDB database.', 'tmu-theme'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=tmu-tools&action=tmdb-sync'); ?>" class="button button-primary">
                            <?php _e('Start Sync', 'tmu-theme'); ?>
                        </a>
                    </div>
                    
                    <div class="action-card">
                        <h3><?php _e('Import Content', 'tmu-theme'); ?></h3>
                        <p><?php _e('Import new content from various sources.', 'tmu-theme'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=tmu-tools&action=import'); ?>" class="button">
                            <?php _e('Import Tools', 'tmu-theme'); ?>
                        </a>
                    </div>
                    
                    <div class="action-card">
                        <h3><?php _e('Manage Categories', 'tmu-theme'); ?></h3>
                        <p><?php _e('Organize genres, countries, and other taxonomies.', 'tmu-theme'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=tmu-taxonomies'); ?>" class="button">
                            <?php _e('Manage Categories', 'tmu-theme'); ?>
                        </a>
                    </div>
                    
                    <div class="action-card">
                        <h3><?php _e('TMU Settings', 'tmu-theme'); ?></h3>
                        <p><?php _e('Configure theme settings and API keys.', 'tmu-theme'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=tmu-settings'); ?>" class="button">
                            <?php _e('Open Settings', 'tmu-theme'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .tmu-content-hub .tmu-stats-grid,
        .tmu-content-hub .tmu-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .tmu-content-hub .stat-card,
        .tmu-content-hub .action-card {
            background: white;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
        }
        
        .tmu-content-hub .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .tmu-content-hub .stat-content h3 {
            font-size: 28px;
            color: #2271b1;
            margin: 10px 0 5px 0;
        }
        
        .tmu-content-hub .stat-content p {
            margin: 0 0 15px 0;
            color: #646970;
        }
        
        .tmu-content-hub .action-card h3 {
            margin-top: 0;
            color: #1d2327;
        }
        
        .tmu-content-hub .action-card p {
            color: #646970;
            margin-bottom: 15px;
        }
        </style>
        <?php
    }
    
    /**
     * Render taxonomies page
     */
    public function renderTaxonomiesPage(): void {
        ?>
        <div class="wrap tmu-taxonomies">
            <h1><?php _e('TMU Categories & Tags', 'tmu-theme'); ?></h1>
            
            <div class="tmu-taxonomy-links">
                <div class="taxonomy-section">
                    <h2><?php _e('Content Categories', 'tmu-theme'); ?></h2>
                    <ul>
                        <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=genre'); ?>"><?php _e('Genres', 'tmu-theme'); ?></a></li>
                        <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=country'); ?>"><?php _e('Countries', 'tmu-theme'); ?></a></li>
                        <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=language'); ?>"><?php _e('Languages', 'tmu-theme'); ?></a></li>
                        <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=network'); ?>"><?php _e('Networks', 'tmu-theme'); ?></a></li>
                        <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=channel'); ?>"><?php _e('Channels', 'tmu-theme'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render tools page
     */
    public function renderToolsPage(): void {
        ?>
        <div class="wrap tmu-tools">
            <h1><?php _e('TMU Tools & Actions', 'tmu-theme'); ?></h1>
            <p><?php _e('Tools and utilities for managing your TMU content.', 'tmu-theme'); ?></p>
            
            <!-- Tools content will be managed by other components -->
            <div id="tmu-tools-content">
                <?php do_action('tmu_render_tools_page'); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function renderSettingsPage(): void {
        ?>
        <div class="wrap tmu-settings">
            <h1><?php _e('TMU Settings', 'tmu-theme'); ?></h1>
            
            <!-- Settings content will be managed by Settings component -->
            <div id="tmu-settings-content">
                <?php do_action('tmu_render_settings_page'); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get content statistics
     * 
     * @return array Content stats
     */
    private function getContentStats(): array {
        return [
            'movies' => wp_count_posts('movie')->publish ?? 0,
            'tv_shows' => wp_count_posts('tv')->publish ?? 0,
            'dramas' => wp_count_posts('drama')->publish ?? 0,
            'people' => wp_count_posts('people')->publish ?? 0,
        ];
    }
}