<?php
/**
 * Sub Menus
 * 
 * Handles submenu organization and customization for TMU admin interface.
 * Provides contextual submenu management based on current admin page.
 * 
 * @package TMU\Admin\Navigation
 * @since 1.0.0
 */

namespace TMU\Admin\Navigation;

/**
 * SubMenus class
 * 
 * Manages submenu organization and contextual menu display
 */
class SubMenus {
    
    /**
     * Current page context
     * @var string
     */
    private $current_context = '';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->initHooks();
        $this->setCurrentContext();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void {
        add_action('admin_menu', [$this, 'organizeSubMenus'], 40);
        add_action('admin_head', [$this, 'addSubMenuStyles']);
        add_filter('submenu_file', [$this, 'highlightActiveSubmenu'], 10, 2);
        add_action('admin_bar_menu', [$this, 'addContextualAdminBarItems'], 200);
    }
    
    /**
     * Set current admin context
     */
    private function setCurrentContext(): void {
        global $pagenow, $post_type;
        
        if (isset($_GET['page']) && strpos($_GET['page'], 'tmu-') === 0) {
            $this->current_context = $_GET['page'];
        } elseif (in_array($post_type, ['movie', 'tv', 'drama', 'people'])) {
            $this->current_context = $post_type;
        } elseif ($pagenow === 'edit-tags.php' && isset($_GET['taxonomy'])) {
            $this->current_context = 'taxonomy_' . $_GET['taxonomy'];
        } else {
            $this->current_context = 'general';
        }
    }
    
    /**
     * Organize submenus based on context
     */
    public function organizeSubMenus(): void {
        global $submenu;
        
        // Organize TMU content hub submenus
        $this->organizeTMUContentSubMenus();
        
        // Add contextual submenus to post types
        $this->addPostTypeSubMenus();
        
        // Add taxonomy-specific submenus
        $this->addTaxonomySubMenus();
    }
    
    /**
     * Organize TMU content hub submenus
     */
    private function organizeTMUContentSubMenus(): void {
        global $submenu;
        
        if (!isset($submenu['tmu-content-hub'])) {
            return;
        }
        
        // Reorder submenus for better organization
        $organized_submenus = [];
        
        // Content management (top priority)
        $content_items = [
            'edit.php?post_type=movie',
            'edit.php?post_type=tv', 
            'edit.php?post_type=drama',
            'edit.php?post_type=people'
        ];
        
        // Organization and settings
        $organization_items = [
            'tmu-taxonomies',
            'tmu-tools',
            'tmu-settings'
        ];
        
        // Reorganize based on priority
        foreach ($content_items as $item) {
            if ($existing = $this->findSubmenuItem($submenu['tmu-content-hub'], $item)) {
                $organized_submenus[] = $existing;
            }
        }
        
        // Add separator
        $organized_submenus[] = ['<hr class="tmu-submenu-separator">', '', '', ''];
        
        foreach ($organization_items as $item) {
            if ($existing = $this->findSubmenuItem($submenu['tmu-content-hub'], $item)) {
                $organized_submenus[] = $existing;
            }
        }
        
        $submenu['tmu-content-hub'] = $organized_submenus;
    }
    
    /**
     * Add contextual submenus to post types
     */
    private function addPostTypeSubMenus(): void {
        $post_types = ['movie', 'tv', 'drama', 'people'];
        
        foreach ($post_types as $post_type) {
            $this->addPostTypeContextualMenus($post_type);
        }
    }
    
    /**
     * Add contextual menus for specific post type
     * 
     * @param string $post_type Post type slug
     */
    private function addPostTypeContextualMenus(string $post_type): void {
        $parent_slug = "edit.php?post_type={$post_type}";
        
        // Quick actions for this post type
        add_submenu_page(
            $parent_slug,
            sprintf(__('%s Quick Actions', 'tmu'), ucfirst($post_type)),
            __('Quick Actions', 'tmu'),
            'edit_posts',
            "tmu-{$post_type}-actions",
            [$this, 'renderPostTypeActions']
        );
        
        // TMDB tools for this post type
        add_submenu_page(
            $parent_slug,
            sprintf(__('%s TMDB Tools', 'tmu'), ucfirst($post_type)),
            __('TMDB Tools', 'tmu'),
            'edit_posts',
            "tmu-{$post_type}-tmdb",
            [$this, 'renderPostTypeTMDBTools']
        );
        
        // Import tools for this post type
        add_submenu_page(
            $parent_slug,
            sprintf(__('Import %s', 'tmu'), ucfirst($post_type)),
            __('Import', 'tmu'),
            'edit_posts',
            "tmu-{$post_type}-import",
            [$this, 'renderPostTypeImport']
        );
    }
    
