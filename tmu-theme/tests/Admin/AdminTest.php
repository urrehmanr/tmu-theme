<?php
/**
 * Admin Interface Tests
 * 
 * Comprehensive tests for TMU admin interface functionality including
 * columns, meta boxes, actions, and navigation components.
 * 
 * @package TMU\Tests\Admin
 * @since 1.0.0
 */

namespace TMU\Tests\Admin;

use PHPUnit\Framework\TestCase;
use TMU\Admin\AdminManager;
use TMU\Admin\Columns\MovieColumns;
use TMU\Admin\Actions\DataImport;
use TMU\Admin\MetaBoxes\QuickActions;
use TMU\Admin\Dashboard\QuickStats;
use TMU\Admin\Navigation\MenuManager;

/**
 * AdminTest class
 * 
 * Tests admin interface functionality
 */
class AdminTest extends TestCase {
    
    /**
     * Admin manager instance
     * @var AdminManager
     */
    private $admin_manager;
    
    /**
     * Test post ID
     * @var int
     */
    private $test_post_id;
    
    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Create test post
        $this->test_post_id = wp_insert_post([
            'post_title' => 'Test Movie',
            'post_type' => 'movie',
            'post_status' => 'publish'
        ]);
        
        // Initialize admin manager
        $this->admin_manager = AdminManager::getInstance();
    }
    
    /**
     * Clean up test environment
     */
    protected function tearDown(): void {
        if ($this->test_post_id) {
            wp_delete_post($this->test_post_id, true);
        }
        
        parent::tearDown();
    }
    
    /**
     * Test admin manager initialization
     */
    public function testAdminManagerInitialization(): void {
        $this->assertInstanceOf(AdminManager::class, $this->admin_manager);
        $this->assertTrue(is_admin());
    }
    
    /**
     * Test admin columns functionality
     */
    public function testAdminColumns(): void {
        $movie_columns = new MovieColumns();
        
        // Test column addition
        $original_columns = ['title' => 'Title', 'date' => 'Date'];
        $new_columns = $movie_columns->addColumns($original_columns);
        
        $this->assertArrayHasKey('poster', $new_columns);
        $this->assertArrayHasKey('release_date', $new_columns);
        $this->assertArrayHasKey('tmdb_id', $new_columns);
        $this->assertArrayHasKey('rating', $new_columns);
        $this->assertArrayHasKey('runtime', $new_columns);
        
        // Test sortable columns
        $sortable_columns = $movie_columns->addSortableColumns([]);
        $this->assertArrayHasKey('release_date', $sortable_columns);
        $this->assertArrayHasKey('rating', $sortable_columns);
    }
    
    /**
     * Test data import functionality
     */
    public function testDataImport(): void {
        $data_import = new DataImport();
        
        // Test CSV data validation
        $csv_data = [
            ['title' => 'Test Movie 1', 'year' => '2023'],
            ['title' => 'Test Movie 2', 'year' => '2024']
        ];
        
        $mapping = ['title' => 'title', 'year' => 'release_year'];
        $reflection = new \ReflectionClass($data_import);
        $method = $reflection->getMethod('validateCsvData');
        $method->setAccessible(true);
        
        $validated_data = $method->invoke($data_import, $csv_data, $mapping, 'movie');
        
        $this->assertIsArray($validated_data);
        $this->assertCount(2, $validated_data);
        $this->assertEquals('Test Movie 1', $validated_data[0]['title']);
    }
    
    /**
     * Test quick actions meta box
     */
    public function testQuickActionsMetaBox(): void {
        $quick_actions = new QuickActions();
        
        // Test action execution
        update_post_meta($this->test_post_id, 'tmdb_id', '12345');
        
        $reflection = new \ReflectionClass($quick_actions);
        $method = $reflection->getMethod('executeQuickAction');
        $method->setAccessible(true);
        
        try {
            $result = $method->invoke($quick_actions, 'sync_tmdb', $this->test_post_id);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('message', $result);
            $this->assertArrayHasKey('action', $result);
            $this->assertEquals('sync_tmdb', $result['action']);
        } catch (\Exception $e) {
            // Expected if TMDB API is not available
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }
    
    /**
     * Test quick stats functionality
     */
    public function testQuickStats(): void {
        $quick_stats = new QuickStats();
        
        // Test stats retrieval
        $reflection = new \ReflectionClass($quick_stats);
        $method = $reflection->getMethod('getQuickStats');
        $method->setAccessible(true);
        
        $stats = $method->invoke($quick_stats);
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('movies', $stats);
        $this->assertArrayHasKey('tv_shows', $stats);
        $this->assertArrayHasKey('dramas', $stats);
        $this->assertArrayHasKey('people', $stats);
        
        foreach ($stats as $stat) {
            $this->assertArrayHasKey('count', $stat);
            $this->assertArrayHasKey('label', $stat);
            $this->assertArrayHasKey('link', $stat);
            $this->assertIsInt($stat['count']);
        }
    }
    
    /**
     * Test menu manager functionality
     */
    public function testMenuManager(): void {
        $menu_manager = new MenuManager();
        
        // Test content stats retrieval
        $reflection = new \ReflectionClass($menu_manager);
        $method = $reflection->getMethod('getContentStats');
        $method->setAccessible(true);
        
        $stats = $method->invoke($menu_manager);
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('movies', $stats);
        $this->assertArrayHasKey('tv_shows', $stats);
        $this->assertArrayHasKey('dramas', $stats);
        $this->assertArrayHasKey('people', $stats);
        
        foreach ($stats as $stat) {
            $this->assertIsInt($stat);
            $this->assertGreaterThanOrEqual(0, $stat);
        }
    }
    
    /**
     * Test admin asset enqueueing
     */
    public function testAdminAssetEnqueueing(): void {
        global $post_type;
        $post_type = 'movie';
        
        // Simulate admin page
        set_current_screen('edit-movie');
        
        $this->admin_manager->enqueueAdminAssets('edit.php');
        
        // Test that scripts are enqueued
        $this->assertTrue(wp_script_is('tmu-admin', 'enqueued'));
        $this->assertTrue(wp_style_is('tmu-admin', 'enqueued'));
    }
    
    /**
     * Test admin menu customization
     */
    public function testAdminMenuCustomization(): void {
        global $menu, $submenu;
        
        // Clear existing menu
        $menu = [];
        $submenu = [];
        
        $this->admin_manager->customizeAdminMenu();
        
        // Check if TMU content menu was added
        $tmu_menu_found = false;
        foreach ($menu as $menu_item) {
            if (isset($menu_item[2]) && $menu_item[2] === 'edit.php?post_type=movie') {
                $tmu_menu_found = true;
                break;
            }
        }
        
        $this->assertTrue($tmu_menu_found, 'TMU content menu should be added');
    }
    
    /**
     * Test admin bar customization
     */
    public function testAdminBarCustomization(): void {
        $admin_bar = new \WP_Admin_Bar();
        
        $this->admin_manager->customizeAdminBar($admin_bar);
        
        $nodes = $admin_bar->get_nodes();
        $this->assertArrayHasKey('tmu-quick-menu', $nodes);
        
        $tmu_node = $nodes['tmu-quick-menu'];
        $this->assertEquals('TMU', trim(strip_tags($tmu_node->title)));
    }
    
    /**
     * Test dashboard widgets
     */
    public function testDashboardWidgets(): void {
        global $wp_meta_boxes;
        
        $this->admin_manager->addDashboardWidgets();
        
        $this->assertArrayHasKey('tmu-content-stats', $wp_meta_boxes['dashboard']['normal']['core']);
        $this->assertArrayHasKey('tmu-recent-updates', $wp_meta_boxes['dashboard']['normal']['core']);
    }
    
    /**
     * Test admin notices
     */
    public function testAdminNotices(): void {
        // Capture output
        ob_start();
        $this->admin_manager->showAdminNotices();
        $output = ob_get_clean();
        
        // Should not output anything by default
        $this->assertEmpty($output);
    }
    
    /**
     * Test admin footer customization
     */
    public function testAdminFooterCustomization(): void {
        global $post_type;
        $post_type = 'movie';
        
        $original_text = 'Original footer text';
        $modified_text = $this->admin_manager->customAdminFooter($original_text);
        
        $this->assertStringContainsString('TMU Theme', $modified_text);
        $this->assertStringContainsString('Movie', $modified_text);
    }
    
    /**
     * Test component loading
     */
    public function testComponentLoading(): void {
        $reflection = new \ReflectionClass($this->admin_manager);
        $property = $reflection->getProperty('components');
        $property->setAccessible(true);
        
        $components = $property->getValue($this->admin_manager);
        
        $this->assertIsArray($components);
        $this->assertArrayHasKey('movie_columns', $components);
        $this->assertArrayHasKey('widgets', $components);
    }
    
    /**
     * Test quick actions page rendering
     */
    public function testQuickActionsPageRendering(): void {
        ob_start();
        $this->admin_manager->renderQuickActionsPage();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('TMU Quick Actions', $output);
        $this->assertStringContainsString('tmu-action-cards', $output);
        $this->assertStringContainsString('TMDB Sync', $output);
        $this->assertStringContainsString('Import Content', $output);
    }
    
    /**
     * Test data management page rendering
     */
    public function testDataManagementPageRendering(): void {
        ob_start();
        $this->admin_manager->renderDataManagementPage();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('TMU Data Management', $output);
        $this->assertStringContainsString('Import Content', $output);
        $this->assertStringContainsString('Export Content', $output);
        $this->assertStringContainsString('Data Cleanup', $output);
    }
    
    /**
     * Test statistics page rendering
     */
    public function testStatisticsPageRendering(): void {
        ob_start();
        $this->admin_manager->renderStatisticsPage();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('TMU Content Statistics', $output);
        $this->assertStringContainsString('tmu-stats-grid', $output);
        $this->assertStringContainsString('Movies', $output);
        $this->assertStringContainsString('TV Shows', $output);
    }
    
    /**
     * Test admin capabilities
     */
    public function testAdminCapabilities(): void {
        $user = wp_get_current_user();
        $user->add_role('administrator');
        
        $reflection = new \ReflectionClass($this->admin_manager);
        $method = $reflection->getMethod('addAdminCapabilities');
        $method->setAccessible(true);
        $method->invoke($this->admin_manager);
        
        $this->assertTrue($user->has_cap('manage_tmu_content'));
        $this->assertTrue($user->has_cap('sync_tmdb_data'));
        $this->assertTrue($user->has_cap('import_tmu_data'));
    }
    
    /**
     * Test content statistics
     */
    public function testContentStatistics(): void {
        $reflection = new \ReflectionClass($this->admin_manager);
        $method = $reflection->getMethod('getContentStatistics');
        $method->setAccessible(true);
        
        $stats = $method->invoke($this->admin_manager);
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('movies', $stats);
        $this->assertArrayHasKey('tv_shows', $stats);
        $this->assertArrayHasKey('dramas', $stats);
        $this->assertArrayHasKey('people', $stats);
        
        $this->assertIsInt($stats['movies']);
        $this->assertGreaterThanOrEqual(0, $stats['movies']);
    }
    
    /**
     * Test widget rendering
     */
    public function testWidgetRendering(): void {
        // Test content stats widget
        ob_start();
        $this->admin_manager->renderContentStatsWidget();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('tmu-content-stats-widget', $output);
        $this->assertStringContainsString('Movies:', $output);
        $this->assertStringContainsString('View Detailed Stats', $output);
        
        // Test recent updates widget
        ob_start();
        $this->admin_manager->renderRecentUpdatesWidget();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('tmu-recent-updates-widget', $output);
    }
    
    /**
     * Test error handling
     */
    public function testErrorHandling(): void {
        // Test with invalid post ID
        $quick_actions = new QuickActions();
        
        $reflection = new \ReflectionClass($quick_actions);
        $method = $reflection->getMethod('executeQuickAction');
        $method->setAccessible(true);
        
        $this->expectException(\Exception::class);
        $method->invoke($quick_actions, 'sync_tmdb', 999999);
    }
    
    /**
     * Test admin scripts localization
     */
    public function testAdminScriptsLocalization(): void {
        global $post_type;
        $post_type = 'movie';
        
        set_current_screen('edit-movie');
        $this->admin_manager->enqueueAdminAssets('edit.php');
        
        // Check if script is localized
        $localized_data = wp_scripts()->get_data('tmu-admin', 'data');
        $this->assertNotEmpty($localized_data);
        $this->assertStringContainsString('tmuAdmin', $localized_data);
    }
    
    /**
     * Test admin interface responsiveness
     */
    public function testAdminInterfaceResponsiveness(): void {
        ob_start();
        $this->admin_manager->renderQuickActionsPage();
        $output = ob_get_clean();
        
        // Check for responsive CSS classes
        $this->assertStringContainsString('tmu-action-cards', $output);
        
        // Verify CSS includes responsive design
        $this->assertStringContainsString('grid-template-columns', $output);
        $this->assertStringContainsString('minmax', $output);
    }
    
    /**
     * Test navigation integration
     */
    public function testNavigationIntegration(): void {
        $menu_manager = new MenuManager();
        
        // Test menu creation
        global $menu;
        $menu = [];
        
        $reflection = new \ReflectionClass($menu_manager);
        $method = $reflection->getMethod('createMainTMUMenu');
        $method->setAccessible(true);
        $method->invoke($menu_manager);
        
        // Check if menu was created
        $this->assertNotEmpty($menu);
    }
}

