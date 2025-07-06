# Step 06: Taxonomies Registration

## Purpose
Implement a modern, object-oriented taxonomy registration system that replicates all TMU plugin taxonomies with enhanced organization, conditional registration, and WordPress best practices.

## Overview
This step converts the plugin's taxonomy registration from procedural functions to a clean OOP architecture, preserving all existing taxonomy functionality while adding improved management and extensibility.

## Analysis from Plugin Taxonomies

### Universal Taxonomies (Apply to Multiple Post Types)
- `genre` - Content genres (movie, tv, drama)
- `country` - Countries (tv, movie, drama)  
- `language` - Languages (tv, movie, drama)
- `by-year` - Release years (movie, tv, drama)

### Specific Taxonomies
- `network` - TV networks (tv only) - conditional on tv_series enabled
- `channel` - TV channels (drama only) - conditional on dramas enabled
- `keyword` - Keywords (tv, movie) - excluded when dramas enabled
- `nationality` - People nationalities (people only)

### Taxonomy Features Analysis
- All use `post_tags_meta_box` style (non-hierarchical)
- Support REST API (`show_in_rest: true`)
- Custom rewrite rules (`with_front: false`)
- Admin columns disabled by default
- Tag cloud support enabled

## Architecture Implementation

### Directory Structure
```
includes/classes/Taxonomies/
├── TaxonomyManager.php      # Main manager class
├── AbstractTaxonomy.php     # Base taxonomy class
├── Types/                   # Individual taxonomy classes
│   ├── Genre.php           # Genre taxonomy
│   ├── Country.php         # Country taxonomy  
│   ├── Language.php        # Language taxonomy
│   ├── ByYear.php          # Release year taxonomy
│   ├── Network.php         # TV network taxonomy
│   ├── Channel.php         # TV channel taxonomy
│   ├── Keyword.php         # Keywords taxonomy
│   └── Nationality.php     # People nationality taxonomy
└── TermManager.php         # Term management utilities
```

## Core Implementation

### 1. Taxonomy Manager (`TaxonomyManager.php`)
```php
<?php
namespace TMU\Taxonomies;

use TMU\Config\ThemeConfig;

class TaxonomyManager {
    private static $instance = null;
    private $taxonomies = [];
    private $config;
    
    public static function getInstance(): TaxonomyManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->config = ThemeConfig::getInstance();
        $this->initHooks();
        $this->registerTaxonomies();
    }
    
    private function initHooks(): void {
        add_action('init', [$this, 'registerAllTaxonomies'], 5);
        add_action('admin_init', [$this, 'addTaxonomyColumns']);
        add_filter('manage_edit-genre_columns', [$this, 'addCustomColumns']);
        add_filter('manage_genre_custom_column', [$this, 'renderCustomColumns'], 10, 3);
    }
    
    private function registerTaxonomies(): void {
        $this->taxonomies = [
            'genre' => new Types\Genre(),
            'country' => new Types\Country(),
            'language' => new Types\Language(),
            'by-year' => new Types\ByYear(),
            'network' => new Types\Network(),
            'channel' => new Types\Channel(),
            'keyword' => new Types\Keyword(),
            'nationality' => new Types\Nationality(),
        ];
    }
    
    public function registerAllTaxonomies(): void {
        foreach ($this->taxonomies as $taxonomy) {
            $taxonomy->register();
        }
    }
    
    public function addTaxonomyColumns(): void {
        foreach ($this->taxonomies as $taxonomy) {
            if (method_exists($taxonomy, 'addAdminColumns')) {
                $taxonomy->addAdminColumns();
            }
        }
    }
    
    public function getTaxonomy(string $name): ?AbstractTaxonomy {
        return $this->taxonomies[$name] ?? null;
    }
    
    public function getAllTaxonomies(): array {
        return $this->taxonomies;
    }
}
```

