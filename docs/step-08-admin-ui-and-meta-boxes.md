# Step 08: Admin UI and Meta Boxes

## Purpose
Implement a comprehensive admin interface that enhances WordPress admin experience for TMU content management, including custom admin columns, bulk actions, enhanced meta boxes, and specialized admin pages for managing movie/TV data.

## Dependencies from Previous Steps
- **[REQUIRED]** Post types registration [FROM STEP 5] - Admin columns attach to post types
- **[REQUIRED]** Taxonomies registration [FROM STEP 6] - Taxonomy management interface
- **[REQUIRED]** Gutenberg blocks [FROM STEP 7] - Block-based meta boxes
- **[REQUIRED]** PSR-4 autoloading [FROM STEP 4] - Admin class loading
- **[REQUIRED]** Asset compilation [FROM STEP 1] - Admin CSS/JS files
- **[REQUIRED]** Helper functions [FROM STEP 4] - Admin utilities

## Files Created in This Step
- **[CREATE NEW]** `includes/classes/Admin/AdminManager.php` - Main admin coordinator
- **[CREATE NEW]** `includes/classes/Admin/Columns/MovieColumns.php` - Movie list enhancements
- **[CREATE NEW]** `includes/classes/Admin/Columns/TVColumns.php` - TV show list enhancements
- **[CREATE NEW]** `includes/classes/Admin/Columns/DramaColumns.php` - Drama list enhancements
- **[CREATE NEW]** `includes/classes/Admin/Columns/PeopleColumns.php` - People list enhancements
- **[CREATE NEW]** `includes/classes/Admin/Actions/TMDBSync.php` - TMDB synchronization
- **[CREATE NEW]** `includes/classes/Admin/Actions/BulkEdit.php` - Bulk editing actions
- **[CREATE NEW]** `includes/classes/Admin/Actions/DataImport.php` - Data import tools
- **[CREATE NEW]** `includes/classes/Admin/Dashboard/Widgets.php` - Custom widgets
- **[CREATE NEW]** `includes/classes/Admin/Dashboard/QuickStats.php` - Statistics display
- **[CREATE NEW]** `includes/classes/Admin/MetaBoxes/TMDBBox.php` - TMDB data integration
- **[CREATE NEW]** `includes/classes/Admin/MetaBoxes/RelationshipBox.php` - Content relationships
- **[CREATE NEW]** `includes/classes/Admin/MetaBoxes/QuickActions.php` - Quick action buttons
- **[CREATE NEW]** `includes/classes/Admin/Navigation/MenuManager.php` - Menu customization
- **[CREATE NEW]** `includes/classes/Admin/Navigation/SubMenus.php` - Submenu organization
- **[CREATE NEW]** `assets/src/scss/admin.scss` - Admin interface styling
- **[CREATE NEW]** `assets/src/js/admin.js` - Admin interface JavaScript
- **[CREATE NEW]** `tests/Admin/AdminTest.php` - Admin interface testing

## Tailwind CSS Status
**USES** - Admin interface uses Tailwind utility classes for consistent styling

## Overview
This step creates:
1. Enhanced admin columns for all post types
2. Custom bulk actions for TMDB data operations
3. Improved meta box layouts and functionality
4. Admin dashboard widgets
5. Quick edit enhancements
6. Admin navigation improvements

## Analysis from Plugin Admin Features

### Current Admin Capabilities
- TMDB API integration for bulk updates
- Custom admin columns showing key data
- SEO options management
- Settings pages for feature toggles
- Meta box integration with conditional fields

### Enhancement Requirements
- Streamlined content creation workflow
- Bulk TMDB data import/update
- Enhanced search and filtering
- Custom admin dashboard
- Improved user experience

## Architecture Implementation

