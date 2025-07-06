# Step 06: Taxonomies Registration

## Purpose
Implement a modern, object-oriented taxonomy registration system that replicates all TMU plugin taxonomies with enhanced organization, conditional registration, and WordPress best practices.

## Overview
This step converts the plugin's taxonomy registration from procedural functions to a clean OOP architecture, preserving all existing taxonomy functionality while adding improved management and extensibility.

## Dependencies from Previous Steps
- **[REQUIRED]** Post types registration [FROM STEP 5] - Taxonomies attach to post types
- **[REQUIRED]** PSR-4 autoloading [FROM STEP 4] - Class autoloading system
- **[REQUIRED]** `includes/classes/ThemeCore.php` [FROM STEP 1] - Theme initialization
- **[REQUIRED]** Theme options system [FROM STEP 2] - Conditional registration
- **[REQUIRED]** `TMU\Config\ThemeConfig` class [FROM STEP 7] - Configuration management

## Files Created in This Step
- **[CREATE NEW]** `includes/classes/Taxonomies/TaxonomyManager.php` - Main manager class
- **[CREATE NEW]** `includes/classes/Taxonomies/AbstractTaxonomy.php` - Base taxonomy class
- **[CREATE NEW]** `includes/classes/Taxonomies/Types/Genre.php` - Genre taxonomy
- **[CREATE NEW]** `includes/classes/Taxonomies/Types/Country.php` - Country taxonomy
- **[CREATE NEW]** `includes/classes/Taxonomies/Types/Language.php` - Language taxonomy
- **[CREATE NEW]** `includes/classes/Taxonomies/Types/ByYear.php` - Release year taxonomy
- **[CREATE NEW]** `includes/classes/Taxonomies/Types/Network.php` - TV network taxonomy
- **[CREATE NEW]** `includes/classes/Taxonomies/Types/Channel.php` - TV channel taxonomy
- **[CREATE NEW]** `includes/classes/Taxonomies/Types/Keyword.php` - Keywords taxonomy
- **[CREATE NEW]** `includes/classes/Taxonomies/Types/Nationality.php` - People nationality taxonomy
- **[CREATE NEW]** `includes/classes/Taxonomies/TermManager.php` - Term management utilities
- **[CREATE NEW]** `tests/TaxonomiesTest.php` - Taxonomies testing

## Tailwind CSS Status
**NOT APPLICABLE** - This step focuses on PHP taxonomy registration and doesn't involve CSS

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

### Directory Structure with File Status
```
includes/classes/Taxonomies/                     # [CREATE DIR - STEP 6] Taxonomy classes directory
├── TaxonomyManager.php      # [CREATE NEW - STEP 6] Main manager class - Orchestrates all taxonomies
├── AbstractTaxonomy.php     # [CREATE NEW - STEP 6] Base taxonomy class - Shared functionality
├── Types/                   # [CREATE DIR - STEP 6] Individual taxonomy classes directory
│   ├── Genre.php           # [CREATE NEW - STEP 6] Genre taxonomy - Content genres
│   ├── Country.php         # [CREATE NEW - STEP 6] Country taxonomy - Content countries
│   ├── Language.php        # [CREATE NEW - STEP 6] Language taxonomy - Content languages
│   ├── ByYear.php          # [CREATE NEW - STEP 6] Release year taxonomy - Content years
│   ├── Network.php         # [CREATE NEW - STEP 6] TV network taxonomy - TV networks/platforms
│   ├── Channel.php         # [CREATE NEW - STEP 6] TV channel taxonomy - Drama channels
│   ├── Keyword.php         # [CREATE NEW - STEP 6] Keywords taxonomy - Content keywords
│   └── Nationality.php     # [CREATE NEW - STEP 6] People nationality taxonomy - People nations
└── TermManager.php         # [CREATE NEW - STEP 6] Term management utilities - Term operations

tests/                                           # [UPDATE DIR - STEP 6] Extend existing directory from Step 4
└── TaxonomiesTest.php       # [CREATE NEW - STEP 6] Taxonomies testing - Unit tests
```