### 2. Abstract Taxonomy Base Class (`AbstractTaxonomy.php`)
```php
<?php
namespace TMU\Taxonomies;

use TMU\Config\ThemeConfig;

abstract class AbstractTaxonomy {
    protected $taxonomy;
    protected $post_types = [];
    protected $labels = [];
    protected $args = [];
    protected $config;
    
    abstract protected function getLabels(): array;
    abstract protected function getPostTypes(): array;
    abstract protected function getArgs(): array;
    
    public function __construct() {
        $this->config = ThemeConfig::getInstance();
    }
    
    public function register(): void {
        if ($this->shouldRegister()) {
            $this->labels = $this->getLabels();
            $this->post_types = $this->getPostTypes();
            $this->args = $this->getArgs();
            
            register_taxonomy($this->taxonomy, $this->post_types, array_merge($this->args, [
                'labels' => $this->labels,
            ]));
            
            $this->afterRegister();
        }
    }
    
    protected function shouldRegister(): bool {
        return true; // Override in child classes for conditional registration
    }
    
    protected function afterRegister(): void {
        // Hook for additional setup after registration
    }
    
    protected function getDefaultArgs(): array {
        return [
            'public' => true,
            'publicly_queryable' => true,
            'hierarchical' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'show_tagcloud' => true,
            'show_in_quick_edit' => true,
            'show_admin_column' => false,
            'query_var' => true,
            'sort' => false,
            'meta_box_cb' => 'post_tags_meta_box',
            'rest_base' => '',
            'rewrite' => [
                'with_front' => false,
                'hierarchical' => false,
            ],
        ];
    }
    
    protected function getDefaultLabels(string $singular, string $plural): array {
        return [
            'name' => $plural,
            'singular_name' => $singular,
            'menu_name' => $plural,
            'search_items' => sprintf(__('Search %s', 'tmu'), $plural),
            'popular_items' => sprintf(__('Popular %s', 'tmu'), $plural),
            'all_items' => sprintf(__('All %s', 'tmu'), $plural),
            'parent_item' => sprintf(__('Parent %s', 'tmu'), $singular),
            'parent_item_colon' => sprintf(__('Parent %s:', 'tmu'), $singular),
            'edit_item' => sprintf(__('Edit %s', 'tmu'), $singular),
            'view_item' => sprintf(__('View %s', 'tmu'), $singular),
            'update_item' => sprintf(__('Update %s', 'tmu'), $singular),
            'add_new_item' => sprintf(__('Add New %s', 'tmu'), $singular),
            'new_item_name' => sprintf(__('New %s Name', 'tmu'), $singular),
            'separate_items_with_commas' => sprintf(__('Separate %s with commas', 'tmu'), strtolower($plural)),
            'add_or_remove_items' => sprintf(__('Add or remove %s', 'tmu'), strtolower($plural)),
            'choose_from_most_used' => sprintf(__('Choose most used %s', 'tmu'), strtolower($plural)),
            'not_found' => sprintf(__('No %s found.', 'tmu'), strtolower($plural)),
            'no_terms' => sprintf(__('No %s', 'tmu'), strtolower($plural)),
            'filter_by_item' => sprintf(__('Filter by %s', 'tmu'), strtolower($singular)),
            'items_list_navigation' => sprintf(__('%s list pagination', 'tmu'), $plural),
            'items_list' => sprintf(__('%s list', 'tmu'), $plural),
            'most_used' => __('Most Used', 'tmu'),
            'back_to_items' => sprintf(__('&larr; Go to %s', 'tmu'), $plural),
        ];
    }
    
    public function addAdminColumns(): void {
        // Override in child classes if needed
    }
    
    public function getTaxonomyName(): string {
        return $this->taxonomy;
    }
    
    public function getAssignedPostTypes(): array {
        return $this->post_types;
    }
}
```