### Directory Structure with File Status
```
includes/classes/Admin/                           # [UPDATE DIR - STEP 8] Extend existing directory from Step 1
├── AdminManager.php          # [CREATE NEW - STEP 8] Main admin coordinator - Central admin management
├── Columns/                  # [CREATE DIR - STEP 8] Custom admin columns directory
│   ├── MovieColumns.php      # [CREATE NEW - STEP 8] Movie list enhancements - Movie admin columns
│   ├── TVColumns.php         # [CREATE NEW - STEP 8] TV show list enhancements - TV admin columns
│   ├── DramaColumns.php      # [CREATE NEW - STEP 8] Drama list enhancements - Drama admin columns
│   └── PeopleColumns.php     # [CREATE NEW - STEP 8] People list enhancements - People admin columns
├── Actions/                  # [CREATE DIR - STEP 8] Bulk actions directory
│   ├── TMDBSync.php          # [CREATE NEW - STEP 8] TMDB synchronization - Bulk TMDB operations
│   ├── BulkEdit.php          # [CREATE NEW - STEP 8] Bulk editing actions - Mass content editing
│   └── DataImport.php        # [CREATE NEW - STEP 8] Data import tools - Content import utilities
├── Dashboard/                # [CREATE DIR - STEP 8] Dashboard customizations directory
│   ├── Widgets.php           # [CREATE NEW - STEP 8] Custom widgets - Dashboard widget management
│   └── QuickStats.php        # [CREATE NEW - STEP 8] Statistics display - Content statistics
├── MetaBoxes/                # [CREATE DIR - STEP 8] Enhanced meta boxes directory
│   ├── TMDBBox.php           # [CREATE NEW - STEP 8] TMDB data integration - TMDB meta box
│   ├── RelationshipBox.php   # [CREATE NEW - STEP 8] Content relationships - Relationship management
│   └── QuickActions.php      # [CREATE NEW - STEP 8] Quick action buttons - Action shortcuts
└── Navigation/               # [CREATE DIR - STEP 8] Admin menu enhancements directory
    ├── MenuManager.php       # [CREATE NEW - STEP 8] Menu customization - Admin menu management
    └── SubMenus.php          # [CREATE NEW - STEP 8] Submenu organization - Submenu structure

assets/src/scss/              # [UPDATE DIR - STEP 8] Extend existing directory from Step 1
└── admin.scss               # [CREATE NEW - STEP 8] Admin interface styling - Tailwind-based admin styles

assets/src/js/                # [UPDATE DIR - STEP 8] Extend existing directory from Step 1
└── admin.js                 # [CREATE NEW - STEP 8] Admin interface JavaScript - Admin interactions

tests/Admin/                  # [CREATE DIR - STEP 8] Admin interface tests
└── AdminTest.php            # [CREATE NEW - STEP 8] Admin interface testing - Admin functionality tests
```

### **Dependencies from Previous Steps:**
- **[REQUIRED]** Post types [FROM STEP 5] - movie, tv, drama, people
- **[REQUIRED]** Taxonomies [FROM STEP 6] - genre, country, language, network, channel
- **[REQUIRED]** Gutenberg blocks [FROM STEP 7] - Block-based meta boxes integration
- **[REQUIRED]** Asset compilation [FROM STEP 1] - Admin CSS/JS compilation
- **[REQUIRED]** Helper functions [FROM STEP 4] - Admin utility functions

### **Files Created in Future Steps:**
- **`includes/classes/API/AdminEndpoints.php`** - [CREATE NEW - STEP 9] Admin API endpoints
- **`includes/classes/Settings/AdminSettings.php`** - [CREATE NEW - STEP 8] Admin configuration
- **`templates/admin/`** - [CREATE NEW - STEP 10] Admin template files

## Core Implementation

### 1. Admin Manager (`includes/classes/Admin/AdminManager.php`)
**File Status**: [CREATE NEW - STEP 8]
**File Path**: `tmu-theme/includes/classes/Admin/AdminManager.php`
**Purpose**: Main admin coordinator that manages all TMU admin interface enhancements
**Dependencies**: 
- [DEPENDS ON] `TMU\Config\ThemeConfig` [FROM STEP 7] - Feature enablement checks
- [DEPENDS ON] Individual admin component classes [CREATE NEW - STEP 8] - Columns, Actions, Dashboard, etc.
- [DEPENDS ON] Asset compilation [FROM STEP 1] - Admin CSS/JS files
- [DEPENDS ON] WordPress admin hooks - admin_init, admin_menu, admin_enqueue_scripts
**Integration**: Central management of all TMU admin interface components
**Used By**: 
- `includes/classes/ThemeCore.php` [FROM STEP 1] - Theme initialization
- WordPress admin interface - Enhanced admin experience
**Features**: 
- Component loading and coordination
- Admin menu customization
- Asset enqueueing for admin pages
- Quick actions page
- Admin bar customization
**AI Action**: Create admin manager class that coordinates all admin interface enhancements

