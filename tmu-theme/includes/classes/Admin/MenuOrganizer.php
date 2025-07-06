<?php
/**
 * Admin Menu Organizer
 *
 * @package TMU\Admin
 * @version 1.0.0
 */

namespace TMU\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Menu Organizer Class
 */
class MenuOrganizer {
    
    /**
     * Organizer instance
     *
     * @var MenuOrganizer
     */
    private static $instance = null;
    
    /**
     * Get organizer instance
     *
     * @return MenuOrganizer
     */
    public static function getInstance(): MenuOrganizer {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize menu organization
     */
    public function init(): void {
        add_action('admin_menu', [$this, 'organizePostTypeMenus'], 25);
        add_action('admin_menu', [$this, 'addCustomMenuItems'], 30);
        add_filter('submenu_file', [$this, 'highlightActiveMenu'], 10, 2);
    }
    
    /**
     * Organize post type menus
     */
    public function organizePostTypeMenus(): void {
        global $submenu;
        
        // Organize TV Show related menus
        $this->organizeTVShowMenus();
        
        // Organize Drama related menus
        $this->organizeDramaMenus();
        
        // Remove redundant menu items
        $this->removeRedundantMenuItems();
        
        // Reorder menu items
        $this->reorderMenuItems();
    }
    
    /**
     * Organize TV Show menus
     */
    private function organizeTVShowMenus(): void {
        if (!tmu_get_option('tmu_tv_series', 'off') === 'on') {
            return;
        }
        
        // Add season management under TV Shows
        if (post_type_exists('season')) {
            add_submenu_page(
                'edit.php?post_type=tv',
                __('All Seasons', 'tmu'),
                __('All Seasons', 'tmu'),
                'edit_posts',
                'edit.php?post_type=season'
            );
            
            add_submenu_page(
                'edit.php?post_type=tv',
                __('Add New Season', 'tmu'),
                __('Add New Season', 'tmu'),
                'edit_posts',
                'post-new.php?post_type=season'
            );
        }
        
        // Add episode management under TV Shows
        if (post_type_exists('episode')) {
            add_submenu_page(
                'edit.php?post_type=tv',
                __('All Episodes', 'tmu'),
                __('All Episodes', 'tmu'),
                'edit_posts',
                'edit.php?post_type=episode'
            );
            
            add_submenu_page(
                'edit.php?post_type=tv',
                __('Add New Episode', 'tmu'),
                __('Add New Episode', 'tmu'),
                'edit_posts',
                'post-new.php?post_type=episode'
            );
        }
    }
    
    /**
     * Organize Drama menus
     */
    private function organizeDramaMenus(): void {
        if (!tmu_get_option('tmu_dramas', 'off') === 'on') {
            return;
        }
        
        // Add drama episode management under Dramas
        if (post_type_exists('drama-episode')) {
            add_submenu_page(
                'edit.php?post_type=drama',
                __('All Episodes', 'tmu'),
                __('All Episodes', 'tmu'),
                'edit_posts',
                'edit.php?post_type=drama-episode'
            );
            
            add_submenu_page(
                'edit.php?post_type=drama',
                __('Add New Episode', 'tmu'),
                __('Add New Episode', 'tmu'),
                'edit_posts',
                'post-new.php?post_type=drama-episode'
            );
        }
    }
    
    /**
     * Add custom menu items
     */
    public function addCustomMenuItems(): void {
        // Add TMU Dashboard submenu to main TMU menu
        add_submenu_page(
            'tmu-settings',
            __('TMU Dashboard', 'tmu'),
            __('Dashboard', 'tmu'),
            'manage_options',
            'tmu-dashboard',
            [$this, 'renderDashboard']
        );
        
        // Add Post Types Management submenu
        add_submenu_page(
            'tmu-settings',
            __('Post Types', 'tmu'),
            __('Post Types', 'tmu'),
            'manage_options',
            'tmu-post-types',
            [$this, 'renderPostTypesPage']
        );
    }
    
    /**
     * Remove redundant menu items
     */
    private function removeRedundantMenuItems(): void {
        global $menu, $submenu;
        
        // Remove individual post type menus for nested types
        if (isset($submenu['edit.php?post_type=season'])) {
            unset($submenu['edit.php?post_type=season']);
        }
        
        if (isset($submenu['edit.php?post_type=episode'])) {
            unset($submenu['edit.php?post_type=episode']);
        }
        
        if (isset($submenu['edit.php?post_type=drama-episode'])) {
            unset($submenu['edit.php?post_type=drama-episode']);
        }
        
        // Remove from main menu
        $this->removeMainMenuItem('edit.php?post_type=season');
        $this->removeMainMenuItem('edit.php?post_type=episode');
        $this->removeMainMenuItem('edit.php?post_type=drama-episode');
    }
    
    /**
     * Remove main menu item
     *
     * @param string $menu_slug Menu slug to remove
     */
    private function removeMainMenuItem(string $menu_slug): void {
        global $menu;
        
        foreach ($menu as $key => $item) {
            if (isset($item[2]) && $item[2] === $menu_slug) {
                unset($menu[$key]);
                break;
            }
        }
    }
    
    /**
     * Reorder menu items
     */
    private function reorderMenuItems(): void {
        global $menu;
        
        // Define custom order for TMU post types
        $tmu_order = [
            'edit.php?post_type=movie' => 25,
            'edit.php?post_type=tv' => 26,
            'edit.php?post_type=drama' => 27,
            'edit.php?post_type=people' => 28,
            'edit.php?post_type=video' => 29,
        ];
        
        foreach ($menu as $key => $item) {
            if (isset($item[2]) && array_key_exists($item[2], $tmu_order)) {
                $menu[$key][2] = $item[2]; // Keep original slug
                $menu[$key]['position'] = $tmu_order[$item[2]];
            }
        }
    }
    
    /**
     * Highlight active menu
     *
     * @param string $submenu_file Current submenu file
     * @param string $parent_file Parent menu file
     * @return string
     */
    public function highlightActiveMenu(string $submenu_file, string $parent_file): string {
        global $current_screen;
        
        if (!$current_screen) {
            return $submenu_file;
        }
        
        // Highlight parent menu for nested post types
        switch ($current_screen->post_type) {
            case 'season':
            case 'episode':
                if (strpos($parent_file, 'edit.php?post_type=tv') !== false) {
                    $submenu_file = 'edit.php?post_type=' . $current_screen->post_type;
                }
                break;
                
            case 'drama-episode':
                if (strpos($parent_file, 'edit.php?post_type=drama') !== false) {
                    $submenu_file = 'edit.php?post_type=drama-episode';
                }
                break;
        }
        
        return $submenu_file;
    }
    
    /**
     * Render TMU Dashboard
     */
    public function renderDashboard(): void {
        $post_type_manager = \TMU\PostTypes\PostTypeManager::getInstance();
        $stats = $post_type_manager->getStatistics();
        
        echo '<div class="wrap">';
        echo '<h1>' . __('TMU Dashboard', 'tmu') . '</h1>';
        
        echo '<div class="tmu-dashboard-widgets">';
        
        // Post Types Overview
        echo '<div class="tmu-widget tmu-post-types-overview">';
        echo '<h2>' . __('Post Types Overview', 'tmu') . '</h2>';
        echo '<div class="tmu-stats-grid">';
        
        foreach ($stats['post_counts'] as $post_type => $count) {
            $post_type_object = get_post_type_object($post_type);
            $name = $post_type_object ? $post_type_object->labels->name : ucfirst($post_type);
            
            echo '<div class="tmu-stat-item">';
            echo '<div class="tmu-stat-number">' . number_format($count) . '</div>';
            echo '<div class="tmu-stat-label">' . esc_html($name) . '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        
        // Quick Actions
        echo '<div class="tmu-widget tmu-quick-actions">';
        echo '<h2>' . __('Quick Actions', 'tmu') . '</h2>';
        echo '<ul>';
        
        foreach ($post_type_manager->getRegisteredPostTypes() as $post_type) {
            $post_type_object = get_post_type_object($post_type);
            if ($post_type_object && $post_type_object->show_ui) {
                echo '<li>';
                echo '<a href="' . admin_url("post-new.php?post_type={$post_type}") . '">';
                echo sprintf(__('Add New %s', 'tmu'), $post_type_object->labels->singular_name);
                echo '</a>';
                echo '</li>';
            }
        }
        
        echo '</ul>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render Post Types Management Page
     */
    public function renderPostTypesPage(): void {
        $post_type_manager = \TMU\PostTypes\PostTypeManager::getInstance();
        
        echo '<div class="wrap">';
        echo '<h1>' . __('Post Types Management', 'tmu') . '</h1>';
        
        if (isset($_POST['action']) && $_POST['action'] === 'toggle_post_type') {
            $this->handlePostTypeToggle();
        }
        
        echo '<form method="post" action="">';
        wp_nonce_field('tmu_post_types_action');
        echo '<input type="hidden" name="action" value="toggle_post_type">';
        
        echo '<table class="form-table">';
        
        $post_type_options = [
            'movies' => __('Movies', 'tmu'),
            'tv_series' => __('TV Series', 'tmu'),
            'dramas' => __('Dramas', 'tmu'),
            'videos' => __('Videos', 'tmu'),
        ];
        
        foreach ($post_type_options as $option => $label) {
            $enabled = tmu_get_option("tmu_{$option}", 'off') === 'on';
            
            echo '<tr>';
            echo '<th scope="row">' . esc_html($label) . '</th>';
            echo '<td>';
            echo '<label>';
            echo '<input type="checkbox" name="' . esc_attr($option) . '" value="on"' . checked($enabled, true, false) . '>';
            echo ' ' . sprintf(__('Enable %s post type', 'tmu'), $label);
            echo '</label>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        
        echo '<p class="submit">';
        echo '<input type="submit" name="submit" class="button-primary" value="' . __('Save Changes', 'tmu') . '">';
        echo '</p>';
        
        echo '</form>';
        echo '</div>';
    }
    
    /**
     * Handle post type toggle
     */
    private function handlePostTypeToggle(): void {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'tmu_post_types_action')) {
            wp_die(__('Security check failed.', 'tmu'));
        }
        
        $post_type_options = ['movies', 'tv_series', 'dramas', 'videos'];
        
        foreach ($post_type_options as $option) {
            $value = isset($_POST[$option]) ? 'on' : 'off';
            tmu_update_option("tmu_{$option}", $value);
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>' . __('Post type settings saved successfully!', 'tmu') . '</p>';
        echo '</div>';
    }
}