### **Dependencies from Previous Steps:**
- **[REQUIRED]** Post types [FROM STEP 5] - Genre applies to movie, tv, drama
- **[REQUIRED]** `TMU\Config\ThemeConfig` [FROM STEP 7] - Feature enablement checks
- **[REQUIRED]** PSR-4 autoloading [FROM STEP 4] - Class loading
- **[REQUIRED]** Theme options [FROM STEP 2] - Conditional registration
- **[REQUIRED]** Helper functions [FROM STEP 4] - Translation and utilities

### **Files Created in Future Steps:**
- **`includes/classes/Admin/TaxonomyColumns.php`** - [CREATE NEW - STEP 8] Admin column management
- **`includes/classes/Frontend/TaxonomyArchives.php`** - [CREATE NEW - STEP 10] Frontend archives
- **`includes/classes/API/TaxonomyEndpoints.php`** - [CREATE NEW - STEP 9] REST API endpoints

## Core Implementation

### 1. Taxonomy Manager (`TaxonomyManager.php`)
**File Status**: [CREATE NEW - STEP 6]
**File Path**: `tmu-theme/includes/classes/Taxonomies/TaxonomyManager.php`
**Purpose**: Main manager class that orchestrates all taxonomy registration and management
**Dependencies**: 
- [DEPENDS ON] Individual taxonomy classes [CREATE NEW - STEP 6] - Genre, Country, Language, etc.
- [DEPENDS ON] `TMU\Config\ThemeConfig` [FROM STEP 7] - Feature enablement checks
- [DEPENDS ON] WordPress `register_taxonomy()` function - Core registration
- [DEPENDS ON] PSR-4 autoloading [FROM STEP 4] - Class loading
**Integration**: Central management of all TMU taxonomies
**Used By**: 
- `includes/classes/ThemeCore.php` [FROM STEP 1] - Theme initialization
- Admin interfaces [CREATE NEW - STEP 8] - Taxonomy management
- Frontend archives [CREATE NEW - STEP 10] - Taxonomy display
**Features**: 
- Singleton pattern for single instance
- Conditional taxonomy registration based on enabled post types
- Admin column management
- Custom taxonomy functionality
**AI Action**: Create singleton manager class that handles all taxonomy registration

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
**File Status**: [CREATE NEW - STEP 6]
**File Path**: `tmu-theme/includes/classes/Taxonomies/AbstractTaxonomy.php`
**Purpose**: Base abstract class providing shared functionality for all taxonomy implementations
**Dependencies**: 
- [DEPENDS ON] `TMU\Config\ThemeConfig` [FROM STEP 7] - Configuration management
- [DEPENDS ON] WordPress `register_taxonomy()` function - Core registration
- [DEPENDS ON] WordPress translation functions - Label localization
**Integration**: Base class for all TMU taxonomies
**Used By**: 
- `Genre.php` [CREATE NEW - STEP 6] - Genre taxonomy
- `Country.php` [CREATE NEW - STEP 6] - Country taxonomy
- `Language.php` [CREATE NEW - STEP 6] - Language taxonomy
- `ByYear.php` [CREATE NEW - STEP 6] - Year taxonomy
- `Network.php` [CREATE NEW - STEP 6] - Network taxonomy
- `Channel.php` [CREATE NEW - STEP 6] - Channel taxonomy
- `Keyword.php` [CREATE NEW - STEP 6] - Keyword taxonomy
- `Nationality.php` [CREATE NEW - STEP 6] - Nationality taxonomy
**Features**: 
- Abstract methods for labels, post types, and args
- Default arguments for taxonomies
- Conditional registration logic
- Default label generation
- Shared functionality across all taxonomies
**AI Action**: Create abstract base class with shared taxonomy functionality

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
**File Status**: [CREATE NEW - STEP 6]
**File Path**: `tmu-theme/includes/classes/Taxonomies/Types/Genre.php`
**Purpose**: Genre taxonomy implementation for movies, TV shows, and dramas
**Dependencies**: 
- [EXTENDS] `AbstractTaxonomy.php` [CREATE NEW - STEP 6] - Base taxonomy functionality
- [DEPENDS ON] `TMU\Config\ThemeConfig` [FROM STEP 7] - Feature enablement checks
- [DEPENDS ON] Post types [FROM STEP 5] - movie, tv, drama post types
- [DEPENDS ON] WordPress taxonomy functions - wp_insert_term, term_exists
**Integration**: Universal genre taxonomy for all content types
**Used By**: 
- `TaxonomyManager.php` [CREATE NEW - STEP 6] - Registration management
- Movie/TV/Drama admin interfaces [CREATE NEW - STEP 8] - Content categorization
- Frontend archive pages [CREATE NEW - STEP 10] - Genre browsing
**Features**: 
- Multi-post-type support (movie, tv, drama)
- Conditional registration based on enabled post types
- Popular genre seeding for new installations
- Admin column display
- REST API support
**AI Action**: Create genre taxonomy class extending AbstractTaxonomy

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
**File Status**: [CREATE NEW - STEP 6]
**File Path**: `tmu-theme/includes/classes/Taxonomies/Types/Network.php`
**Purpose**: Network taxonomy implementation for TV shows and streaming platforms
**Dependencies**: 
- [EXTENDS] `AbstractTaxonomy.php` [CREATE NEW - STEP 6] - Base taxonomy functionality
- [DEPENDS ON] `TMU\Config\ThemeConfig` [FROM STEP 7] - Feature enablement checks
- [DEPENDS ON] TV show post type [FROM STEP 5] - tv post type only
- [DEPENDS ON] WordPress taxonomy functions - wp_insert_term, term_exists
**Integration**: TV show specific taxonomy for networks and platforms
**Used By**: 
- `TaxonomyManager.php` [CREATE NEW - STEP 6] - Registration management
- TV show admin interface [CREATE NEW - STEP 8] - Network assignment
- Frontend TV show pages [CREATE NEW - STEP 10] - Network display
**Features**: 
- TV show specific taxonomy
- Conditional registration based on TV series enablement
- Popular network seeding (Netflix, HBO, etc.)
- Admin column display
- Streaming platform support
**AI Action**: Create network taxonomy class for TV shows only

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