```php
<?php
namespace TMU\Admin;

use TMU\Config\ThemeConfig;

class AdminManager {
    private static $instance = null;
    private $config;
    
    public static function getInstance(): AdminManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->config = ThemeConfig::getInstance();
        $this->initHooks();
        $this->loadComponents();
    }
    
    private function initHooks(): void {
        add_action('admin_init', [$this, 'initAdmin']);
        add_action('admin_menu', [$this, 'customizeAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('admin_bar_menu', [$this, 'customizeAdminBar'], 100);
        add_filter('admin_footer_text', [$this, 'customAdminFooter']);
    }
    
    private function loadComponents(): void {
        // Load admin column managers
        new Columns\MovieColumns();
        new Columns\TVColumns();
        new Columns\DramaColumns();
        new Columns\PeopleColumns();
        
        // Load action handlers
        new Actions\TMDBSync();
        new Actions\BulkEdit();
        
        // Load dashboard components
        new Dashboard\Widgets();
        
        // Load enhanced meta boxes
        new MetaBoxes\TMDBBox();
        new MetaBoxes\RelationshipBox();
        
        // Load navigation enhancements
        new Navigation\MenuManager();
    }
    
    public function customizeAdminMenu(): void {
        // Reorder menu items for TMU content
        global $menu;
        
        // Group TMU content together
        $tmu_position = 25;
        
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
        
        // Add quick links submenu
        add_submenu_page(
            'edit.php?post_type=movie',
            __('Quick Actions', 'tmu'),
            __('Quick Actions', 'tmu'),
            'manage_options',
            'tmu-quick-actions',
            [$this, 'renderQuickActionsPage']
        );
    }
    
    public function enqueueAdminAssets($hook_suffix): void {
        global $post_type;
        
        // Load on TMU post type pages
        if (in_array($post_type, ['movie', 'tv', 'drama', 'people'])) {
            wp_enqueue_style(
                'tmu-admin',
                TMU_ASSETS_URL . '/css/admin.css',
                [],
                TMU_VERSION
            );
            
            wp_enqueue_script(
                'tmu-admin',
                TMU_ASSETS_URL . '/js/admin.js',
                ['jquery', 'wp-util'],
                TMU_VERSION,
                true
            );
            
            wp_localize_script('tmu-admin', 'tmuAdmin', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('tmu_admin_nonce'),
                'strings' => [
                    'confirm_sync' => __('Sync with TMDB? This will overwrite existing data.', 'tmu'),
                    'syncing' => __('Syncing...', 'tmu'),
                    'sync_complete' => __('Sync completed successfully!', 'tmu'),
                    'sync_error' => __('Sync failed. Please try again.', 'tmu'),
                ],
            ]);
        }
    }
    
    public function renderQuickActionsPage(): void {
        ?>
        <div class="wrap tmu-quick-actions">
            <h1><?php _e('TMU Quick Actions', 'tmu'); ?></h1>
            
            <div class="tmu-action-cards">
                <div class="tmu-action-card">
                    <h3><?php _e('TMDB Sync', 'tmu'); ?></h3>
                    <p><?php _e('Sync all content with TMDB for latest information.', 'tmu'); ?></p>
                    <button id="bulk-tmdb-sync" class="button button-primary"><?php _e('Start Sync', 'tmu'); ?></button>
                </div>
                
                <div class="tmu-action-card">
                    <h3><?php _e('Import Content', 'tmu'); ?></h3>
                    <p><?php _e('Import new movies/shows from TMDB.', 'tmu'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=tmu-import'); ?>" class="button"><?php _e('Import Tools', 'tmu'); ?></a>
                </div>
                
                <div class="tmu-action-card">
                    <h3><?php _e('Data Health', 'tmu'); ?></h3>
                    <p><?php _e('Check and repair data integrity issues.', 'tmu'); ?></p>
                    <button id="data-health-check" class="button"><?php _e('Health Check', 'tmu'); ?></button>
                </div>
            </div>
            
            <div id="action-progress" class="tmu-progress" style="display:none;">
                <div class="progress-bar"><div class="progress-fill"></div></div>
                <div class="progress-text"></div>
            </div>
        </div>
        <?php
    }
}
```

### 2. Movie Admin Columns (`includes/classes/Admin/Columns/MovieColumns.php`)
**File Status**: [CREATE NEW - STEP 8]
**File Path**: `tmu-theme/includes/classes/Admin/Columns/MovieColumns.php`
**Purpose**: Enhanced admin columns for movie post type management
**Dependencies**: 
- [DEPENDS ON] Movie post type [FROM STEP 5] - Content type integration
- [DEPENDS ON] Helper functions [FROM STEP 4] - tmu_get_meta function
- [DEPENDS ON] WordPress admin hooks - manage_{post_type}_posts_columns
- [DEPENDS ON] Custom movie data fields from database [FROM STEP 3]
**Integration**: Enhanced movie list interface in WordPress admin
**Used By**: 
- WordPress admin movie list page - Enhanced columns display
- Admin sorting and filtering - Sortable columns
- Movie management workflow - Quick data overview
**Features**: 
- Poster thumbnail display
- Release date formatting
- TMDB ID with external link
- Rating display with stars
- Runtime information
- Sortable columns by date and rating
**AI Action**: Create movie admin columns class with comprehensive movie data display