### 3. Genre Taxonomy (`Types/Genre.php`)
```php
<?php
namespace TMU\Taxonomies\Types;

use TMU\Taxonomies\AbstractTaxonomy;

class Genre extends AbstractTaxonomy {
    protected $taxonomy = 'genre';
    
    protected function getLabels(): array {
        return $this->getDefaultLabels(__('Genre', 'tmu'), __('Genres', 'tmu'));
    }
    
    protected function getPostTypes(): array {
        $post_types = [];
        
        if ($this->config->isFeatureEnabled('movies')) {
            $post_types[] = 'movie';
        }
        
        if ($this->config->isFeatureEnabled('tv_series')) {
            $post_types[] = 'tv';
        }
        
        if ($this->config->isFeatureEnabled('dramas')) {
            $post_types[] = 'drama';
        }
        
        return $post_types;
    }
    
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('Content genres for movies, TV shows, and dramas', 'tmu'),
            'show_admin_column' => true, // Show in post list
        ]);
    }
    
    protected function shouldRegister(): bool {
        // Register if any content type is enabled
        return $this->config->isFeatureEnabled('movies') || 
               $this->config->isFeatureEnabled('tv_series') || 
               $this->config->isFeatureEnabled('dramas');
    }
    
    protected function afterRegister(): void {
        // Add popular genres for new installs
        add_action('admin_init', [$this, 'seedPopularGenres']);
    }
    
    public function seedPopularGenres(): void {
        if (get_option('tmu_genres_seeded')) {
            return;
        }
        
        $popular_genres = [
            'Action', 'Adventure', 'Animation', 'Comedy', 'Crime',
            'Documentary', 'Drama', 'Family', 'Fantasy', 'History',
            'Horror', 'Music', 'Mystery', 'Romance', 'Science Fiction',
            'Thriller', 'War', 'Western'
        ];
        
        foreach ($popular_genres as $genre) {
            if (!term_exists($genre, 'genre')) {
                wp_insert_term($genre, 'genre');
            }
        }
        
        update_option('tmu_genres_seeded', true);
    }
}
```

### 4. Network Taxonomy (`Types/Network.php`)
```php
<?php
namespace TMU\Taxonomies\Types;

use TMU\Taxonomies\AbstractTaxonomy;

class Network extends AbstractTaxonomy {
    protected $taxonomy = 'network';
    
    protected function getLabels(): array {
        return $this->getDefaultLabels(__('Network', 'tmu'), __('Networks', 'tmu'));
    }
    
    protected function getPostTypes(): array {
        return ['tv']; // Only for TV shows
    }
    
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('TV networks and streaming platforms', 'tmu'),
            'show_admin_column' => true,
        ]);
    }
    
    protected function shouldRegister(): bool {
        return $this->config->isFeatureEnabled('tv_series');
    }
    
    protected function afterRegister(): void {
        add_action('admin_init', [$this, 'seedPopularNetworks']);
    }
    
    public function seedPopularNetworks(): void {
        if (get_option('tmu_networks_seeded')) {
            return;
        }
        
        $popular_networks = [
            'Netflix', 'HBO', 'Amazon Prime', 'Disney+', 'Hulu',
            'ABC', 'CBS', 'NBC', 'FOX', 'The CW',
            'BBC', 'ITV', 'Channel 4', 'Sky',
            'AMC', 'FX', 'Showtime', 'Starz'
        ];
        
        foreach ($popular_networks as $network) {
            if (!term_exists($network, 'network')) {
                wp_insert_term($network, 'network');
            }
        }
        
        update_option('tmu_networks_seeded', true);
    }
}
```

