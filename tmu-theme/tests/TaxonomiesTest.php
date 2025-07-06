<?php
/**
 * Taxonomies Test
 *
 * @package TMU\Tests
 * @version 1.0.0
 */

namespace TMU\Tests;

use PHPUnit\Framework\TestCase;
use TMU\Taxonomies\TaxonomyManager;
use TMU\Taxonomies\Genre;
use TMU\Taxonomies\Country;
use TMU\Taxonomies\Network;
use TMU\Taxonomies\Channel;
use TMU\Taxonomies\Keyword;
use TMU\Taxonomies\Nationality;

/**
 * Test taxonomies functionality
 */
class TaxonomiesTest extends TestCase {
    
    /**
     * Taxonomy manager instance
     *
     * @var TaxonomyManager
     */
    private $manager;
    
    /**
     * Set up test
     */
    public function setUp(): void {
        parent::setUp();
        $this->manager = TaxonomyManager::getInstance();
    }
    
    /**
     * Test taxonomy manager singleton
     */
    public function testTaxonomyManagerSingleton(): void {
        $manager1 = TaxonomyManager::getInstance();
        $manager2 = TaxonomyManager::getInstance();
        
        $this->assertSame($manager1, $manager2);
        $this->assertInstanceOf(TaxonomyManager::class, $manager1);
    }
    
    /**
     * Test taxonomy registration with all features enabled
     */
    public function testTaxonomyRegistrationAllEnabled(): void {
        // Enable all post types
        update_option('tmu_movies', 'on');
        update_option('tmu_tv_series', 'on');
        update_option('tmu_dramas', 'on');
        
        // Register taxonomies
        $this->manager->registerTaxonomies();
        
        // Test universal taxonomies (should be registered)
        $this->assertTrue(taxonomy_exists('genre'));
        $this->assertTrue(taxonomy_exists('country'));
        $this->assertTrue(taxonomy_exists('language'));
        $this->assertTrue(taxonomy_exists('by-year'));
        
        // Test specific taxonomies
        $this->assertTrue(taxonomy_exists('network')); // TV series enabled
        $this->assertTrue(taxonomy_exists('channel')); // Dramas enabled
        $this->assertTrue(taxonomy_exists('keyword')); // Movies + TV enabled
        $this->assertTrue(taxonomy_exists('nationality')); // Always enabled for people
    }
    
    /**
     * Test conditional registration - only movies enabled
     */
    public function testConditionalRegistrationMoviesOnly(): void {
        // Enable only movies
        update_option('tmu_movies', 'on');
        update_option('tmu_tv_series', 'off');
        update_option('tmu_dramas', 'off');
        
        $this->manager->registerTaxonomies();
        
        // Universal taxonomies should be registered
        $this->assertTrue(taxonomy_exists('genre'));
        $this->assertTrue(taxonomy_exists('country'));
        $this->assertTrue(taxonomy_exists('language'));
        $this->assertTrue(taxonomy_exists('by-year'));
        
        // Specific taxonomies
        $this->assertFalse(taxonomy_exists('network')); // TV only
        $this->assertFalse(taxonomy_exists('channel')); // Drama only
        $this->assertTrue(taxonomy_exists('keyword')); // Movies enabled
        $this->assertTrue(taxonomy_exists('nationality')); // Always enabled
    }
    
    /**
     * Test conditional registration - only TV series enabled
     */
    public function testConditionalRegistrationTVOnly(): void {
        // Enable only TV series
        update_option('tmu_movies', 'off');
        update_option('tmu_tv_series', 'on');
        update_option('tmu_dramas', 'off');
        
        $this->manager->registerTaxonomies();
        
        // Universal taxonomies should be registered
        $this->assertTrue(taxonomy_exists('genre'));
        $this->assertTrue(taxonomy_exists('country'));
        $this->assertTrue(taxonomy_exists('language'));
        $this->assertTrue(taxonomy_exists('by-year'));
        
        // Specific taxonomies
        $this->assertTrue(taxonomy_exists('network')); // TV enabled
        $this->assertFalse(taxonomy_exists('channel')); // Drama only
        $this->assertTrue(taxonomy_exists('keyword')); // TV enabled
        $this->assertTrue(taxonomy_exists('nationality')); // Always enabled
    }
    