## AI Implementation Instructions for Step 6

### **Prerequisites Check**
Before implementing Step 6, verify these files exist from previous steps:
- **[REQUIRED]** Post types registration [FROM STEP 5] - Taxonomies attach to post types
- **[REQUIRED]** PSR-4 autoloading [FROM STEP 4] - Class autoloading system
- **[REQUIRED]** `includes/classes/ThemeCore.php` [FROM STEP 1] - Theme initialization
- **[REQUIRED]** Theme options system [FROM STEP 2] - Conditional registration
- **[REQUIRED]** `TMU\Config\ThemeConfig` class [FROM STEP 7] - Configuration management

### **Implementation Order for AI Models**

#### **Phase 1: Create Directories** (Required First)
```bash
mkdir -p tmu-theme/includes/classes/Taxonomies
mkdir -p tmu-theme/includes/classes/Taxonomies/Types
```

#### **Phase 2: Base Classes** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/Taxonomies/AbstractTaxonomy.php` - Base functionality
2. **[CREATE SECOND]** `includes/classes/Taxonomies/TaxonomyManager.php` - Manager class

#### **Phase 3: Universal Taxonomies** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/Taxonomies/Types/Genre.php` - Multi-post-type genre
2. **[CREATE SECOND]** `includes/classes/Taxonomies/Types/Country.php` - Multi-post-type country
3. **[CREATE THIRD]** `includes/classes/Taxonomies/Types/Language.php` - Multi-post-type language
4. **[CREATE FOURTH]** `includes/classes/Taxonomies/Types/ByYear.php` - Multi-post-type year

#### **Phase 4: Specific Taxonomies** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/Taxonomies/Types/Network.php` - TV-specific networks
2. **[CREATE SECOND]** `includes/classes/Taxonomies/Types/Channel.php` - Drama-specific channels
3. **[CREATE THIRD]** `includes/classes/Taxonomies/Types/Keyword.php` - Movie/TV keywords
4. **[CREATE FOURTH]** `includes/classes/Taxonomies/Types/Nationality.php` - People-specific

#### **Phase 5: Supporting Classes** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/Taxonomies/TermManager.php` - Term utilities
2. **[CREATE SECOND]** `includes/config/taxonomies.php` - Configuration file
3. **[CREATE THIRD]** `includes/classes/Admin/TaxonomyColumns.php` - Admin columns
4. **[CREATE FOURTH]** `includes/classes/Frontend/TaxonomyRewrite.php` - URL rewriting

#### **Phase 6: Testing** (Exact Order)
1. **[CREATE FIRST]** `tests/TaxonomiesTest.php` - Taxonomy tests