    /**
     * Add taxonomy-specific submenus
     */
    private function addTaxonomySubMenus(): void {
        $taxonomies = ['genre', 'country', 'language', 'network', 'channel'];
        
        foreach ($taxonomies as $taxonomy) {
            // Add contextual actions for each taxonomy
            add_submenu_page(
                "edit-tags.php?taxonomy={$taxonomy}",
                sprintf(__('%s Tools', 'tmu'), ucfirst($taxonomy)),
                __('Tools', 'tmu'),
                'manage_categories',
                "tmu-{$taxonomy}-tools",
                [$this, 'renderTaxonomyTools']
            );
        }
    }
    
    /**
     * Find submenu item by slug
     * 
     * @param array $submenu_items Submenu items
     * @param string $slug Item slug to find
     * @return array|null Found item or null
     */
    private function findSubmenuItem(array $submenu_items, string $slug): ?array {
        foreach ($submenu_items as $item) {
            if (isset($item[2]) && $item[2] === $slug) {
                return $item;
            }
        }
        return null;
    }
    
    /**
     * Highlight active submenu
     * 
     * @param string $submenu_file Current submenu file
     * @param string $parent_file Current parent file
     * @return string Modified submenu file
     */
    public function highlightActiveSubmenu(string $submenu_file, string $parent_file): string {
        global $post_type;
        
        // Highlight TMU content hub for TMU pages
        if (isset($_GET['page']) && strpos($_GET['page'], 'tmu-') === 0) {
            if ($parent_file === 'tmu-content-hub') {
                return $_GET['page'];
            }
        }
        
        // Highlight appropriate submenu for post types
        if (in_array($post_type, ['movie', 'tv', 'drama', 'people'])) {
            if (strpos($submenu_file, $post_type) !== false) {
                return $submenu_file;
            }
        }
        
        return $submenu_file;
    }
    
    /**
     * Add contextual admin bar items
     * 
     * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance
     */
    public function addContextualAdminBarItems($wp_admin_bar): void {
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        // Add contextual items based on current page
        switch ($this->current_context) {
            case 'movie':
            case 'tv':
            case 'drama':
            case 'people':
                $this->addPostTypeAdminBarItems($wp_admin_bar, $this->current_context);
                break;
                
            case str_starts_with($this->current_context, 'taxonomy_'):
                $taxonomy = str_replace('taxonomy_', '', $this->current_context);
                $this->addTaxonomyAdminBarItems($wp_admin_bar, $taxonomy);
                break;
        }
    }
    
    /**
     * Add post type specific admin bar items
     * 
     * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance
     * @param string $post_type Post type
     */
    private function addPostTypeAdminBarItems($wp_admin_bar, string $post_type): void {
        $wp_admin_bar->add_node([
            'id' => "tmu-{$post_type}-quick",
            'parent' => 'tmu-quick-menu',
            'title' => sprintf(__('Quick %s Actions', 'tmu'), ucfirst($post_type)),
            'href' => admin_url("admin.php?page=tmu-{$post_type}-actions"),
        ]);
        
        $wp_admin_bar->add_node([
            'id' => "tmu-{$post_type}-import",
            'parent' => 'tmu-quick-menu', 
            'title' => sprintf(__('Import %s', 'tmu'), ucfirst($post_type)),
            'href' => admin_url("admin.php?page=tmu-{$post_type}-import"),
        ]);
    }
    
    /**
     * Add taxonomy specific admin bar items
     * 
     * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance
     * @param string $taxonomy Taxonomy name
     */
    private function addTaxonomyAdminBarItems($wp_admin_bar, string $taxonomy): void {
        $wp_admin_bar->add_node([
            'id' => "tmu-{$taxonomy}-tools",
            'parent' => 'tmu-quick-menu',
            'title' => sprintf(__('%s Tools', 'tmu'), ucfirst($taxonomy)),
            'href' => admin_url("admin.php?page=tmu-{$taxonomy}-tools"),
        ]);
    }
    
    /**
     * Add submenu styles
     */
    public function addSubMenuStyles(): void {
        ?>
        <style>
        .tmu-submenu-separator {
            border: none;
            border-top: 1px solid #c3c4c7;
            margin: 5px 0;
            opacity: 0.5;
        }
        
        .wp-submenu .tmu-submenu-separator {
            margin: 10px 12px;
        }
        
        .wp-submenu a[href*="tmu-"] {
            position: relative;
        }
        
        .wp-submenu a[href*="tmu-"]:before {
            content: "🔧";
            margin-right: 5px;
            opacity: 0.6;
        }
        
        .wp-submenu a[href*="tmdb"]:before {
            content: "🎬";
        }
        
        .wp-submenu a[href*="import"]:before {
            content: "📥";
        }
        
        .wp-submenu a[href*="tools"]:before {
            content: "🛠️";
        }
        
        .wp-submenu a[href*="settings"]:before {
            content: "⚙️";
        }
        </style>
        <?php
    }
    
