<?php
/**
 * Taxonomy Manager
 *
 * @package TMU\Taxonomies
 * @version 1.0.0
 */

namespace TMU\Taxonomies;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Taxonomy Manager Class
 * 
 * Manages all taxonomy registrations and operations
 */
class TaxonomyManager {
    
    /**
     * Singleton instance
     *
     * @var TaxonomyManager
     */
    private static $instance = null;
    
    /**
     * Registered taxonomies
     *
     * @var array
     */
    private $taxonomies = [];
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->initializeTaxonomies();
        $this->addHooks();
    }
    
    /**
     * Get singleton instance
     *
     * @return TaxonomyManager
     */
    public static function getInstance(): TaxonomyManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Initialize all taxonomies
     */
    private function initializeTaxonomies(): void {
        $taxonomy_classes = [
            'Genre',
            'Country',
            'Language',
            'ByYear',
            'ProductionCompany',
            'Network',
            'Profession',
            'Channel',
            'Keyword',
            'Nationality',
        ];
        
        foreach ($taxonomy_classes as $class) {
            $full_class = "TMU\\Taxonomies\\{$class}";
            
            if (class_exists($full_class)) {
                $taxonomy = new $full_class();
                $this->taxonomies[$taxonomy->getTaxonomy()] = $taxonomy;
            }
        }
    }
    
    /**
     * Add WordPress hooks
     */
    private function addHooks(): void {
        add_action('init', [$this, 'registerTaxonomies'], 5);
        add_action('admin_init', [$this, 'flushRewriteRules']);
        add_action('wp_loaded', [$this, 'afterTaxonomiesRegistered']);
    }
    
    /**
     * Register all taxonomies
     */
    public function registerTaxonomies(): void {
        foreach ($this->taxonomies as $taxonomy) {
            $taxonomy->register();
        }
        
        // Log registered taxonomies
        if (tmu_get_option('tmu_debug_mode', 'off') === 'on') {
            $registered_taxonomies = array_keys($this->taxonomies);
            tmu_log('Registered taxonomies: ' . implode(', ', $registered_taxonomies), 'debug');
        }
    }
    
    /**
     * Flush rewrite rules when needed
     */
    public function flushRewriteRules(): void {
        if (get_option('tmu_taxonomies_rewrite_flush') === '1') {
            flush_rewrite_rules();
            delete_option('tmu_taxonomies_rewrite_flush');
        }
    }
    
    /**
     * Actions after taxonomies are registered
     */
    public function afterTaxonomiesRegistered(): void {
        // Seed default terms if needed
        $this->seedDefaultTerms();
        
        // Update taxonomy counts
        $this->updateTaxonomyCounts();
    }
    
    /**
     * Seed default terms
     */
    private function seedDefaultTerms(): void {
        if (get_option('tmu_taxonomies_seeded') === '1') {
            return;
        }
        
        $this->seedGenres();
        $this->seedCountries();
        $this->seedLanguages();
        $this->seedProfessions();
        
        update_option('tmu_taxonomies_seeded', '1');
    }
    
    /**
     * Seed default genres
     */
    private function seedGenres(): void {
        if (!taxonomy_exists('genre')) {
            return;
        }
        
        $genres = [
            'Action', 'Adventure', 'Animation', 'Comedy', 'Crime', 'Documentary',
            'Drama', 'Family', 'Fantasy', 'History', 'Horror', 'Music',
            'Mystery', 'Romance', 'Science Fiction', 'Thriller', 'War', 'Western'
        ];
        
        foreach ($genres as $genre) {
            if (!term_exists($genre, 'genre')) {
                wp_insert_term($genre, 'genre');
            }
        }
    }
    
    /**
     * Seed default countries
     */
    private function seedCountries(): void {
        if (!taxonomy_exists('country')) {
            return;
        }
        
        $countries = [
            'United States', 'United Kingdom', 'Canada', 'Australia', 'France',
            'Germany', 'Italy', 'Spain', 'Japan', 'South Korea', 'China',
            'India', 'Brazil', 'Mexico', 'Russia', 'Netherlands', 'Sweden',
            'Norway', 'Denmark', 'Finland', 'Belgium', 'Switzerland'
        ];
        
        foreach ($countries as $country) {
            if (!term_exists($country, 'country')) {
                wp_insert_term($country, 'country');
            }
        }
    }
    
    /**
     * Seed default languages
     */
    private function seedLanguages(): void {
        if (!taxonomy_exists('language')) {
            return;
        }
        
        $languages = [
            'English', 'Spanish', 'French', 'German', 'Italian', 'Portuguese',
            'Japanese', 'Korean', 'Chinese', 'Hindi', 'Arabic', 'Russian',
            'Dutch', 'Swedish', 'Norwegian', 'Danish', 'Finnish'
        ];
        
        foreach ($languages as $language) {
            if (!term_exists($language, 'language')) {
                wp_insert_term($language, 'language');
            }
        }
    }
    
    /**
     * Seed default professions
     */
    private function seedProfessions(): void {
        if (!taxonomy_exists('profession')) {
            return;
        }
        
        $professions = [
            'Actor', 'Actress', 'Director', 'Producer', 'Writer', 'Cinematographer',
            'Editor', 'Composer', 'Production Designer', 'Costume Designer',
            'Makeup Artist', 'Visual Effects Supervisor', 'Sound Designer',
            'Casting Director', 'Stunt Coordinator', 'Executive Producer'
        ];
        
        foreach ($professions as $profession) {
            if (!term_exists($profession, 'profession')) {
                wp_insert_term($profession, 'profession');
            }
        }
    }
    
    /**
     * Update taxonomy counts
     */
    private function updateTaxonomyCounts(): void {
        foreach ($this->taxonomies as $taxonomy_slug => $taxonomy) {
            if (taxonomy_exists($taxonomy_slug)) {
                $terms = get_terms([
                    'taxonomy' => $taxonomy_slug,
                    'hide_empty' => false,
                ]);
                
                if (!is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        wp_update_term_count_now([$term->term_id], $taxonomy_slug);
                    }
                }
            }
        }
    }
    
    /**
     * Get all registered taxonomies
     *
     * @return array
     */
    public function getTaxonomies(): array {
        return $this->taxonomies;
    }
    
    /**
     * Get specific taxonomy
     *
     * @param string $taxonomy_slug Taxonomy slug
     * @return AbstractTaxonomy|null
     */
    public function getTaxonomy(string $taxonomy_slug): ?AbstractTaxonomy {
        return $this->taxonomies[$taxonomy_slug] ?? null;
    }
    
    /**
     * Check if taxonomy is registered
     *
     * @param string $taxonomy_slug Taxonomy slug
     * @return bool
     */
    public function isRegistered(string $taxonomy_slug): bool {
        return isset($this->taxonomies[$taxonomy_slug]);
    }
    
    /**
     * Get taxonomy statistics
     *
     * @return array
     */
    public function getStatistics(): array {
        $stats = [];
        
        foreach ($this->taxonomies as $taxonomy_slug => $taxonomy) {
            if (taxonomy_exists($taxonomy_slug)) {
                $terms = get_terms([
                    'taxonomy' => $taxonomy_slug,
                    'hide_empty' => false,
                ]);
                
                $stats[$taxonomy_slug] = [
                    'total_terms' => is_wp_error($terms) ? 0 : count($terms),
                    'object_types' => $taxonomy->getObjectTypes(),
                ];
            }
        }
        
        return $stats;
    }
    
    /**
     * Clear taxonomy cache
     *
     * @param string $taxonomy_slug Taxonomy slug
     */
    public function clearCache(string $taxonomy_slug = ''): void {
        if (empty($taxonomy_slug)) {
            // Clear all taxonomy caches
            foreach ($this->taxonomies as $slug => $taxonomy) {
                wp_cache_delete($slug, 'terms');
                clean_term_cache([], $slug);
            }
        } else {
            // Clear specific taxonomy cache
            wp_cache_delete($taxonomy_slug, 'terms');
            clean_term_cache([], $taxonomy_slug);
        }
    }
    
    /**
     * Get terms for specific post
     *
     * @param int $post_id Post ID
     * @param string $taxonomy_slug Taxonomy slug
     * @return array
     */
    public function getPostTerms(int $post_id, string $taxonomy_slug): array {
        $terms = get_the_terms($post_id, $taxonomy_slug);
        
        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }
        
        return $terms;
    }
    
    /**
     * Set terms for specific post
     *
     * @param int $post_id Post ID
     * @param string $taxonomy_slug Taxonomy slug
     * @param array $terms Terms to set
     * @return bool
     */
    public function setPostTerms(int $post_id, string $taxonomy_slug, array $terms): bool {
        $result = wp_set_post_terms($post_id, $terms, $taxonomy_slug);
        
        if (is_wp_error($result)) {
            tmu_log("Failed to set terms for post {$post_id}: " . $result->get_error_message(), 'error');
            return false;
        }
        
        return true;
    }
    
    /**
     * Get popular terms
     *
     * @param string $taxonomy_slug Taxonomy slug
     * @param int $limit Number of terms to return
     * @return array
     */
    public function getPopularTerms(string $taxonomy_slug, int $limit = 10): array {
        $terms = get_terms([
            'taxonomy' => $taxonomy_slug,
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => $limit,
            'hide_empty' => true,
        ]);
        
        if (is_wp_error($terms)) {
            return [];
        }
        
        return $terms;
    }
    
    /**
     * Search terms
     *
     * @param string $taxonomy_slug Taxonomy slug
     * @param string $search Search term
     * @param int $limit Number of terms to return
     * @return array
     */
    public function searchTerms(string $taxonomy_slug, string $search, int $limit = 10): array {
        $terms = get_terms([
            'taxonomy' => $taxonomy_slug,
            'search' => $search,
            'number' => $limit,
            'hide_empty' => false,
        ]);
        
        if (is_wp_error($terms)) {
            return [];
        }
        
        return $terms;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    private function __wakeup() {}
}