### 5. Channel Taxonomy (`Types/Channel.php`)
```php
<?php
namespace TMU\Taxonomies\Types;

use TMU\Taxonomies\AbstractTaxonomy;

class Channel extends AbstractTaxonomy {
    protected $taxonomy = 'channel';
    
    protected function getLabels(): array {
        return $this->getDefaultLabels(__('Channel', 'tmu'), __('Channels', 'tmu'));
    }
    
    protected function getPostTypes(): array {
        return ['drama']; // Only for dramas
    }
    
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('TV channels for drama series', 'tmu'),
            'show_admin_column' => true,
        ]);
    }
    
    protected function shouldRegister(): bool {
        return $this->config->isFeatureEnabled('dramas');
    }
}
```

### 6. Keyword Taxonomy (`Types/Keyword.php`)
```php
<?php
namespace TMU\Taxonomies\Types;

use TMU\Taxonomies\AbstractTaxonomy;

class Keyword extends AbstractTaxonomy {
    protected $taxonomy = 'keyword';
    
    protected function getLabels(): array {
        return $this->getDefaultLabels(__('Keyword', 'tmu'), __('Keywords', 'tmu'));
    }
    
    protected function getPostTypes(): array {
        $post_types = [];
        
        if ($this->config->isFeatureEnabled('tv_series')) {
            $post_types[] = 'tv';
        }
        
        if ($this->config->isFeatureEnabled('movies')) {
            $post_types[] = 'movie';
        }
        
        return $post_types;
    }
    
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('Keywords and tags for movies and TV shows', 'tmu'),
        ]);
    }
    
    protected function shouldRegister(): bool {
        // Register if movies or TV series enabled, but not if dramas is the only one
        $has_movies = $this->config->isFeatureEnabled('movies');
        $has_tv = $this->config->isFeatureEnabled('tv_series');
        $has_dramas = $this->config->isFeatureEnabled('dramas');
        
        // Don't register if only dramas is enabled
        if ($has_dramas && !$has_movies && !$has_tv) {
            return false;
        }
        
        return $has_movies || $has_tv;
    }
}
```

### 7. By Year Taxonomy (`Types/ByYear.php`)
```php
<?php
namespace TMU\Taxonomies\Types;

use TMU\Taxonomies\AbstractTaxonomy;

class ByYear extends AbstractTaxonomy {
    protected $taxonomy = 'by-year';
    
    protected function getLabels(): array {
        return $this->getDefaultLabels(__('Year', 'tmu'), __('Years', 'tmu'));
    }
    
    protected function getPostTypes(): array {
        $post_types = [];
        
        if ($this->config->isFeatureEnabled('movies')) {
            $post_types[] = 'movie';
        }
        
        if ($this->config->isFeatureEnabled('tv_series')) {
            $post_types[] = 'tv';
        }
        
        if ($this->config->isFeatureEnabled('dramas')) {
            $post_types[] = 'drama';
        }
        
        return $post_types;
    }
    
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('Release years for content', 'tmu'),
            'show_admin_column' => true,
            'rewrite' => [
                'slug' => 'year',
                'with_front' => false,
                'hierarchical' => false,
            ],
        ]);
    }
    
    protected function shouldRegister(): bool {
        return $this->config->isFeatureEnabled('movies') || 
               $this->config->isFeatureEnabled('tv_series') || 
               $this->config->isFeatureEnabled('dramas');
    }
    
    protected function afterRegister(): void {
        add_action('save_post', [$this, 'autoAssignYear'], 10, 2);
    }
    
    public function autoAssignYear(int $post_id, \WP_Post $post): void {
        // Auto-assign year based on release date meta
        if (!in_array($post->post_type, ['movie', 'tv', 'drama'])) {
            return;
        }
        
        $release_date = get_post_meta($post_id, 'release_date', true);
        if ($release_date) {
            $year = date('Y', strtotime($release_date));
            
            if (!term_exists($year, 'by-year')) {
                wp_insert_term($year, 'by-year');
            }
            
            wp_set_post_terms($post_id, [$year], 'by-year');
        }
    }
}
```