    /**
     * Render post type actions page
     */
    public function renderPostTypeActions(): void {
        $post_type = $this->extractPostTypeFromPage();
        ?>
        <div class="wrap tmu-post-type-actions">
            <h1><?php printf(__('%s Quick Actions', 'tmu'), ucfirst($post_type)); ?></h1>
            
            <div class="tmu-action-sections">
                <section class="bulk-actions">
                    <h2><?php _e('Bulk Actions', 'tmu'); ?></h2>
                    <p><?php printf(__('Perform bulk operations on %s content.', 'tmu'), $post_type); ?></p>
                    
                    <div class="action-buttons">
                        <button class="button" onclick="tmuBulkAction('sync_tmdb', '<?php echo $post_type; ?>')">
                            <?php _e('Bulk TMDB Sync', 'tmu'); ?>
                        </button>
                        <button class="button" onclick="tmuBulkAction('update_metadata', '<?php echo $post_type; ?>')">
                            <?php _e('Update Metadata', 'tmu'); ?>
                        </button>
                        <button class="button" onclick="tmuBulkAction('regenerate_thumbnails', '<?php echo $post_type; ?>')">
                            <?php _e('Regenerate Images', 'tmu'); ?>
                        </button>
                    </div>
                </section>
                
                <section class="quick-create">
                    <h2><?php _e('Quick Create', 'tmu'); ?></h2>
                    <p><?php printf(__('Quickly create new %s content.', 'tmu'), $post_type); ?></p>
                    
                    <a href="<?php echo admin_url("post-new.php?post_type={$post_type}"); ?>" class="button button-primary">
                        <?php printf(__('Add New %s', 'tmu'), ucfirst($post_type)); ?>
                    </a>
                </section>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render post type TMDB tools page
     */
    public function renderPostTypeTMDBTools(): void {
        $post_type = $this->extractPostTypeFromPage();
        ?>
        <div class="wrap tmu-tmdb-tools">
            <h1><?php printf(__('%s TMDB Tools', 'tmu'), ucfirst($post_type)); ?></h1>
            
            <div class="tmdb-tool-sections">
                <section class="sync-section">
                    <h2><?php _e('TMDB Synchronization', 'tmu'); ?></h2>
                    <p><?php printf(__('Sync %s data with TMDB database.', 'tmu'), $post_type); ?></p>
                    
                    <!-- TMDB sync tools will be rendered here -->
                    <?php do_action('tmu_render_tmdb_tools', $post_type); ?>
                </section>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render post type import page
     */
    public function renderPostTypeImport(): void {
        $post_type = $this->extractPostTypeFromPage();
        ?>
        <div class="wrap tmu-import-tools">
            <h1><?php printf(__('Import %s', 'tmu'), ucfirst($post_type)); ?></h1>
            
            <div class="import-sections">
                <section class="tmdb-import">
                    <h2><?php _e('TMDB Import', 'tmu'); ?></h2>
                    <p><?php printf(__('Import %s from TMDB database.', 'tmu'), $post_type); ?></p>
                    
                    <!-- Import tools will be rendered here -->
                    <?php do_action('tmu_render_import_tools', $post_type); ?>
                </section>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render taxonomy tools page
     */
    public function renderTaxonomyTools(): void {
        $taxonomy = $this->extractTaxonomyFromPage();
        ?>
        <div class="wrap tmu-taxonomy-tools">
            <h1><?php printf(__('%s Tools', 'tmu'), ucfirst($taxonomy)); ?></h1>
            
            <div class="taxonomy-tool-sections">
                <section class="management-section">
                    <h2><?php _e('Taxonomy Management', 'tmu'); ?></h2>
                    <p><?php printf(__('Manage %s taxonomy terms and relationships.', 'tmu'), $taxonomy); ?></p>
                    
                    <!-- Taxonomy tools will be rendered here -->
                    <?php do_action('tmu_render_taxonomy_tools', $taxonomy); ?>
                </section>
            </div>
        </div>
        <?php
    }
    
    /**
     * Extract post type from current page
     * 
     * @return string Post type
     */
    private function extractPostTypeFromPage(): string {
        if (isset($_GET['page']) && preg_match('/tmu-(.+)-/', $_GET['page'], $matches)) {
            return $matches[1];
        }
        return 'movie'; // Default fallback
    }
    
    /**
     * Extract taxonomy from current page
     * 
     * @return string Taxonomy name
     */
    private function extractTaxonomyFromPage(): string {
        if (isset($_GET['page']) && preg_match('/tmu-(.+)-tools/', $_GET['page'], $matches)) {
            return $matches[1];
        }
        return 'genre'; // Default fallback
    }
}