```php
<?php
namespace TMU\Admin\Columns;

class MovieColumns {
    public function __construct() {
        add_filter('manage_movie_posts_columns', [$this, 'addColumns']);
        add_action('manage_movie_posts_custom_column', [$this, 'renderColumns'], 10, 2);
        add_filter('manage_edit-movie_sortable_columns', [$this, 'addSortableColumns']);
        add_action('pre_get_posts', [$this, 'handleSorting']);
    }
    
    public function addColumns(array $columns): array {
        $new_columns = [];
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['poster'] = __('Poster', 'tmu');
                $new_columns['release_date'] = __('Release Date', 'tmu');
                $new_columns['tmdb_id'] = __('TMDB ID', 'tmu');
                $new_columns['rating'] = __('Rating', 'tmu');
                $new_columns['runtime'] = __('Runtime', 'tmu');
            }
        }
        
        return $new_columns;
    }
    
    public function renderColumns(string $column, int $post_id): void {
        switch ($column) {
            case 'poster':
                $this->renderPoster($post_id);
                break;
            case 'release_date':
                $this->renderReleaseDate($post_id);
                break;
            case 'tmdb_id':
                $this->renderTMDBId($post_id);
                break;
            case 'rating':
                $this->renderRating($post_id);
                break;
            case 'runtime':
                $this->renderRuntime($post_id);
                break;
        }
    }
    
    private function renderPoster(int $post_id): void {
        $poster_id = get_post_thumbnail_id($post_id);
        if ($poster_id) {
            $poster = wp_get_attachment_image($poster_id, [50, 75], false, [
                'class' => 'tmu-admin-poster'
            ]);
            echo '<div class="tmu-poster-column">' . $poster . '</div>';
        } else {
            echo '<div class="tmu-poster-placeholder">No poster</div>';
        }
    }
    
    private function renderReleaseDate(int $post_id): void {
        $date = tmu_get_meta($post_id, 'release_date');
        if ($date) {
            $formatted = date('M j, Y', strtotime($date));
            echo '<span class="tmu-release-date">' . esc_html($formatted) . '</span>';
        } else {
            echo '<span class="tmu-no-date">—</span>';
        }
    }
    
    private function renderTMDBId(int $post_id): void {
        $tmdb_id = tmu_get_meta($post_id, 'tmdb_id');
        if ($tmdb_id) {
            $url = 'https://www.themoviedb.org/movie/' . $tmdb_id;
            echo '<a href="' . esc_url($url) . '" target="_blank" class="tmu-tmdb-link">';
            echo esc_html($tmdb_id);
            echo '</a>';
        } else {
            echo '<span class="tmu-no-tmdb">No TMDB ID</span>';
        }
    }
    
    private function renderRating(int $post_id): void {
        $rating = tmu_get_meta($post_id, 'vote_average', 0);
        if ($rating > 0) {
            echo '<div class="tmu-rating-display">';
            echo '<span class="rating-value">' . number_format($rating, 1) . '</span>';
            echo '<span class="rating-stars">' . $this->getStarRating($rating) . '</span>';
            echo '</div>';
        } else {
            echo '<span class="tmu-no-rating">Not rated</span>';
        }
    }
    
    private function getStarRating(float $rating): string {
        $stars = '';
        $full_stars = floor($rating / 2);
        $half_star = ($rating / 2) - $full_stars >= 0.5;
        
        for ($i = 0; $i < $full_stars; $i++) {
            $stars .= '★';
        }
        
        if ($half_star) {
            $stars .= '☆';
        }
        
        return $stars;
    }
    
    public function addSortableColumns(array $columns): array {
        $columns['release_date'] = 'release_date';
        $columns['rating'] = 'rating';
        return $columns;
    }
    
    public function handleSorting(\WP_Query $query): void {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        if ($orderby === 'release_date') {
            $query->set('meta_key', 'release_date');
            $query->set('orderby', 'meta_value');
        } elseif ($orderby === 'rating') {
            $query->set('meta_key', 'vote_average');
            $query->set('orderby', 'meta_value_num');
        }
    }
}
```

### 3. TMDB Meta Box (`includes/classes/Admin/MetaBoxes/TMDBBox.php`)
**File Status**: [CREATE NEW - STEP 8]
**File Path**: `tmu-theme/includes/classes/Admin/MetaBoxes/TMDBBox.php`
**Purpose**: TMDB integration meta box for content synchronization and management
**Dependencies**: 
- [DEPENDS ON] TMDB API client [FROM STEP 9] - API integration
- [DEPENDS ON] Helper functions [FROM STEP 4] - tmu_get_meta function
- [DEPENDS ON] WordPress meta box hooks - add_meta_boxes
- [DEPENDS ON] WordPress AJAX system - wp_ajax actions
- [DEPENDS ON] Post types [FROM STEP 5] - movie, tv, drama, people
**Integration**: TMDB data synchronization interface within post editor
**Used By**: 
- Post editor interfaces - TMDB sync functionality
- TMDB API system [FROM STEP 9] - Data synchronization
- Content management workflow - Data updates
**Features**: 
- TMDB ID display with external link
- Last sync timestamp tracking
- Manual and automatic TMDB search
- One-click data synchronization
- Image update functionality
- Manual TMDB ID assignment
**AI Action**: Create TMDB meta box class with comprehensive API integration