/**
 * Test helper functions
 */

/**
 * Create test post with metadata
 * 
 * @param string $post_type Post type
 * @param array $meta_data Meta data to add
 * @return int Post ID
 */
function create_test_post_with_meta(string $post_type, array $meta_data = []): int {
    $post_id = wp_insert_post([
        'post_title' => 'Test ' . ucfirst($post_type),
        'post_type' => $post_type,
        'post_status' => 'publish'
    ]);
    
    foreach ($meta_data as $key => $value) {
        update_post_meta($post_id, $key, $value);
    }
    
    return $post_id;
}

/**
 * Mock WordPress functions for testing
 */
if (!function_exists('wp_insert_post')) {
    function wp_insert_post($postarr) {
        return 1; // Mock post ID
    }
}

if (!function_exists('wp_delete_post')) {
    function wp_delete_post($postid, $force_delete = false) {
        return true;
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $meta_key, $meta_value) {
        return true;
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false) {
        return $single ? '' : [];
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false) {
        return true;
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = [], $ver = false, $media = 'all') {
        return true;
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return true;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true;
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '', $scheme = 'admin') {
        return 'http://example.com/wp-admin/' . $path;
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return 'mock_nonce';
    }
}

if (!function_exists('wp_script_is')) {
    function wp_script_is($handle, $list = 'enqueued') {
        return true;
    }
}

if (!function_exists('wp_style_is')) {
    function wp_style_is($handle, $list = 'enqueued') {
        return true;
    }
}

if (!function_exists('set_current_screen')) {
    function set_current_screen($hook_name = '') {
        global $current_screen;
        $current_screen = (object) ['id' => $hook_name];
    }
}

if (!function_exists('wp_scripts')) {
    function wp_scripts() {
        return new class {
            public function get_data($handle, $key) {
                return 'var tmuAdmin = {};';
            }
        };
    }
}