### 8. Country Taxonomy (`Types/Country.php`)
```php
<?php
namespace TMU\Taxonomies\Types;

use TMU\Taxonomies\AbstractTaxonomy;

class Country extends AbstractTaxonomy {
    protected $taxonomy = 'country';
    
    protected function getLabels(): array {
        return $this->getDefaultLabels(__('Country', 'tmu'), __('Countries', 'tmu'));
    }
    
    protected function getPostTypes(): array {
        $post_types = [];
        
        if ($this->config->isFeatureEnabled('tv_series')) {
            $post_types[] = 'tv';
        }
        
        if ($this->config->isFeatureEnabled('movies')) {
            $post_types[] = 'movie';
        }
        
        if ($this->config->isFeatureEnabled('dramas')) {
            $post_types[] = 'drama';
        }
        
        return $post_types;
    }
    
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('Countries of origin for content', 'tmu'),
            'show_admin_column' => true,
        ]);
    }
    
    protected function shouldRegister(): bool {
        return $this->config->isFeatureEnabled('movies') || 
               $this->config->isFeatureEnabled('tv_series') || 
               $this->config->isFeatureEnabled('dramas');
    }
    
    protected function afterRegister(): void {
        add_action('admin_init', [$this, 'seedPopularCountries']);
    }
    
    public function seedPopularCountries(): void {
        if (get_option('tmu_countries_seeded')) {
            return;
        }
        
        $popular_countries = [
            'United States', 'United Kingdom', 'Canada', 'Australia',
            'France', 'Germany', 'Italy', 'Spain', 'Japan', 'South Korea',
            'China', 'India', 'Brazil', 'Mexico', 'Russia', 'Netherlands',
            'Sweden', 'Norway', 'Denmark', 'Finland'
        ];
        
        foreach ($popular_countries as $country) {
            if (!term_exists($country, 'country')) {
                wp_insert_term($country, 'country');
            }
        }
        
        update_option('tmu_countries_seeded', true);
    }
}
```

## Taxonomy Configuration

### Configuration File (`includes/config/taxonomies.php`)
```php
<?php
/**
 * TMU Taxonomies Configuration
 */

return [
    'genre' => [
        'post_types' => ['movie', 'tv', 'drama'],
        'conditional' => ['movies', 'tv_series', 'dramas'],
        'show_admin_column' => true,
        'seed_terms' => true,
    ],
    'country' => [
        'post_types' => ['movie', 'tv', 'drama'],
        'conditional' => ['movies', 'tv_series', 'dramas'],
        'show_admin_column' => true,
        'seed_terms' => true,
    ],
    'language' => [
        'post_types' => ['movie', 'tv', 'drama'],
        'conditional' => ['movies', 'tv_series', 'dramas'],
        'show_admin_column' => false,
        'seed_terms' => true,
    ],
    'by-year' => [
        'post_types' => ['movie', 'tv', 'drama'],
        'conditional' => ['movies', 'tv_series', 'dramas'],
        'show_admin_column' => true,
        'auto_assign' => true,
    ],
    'network' => [
        'post_types' => ['tv'],
        'conditional' => ['tv_series'],
        'show_admin_column' => true,
        'seed_terms' => true,
    ],
    'channel' => [
        'post_types' => ['drama'],
        'conditional' => ['dramas'],
        'show_admin_column' => true,
    ],
    'keyword' => [
        'post_types' => ['movie', 'tv'],
        'conditional' => ['movies', 'tv_series'],
        'exclude_when' => ['dramas_only'],
    ],
    'nationality' => [
        'post_types' => ['people'],
        'conditional' => [],
        'show_admin_column' => true,
    ],
];
```

## Admin Interface Enhancements