```php
<?php
namespace TMU\Admin\MetaBoxes;

class TMDBBox {
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('wp_ajax_tmu_tmdb_sync', [$this, 'handleTMDBSync']);
        add_action('wp_ajax_tmu_tmdb_search', [$this, 'handleTMDBSearch']);
    }
    
    public function addMetaBox(): void {
        $post_types = ['movie', 'tv', 'drama', 'people'];
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'tmu-tmdb-actions',
                __('TMDB Integration', 'tmu'),
                [$this, 'renderMetaBox'],
                $post_type,
                'side',
                'high'
            );
        }
    }
    
    public function renderMetaBox(\WP_Post $post): void {
        $tmdb_id = tmu_get_meta($post->ID, 'tmdb_id');
        $last_sync = get_post_meta($post->ID, '_tmdb_last_sync', true);
        
        wp_nonce_field('tmdb_actions', 'tmdb_nonce');
        ?>
        <div class="tmu-tmdb-box">
            <?php if ($tmdb_id): ?>
                <div class="tmu-tmdb-status">
                    <strong><?php _e('TMDB ID:', 'tmu'); ?></strong> 
                    <a href="https://www.themoviedb.org/<?php echo esc_attr($post->post_type); ?>/<?php echo esc_attr($tmdb_id); ?>" target="_blank">
                        <?php echo esc_html($tmdb_id); ?>
                    </a>
                </div>
                
                <?php if ($last_sync): ?>
                    <div class="tmu-last-sync">
                        <strong><?php _e('Last Sync:', 'tmu'); ?></strong>
                        <?php echo esc_html(human_time_diff(strtotime($last_sync))); ?> ago
                    </div>
                <?php endif; ?>
                
                <div class="tmu-tmdb-actions">
                    <button type="button" id="sync-tmdb-data" class="button button-secondary" data-post-id="<?php echo $post->ID; ?>">
                        <?php _e('Sync from TMDB', 'tmu'); ?>
                    </button>
                    
                    <button type="button" id="refresh-tmdb-images" class="button button-secondary" data-post-id="<?php echo $post->ID; ?>">
                        <?php _e('Update Images', 'tmu'); ?>
                    </button>
                </div>
            <?php else: ?>
                <div class="tmu-no-tmdb">
                    <p><?php _e('No TMDB ID found. Search for this content to link it:', 'tmu'); ?></p>
                    
                    <input type="text" id="tmdb-search" placeholder="<?php esc_attr_e('Search TMDB...', 'tmu'); ?>" class="widefat">
                    <button type="button" id="search-tmdb" class="button button-secondary">
                        <?php _e('Search', 'tmu'); ?>
                    </button>
                    
                    <div id="tmdb-search-results" class="tmu-search-results" style="display:none;"></div>
                </div>
            <?php endif; ?>
            
            <div class="tmu-tmdb-manual">
                <label for="manual-tmdb-id"><?php _e('Manual TMDB ID:', 'tmu'); ?></label>
                <input type="number" id="manual-tmdb-id" name="manual_tmdb_id" value="<?php echo esc_attr($tmdb_id); ?>" class="small-text">
                <button type="button" id="save-tmdb-id" class="button button-secondary">
                    <?php _e('Save', 'tmu'); ?>
                </button>
            </div>
        </div>
        
        <style>
        .tmu-tmdb-box { padding: 0; }
        .tmu-tmdb-status, .tmu-last-sync { margin-bottom: 10px; }
        .tmu-tmdb-actions button { margin: 2px 0; width: 100%; }
        .tmu-search-results { border: 1px solid #ddd; max-height: 200px; overflow-y: auto; margin-top: 10px; }
        .tmu-search-result { padding: 8px; border-bottom: 1px solid #eee; cursor: pointer; }
        .tmu-search-result:hover { background: #f5f5f5; }
        .tmu-tmdb-manual { margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
        </style>
        <?php
    }
    
    public function handleTMDBSync(): void {
        check_ajax_referer('tmu_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Unauthorized', 'tmu'));
        }
        
        $post_id = intval($_POST['post_id']);
        $tmdb_id = tmu_get_meta($post_id, 'tmdb_id');
        
        if (!$tmdb_id) {
            wp_send_json_error(['message' => __('No TMDB ID found', 'tmu')]);
        }
        
        // Integrate with TMDB API
        $api = new \TMU\API\TMDB\Client();
        $post_type = get_post_type($post_id);
        
        try {
            $data = $api->getDetails($tmdb_id, $this->mapPostTypeToTMDB($post_type));
            $this->updatePostWithTMDBData($post_id, $data);
            
            update_post_meta($post_id, '_tmdb_last_sync', current_time('mysql'));
            
            wp_send_json_success([
                'message' => __('Successfully synced with TMDB', 'tmu'),
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    private function mapPostTypeToTMDB(string $post_type): string {
        $map = [
            'movie' => 'movie',
            'tv' => 'tv',
            'drama' => 'tv',
            'people' => 'person'
        ];
        
        return $map[$post_type] ?? 'movie';
    }
    
    private function updatePostWithTMDBData(int $post_id, array $data): void {
        $field_mappings = [
            'overview' => 'overview',
            'release_date' => 'release_date',
            'vote_average' => 'vote_average',
            'vote_count' => 'vote_count',
            'popularity' => 'popularity',
            'runtime' => 'runtime',
            'revenue' => 'revenue',
            'budget' => 'budget',
        ];
        
        foreach ($field_mappings as $tmdb_field => $meta_field) {
            if (isset($data[$tmdb_field])) {
                $storage = new \TMU\Fields\Storage\CustomTableStorage();
                $storage->save($post_id, $meta_field, $data[$tmdb_field]);
            }
        }
        
        // Update post content if empty
        if (empty(get_post_field('post_content', $post_id)) && isset($data['overview'])) {
            wp_update_post([
                'ID' => $post_id,
                'post_content' => $data['overview']
            ]);
        }
    }
}
```

