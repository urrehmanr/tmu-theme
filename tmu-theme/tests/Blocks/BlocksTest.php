<?php
/**
 * TMU Blocks Test Suite
 * 
 * Comprehensive testing for the TMU Gutenberg block system including
 * block registration, data persistence, and rendering functionality.
 * 
 * @package TMU\Tests\Blocks
 * @since 1.0.0
 */

namespace TMU\Tests\Blocks;

use TMU\Blocks\BlockRegistry;
use TMU\Blocks\BaseBlock;
use TMU\Blocks\MovieMetadataBlock;
use TMU\Blocks\TvSeriesMetadataBlock;
use TMU\Blocks\DramaMetadataBlock;
use TMU\Blocks\PeopleMetadataBlock;
use TMU\Blocks\TvEpisodeMetadataBlock;
use PHPUnit\Framework\TestCase;
use WP_Mock;

/**
 * BlocksTest class
 * 
 * Tests all aspects of the TMU block system
 */
class BlocksTest extends TestCase {
    
    /**
     * Block registry instance
     * @var BlockRegistry
     */
    private $block_registry;
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        WP_Mock::setUp();
        
        // Mock WordPress functions
        WP_Mock::userFunction('register_block_type');
        WP_Mock::userFunction('wp_enqueue_script');
        WP_Mock::userFunction('wp_enqueue_style');
        WP_Mock::userFunction('get_template_directory_uri')->andReturn('/themes/tmu-theme');
        WP_Mock::userFunction('get_template_directory')->andReturn('/themes/tmu-theme');
        WP_Mock::userFunction('current_user_can')->andReturn(true);
        WP_Mock::userFunction('wp_create_nonce')->andReturn('test_nonce');
        WP_Mock::userFunction('rest_url')->andReturn('https://example.com/wp-json/');
        WP_Mock::userFunction('get_option')->andReturn('test_api_key');
        WP_Mock::userFunction('__')->andReturnFirstArg();
        