    /**
     * Test conditional registration - only dramas enabled
     */
    public function testConditionalRegistrationDramasOnly(): void {
        // Enable only dramas
        update_option('tmu_movies', 'off');
        update_option('tmu_tv_series', 'off');
        update_option('tmu_dramas', 'on');
        
        $this->manager->registerTaxonomies();
        
        // Universal taxonomies should be registered
        $this->assertTrue(taxonomy_exists('genre'));
        $this->assertTrue(taxonomy_exists('country'));
        $this->assertTrue(taxonomy_exists('language'));
        $this->assertTrue(taxonomy_exists('by-year'));
        
        // Specific taxonomies
        $this->assertFalse(taxonomy_exists('network')); // TV only
        $this->assertTrue(taxonomy_exists('channel')); // Drama enabled
        $this->assertFalse(taxonomy_exists('keyword')); // Excluded when only dramas
        $this->assertTrue(taxonomy_exists('nationality')); // Always enabled
    }
    
    /**
     * Test keyword exclusion logic
     */
    public function testKeywordExclusionLogic(): void {
        $keyword = new Keyword();
        
        // Test when only dramas is enabled
        update_option('tmu_movies', 'off');
        update_option('tmu_tv_series', 'off');
        update_option('tmu_dramas', 'on');
        
        // Use reflection to test shouldRegister method
        $reflection = new \ReflectionClass($keyword);
        $method = $reflection->getMethod('shouldRegister');
        $method->setAccessible(true);
        
        $this->assertFalse($method->invoke($keyword));
        
        // Test when movies or TV is enabled
        update_option('tmu_movies', 'on');
        $this->assertTrue($method->invoke($keyword));
    }
    
    /**
     * Test taxonomy instances
     */
    public function testTaxonomyInstances(): void {
        $genre = new Genre();
        $country = new Country();
        $network = new Network();
        $channel = new Channel();
        $keyword = new Keyword();
        $nationality = new Nationality();
        
        $this->assertInstanceOf(Genre::class, $genre);
        $this->assertInstanceOf(Country::class, $country);
        $this->assertInstanceOf(Network::class, $network);
        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertInstanceOf(Keyword::class, $keyword);
        $this->assertInstanceOf(Nationality::class, $nationality);
        
        // Test taxonomy slugs
        $this->assertEquals('genre', $genre->getTaxonomy());
        $this->assertEquals('country', $country->getTaxonomy());
        $this->assertEquals('network', $network->getTaxonomy());
        $this->assertEquals('channel', $channel->getTaxonomy());
        $this->assertEquals('keyword', $keyword->getTaxonomy());
        $this->assertEquals('nationality', $nationality->getTaxonomy());
    }
    
    /**
     * Test taxonomy post type assignments
     */
    public function testTaxonomyPostTypeAssignments(): void {
        // Enable all post types
        update_option('tmu_movies', 'on');
        update_option('tmu_tv_series', 'on');
        update_option('tmu_dramas', 'on');
        
        $genre = new Genre();
        $network = new Network();
        $channel = new Channel();
        $nationality = new Nationality();
        
        // Test object types
        $this->assertContains('movie', $genre->getObjectTypes());
        $this->assertContains('tv', $genre->getObjectTypes());
        $this->assertContains('drama', $genre->getObjectTypes());
        
        $this->assertEquals(['tv'], $network->getObjectTypes());
        $this->assertEquals(['drama'], $channel->getObjectTypes());
        $this->assertEquals(['people'], $nationality->getObjectTypes());
    }
    
    /**
     * Test manager statistics
     */
    public function testManagerStatistics(): void {
        // Enable all features
        update_option('tmu_movies', 'on');
        update_option('tmu_tv_series', 'on');
        update_option('tmu_dramas', 'on');
        
        $this->manager->registerTaxonomies();
        $stats = $this->manager->getStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('genre', $stats);
        $this->assertArrayHasKey('network', $stats);
        $this->assertArrayHasKey('channel', $stats);
        $this->assertArrayHasKey('nationality', $stats);
        
        foreach ($stats as $taxonomy_stats) {
            $this->assertArrayHasKey('total_terms', $taxonomy_stats);
            $this->assertArrayHasKey('object_types', $taxonomy_stats);
        }
    }
    