### 4. Dashboard Widgets (`Dashboard/Widgets.php`)
```php
<?php
namespace TMU\Admin\Dashboard;

class Widgets {
    public function __construct() {
        add_action('wp_dashboard_setup', [$this, 'addDashboardWidgets']);
    }
    
    public function addDashboardWidgets(): void {
        wp_add_dashboard_widget(
            'tmu_content_stats',
            __('TMU Content Statistics', 'tmu'),
            [$this, 'renderContentStatsWidget']
        );
        
        wp_add_dashboard_widget(
            'tmu_recent_additions',
            __('Recent TMU Additions', 'tmu'),
            [$this, 'renderRecentAdditionsWidget']
        );
    }
    
    public function renderContentStatsWidget(): void {
        $stats = $this->getContentStats();
        ?>
        <div class="tmu-dashboard-stats">
            <?php foreach ($stats as $post_type => $data): ?>
                <div class="tmu-stat-item">
                    <div class="tmu-stat-count"><?php echo number_format($data['count']); ?></div>
                    <div class="tmu-stat-label"><?php echo esc_html($data['label']); ?></div>
                    <div class="tmu-stat-actions">
                        <a href="<?php echo admin_url('edit.php?post_type=' . $post_type); ?>">
                            <?php _e('View All', 'tmu'); ?>
                        </a>
                        |
                        <a href="<?php echo admin_url('post-new.php?post_type=' . $post_type); ?>">
                            <?php _e('Add New', 'tmu'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <style>
        .tmu-dashboard-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
        .tmu-stat-item { text-align: center; padding: 15px; background: #f9f9f9; border-radius: 4px; }
        .tmu-stat-count { font-size: 24px; font-weight: bold; color: #0073aa; }
        .tmu-stat-label { margin: 5px 0; font-weight: 500; }
        .tmu-stat-actions { font-size: 12px; margin-top: 8px; }
        .tmu-stat-actions a { text-decoration: none; }
        </style>
        <?php
    }
    
    private function getContentStats(): array {
        $stats = [];
        $post_types = ['movie', 'tv', 'drama', 'people'];
        
        foreach ($post_types as $post_type) {
            if (post_type_exists($post_type)) {
                $count = wp_count_posts($post_type);
                $post_type_object = get_post_type_object($post_type);
                
                $stats[$post_type] = [
                    'count' => $count->publish ?? 0,
                    'label' => $post_type_object->labels->name ?? ucfirst($post_type)
                ];
            }
        }
        
        return $stats;
    }
}
```

## Enhanced Admin Styling

### Admin CSS (`assets/css/admin.css`)
```css
/* TMU Admin Enhancements */
.tmu-admin-poster {
    max-width: 50px;
    height: auto;
    border-radius: 2px;
}

.tmu-poster-placeholder {
    width: 50px;
    height: 75px;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: #666;
    border-radius: 2px;
}

.tmu-rating-display {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.rating-value {
    font-weight: bold;
    color: #0073aa;
}

.rating-stars {
    color: #ffb900;
    font-size: 12px;
}

.tmu-quick-actions .tmu-action-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.tmu-action-card {
    background: white;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.tmu-action-card h3 {
    margin-top: 0;
    color: #1d2327;
}

.tmu-progress {
    margin: 20px 0;
    background: white;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #0073aa;
    width: 0;
    transition: width 0.3s ease;
}

.progress-text {
    text-align: center;
    margin-top: 10px;
    font-weight: 500;
}
```

## Integration and Testing