        $this->block_registry = new BlockRegistry();
    }
    
    /**
     * Tear down test environment
     */
    public function tearDown(): void {
        WP_Mock::tearDown();
    }
    
    /**
     * Test block registry initialization
     */
    public function test_block_registry_initialization(): void {
        $this->assertInstanceOf(BlockRegistry::class, $this->block_registry);
        
        $blocks = $this->block_registry->get_blocks();
        $this->assertIsArray($blocks);
        $this->assertNotEmpty($blocks);
    }
    
    /**
     * Test all expected blocks are registered
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
            $this->assertTrue(
                $this->block_registry->is_block_registered($block_name),
                "Block '{$block_name}' should be registered"
            );
        }
    }
    
    /**
     * Test movie metadata block
     */
    public function test_movie_metadata_block(): void {
        $block = new MovieMetadataBlock();
        $config = $block->get_block_config();
        
        $this->assertEquals('movie-metadata', $config['name']);
        $this->assertEquals('Movie Metadata', $config['title']);
        $this->assertArrayHasKey('attributes', $config);
        
        // Test attributes schema
        $attributes = MovieMetadataBlock::get_attributes();
        $this->assertArrayHasKey('tmdb_id', $attributes);
        $this->assertArrayHasKey('title', $attributes);
        $this->assertArrayHasKey('overview', $attributes);
        $this->assertArrayHasKey('release_date', $attributes);
        $this->assertArrayHasKey('runtime', $attributes);
        $this->assertArrayHasKey('budget', $attributes);
        $this->assertArrayHasKey('revenue', $attributes);
        
        // Test attribute types
        $this->assertEquals('number', $attributes['tmdb_id']['type']);
        $this->assertEquals('string', $attributes['title']['type']);
        $this->assertEquals('boolean', $attributes['adult']['type']);
    }
    
    /**
     * Test TV series metadata block
     */
    public function test_tv_series_metadata_block(): void {
        $block = new TvSeriesMetadataBlock();
        $config = $block->get_block_config();
        
        $this->assertEquals('tv-series-metadata', $config['name']);
        $this->assertEquals('TV Series Metadata', $config['title']);
        
        // Test TV-specific attributes
        $attributes = TvSeriesMetadataBlock::get_attributes();
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('number_of_seasons', $attributes);
        $this->assertArrayHasKey('number_of_episodes', $attributes);
        $this->assertArrayHasKey('first_air_date', $attributes);
        $this->assertArrayHasKey('networks', $attributes);
        $this->assertArrayHasKey('in_production', $attributes);
        
        $this->assertEquals('array', $attributes['networks']['type']);
        $this->assertEquals('boolean', $attributes['in_production']['type']);
    }
    
    /**
     * Test drama metadata block
     */
    public function test_drama_metadata_block(): void {
        $block = new DramaMetadataBlock();
        $config = $block->get_block_config();
        
        $this->assertEquals('drama-metadata', $config['name']);
        $this->assertEquals('Drama Metadata', $config['title']);
        
        // Test drama-specific attributes
        $attributes = DramaMetadataBlock::get_attributes();
        $this->assertArrayHasKey('episodes', $attributes);
        $this->assertArrayHasKey('channel', $attributes);
        $this->assertArrayHasKey('broadcast_day', $attributes);
        $this->assertArrayHasKey('drama_type', $attributes);
    }
    
    /**
     * Test people metadata block
     */
    public function test_people_metadata_block(): void {
        $block = new PeopleMetadataBlock();
        $config = $block->get_block_config();
        
        $this->assertEquals('people-metadata', $config['name']);
        $this->assertEquals('People Metadata', $config['title']);
        
        // Test people-specific attributes
        $attributes = PeopleMetadataBlock::get_attributes();
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('birthday', $attributes);
        $this->assertArrayHasKey('place_of_birth', $attributes);
        $this->assertArrayHasKey('biography', $attributes);
        $this->assertArrayHasKey('known_for_department', $attributes);
        $this->assertArrayHasKey('gender', $attributes);
        
        $this->assertEquals('number', $attributes['gender']['type']);
    }
    
    /**
     * Test episode metadata block
     */
    public function test_episode_metadata_block(): void {
        $block = new TvEpisodeMetadataBlock();
        $config = $block->get_block_config();
        
        $this->assertEquals('tv-episode-metadata', $config['name']);
        $this->assertEquals('TV Episode Metadata', $config['title']);
        
        // Test episode-specific attributes
        $attributes = TvEpisodeMetadataBlock::get_attributes();
        $this->assertArrayHasKey('tv_series', $attributes);
        $this->assertArrayHasKey('season_number', $attributes);
        $this->assertArrayHasKey('episode_number', $attributes);
        $this->assertArrayHasKey('air_date', $attributes);
        $this->assertArrayHasKey('guest_stars', $attributes);
        $this->assertArrayHasKey('crew', $attributes);
        
        $this->assertEquals('array', $attributes['guest_stars']['type']);
        $this->assertEquals('array', $attributes['crew']['type']);
    }
    
    /**
     * Test block attribute validation
     */
    public function test_block_attribute_validation(): void {
        $test_attributes = [
            'tmdb_id' => '12345',
            'title' => 'Test Movie',
            'adult' => '1',
            'runtime' => '120.5',
            'invalid_field' => 'should_be_ignored'
        ];
        
        $validated = MovieMetadataBlock::validate_attributes($test_attributes);
        
        $this->assertEquals(12345, $validated['tmdb_id']);
        $this->assertEquals('Test Movie', $validated['title']);
        $this->assertTrue($validated['adult']);
        $this->assertEquals(120.5, $validated['runtime']);
        $this->assertArrayNotHasKey('invalid_field', $validated);
    }
    
    /**
     * Test block rendering
     */
    public function test_block_rendering(): void {
        $attributes = [
            'title' => 'Test Movie',
            'overview' => 'This is a test movie.',
            'release_date' => '2023-01-01'
        ];
        
        $rendered = MovieMetadataBlock::render($attributes, '');
        
        $this->assertIsString($rendered);
        $this->assertStringContainsString('tmu-movie-metadata', $rendered);
        $this->assertStringContainsString('Test Movie', $rendered);
    }
    
    /**
     * Test post type restrictions
     */
    public function test_post_type_restrictions(): void {
        $movie_block = new MovieMetadataBlock();
        $tv_block = new TvSeriesMetadataBlock();
        $people_block = new PeopleMetadataBlock();
        
        // Movie block should only be allowed on movie post type
        $this->assertTrue($movie_block->is_allowed_post_type('movie'));
        $this->assertFalse($movie_block->is_allowed_post_type('tv'));
        $this->assertFalse($movie_block->is_allowed_post_type('post'));
        
        // TV block should only be allowed on tv post type
        $this->assertTrue($tv_block->is_allowed_post_type('tv'));
        $this->assertFalse($tv_block->is_allowed_post_type('movie'));
        
        // People block should only be allowed on people post type
        $this->assertTrue($people_block->is_allowed_post_type('people'));
        $this->assertFalse($people_block->is_allowed_post_type('movie'));
    }
    
    /**
     * Test block data persistence (mock)
     */
    public function test_block_data_persistence(): void {
        // Mock WordPress database functions
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->prefix = 'wp_';
        
        $wpdb->expects($this->once())
             ->method('get_row')
             ->willReturn(null);
             
        $wpdb->expects($this->once())
             ->method('insert')
             ->willReturn(1);
        
        WP_Mock::userFunction('current_time')->andReturn('2023-01-01 12:00:00');
        
        $attributes = [
            'tmdb_id' => 12345,
            'title' => 'Test Movie',
            'overview' => 'Test overview',
            'release_date' => '2023-01-01',
            'runtime' => 120,
            'budget' => 1000000,
            'revenue' => 5000000
        ];
        
        $result = MovieMetadataBlock::save_to_database(123, $attributes);
        $this->assertTrue($result);
    }
    
    /**
     * Test block category registration
     */
    public function test_block_category_registration(): void {
        $existing_categories = [
            ['slug' => 'text', 'title' => 'Text'],
            ['slug' => 'media', 'title' => 'Media']
        ];
        
        $categories = $this->block_registry->register_block_category($existing_categories);
        
        $this->assertCount(3, $categories);
        $this->assertEquals('tmu-blocks', $categories[0]['slug']);
        $this->assertEquals('TMU Blocks', $categories[0]['title']);
    }
    
    /**
     * Test dynamic block registration
     */
    public function test_dynamic_block_registration(): void {
        $initial_count = count($this->block_registry->get_blocks());
        
        // Test adding a new block
        $result = $this->block_registry->register_additional_block('test-block', MovieMetadataBlock::class);
        $this->assertTrue($result);
        
        $new_count = count($this->block_registry->get_blocks());
        $this->assertEquals($initial_count + 1, $new_count);
        
        // Test duplicate registration
        $result = $this->block_registry->register_additional_block('test-block', MovieMetadataBlock::class);
        $this->assertFalse($result);
    }
    
    /**
     * Test block unregistration
     */
    public function test_block_unregistration(): void {
        WP_Mock::userFunction('unregister_block_type')->andReturn(true);
        
        $initial_count = count($this->block_registry->get_blocks());
        
        $result = $this->block_registry->unregister_block('movie-metadata');
        $this->assertTrue($result);
        
        $new_count = count($this->block_registry->get_blocks());
        $this->assertEquals($initial_count - 1, $new_count);
        $this->assertFalse($this->block_registry->is_block_registered('movie-metadata'));
    }
    
    /**
     * Test asset enqueueing
     */
    public function test_asset_enqueueing(): void {
        WP_Mock::expectAction('enqueue_block_editor_assets', [$this->block_registry, 'enqueue_editor_assets']);
        WP_Mock::expectAction('enqueue_block_assets', [$this->block_registry, 'enqueue_block_assets']);
        WP_Mock::expectAction('wp_enqueue_scripts', [$this->block_registry, 'enqueue_frontend_assets']);
        
        // Mock file_exists to return true
        WP_Mock::userFunction('file_exists')->andReturn(true);
        WP_Mock::userFunction('filemtime')->andReturn(time());
        
        // Test editor asset enqueueing
        WP_Mock::expectActionAdded('wp_enqueue_script', 'tmu-blocks-editor');
        WP_Mock::expectActionAdded('wp_enqueue_style', 'tmu-blocks-editor');
        WP_Mock::expectActionAdded('wp_localize_script', 'tmu-blocks-editor');
        
        $this->block_registry->enqueue_editor_assets();
        
        $this->assertTrue(true); // If we get here without exceptions, the test passes
    }
    
    /**
     * Test error handling
     */
    public function test_error_handling(): void {
        // Test handling of non-existent block class
        $result = $this->block_registry->register_additional_block('invalid-block', 'NonExistentClass');
        $this->assertFalse($result);
        
        // Test getting non-existent block instance
        $instance = $this->block_registry->get_block_instance('non-existent-block');
        $this->assertNull($instance);
    }
    
    /**
     * Test block configuration completeness
     */
    public function test_block_configuration_completeness(): void {
        $blocks_to_test = [
            MovieMetadataBlock::class,
            TvSeriesMetadataBlock::class,
            DramaMetadataBlock::class,
            PeopleMetadataBlock::class,
            TvEpisodeMetadataBlock::class
        ];
        
        foreach ($blocks_to_test as $block_class) {
            $attributes = $block_class::get_attributes();
            
            // Ensure all blocks have required basic attributes
            $this->assertArrayHasKey('title', $attributes, $block_class . ' missing title attribute');
            $this->assertArrayHasKey('overview', $attributes, $block_class . ' missing overview attribute');
            
            // Ensure all attributes have proper type definitions
            foreach ($attributes as $attr_name => $attr_config) {
                $this->assertArrayHasKey('type', $attr_config, 
                    $block_class . " attribute '{$attr_name}' missing type definition");
                $this->assertContains($attr_config['type'], 
                    ['string', 'number', 'boolean', 'array', 'object'],
                    $block_class . " attribute '{$attr_name}' has invalid type");
            }
        }
    }
}