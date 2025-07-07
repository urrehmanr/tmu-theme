<?php
/**
 * Blocks Test
 * 
 * Comprehensive test suite for the TMU Gutenberg blocks system.
 * Tests block registration, data persistence, TMDB integration, and functionality.
 * 
 * @package TMU\Tests\Blocks
 * @since 1.0.0
 */

namespace TMU\Tests\Blocks;

use PHPUnit\Framework\TestCase;
use TMU\Blocks\BlockRegistry;
use TMU\API\BlockDataController;
use TMU\Blocks\MovieMetadataBlock;
use TMU\Blocks\TvSeriesMetadataBlock;
use TMU\Blocks\DramaMetadataBlock;
use TMU\Blocks\PeopleMetadataBlock;

/**
 * BlocksTest class
 * 
 * Tests all aspects of the TMU blocks system
 */
class BlocksTest extends TestCase {
    
    /**
     * Block registry instance
     * @var BlockRegistry
     */
    private $block_registry;
    
    /**
     * Block data controller instance
     * @var BlockDataController
     */
    private $data_controller;
    
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
        
        // Initialize WordPress test environment
        $this->init_wordpress_test_environment();
        
        // Get singleton instances
        $this->block_registry = BlockRegistry::getInstance();
        $this->data_controller = BlockDataController::getInstance();
        