    /**
     * Test getting specific taxonomy
     */
    public function testGetSpecificTaxonomy(): void {
        $genre_taxonomy = $this->manager->getTaxonomy('genre');
        $this->assertInstanceOf(Genre::class, $genre_taxonomy);
        
        $non_existent = $this->manager->getTaxonomy('non_existent');
        $this->assertNull($non_existent);
    }
    
    /**
     * Test taxonomy registration check
     */
    public function testIsRegisteredCheck(): void {
        // Enable some taxonomies
        update_option('tmu_movies', 'on');
        update_option('tmu_tv_series', 'off');
        update_option('tmu_dramas', 'off');
        
        $this->manager->registerTaxonomies();
        
        $this->assertTrue($this->manager->isRegistered('genre'));
        $this->assertTrue($this->manager->isRegistered('keyword'));
        $this->assertFalse($this->manager->isRegistered('network'));
        $this->assertFalse($this->manager->isRegistered('channel'));
    }
    
    /**
     * Test term management functions
     */
    public function testTermManagement(): void {
        // Enable movies to test genre
        update_option('tmu_movies', 'on');
        $this->manager->registerTaxonomies();
        
        // Create a test post
        $post_id = wp_insert_post([
            'post_title' => 'Test Movie',
            'post_type' => 'movie',
            'post_status' => 'publish',
        ]);
        
        // Test setting terms
        $result = $this->manager->setPostTerms($post_id, 'genre', ['action', 'comedy']);
        $this->assertTrue($result);
        
        // Test getting terms
        $terms = $this->manager->getPostTerms($post_id, 'genre');
        $this->assertIsArray($terms);
        
        // Clean up
        wp_delete_post($post_id, true);
    }
    
    /**
     * Test search functionality
     */
    public function testSearchFunctionality(): void {
        // Enable movies to test genre
        update_option('tmu_movies', 'on');
        $this->manager->registerTaxonomies();
        
        // Create test terms
        $action_term = wp_insert_term('Action', 'genre');
        $adventure_term = wp_insert_term('Adventure', 'genre');
        
        if (!is_wp_error($action_term) && !is_wp_error($adventure_term)) {
            // Test search
            $results = $this->manager->searchTerms('genre', 'act', 5);
            $this->assertIsArray($results);
            
            // Should find "Action"
            $found_action = false;
            foreach ($results as $term) {
                if ($term->name === 'Action') {
                    $found_action = true;
                    break;
                }
            }
            $this->assertTrue($found_action);
            
            // Clean up
            wp_delete_term($action_term['term_id'], 'genre');
            wp_delete_term($adventure_term['term_id'], 'genre');
        }
    }
    
    /**
     * Test popular terms functionality
     */
    public function testPopularTerms(): void {
        // Enable movies to test genre
        update_option('tmu_movies', 'on');
        $this->manager->registerTaxonomies();
        
        $popular_terms = $this->manager->getPopularTerms('genre', 5);
        $this->assertIsArray($popular_terms);
        $this->assertLessThanOrEqual(5, count($popular_terms));
    }
    
    /**
     * Test cache functionality
     */
    public function testCacheFunctionality(): void {
        // Test clearing all cache
        $this->manager->clearCache();
        
        // Test clearing specific taxonomy cache
        $this->manager->clearCache('genre');
        
        // Should not throw any errors
        $this->assertTrue(true);
    }
    
    /**
     * Clean up test
     */
    public function tearDown(): void {
        // Reset taxonomy settings
        update_option('tmu_movies', 'off');
        update_option('tmu_tv_series', 'off');
        update_option('tmu_dramas', 'off');
        
        // Clear any test terms
        $this->manager->clearCache();
        
        parent::tearDown();
    }
}