### Integration with Theme Core
```php
// In ThemeCore loadDependencies:
require_once TMU_INCLUDES_DIR . '/classes/Admin/AdminManager.php';

// In initTheme method:
if (is_admin()) {
    Admin\AdminManager::getInstance();
}
```

### Admin Functionality Test
```php
public function testAdminColumns(): void {
    $columns = new \TMU\Admin\Columns\MovieColumns();
    
    // Test column addition
    $original_columns = ['title' => 'Title', 'date' => 'Date'];
    $new_columns = $columns->addColumns($original_columns);
    
    $this->assertArrayHasKey('poster', $new_columns);
    $this->assertArrayHasKey('tmdb_id', $new_columns);
    $this->assertArrayHasKey('rating', $new_columns);
}
```

## Next Steps

1. **[Step 09: TMDB API Integration](./09_tmdb-api-integration.md)** - Complete API system
2. **[Step 10: Frontend Templates](./10_frontend-templates.md)** - Display templates
3. **[Step 11: SEO and Schema](./11_seo-and-schema.md)** - SEO optimization

## Verification Checklist

- [ ] Enhanced admin columns working
- [ ] TMDB meta box functional
- [ ] Dashboard widgets active
- [ ] Quick actions operational
- [ ] Bulk operations working
- [ ] Admin styling applied
- [ ] Navigation enhanced
- [ ] Performance optimized
- [ ] User experience improved

## AI Implementation Instructions for Step 8

### **Prerequisites Check**
Before implementing Step 8, verify these files exist from previous steps:
- **[REQUIRED]** Post types registration [FROM STEP 5] - Admin columns attach to post types
- **[REQUIRED]** Taxonomies registration [FROM STEP 6] - Taxonomy management interface  
- **[REQUIRED]** Gutenberg blocks [FROM STEP 7] - Block-based meta boxes
- **[REQUIRED]** PSR-4 autoloading [FROM STEP 4] - Admin class loading
- **[REQUIRED]** Asset compilation [FROM STEP 1] - Admin CSS/JS files
- **[REQUIRED]** Helper functions [FROM STEP 4] - Admin utilities

### **Implementation Order for AI Models**

#### **Phase 1: Create Directories** (Required First)
```bash
mkdir -p tmu-theme/includes/classes/Admin/Columns
mkdir -p tmu-theme/includes/classes/Admin/Actions  
mkdir -p tmu-theme/includes/classes/Admin/Dashboard
mkdir -p tmu-theme/includes/classes/Admin/MetaBoxes
mkdir -p tmu-theme/includes/classes/Admin/Navigation
mkdir -p tmu-theme/tests/Admin
```

#### **Phase 2: Core Admin System** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/Admin/AdminManager.php` - Main admin coordinator
2. **[UPDATE SECOND]** `includes/classes/ThemeCore.php` - Include admin manager

#### **Phase 3: Admin Columns** (Exact Order)  
1. **[CREATE FIRST]** `includes/classes/Admin/Columns/MovieColumns.php` - Movie admin columns
2. **[CREATE SECOND]** `includes/classes/Admin/Columns/TVColumns.php` - TV show admin columns
3. **[CREATE THIRD]** `includes/classes/Admin/Columns/DramaColumns.php` - Drama admin columns
4. **[CREATE FOURTH]** `includes/classes/Admin/Columns/PeopleColumns.php` - People admin columns

#### **Phase 4: Meta Boxes** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/Admin/MetaBoxes/TMDBBox.php` - TMDB integration meta box
2. **[CREATE SECOND]** `includes/classes/Admin/MetaBoxes/RelationshipBox.php` - Content relationships
3. **[CREATE THIRD]** `includes/classes/Admin/MetaBoxes/QuickActions.php` - Quick action buttons

#### **Phase 5: Admin Actions** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/Admin/Actions/TMDBSync.php` - TMDB synchronization
2. **[CREATE SECOND]** `includes/classes/Admin/Actions/BulkEdit.php` - Bulk editing actions
3. **[CREATE THIRD]** `includes/classes/Admin/Actions/DataImport.php` - Data import tools

#### **Phase 6: Dashboard Components** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/Admin/Dashboard/Widgets.php` - Custom widgets
2. **[CREATE SECOND]** `includes/classes/Admin/Dashboard/QuickStats.php` - Statistics display

#### **Phase 7: Navigation Enhancements** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/Admin/Navigation/MenuManager.php` - Menu customization
2. **[CREATE SECOND]** `includes/classes/Admin/Navigation/SubMenus.php` - Submenu organization

#### **Phase 8: Admin Assets** (Exact Order)
1. **[CREATE FIRST]** `assets/src/scss/admin.scss` - Admin interface styling
2. **[CREATE SECOND]** `assets/src/js/admin.js` - Admin interface JavaScript
3. **[UPDATE THIRD]** `webpack.config.js` - Include admin asset compilation

