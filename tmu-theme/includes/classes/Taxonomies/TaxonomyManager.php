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
     * Taxonomy configuration
     *
     * @var array
     */
    private $config = [];
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->initializeTaxonomies();
        
        // Don't register taxonomies in constructor to avoid early registration
        // $this->registerTaxonomies();
        
        // Add WordPress hooks
        $this->addHooks();
        
        // Register activation hook for flushing rewrite rules
        add_action('after_switch_theme', [$this, 'flushRewriteRules']);
        
        // Register deactivation hook
        add_action('switch_theme', [$this, 'deactivateTaxonomies']);
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
     * Initialize taxonomies
     */
    private function initializeTaxonomies(): void {
        // Load taxonomy configuration
        $this->loadTaxonomyConfig();
        
        // Create taxonomy instances
        $this->taxonomies = [
            'genre' => new Genre(),
            'country' => new Country(),
            'language' => new Language(),
            'network' => new Network(),
            'production-company' => new ProductionCompany(),
            'by-year' => new ByYear(),
            'profession' => new Profession(),
            'nationality' => new Nationality(),
        ];
    }
    
    /**
     * Load taxonomy configuration
     */
    private function loadTaxonomyConfig(): void {
        $config_file = TMU_INCLUDES_DIR . '/config/taxonomies.php';
        
        if (file_exists($config_file)) {
            $this->config = include $config_file;
        }
        
        // Set default configuration if not loaded
        if (empty($this->config)) {
            $this->config = [
                'genre' => ['enabled' => true],
                'country' => ['enabled' => true],
                'language' => ['enabled' => true],
                'network' => ['enabled' => true],
                'production-company' => ['enabled' => true],
                'by-year' => ['enabled' => true],
                'profession' => ['enabled' => true],
                'nationality' => ['enabled' => true],
            ];
        }
    }
    
    /**
     * Add WordPress hooks
     */
    private function addHooks(): void {
        // Register taxonomies on init with priority 5 (before post types at priority 10)
        add_action('init', [$this, 'registerTaxonomies'], 5);
        
        // Only flush rewrite rules when needed, not on every admin_init
        add_action('admin_init', [$this, 'maybeFlushRewriteRules']);
        
        add_action('wp_loaded', [$this, 'afterTaxonomiesRegistered']);
    }
    
    /**
     * Maybe flush rewrite rules if needed
     */
    public function maybeFlushRewriteRules(): void {
        if (get_option('tmu_taxonomies_rewrite_flush') === '1') {
            flush_rewrite_rules();
            delete_option('tmu_taxonomies_rewrite_flush');
        }
    }
    
    /**
     * Register all taxonomies
     */
    public function registerTaxonomies(): void {
        // Load taxonomy configuration
        $this->loadTaxonomyConfig();
        
        tmu_log("Starting taxonomy registration", 'debug');
        
        // Force enable all taxonomies for debugging
        $this->config = [
            'genre' => ['enabled' => true],
            'country' => ['enabled' => true],
            'language' => ['enabled' => true],
            'network' => ['enabled' => true],
            'production-company' => ['enabled' => true],
            'by-year' => ['enabled' => true],
            'profession' => ['enabled' => true],
            'nationality' => ['enabled' => true],
        ];
        
        // Store taxonomies that were registered
        $registered = [];
        
        foreach ($this->taxonomies as $taxonomy_slug => $taxonomy) {
            tmu_log("Registering taxonomy: {$taxonomy_slug}", 'debug');
            $taxonomy->register();
            
            // Check if registration was successful
            if (taxonomy_exists($taxonomy_slug)) {
                $registered[] = $taxonomy_slug;
                tmu_log("Successfully registered taxonomy: {$taxonomy_slug}", 'info');
            } else {
                tmu_log("Failed to register taxonomy: {$taxonomy_slug} - trying again directly", 'error');
                
                // Try direct registration as fallback
                $object_types = $taxonomy->getObjectTypes();
                $args = $taxonomy->getArgs();
                
                if (!empty($args)) {
                    register_taxonomy($taxonomy_slug, $object_types, $args);
                    
                    if (taxonomy_exists($taxonomy_slug)) {
                        $registered[] = $taxonomy_slug;
                        tmu_log("Successfully registered taxonomy {$taxonomy_slug} directly", 'info');
                    } else {
                        tmu_log("Failed to register taxonomy {$taxonomy_slug} even with direct registration", 'error');
                    }
                }
            }
        }
        
        // Log registered taxonomies
        if (function_exists('tmu_log')) {
            tmu_log('Registered taxonomies: ' . implode(', ', $registered), 'debug');
        }
        
        // Set flag to flush rewrite rules
        update_option('tmu_taxonomies_rewrite_flush', '1');
        
        // Debug: Check if taxonomies are registered
        global $wp_taxonomies;
        if (isset($wp_taxonomies) && is_array($wp_taxonomies)) {
            $registered_taxonomies = array_keys($wp_taxonomies);
            tmu_log("Registered taxonomies in WP: " . implode(', ', $registered_taxonomies), 'debug');
            
            // Check our expected taxonomies
            foreach ($this->taxonomies as $taxonomy_slug => $taxonomy) {
                if (in_array($taxonomy_slug, $registered_taxonomies)) {
                    tmu_log("Taxonomy {$taxonomy_slug} is registered in WordPress", 'debug');
                } else {
                    tmu_log("Taxonomy {$taxonomy_slug} is NOT registered in WordPress", 'error');
                }
            }
        } else {
            tmu_log("No taxonomies registered in WordPress", 'error');
        }
    }
    
    /**
     * Flush rewrite rules when needed
     */
    public function flushRewriteRules(): void {
        // First, register all taxonomies
        $this->registerTaxonomies();
        
        // Then flush rewrite rules
        flush_rewrite_rules();
        
        // Store activation timestamp
        update_option('tmu_taxonomies_activated', time());
    }
    
    /**
     * Deactivate taxonomies on theme switch
     */
    public function deactivateTaxonomies(): void {
        // Store deactivation timestamp
        update_option('tmu_taxonomies_deactivated', time());
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
    public function __wakeup() {}
}