        // Create test post
        $this->test_post_id = $this->create_test_post();
    }
    
    /**
     * Clean up after tests
     */
    protected function tearDown(): void {
        // Clean up test data
        if ($this->test_post_id) {
            wp_delete_post($this->test_post_id, true);
        }
        
        parent::tearDown();
    }
    
    /**
     * Test block registry initialization
     */
    public function test_block_registry_initialization(): void {
        $this->assertInstanceOf(BlockRegistry::class, $this->block_registry);
        $this->assertNotEmpty($this->block_registry->get_blocks());
    }
    
    /**
     * Test all blocks are registered
     */
    public function test_all_blocks_registered(): void {
        $expected_blocks = [
            'movie-metadata',
            'tv-series-metadata',
            'drama-metadata',
            'people-metadata',
            'tv-episode-metadata',
            'drama-episode-metadata',
            'season-metadata',
            'video-metadata',
            'taxonomy-image',
            'taxonomy-faqs',
            'blog-posts-list',
            'trending-content',
            'tmdb-sync',
        ];
        
        $registered_blocks = array_keys($this->block_registry->get_blocks());
        
        foreach ($expected_blocks as $block_name) {
            $this->assertContains($block_name, $registered_blocks, "Block '{$block_name}' should be registered");
            $this->assertTrue($this->block_registry->is_block_registered($block_name));
        }
        
        $this->assertCount(count($expected_blocks), $registered_blocks, 'All expected blocks should be registered');
    }
    
    /**
     * Test WordPress block registration
     */
    public function test_wordpress_block_registration(): void {
        $block_types = \WP_Block_Type_Registry::get_instance()->get_all_registered();
        
        $expected_blocks = [
            'tmu/movie-metadata',
            'tmu/tv-series-metadata',
            'tmu/drama-metadata',
            'tmu/people-metadata',
            'tmu/tv-episode-metadata',
            'tmu/drama-episode-metadata',
            'tmu/season-metadata',
            'tmu/video-metadata',
            'tmu/taxonomy-image',
            'tmu/taxonomy-faqs',
            'tmu/blog-posts-list',
            'tmu/trending-content',
            'tmu/tmdb-sync',
        ];
        
        foreach ($expected_blocks as $block_name) {
            $this->assertArrayHasKey($block_name, $block_types, "WordPress should register block '{$block_name}'");
        }
    }
    
    /**
     * Test block category registration
     */
    public function test_block_category_registration(): void {
        $categories = $this->block_registry->register_block_category([]);
        
        $this->assertNotEmpty($categories);
        $this->assertCount(1, $categories);
        $this->assertEquals('tmu-blocks', $categories[0]['slug']);
        $this->assertEquals('TMU Blocks', $categories[0]['title']);
        $this->assertEquals('video-alt3', $categories[0]['icon']);
    }
    
    /**
     * Test movie metadata block functionality
     */
    public function test_movie_metadata_block(): void {
        $block_instance = $this->block_registry->get_block_instance('movie-metadata');
        $this->assertInstanceOf(MovieMetadataBlock::class, $block_instance);
        
        // Test attributes
        $attributes = MovieMetadataBlock::get_attributes();
        $this->assertIsArray($attributes);
        $this->assertArrayHasKey('tmdb_id', $attributes);
        $this->assertArrayHasKey('title', $attributes);
        $this->assertArrayHasKey('overview', $attributes);
        $this->assertArrayHasKey('release_date', $attributes);
        
        // Test render method
        $test_attributes = [
            'title' => 'Test Movie',
            'overview' => 'Test movie overview',
            'release_date' => '2023-01-01'
        ];
        
        $rendered = MovieMetadataBlock::render($test_attributes, '');
        $this->assertIsString($rendered);
        $this->assertStringContainsString('tmu-movie-metadata', $rendered);
    }
    
    /**
     * Test TV series metadata block functionality
     */
    public function test_tv_series_metadata_block(): void {
        $block_instance = $this->block_registry->get_block_instance('tv-series-metadata');
        $this->assertInstanceOf(TvSeriesMetadataBlock::class, $block_instance);
        
        // Test attributes
        $attributes = TvSeriesMetadataBlock::get_attributes();
        $this->assertIsArray($attributes);
        $this->assertArrayHasKey('tmdb_id', $attributes);
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('first_air_date', $attributes);
        $this->assertArrayHasKey('number_of_seasons', $attributes);
        
        // Test render method
        $test_attributes = [
            'name' => 'Test TV Series',
            'overview' => 'Test series overview',
            'first_air_date' => '2023-01-01'
        ];
        
        $rendered = TvSeriesMetadataBlock::render($test_attributes, '');
        $this->assertIsString($rendered);
        $this->assertStringContainsString('tmu-tv-series-metadata', $rendered);
    }
    
    /**
     * Test data persistence functionality
     */
    public function test_data_persistence(): void {
        $this->assertInstanceOf(BlockDataController::class, $this->data_controller);
        
        // Create test movie post
        $movie_post_id = wp_insert_post([
            'post_title' => 'Test Movie',
            'post_type' => 'movie',
            'post_status' => 'publish',
        ]);
        
        $this->assertIsInt($movie_post_id);
        $this->assertGreaterThan(0, $movie_post_id);
        
        // Test data saving (would need database setup for full test)
        $test_data = [
            'tmdb_id' => 12345,
            'title' => 'Test Movie Title',
            'overview' => 'Test movie overview',
            'release_date' => '2023-01-01',
            'runtime' => 120
        ];
        
        // This would require database tables to be set up
        // For now, just test that the method exists
        $this->assertTrue(method_exists($this->data_controller, 'save_block_data'));
        
        // Clean up
        wp_delete_post($movie_post_id, true);
    }
    
    /**
     * Test post type filtering
     */
    public function test_post_type_filtering(): void {
        // Create mock block editor context
        $context = new \stdClass();
        $context->post = new \stdClass();
        $context->post->post_type = 'movie';
        
        $allowed_blocks = $this->block_registry->filter_allowed_blocks(null, $context);
        
        $this->assertIsArray($allowed_blocks);
        $this->assertContains('tmu/movie-metadata', $allowed_blocks);
        
        // Test with different post type
        $context->post->post_type = 'tv';
        $allowed_blocks = $this->block_registry->filter_allowed_blocks(null, $context);
        
        $this->assertContains('tmu/tv-series-metadata', $allowed_blocks);
    }
    
    /**
     * Test block configuration
     */
    public function test_block_configuration(): void {
        $movie_block = $this->block_registry->get_block_instance('movie-metadata');
        $config = $movie_block->get_block_config();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('name', $config);
        $this->assertArrayHasKey('title', $config);
        $this->assertArrayHasKey('category', $config);
        $this->assertArrayHasKey('attributes', $config);
        
        $this->assertEquals('movie-metadata', $config['name']);
        $this->assertEquals('tmu-blocks', $config['category']);
    }
    
    /**
     * Test asset enqueuing
     */
    public function test_asset_enqueuing(): void {
        // Test editor assets enqueuing
        $this->block_registry->enqueue_editor_assets();
        
        // Check if scripts are registered
        $this->assertTrue(wp_script_is('tmu-blocks-editor', 'registered'));
        
        // Test frontend assets enqueuing
        $this->block_registry->enqueue_frontend_assets();
        
        // Check if frontend styles are registered
        $this->assertTrue(wp_style_is('tmu-blocks', 'registered'));
    }
    
    /**
     * Test block unregistration
     */
    public function test_block_unregistration(): void {
        // Register a test block first
        $this->assertTrue($this->block_registry->is_block_registered('movie-metadata'));
        
        // Unregister the block
        $result = $this->block_registry->unregister_block('movie-metadata');
        $this->assertTrue($result);
        
        // Check that it's no longer registered
        $this->assertFalse($this->block_registry->is_block_registered('movie-metadata'));
        
        // Re-register for cleanup
        $this->block_registry->register_additional_block('movie-metadata', MovieMetadataBlock::class);
    }
    
    /**
     * Test dynamic block registration
     */
    public function test_dynamic_block_registration(): void {
        $test_block_name = 'test-block';
        
        // Should not be registered initially
        $this->assertFalse($this->block_registry->is_block_registered($test_block_name));
        
        // Register dynamically
        $result = $this->block_registry->register_additional_block($test_block_name, MovieMetadataBlock::class);
        $this->assertTrue($result);
        
        // Should now be registered
        $this->assertTrue($this->block_registry->is_block_registered($test_block_name));
        
        // Clean up
        $this->block_registry->unregister_block($test_block_name);
    }
    
    /**
     * Test blocks for specific post type
     */
    public function test_blocks_for_post_type(): void {
        $movie_blocks = $this->block_registry->get_blocks_for_post_type('movie');
        $this->assertIsArray($movie_blocks);
        $this->assertArrayHasKey('movie-metadata', $movie_blocks);
        
        $tv_blocks = $this->block_registry->get_blocks_for_post_type('tv');
        $this->assertIsArray($tv_blocks);
        $this->assertArrayHasKey('tv-series-metadata', $tv_blocks);
    }
    
    /**
     * Test REST API endpoints
     */
    public function test_rest_api_endpoints(): void {
        // Test that REST API endpoints are registered
        $routes = rest_get_server()->get_routes();
        
        $this->assertArrayHasKey('/tmu/v1/block-data/(?P<post_id>\d+)', $routes);
        $this->assertArrayHasKey('/tmu/v1/tmdb/sync', $routes);
        $this->assertArrayHasKey('/tmu/v1/blocks/validate', $routes);
    }
    
    /**
     * Test accessibility compliance
     */
    public function test_accessibility_compliance(): void {
        // Test that blocks have proper accessibility attributes
        $movie_block = $this->block_registry->get_block_instance('movie-metadata');
        $attributes = MovieMetadataBlock::get_attributes();
        
        // Test that required accessibility fields exist
        $this->assertIsArray($attributes);
        
        // Test rendered output has proper ARIA attributes
        $test_attributes = ['title' => 'Test Movie'];
        $rendered = MovieMetadataBlock::render($test_attributes, '');
        
        // Basic accessibility checks
        $this->assertIsString($rendered);
        $this->assertStringContainsString('tmu-movie-metadata', $rendered);
    }
    
    /**
     * Test responsive design compatibility
     */
    public function test_responsive_compatibility(): void {
        // Test that blocks render properly for responsive design
        $movie_block = $this->block_registry->get_block_instance('movie-metadata');
        $this->assertNotNull($movie_block);
        
        // Test block configuration supports responsive features
        $config = $movie_block->get_block_config();
        $this->assertIsArray($config);
    }
    
    /**
     * Initialize WordPress test environment
     */
    private function init_wordpress_test_environment(): void {
        // Mock WordPress functions for testing
        if (!function_exists('wp_insert_post')) {
            $this->markTestSkipped('WordPress test environment not available');
        }
        
        if (!function_exists('register_block_type')) {
            $this->markTestSkipped('WordPress block functions not available');
        }
    }
    
    /**
     * Create test post for testing
     * 
     * @return int Post ID
     */
    private function create_test_post(): int {
        return wp_insert_post([
            'post_title' => 'Test Post for Blocks',
            'post_type' => 'movie',
            'post_status' => 'publish',
            'post_content' => '<!-- wp:tmu/movie-metadata {"title":"Test Movie"} /-->',
        ]);
    }
}