### Custom Admin Columns (`includes/classes/Admin/TaxonomyColumns.php`)
```php
<?php
namespace TMU\Admin;

class TaxonomyColumns {
    
    public function init(): void {
        add_filter('manage_edit-genre_columns', [$this, 'addGenreColumns']);
        add_filter('manage_genre_custom_column', [$this, 'renderGenreColumns'], 10, 3);
        
        add_filter('manage_edit-country_columns', [$this, 'addCountryColumns']);
        add_filter('manage_country_custom_column', [$this, 'renderCountryColumns'], 10, 3);
    }
    
    public function addGenreColumns(array $columns): array {
        $new_columns = [];
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'name') {
                $new_columns['content_count'] = __('Content Count', 'tmu');
                $new_columns['post_types'] = __('Post Types', 'tmu');
            }
        }
        
        return $new_columns;
    }
    
    public function renderGenreColumns(string $content, string $column_name, int $term_id): string {
        switch ($column_name) {
            case 'content_count':
                $term = get_term($term_id, 'genre');
                return $this->getContentCountByType($term);
                
            case 'post_types':
                return $this->getAssignedPostTypes('genre');
        }
        
        return $content;
    }
    
    private function getContentCountByType(\WP_Term $term): string {
        $counts = [];
        $post_types = ['movie', 'tv', 'drama'];
        
        foreach ($post_types as $post_type) {
            if (post_type_exists($post_type)) {
                $count = wp_count_posts($post_type);
                $published = $count->publish ?? 0;
                
                if ($published > 0) {
                    $counts[] = sprintf('%s: %d', ucfirst($post_type), $published);
                }
            }
        }
        
        return implode('<br>', $counts);
    }
    
    private function getAssignedPostTypes(string $taxonomy): string {
        $taxonomy_object = get_taxonomy($taxonomy);
        $post_types = $taxonomy_object->object_type ?? [];
        
        return implode(', ', array_map('ucfirst', $post_types));
    }
}
```

## URL Rewrite Customization

### Custom Rewrite Rules (`includes/classes/Frontend/TaxonomyRewrite.php`)
```php
<?php
namespace TMU\Frontend;

class TaxonomyRewrite {
    
    public function init(): void {
        add_action('init', [$this, 'addRewriteRules']);
        add_filter('term_link', [$this, 'customTermLinks'], 10, 3);
    }
    
    public function addRewriteRules(): void {
        // Custom URL structure for year taxonomy
        add_rewrite_rule(
            '^year/([0-9]{4})/?$',
            'index.php?by-year=$matches[1]',
            'top'
        );
        
        // Custom URLs for genre with post type
        add_rewrite_rule(
            '^movies/genre/([^/]+)/?$',
            'index.php?post_type=movie&genre=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^tv-shows/genre/([^/]+)/?$',
            'index.php?post_type=tv&genre=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^dramas/genre/([^/]+)/?$',
            'index.php?post_type=drama&genre=$matches[1]',
            'top'
        );
    }
    
    public function customTermLinks(string $termlink, \WP_Term $term, string $taxonomy): string {
        switch ($taxonomy) {
            case 'by-year':
                return home_url("/year/{$term->slug}/");
                
            case 'genre':
                // Context-aware genre links
                global $post;
                if ($post && in_array($post->post_type, ['movie', 'tv', 'drama'])) {
                    $post_type_slug = $post->post_type === 'tv' ? 'tv-shows' : $post->post_type . 's';
                    return home_url("/{$post_type_slug}/genre/{$term->slug}/");
                }
                break;
        }
        
        return $termlink;
    }
}
```

## Testing Framework