#### **Phase 7: Integration** (Final)
1. **[UPDATE]** `includes/classes/ThemeCore.php` - Include taxonomy manager

### **Key Implementation Notes**
- **Inheritance Structure**: AbstractTaxonomy must be created before individual taxonomies
- **Conditional Registration**: All taxonomies check post type enablement before registration
- **Multi-Post-Type Support**: Genre, Country, Language, ByYear support multiple post types
- **Specific Taxonomies**: Network (TV only), Channel (Drama only), Nationality (People only)
- **Keyword Logic**: Only registers when Movies or TV enabled, excludes Drama-only setups

### **Taxonomy Relationships Matrix**
```
Taxonomy     → Movie | TV | Drama | People
Genre        →   ✓   | ✓  |   ✓   |   ✗
Country      →   ✓   | ✓  |   ✓   |   ✗
Language     →   ✓   | ✓  |   ✓   |   ✗
ByYear       →   ✓   | ✓  |   ✓   |   ✗
Network      →   ✗   | ✓  |   ✗   |   ✗
Channel      →   ✗   | ✗  |   ✓   |   ✗
Keyword      →   ✓   | ✓  |   ✗   |   ✗
Nationality  →   ✗   | ✗  |   ✗   |   ✓
```

### **Critical Dependencies**
- **WordPress Functions**: `register_taxonomy()`, `wp_insert_term()`, `term_exists()`
- **Post Types**: Must be registered before taxonomies can attach
- **Theme Config**: Feature enablement checks for conditional registration
- **Autoloading**: PSR-4 autoloading for class loading

### **Conditional Registration Logic**
1. **Genre/Country/Language/ByYear**: Register if ANY content type (movie/tv/drama) enabled
2. **Network**: Register ONLY if TV series enabled
3. **Channel**: Register ONLY if dramas enabled
4. **Keyword**: Register if movie OR TV enabled, BUT NOT if only dramas enabled
5. **Nationality**: Always register (for people post type)

### **Testing Requirements**
1. **Registration Test** - Verify all taxonomies register correctly
2. **Conditional Test** - Verify settings-based registration
3. **Post Type Assignment** - Verify correct post type associations
4. **Term Seeding** - Verify popular terms are created
5. **URL Rewriting** - Verify custom permalink structures

### **Term Seeding Features**
- **Genre**: Popular movie/TV genres (Action, Comedy, Drama, etc.)
- **Country**: Major content-producing countries
- **Language**: Common content languages
- **Network**: Popular streaming platforms and networks
- **Auto-assignment**: ByYear automatically assigns based on release date

### **Integration Points**
- **ThemeCore.php** - Initializes TaxonomyManager
- **Post Types** - Taxonomies attach to registered post types
- **Admin Interface** - Custom columns and management
- **Frontend** - Archive pages and filtering
- **API Integration** - REST API support for all taxonomies

### **Common Issues and Solutions**
1. **Taxonomy Not Registered**: Check post type enablement and dependencies
2. **Terms Not Seeded**: Verify seeding flags and admin_init hooks
3. **Wrong Post Types**: Check conditional logic in getPostTypes()
4. **URL Issues**: Flush rewrite rules after registration

### **Verification Commands**
```bash
# Run taxonomy tests
composer test tests/TaxonomiesTest.php

# Check registered taxonomies in WordPress admin
# Go to WordPress admin and verify taxonomies appear correctly

# Test conditional registration
# Toggle post type options and verify taxonomies enable/disable

# Check term seeding
# Verify popular terms are created for each taxonomy
```

### **Post-Implementation Checklist**
- [ ] All taxonomy classes created
- [ ] TaxonomyManager implemented
- [ ] AbstractTaxonomy base class functional
- [ ] Conditional registration working correctly
- [ ] Term seeding functional
- [ ] Admin columns displaying
- [ ] URL rewriting active
- [ ] Tests passing
- [ ] ThemeCore integration complete

---

This modern taxonomy system provides enhanced organization, conditional logic, and maintainability while preserving all original plugin functionality and data relationships.

**Step 6 Status**: ✅ READY FOR AI IMPLEMENTATION
**Dependencies**: Steps 1-5 must be completed (Step 7 ThemeConfig recommended)
**Next Step**: Step 7 - Custom Fields System