<?php
/**
 * Post Types Test
 *
 * @package TMU\Tests
 * @version 1.0.0
 */

namespace TMU\Tests;

use PHPUnit\Framework\TestCase;
use TMU\PostTypes\PostTypeManager;
use TMU\PostTypes\Movie;
use TMU\PostTypes\TVShow;
use TMU\PostTypes\Drama;
use TMU\PostTypes\People;

/**
 * Test post types functionality
 */
class PostTypesTest extends TestCase {
    
    /**
     * Post type manager instance
     *
     * @var PostTypeManager
     */
    private $manager;
    
    /**
     * Set up test
     */
    public function setUp(): void {
        parent::setUp();
        $this->manager = PostTypeManager::getInstance();
    }
    
    /**
     * Test post type manager singleton
     */
    public function testPostTypeManagerSingleton(): void {
        $manager1 = PostTypeManager::getInstance();
        $manager2 = PostTypeManager::getInstance();
        
        $this->assertSame($manager1, $manager2);
        $this->assertInstanceOf(PostTypeManager::class, $manager1);
    }
    
    /**
     * Test post type registration
     */
    public function testPostTypeRegistration(): void {
        // Enable all post types
        update_option('tmu_movies', 'on');
        update_option('tmu_tv_series', 'on');
        update_option('tmu_dramas', 'on');
        update_option('tmu_videos', 'on');
        
        // Register post types
        $this->manager->registerAllPostTypes();
        
        // Test individual post types exist
        $this->assertTrue(post_type_exists('movie'));
        $this->assertTrue(post_type_exists('tv'));
        $this->assertTrue(post_type_exists('drama'));
        $this->assertTrue(post_type_exists('people')); // Always enabled
        $this->assertTrue(post_type_exists('video'));
        
        // Test nested post types
        $this->assertTrue(post_type_exists('season'));
        $this->assertTrue(post_type_exists('episode'));
        $this->assertTrue(post_type_exists('drama-episode'));
    }
    
    /**
     * Test conditional registration
     */
    public function testConditionalRegistration(): void {
        // Disable movies
        update_option('tmu_movies', 'off');
        update_option('tmu_tv_series', 'on');
        
        $this->manager->resetPostTypes();
        $this->manager->registerAllPostTypes();
        
        // Movie should not be registered
        $this->assertFalse(post_type_exists('movie'));
        
        // TV series should be registered
        $this->assertTrue(post_type_exists('tv'));
        
        // People should always be registered
        $this->assertTrue(post_type_exists('people'));
    }
    
    /**
     * Test post type instances
     */
    public function testPostTypeInstances(): void {
        $movie = new Movie();
        $tvshow = new TVShow();
        $drama = new Drama();
        $people = new People();
        
        $this->assertInstanceOf(Movie::class, $movie);
        $this->assertInstanceOf(TVShow::class, $tvshow);
        $this->assertInstanceOf(Drama::class, $drama);
        $this->assertInstanceOf(People::class, $people);
        
        // Test post type slugs
        $this->assertEquals('movie', $movie->getPostType());
        $this->assertEquals('tv', $tvshow->getPostType());
        $this->assertEquals('drama', $drama->getPostType());
        $this->assertEquals('people', $people->getPostType());
    }
    
    /**
     * Test manager statistics
     */
    public function testManagerStatistics(): void {
        // Enable all post types
        update_option('tmu_movies', 'on');
        update_option('tmu_tv_series', 'on');
        update_option('tmu_dramas', 'on');
        update_option('tmu_videos', 'on');
        
        $this->manager->registerAllPostTypes();
        $stats = $this->manager->getStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_post_types', $stats);
        $this->assertArrayHasKey('registered_post_types', $stats);
        $this->assertArrayHasKey('post_counts', $stats);
        
        $this->assertGreaterThan(0, $stats['total_post_types']);
        $this->assertGreaterThan(0, $stats['registered_post_types']);
    }
    
    /**
     * Test post type activation/deactivation
     */
    public function testPostTypeToggling(): void {
        // Test activation
        $result = $this->manager->activatePostType('movies');
        $this->assertTrue($result);
        $this->assertTrue($this->manager->isPostTypeEnabled('movies'));
        
        // Test deactivation
        $result = $this->manager->deactivatePostType('movies');
        $this->assertTrue($result);
        $this->assertFalse($this->manager->isPostTypeEnabled('movies'));
    }
    
    /**
     * Test getting registered post types
     */
    public function testGetRegisteredPostTypes(): void {
        update_option('tmu_movies', 'on');
        update_option('tmu_tv_series', 'off');
        
        $this->manager->resetPostTypes();
        $this->manager->registerAllPostTypes();
        
        $registered = $this->manager->getRegisteredPostTypes();
        
        $this->assertIsArray($registered);
        $this->assertContains('movie', $registered);
        $this->assertContains('people', $registered); // Always enabled
        $this->assertNotContains('tv', $registered);
    }
    
    /**
     * Test post type count
     */
    public function testPostTypesCount(): void {
        update_option('tmu_movies', 'on');
        update_option('tmu_tv_series', 'on');
        
        $this->manager->resetPostTypes();
        $this->manager->registerAllPostTypes();
        
        $count = $this->manager->getPostTypesCount();
        $this->assertGreaterThan(0, $count);
    }
    
    /**
     * Test getting specific post type
     */
    public function testGetPostType(): void {
        update_option('tmu_movies', 'on');
        $this->manager->registerAllPostTypes();
        
        $movie_type = $this->manager->getPostType('movie');
        $this->assertInstanceOf(Movie::class, $movie_type);
        
        $non_existent = $this->manager->getPostType('non_existent');
        $this->assertNull($non_existent);
    }
    
    /**
     * Clean up test
     */
    public function tearDown(): void {
        // Reset post type settings
        update_option('tmu_movies', 'off');
        update_option('tmu_tv_series', 'off');
        update_option('tmu_dramas', 'off');
        update_option('tmu_videos', 'off');
        
        parent::tearDown();
    }
}