#### **Phase 9: Testing** (Exact Order)
1. **[CREATE FIRST]** `tests/Admin/AdminTest.php` - Admin interface testing

### **Key Implementation Notes**
- **Admin Columns**: Each post type gets custom admin columns with relevant data
- **TMDB Integration**: Meta boxes provide one-click TMDB synchronization
- **Bulk Actions**: Admin can perform mass operations on content
- **Dashboard Widgets**: Custom widgets show content statistics and quick actions
- **Responsive Design**: Admin interface works on mobile devices
- **Performance**: Efficient database queries for admin operations

### **Admin Column Features by Post Type**
```
Movie Columns:
├── Poster thumbnail (50x75px)
├── Release date (formatted)
├── TMDB ID (linked to TMDB)
├── Rating (stars + number)
├── Runtime (minutes)
└── Sortable by date/rating

TV Show Columns:
├── Poster thumbnail
├── First air date
├── Network
├── Number of seasons
├── Status (ongoing/ended)
└── TMDB ID linked

Drama Columns:
├── Poster thumbnail  
├── Channel
├── Air date
├── Episode count
├── Status
└── TMDB ID linked

People Columns:
├── Profile photo
├── Known for department
├── Popularity score
├── Birth date
├── TMDB ID linked
└── Number of credits
```

### **Meta Box Functionality**
1. **TMDB Box**: 
   - Display TMDB ID with external link
   - Show last sync timestamp
   - One-click sync button
   - Manual TMDB search
   - Manual ID assignment

2. **Relationship Box**:
   - Cast/crew relationships
   - Season/episode connections
   - Related content links
   - Quick relationship management

3. **Quick Actions Box**:
   - Bulk operations shortcuts
   - Status change buttons
   - Featured content toggle
   - Publication controls

### **Dashboard Widget Features**
- **Content Statistics**: Count of each post type with quick links
- **Recent Additions**: Latest content added to the system
- **TMDB Sync Status**: Progress of ongoing synchronizations
- **Popular Content**: Most viewed/rated content
- **System Health**: Database and API status

### **Critical Dependencies**
- **WordPress Admin APIs**: add_meta_boxes, manage_{post_type}_posts_columns
- **AJAX System**: wp_ajax actions for dynamic functionality
- **TMDB API**: Integration for data synchronization (Step 9)
- **Custom Tables**: TMU database tables for data storage (Step 3)
- **Asset System**: Compiled CSS/JS for admin interfaces

### **Testing Requirements**
1. **Admin Columns Test** - Verify columns display correct data
2. **Meta Box Test** - Verify meta boxes render and function
3. **AJAX Test** - Verify AJAX operations work correctly
4. **Permission Test** - Verify proper user capability checks
5. **Data Test** - Verify admin operations save data correctly

### **Development Workflow**
```bash
# Compile admin assets
npm run build

# Test admin functionality  
composer test tests/Admin/AdminTest.php

# Verify admin interface in WordPress
# Go to WordPress admin and check:
# - Enhanced post type list pages
# - Meta boxes in post editor
# - Dashboard widgets
# - Quick actions functionality
```

### **Common Issues and Solutions**
1. **Columns Not Showing**: Check post type registration and column hooks
2. **Meta Box Not Rendering**: Verify meta box hooks and post type support
3. **AJAX Failing**: Check nonce verification and user capabilities
4. **Styles Not Loading**: Verify asset compilation and enqueueing
5. **Data Not Saving**: Check custom table structure and data persistence

### **Verification Commands**
```bash
# Check admin assets compiled
ls -la assets/css/admin.css assets/js/admin.js

# Test admin column functionality
# Go to Movies list page in admin and verify:
# - Poster thumbnails display
# - Release dates show formatted
# - TMDB IDs link to external site
# - Rating stars display correctly
# - Columns are sortable

# Test meta box functionality  
# Go to edit movie page and verify:
# - TMDB meta box appears
# - Sync buttons work
# - Search functionality works
# - Manual ID assignment works
```

### **Post-Implementation Checklist**
- [ ] AdminManager class created and functional
- [ ] All admin column classes implemented
- [ ] Enhanced columns display correct data
- [ ] Meta boxes render properly in post editor
- [ ] TMDB integration meta box functional
- [ ] Dashboard widgets display statistics
- [ ] Admin navigation enhancements active
- [ ] Admin assets compiled and loading
- [ ] AJAX operations working correctly
- [ ] User permissions properly checked
- [ ] Admin interface responsive on mobile
- [ ] Tests passing
- [ ] ThemeCore integration complete

---

This admin enhancement system provides a streamlined, professional interface for managing TMU content while maintaining WordPress standards and improving productivity.

**Step 8 Status**: ✅ READY FOR AI IMPLEMENTATION
**Dependencies**: Steps 1, 4, 5, 6, 7 must be completed
**Next Step**: Step 9 - TMDB API Integration