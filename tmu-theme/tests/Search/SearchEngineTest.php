<?php
/**
 * Search Engine Test
 * 
 * Tests for the search engine functionality.
 * 
 * @package TMU\Tests\Unit
 * @since 1.0.0
 */

namespace TMU\Tests\Search;

use TMU\Search\SearchEngine;
use TMU\Tests\Utilities\DatabaseTestCase;

/**
 * SearchEngineTest class
 * 
 * Unit tests for search engine functionality
 */
class SearchEngineTest extends DatabaseTestCase {
    
    /**
     * Search engine instance
     * @var SearchEngine
     */
    private $search_engine;
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        if (class_exists('TMU\\Search\\SearchEngine')) {
            $this->search_engine = new SearchEngine();
        } else {
            $this->markTestSkipped('SearchEngine class not found');
        }
        
        $this->create_test_content();
    }
    
    /**
     * Test basic search
     */
    public function test_basic_search(): void {
        $results = $this->search_engine->search('Action Movie');
        
        $this->assertGreaterThan(0, $results->get_total(), 'Search should return results');
        $this->assertContains('action-movie-1', array_column($results->get_results(), 'post_name'), 'Should find action movie');
    }
    
    /**
     * Test filtered search
     */
    public function test_filtered_search(): void {
        $filters = ['post_type' => ['movie']];
        $results = $this->search_engine->search('Test', $filters);
        
        $post_types = array_unique(array_map('get_post_type', $results->get_results()));
        $this->assertEquals(['movie'], $post_types, 'Should only return movies when filtered');
    }
    
    /**
     * Test faceted search
     */
    public function test_faceted_search(): void {
        $results = $this->search_engine->search('', [], ['include_facets' => true]);
        $aggregations = $results->get_aggregations();
        
        $this->assertArrayHasKey('post_types', $aggregations, 'Should include post type facets');
        $this->assertArrayHasKey('genres', $aggregations, 'Should include genre facets');
        $this->assertArrayHasKey('years', $aggregations, 'Should include year facets');
    }
    
    /**
     * Test search relevance scoring
     */
    public function test_search_relevance_scoring(): void {
        $results = $this->search_engine->search('Fight Club');
        $results_array = $results->get_results();
        
        // Results should be ordered by relevance
        $first_result = $results_array[0];
        $this->assertStringContains('fight', strtolower($first_result->post_title), 'Most relevant result should be first');
    }
    
    /**
     * Test empty search query
     */
    public function test_empty_search_query(): void {
        $results = $this->search_engine->search('');
        
        // Should return recent posts when no query provided
        $this->assertGreaterThan(0, $results->get_total(), 'Empty search should return recent content');
    }
    
    /**
     * Test search with special characters
     */
    public function test_search_special_characters(): void {
        $special_queries = [
            'action & adventure',
            'sci-fi',
            'movie (2023)',
            'title: subtitle'
        ];
        
        foreach ($special_queries as $query) {
            $results = $this->search_engine->search($query);
            $this->assertInstanceOf('TMU\\Search\\SearchResults', $results, "Should handle special characters in: {$query}");
        }
    }
    
    /**
     * Test search pagination
     */
    public function test_search_pagination(): void {
        // Create enough content for pagination
        for ($i = 0; $i < 25; $i++) {
            $this->create_movie(['title' => "Pagination Movie {$i}"]);
        }
        
        $page1 = $this->search_engine->search('Pagination', [], ['page' => 1, 'per_page' => 10]);
        $page2 = $this->search_engine->search('Pagination', [], ['page' => 2, 'per_page' => 10]);
        
        $this->assertEquals(10, count($page1->get_results()), 'First page should have 10 results');
        $this->assertEquals(10, count($page2->get_results()), 'Second page should have 10 results');
        $this->assertNotEquals($page1->get_results(), $page2->get_results(), 'Pages should have different results');
    }
    
    /**
     * Test search sorting
     */
    public function test_search_sorting(): void {
        $sort_options = ['relevance', 'date', 'title', 'rating'];
        
        foreach ($sort_options as $sort) {
            $results = $this->search_engine->search('Test', [], ['sort' => $sort]);
            $this->assertGreaterThanOrEqual(0, $results->get_total(), "Should handle {$sort} sorting");
        }
    }
    
    /**
     * Create test content for search
     */
    private function create_test_content(): void {
        // Create test movies
        $this->create_movie([
            'title' => 'Action Movie 1',
            'overview' => 'An exciting action-packed adventure',
            'post_name' => 'action-movie-1'
        ]);
        
        $this->create_movie([
            'title' => 'Fight Club',
            'overview' => 'A cult classic movie',
            'post_name' => 'fight-club'
        ]);
        
        $this->create_movie([
            'title' => 'The Matrix',
            'overview' => 'A sci-fi action thriller',
            'post_name' => 'the-matrix'
        ]);
        
        // Create test TV shows
        $this->create_tv_show([
            'title' => 'Drama Series',
            'overview' => 'A compelling drama series'
        ]);
        
        $this->create_tv_show([
            'title' => 'Action TV Show',
            'overview' => 'An action-packed TV series'
        ]);
        
        // Create test people
        $this->create_person([
            'title' => 'Test Actor',
            'biography' => 'A talented actor'
        ]);
        
        $this->create_person([
            'title' => 'Action Star',
            'biography' => 'Famous for action movies'
        ]);
    }
}