### Taxonomy Test (`tests/TaxonomiesTest.php`)
```php
<?php
namespace TMU\Tests;

use PHPUnit\Framework\TestCase;
use TMU\Taxonomies\TaxonomyManager;

class TaxonomiesTest extends TestCase {
    
    public function testTaxonomyRegistration(): void {
        // Enable features
        update_option('tmu_movies', 'on');
        update_option('tmu_tv_series', 'on');
        update_option('tmu_dramas', 'on');
        
        $manager = TaxonomyManager::getInstance();
        $manager->registerAllTaxonomies();
        
        // Verify taxonomies are registered
        $this->assertTrue(taxonomy_exists('genre'));
        $this->assertTrue(taxonomy_exists('country'));
        $this->assertTrue(taxonomy_exists('language'));
        $this->assertTrue(taxonomy_exists('by-year'));
        $this->assertTrue(taxonomy_exists('network'));
        $this->assertTrue(taxonomy_exists('channel'));
        $this->assertTrue(taxonomy_exists('nationality'));
    }
    
    public function testConditionalRegistration(): void {
        // Disable all features
        update_option('tmu_movies', 'off');
        update_option('tmu_tv_series', 'off');
        update_option('tmu_dramas', 'off');
        
        $manager = TaxonomyManager::getInstance();
        $manager->registerAllTaxonomies();
        
        // Verify conditional taxonomies are not registered
        $this->assertFalse(taxonomy_exists('genre'));
        $this->assertFalse(taxonomy_exists('network'));
        $this->assertFalse(taxonomy_exists('channel'));
        
        // Nationality should still be registered (for people)
        $this->assertTrue(taxonomy_exists('nationality'));
    }
    
    public function testKeywordExclusion(): void {
        // Enable only dramas
        update_option('tmu_movies', 'off');
        update_option('tmu_tv_series', 'off');
        update_option('tmu_dramas', 'on');
        
        $manager = TaxonomyManager::getInstance();
        $manager->registerAllTaxonomies();
        
        // Keywords should not be registered when only dramas is enabled
        $this->assertFalse(taxonomy_exists('keyword'));
    }
}
```

## Integration with Theme Core

### Loading in ThemeCore
```php
// In the loadDependencies method:
require_once TMU_INCLUDES_DIR . '/classes/Taxonomies/TaxonomyManager.php';

// In the initTheme method:
Taxonomies\TaxonomyManager::getInstance();
```

## Migration from Plugin

### Taxonomy Data Preservation
```php
<?php
namespace TMU\Migration;

class TaxonomyMigrator {
    
    public function migrateTaxonomyData(): void {
        $plugin_taxonomies = [
            'genre', 'country', 'language', 'by-year',
            'network', 'channel', 'keyword', 'nationality'
        ];
        
        foreach ($plugin_taxonomies as $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                $this->verifyTaxonomyIntegrity($taxonomy);
            }
        }
    }
    
    private function verifyTaxonomyIntegrity(string $taxonomy): void {
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ]);
        
        tmu_log("Found " . count($terms) . " terms in {$taxonomy} taxonomy");
        
        // Verify term-post relationships
        foreach ($terms as $term) {
            $posts = get_posts([
                'post_type' => 'any',
                'tax_query' => [
                    [
                        'taxonomy' => $taxonomy,
                        'field' => 'term_id',
                        'terms' => $term->term_id,
                    ],
                ],
                'fields' => 'ids',
            ]);
            
            if (!empty($posts)) {
                tmu_log("Term {$term->name} in {$taxonomy} has " . count($posts) . " associated posts");
            }
        }
    }
}
```

## Next Steps

1. **[Step 07: Custom Fields System](./07_custom-fields-system.md)** - Implement meta fields system
2. **[Step 08: Admin UI and Meta Boxes](./08_admin-ui-and-meta-boxes.md)** - Create admin interface
3. **[Step 09: TMDB API Integration](./09_tmdb-api-integration.md)** - API integration system

## Verification Checklist

- [ ] TaxonomyManager class implemented
- [ ] AbstractTaxonomy base class created
- [ ] All taxonomy types implemented
- [ ] Conditional registration working
- [ ] Admin columns functional
- [ ] URL rewrite rules configured
- [ ] Term seeding operational
- [ ] Testing framework operational
- [ ] Migration system ready
- [ ] Integration with theme core complete

---

This modern taxonomy system provides enhanced organization, conditional logic, and maintainability while preserving all original plugin functionality and data relationships.