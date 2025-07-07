# TMU Theme Developer Guide

## Overview
This guide provides comprehensive technical documentation for developers working with the TMU theme architecture, APIs, customization, and extension development.

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Development Environment](#development-environment)
3. [Code Standards](#code-standards)
4. [API Reference](#api-reference)
5. [Database Schema](#database-schema)
6. [Hooks and Filters](#hooks-and-filters)
7. [Block Development](#block-development)
8. [Extension Development](#extension-development)
9. [Testing Guidelines](#testing-guidelines)
10. [Performance Optimization](#performance-optimization)

## Architecture Overview

### Theme Structure
```
tmu-theme/
├── assets/
│   ├── src/                 # Source files
│   │   ├── css/            # Tailwind CSS
│   │   ├── js/             # JavaScript
│   │   └── images/         # Image assets
│   └── build/              # Compiled assets
├── includes/
│   ├── classes/            # PHP classes (PSR-4)
│   │   ├── Core/          # Core functionality
│   │   ├── PostTypes/     # Custom post types
│   │   ├── Taxonomies/    # Custom taxonomies
│   │   ├── Blocks/        # Gutenberg blocks
│   │   ├── API/           # TMDB API integration
│   │   ├── Admin/         # Admin interface
│   │   ├── Frontend/      # Frontend functionality
│   │   ├── Migration/     # Database migrations
│   │   ├── Performance/   # Performance optimization
│   │   ├── Security/      # Security features
│   │   └── Utils/         # Utility classes
│   ├── config/            # Configuration files
│   ├── helpers/           # Helper functions
│   └── templates/         # PHP templates
├── templates/             # Twig templates
├── migrations/           # Database migrations
├── tests/               # Test files
└── languages/           # Translation files
```

### Core Components

#### 1. Autoloading System
```php
// PSR-4 autoloading via Composer
"autoload": {
    "psr-4": {
        "TMU\\": "includes/classes/"
    }
}

// Manual class loading
require_once get_template_directory() . '/includes/classes/Core/ThemeCore.php';
```

#### 2. Namespace Structure
```php
TMU\                    # Root namespace
├── Core\              # Core functionality
├── PostTypes\         # Post type classes
├── Taxonomies\        # Taxonomy classes
├── Blocks\            # Gutenberg blocks
├── API\               # External APIs
├── Admin\             # Admin interface
├── Frontend\          # Frontend classes
├── Migration\         # Database operations
├── Performance\       # Performance classes
├── Security\          # Security classes
└── Utils\             # Utilities
```

#### 3. Theme Initialization
```php
// includes/classes/Core/ThemeCore.php
namespace TMU\Core;

class ThemeCore {
    public function __construct() {
        add_action('after_setup_theme', [$this, 'setup_theme']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('init', [$this, 'init_components']);
    }
    
    public function setup_theme(): void {
        // Theme supports
        add_theme_support('post-thumbnails');
        add_theme_support('custom-logo');
        add_theme_support('html5', ['comment-list', 'comment-form', 'search-form']);
        
        // Register menus
        register_nav_menus([
            'primary' => __('Primary Menu', 'tmu'),
            'footer' => __('Footer Menu', 'tmu')
        ]);
    }
    
    public function init_components(): void {
        // Initialize core components
        new \TMU\PostTypes\PostTypeManager();
        new \TMU\Taxonomies\TaxonomyManager();
        new \TMU\Blocks\BlockManager();
        new \TMU\API\TMDBManager();
    }
}
```

## Development Environment

### Prerequisites
```bash
# Required software
PHP >= 7.4
Node.js >= 16
Composer >= 2.0
WordPress >= 6.0
MySQL >= 5.7

# Development tools
npm install -g @wordpress/scripts
composer global require squizlabs/php_codesniffer
composer global require phpstan/phpstan
```

### Setup Commands
```bash
# Clone repository
git clone <repository-url> wp-content/themes/tmu
cd wp-content/themes/tmu

# Install dependencies
composer install
npm install

# Build assets for development
npm run dev

# Build assets for production
npm run build

# Run tests
npm test
composer test

# Run code quality checks
npm run lint
composer run quality
```

### Development Scripts
```json
// package.json scripts
{
  "dev": "webpack --mode=development --watch",
  "build": "webpack --mode=production",
  "test": "jest",
  "test:watch": "jest --watch",
  "lint": "eslint assets/src --ext .js,.jsx",
  "lint:fix": "eslint assets/src --ext .js,.jsx --fix"
}
```

```json
// composer.json scripts
{
  "test": "phpunit --configuration phpunit.xml",
  "cs-check": "phpcs --standard=WordPress includes/",
  "cs-fix": "phpcbf --standard=WordPress includes/",
  "analyze": "phpstan analyse includes/ --level=5"
}
```

## Code Standards

### PHP Standards
```php
<?php
/**
 * Class documentation following WordPress standards
 * 
 * @package TMU\PostTypes
 * @since 1.0.0
 */

namespace TMU\PostTypes;

use TMU\Core\BasePostType;
use TMU\Interfaces\PostTypeInterface;

/**
 * Movie post type implementation
 */
class MoviePostType extends BasePostType implements PostTypeInterface {
    /**
     * Post type identifier
     * 
     * @var string
     */
    protected string $post_type = 'movie';
    
    /**
     * Register the post type
     * 
     * @return void
     */
    public function register(): void {
        if (!$this->is_enabled()) {
            return;
        }
        
        register_post_type($this->post_type, $this->get_args());
    }
    
    /**
     * Check if post type is enabled
     * 
     * @return bool
     */
    private function is_enabled(): bool {
        return get_option('tmu_movies', 'on') === 'on';
    }
}
```

### JavaScript Standards
```javascript
/**
 * Movie block component
 * 
 * @package TMU\Blocks
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Movie block edit component
 * 
 * @param {Object} props Block properties
 * @return {JSX.Element} Component
 */
export default function Edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps();
    
    return (
        <div {...blockProps}>
            {/* Block content */}
        </div>
    );
}
```

### CSS Standards
```css
/* Tailwind CSS with custom components */
@layer components {
  .tmu-card {
    @apply bg-white rounded-lg shadow-md overflow-hidden;
  }
  
  .tmu-card__image {
    @apply w-full h-48 object-cover;
  }
  
  .tmu-card__content {
    @apply p-4;
  }
  
  .tmu-card__title {
    @apply text-lg font-semibold mb-2 text-gray-900;
  }
}
```

## API Reference

### Core Classes

#### ThemeCore
Main theme initialization and management.

```php
// Usage
$theme = new TMU\Core\ThemeCore();

// Methods
$theme->setup_theme();     // Setup theme supports
$theme->enqueue_assets();  // Enqueue scripts/styles
$theme->init_components(); // Initialize components
```

#### PostTypeManager
Manages all custom post types.

```php
// Usage
$manager = new TMU\PostTypes\PostTypeManager();

// Methods
$manager->register_post_types();  // Register all post types
$manager->get_post_type($type);   // Get specific post type
$manager->is_enabled($type);      // Check if type is enabled
```

#### TMDBClient
TMDB API integration client.

```php
// Usage
$client = new TMU\API\TMDBClient();

// Methods
$movie = $client->get_movie(550);           // Get movie by ID
$results = $client->search_movies('fight'); // Search movies
$person = $client->get_person(287);         // Get person by ID
```

### Helper Functions

#### Content Helpers
```php
// Get movie data
function tmu_get_movie_data($post_id): array;

// Get TV series data  
function tmu_get_tv_data($post_id): array;

// Get person data
function tmu_get_person_data($post_id): array;

// Format runtime
function tmu_format_runtime($minutes): string;

// Format release date
function tmu_format_date($date, $format = 'F j, Y'): string;
```

#### Template Helpers
```php
// Load template part
function tmu_get_template_part($slug, $name = null, $args = []): void;

// Get template content
function tmu_get_template_content($template, $args = []): string;

// Check if content type is enabled
function tmu_is_content_type_enabled($type): bool;
```

#### TMDB Helpers
```php
// Get TMDB image URL
function tmu_get_tmdb_image_url($path, $size = 'w500'): string;

// Format TMDB rating
function tmu_format_rating($rating, $decimals = 1): string;

// Get TMDB genres
function tmu_get_tmdb_genres($genre_ids): array;
```

## Database Schema

### Custom Tables

#### tmu_movies
```sql
CREATE TABLE tmu_movies (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    post_id bigint(20) unsigned NOT NULL,
    tmdb_id int(11) DEFAULT NULL,
    title varchar(255) DEFAULT NULL,
    original_title varchar(255) DEFAULT NULL,
    tagline text DEFAULT NULL,
    overview text DEFAULT NULL,
    runtime int(11) DEFAULT NULL,
    release_date date DEFAULT NULL,
    status varchar(50) DEFAULT NULL,
    budget bigint(20) DEFAULT NULL,
    revenue bigint(20) DEFAULT NULL,
    homepage varchar(255) DEFAULT NULL,
    poster_path varchar(255) DEFAULT NULL,
    backdrop_path varchar(255) DEFAULT NULL,
    tmdb_vote_average decimal(3,1) DEFAULT NULL,
    tmdb_vote_count int(11) DEFAULT NULL,
    tmdb_popularity decimal(8,3) DEFAULT NULL,
    adult tinyint(1) DEFAULT 0,
    video tinyint(1) DEFAULT 0,
    belongs_to_collection text DEFAULT NULL,
    production_companies text DEFAULT NULL,
    production_countries text DEFAULT NULL,
    spoken_languages text DEFAULT NULL,
    genres text DEFAULT NULL,
    keywords text DEFAULT NULL,
    credits text DEFAULT NULL,
    images text DEFAULT NULL,
    videos text DEFAULT NULL,
    reviews text DEFAULT NULL,
    similar text DEFAULT NULL,
    recommendations text DEFAULT NULL,
    external_ids text DEFAULT NULL,
    last_updated datetime DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY post_id (post_id),
    KEY tmdb_id (tmdb_id),
    KEY release_date (release_date),
    KEY tmdb_popularity (tmdb_popularity)
);
```

#### tmu_tv_series
```sql
CREATE TABLE tmu_tv_series (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    post_id bigint(20) unsigned NOT NULL,
    tmdb_id int(11) DEFAULT NULL,
    title varchar(255) DEFAULT NULL,
    original_title varchar(255) DEFAULT NULL,
    overview text DEFAULT NULL,
    first_air_date date DEFAULT NULL,
    last_air_date date DEFAULT NULL,
    status varchar(50) DEFAULT NULL,
    type varchar(50) DEFAULT NULL,
    number_of_episodes int(11) DEFAULT NULL,
    number_of_seasons int(11) DEFAULT NULL,
    episode_run_time text DEFAULT NULL,
    homepage varchar(255) DEFAULT NULL,
    poster_path varchar(255) DEFAULT NULL,
    backdrop_path varchar(255) DEFAULT NULL,
    tmdb_vote_average decimal(3,1) DEFAULT NULL,
    tmdb_vote_count int(11) DEFAULT NULL,
    tmdb_popularity decimal(8,3) DEFAULT NULL,
    in_production tinyint(1) DEFAULT 0,
    languages text DEFAULT NULL,
    origin_country text DEFAULT NULL,
    networks text DEFAULT NULL,
    production_companies text DEFAULT NULL,
    production_countries text DEFAULT NULL,
    seasons text DEFAULT NULL,
    created_by text DEFAULT NULL,
    genres text DEFAULT NULL,
    keywords text DEFAULT NULL,
    credits text DEFAULT NULL,
    images text DEFAULT NULL,
    videos text DEFAULT NULL,
    similar text DEFAULT NULL,
    recommendations text DEFAULT NULL,
    external_ids text DEFAULT NULL,
    last_updated datetime DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY post_id (post_id),
    KEY tmdb_id (tmdb_id),
    KEY first_air_date (first_air_date),
    KEY status (status)
);
```

## Hooks and Filters

### Action Hooks

#### Theme Setup
```php
// Before theme initialization
do_action('tmu_before_init');

// After theme initialization
do_action('tmu_after_init');

// Before post type registration
do_action('tmu_before_register_post_types');

// After post type registration
do_action('tmu_after_register_post_types', $post_types);
```

#### Content Management
```php
// Before saving movie data
do_action('tmu_before_save_movie', $post_id, $movie_data);

// After saving movie data
do_action('tmu_after_save_movie', $post_id, $movie_data);

// Before TMDB sync
do_action('tmu_before_tmdb_sync', $content_id, $content_type);

// After TMDB sync
do_action('tmu_after_tmdb_sync', $content_id, $content_type, $success);
```

### Filter Hooks

#### Content Filtering
```php
// Filter movie data before saving
$movie_data = apply_filters('tmu_movie_data', $movie_data, $post_id);

// Filter TMDB API response
$response = apply_filters('tmu_tmdb_response', $response, $endpoint);

// Filter search results
$results = apply_filters('tmu_search_results', $results, $query);
```

#### Template Filtering
```php
// Filter template path
$template = apply_filters('tmu_template_path', $template, $slug, $name);

// Filter template variables
$vars = apply_filters('tmu_template_vars', $vars, $template);
```

### Custom Hook Examples

#### Adding Custom Movie Fields
```php
// Add custom field to movie data
add_filter('tmu_movie_data', function($data, $post_id) {
    $data['custom_field'] = get_post_meta($post_id, 'custom_field', true);
    return $data;
}, 10, 2);

// Save custom field
add_action('tmu_after_save_movie', function($post_id, $movie_data) {
    if (isset($movie_data['custom_field'])) {
        update_post_meta($post_id, 'custom_field', $movie_data['custom_field']);
    }
}, 10, 2);
```

## Block Development

### Creating Custom Blocks

#### Block Registration
```php
// includes/classes/Blocks/MovieInfoBlock.php
namespace TMU\Blocks;

class MovieInfoBlock {
    public function __construct() {
        add_action('init', [$this, 'register_block']);
    }
    
    public function register_block(): void {
        register_block_type(
            get_template_directory() . '/assets/blocks/movie-info',
            [
                'render_callback' => [$this, 'render_block'],
                'attributes' => $this->get_attributes()
            ]
        );
    }
    
    public function render_block($attributes, $content): string {
        $post_id = get_the_ID();
        $movie_data = tmu_get_movie_data($post_id);
        
        ob_start();
        include get_template_directory() . '/templates/blocks/movie-info.php';
        return ob_get_clean();
    }
    
    private function get_attributes(): array {
        return [
            'showRuntime' => [
                'type' => 'boolean',
                'default' => true
            ],
            'showRating' => [
                'type' => 'boolean', 
                'default' => true
            ]
        ];
    }
}
```

#### Block JavaScript
```javascript
// assets/src/blocks/movie-info/index.js
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import Edit from './edit';

registerBlockType('tmu/movie-info', {
    title: __('Movie Information', 'tmu'),
    description: __('Display movie information and details.', 'tmu'),
    category: 'tmu-blocks',
    icon: 'video-alt3',
    keywords: ['movie', 'film', 'info'],
    attributes: {
        showRuntime: {
            type: 'boolean',
            default: true
        },
        showRating: {
            type: 'boolean',
            default: true
        }
    },
    edit: Edit,
    save: () => null // Server-side rendering
});
```

## Extension Development

### Creating Extensions

#### Plugin Structure
```php
<?php
/**
 * Plugin Name: TMU Extension Example
 * Description: Example extension for TMU theme
 * Version: 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TMU_Extension_Example {
    public function __construct() {
        add_action('tmu_after_init', [$this, 'init']);
    }
    
    public function init(): void {
        // Extension initialization
        add_filter('tmu_movie_data', [$this, 'add_custom_data'], 10, 2);
        add_action('tmu_after_save_movie', [$this, 'save_custom_data'], 10, 2);
    }
    
    public function add_custom_data($data, $post_id): array {
        $data['custom_rating'] = get_post_meta($post_id, 'custom_rating', true);
        return $data;
    }
    
    public function save_custom_data($post_id, $movie_data): void {
        if (isset($movie_data['custom_rating'])) {
            update_post_meta($post_id, 'custom_rating', $movie_data['custom_rating']);
        }
    }
}

new TMU_Extension_Example();
```

### Child Theme Development
```php
<?php
// child-theme/functions.php

// Enqueue parent theme styles
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
});

// Override template parts
add_filter('tmu_template_path', function($template, $slug, $name) {
    $child_template = get_stylesheet_directory() . "/templates/{$slug}";
    if ($name) {
        $child_template .= "-{$name}";
    }
    $child_template .= '.php';
    
    if (file_exists($child_template)) {
        return $child_template;
    }
    
    return $template;
}, 10, 3);
```

## Testing Guidelines

### Unit Testing Setup
```php
// tests/Unit/PostTypes/MoviePostTypeTest.php
namespace TMU\Tests\Unit\PostTypes;

use PHPUnit\Framework\TestCase;
use TMU\PostTypes\MoviePostType;

class MoviePostTypeTest extends TestCase {
    private MoviePostType $movie_post_type;
    
    protected function setUp(): void {
        $this->movie_post_type = new MoviePostType();
    }
    
    public function test_post_type_registration(): void {
        // Enable movies
        update_option('tmu_movies', 'on');
        
        // Register post type
        $this->movie_post_type->register();
        
        // Assert post type exists
        $this->assertTrue(post_type_exists('movie'));
    }
}
```

### Integration Testing
```php
// tests/Integration/TMDBIntegrationTest.php
namespace TMU\Tests\Integration;

use TMU\API\TMDBClient;
use TMU\Tests\TestCase;

class TMDBIntegrationTest extends TestCase {
    public function test_tmdb_api_connection(): void {
        $client = new TMDBClient();
        $movie = $client->get_movie(550); // Fight Club
        
        $this->assertNotEmpty($movie);
        $this->assertEquals('Fight Club', $movie['title']);
    }
}
```

## Performance Optimization

### Database Optimization
```php
// Optimize queries with proper indexing
class MovieQuery {
    public function get_popular_movies($limit = 10): array {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT p.ID, p.post_title, m.tmdb_popularity 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->prefix}tmu_movies m ON p.ID = m.post_id
            WHERE p.post_type = 'movie' 
            AND p.post_status = 'publish'
            ORDER BY m.tmdb_popularity DESC
            LIMIT %d
        ", $limit);
        
        return $wpdb->get_results($query);
    }
}
```

### Caching Implementation
```php
// Cache expensive operations
function tmu_get_cached_movie_data($post_id): array {
    $cache_key = "tmu_movie_data_{$post_id}";
    $cached_data = wp_cache_get($cache_key, 'tmu_movies');
    
    if ($cached_data === false) {
        $cached_data = tmu_get_movie_data($post_id);
        wp_cache_set($cache_key, $cached_data, 'tmu_movies', 3600);
    }
    
    return $cached_data;
}
```

### Asset Optimization
```javascript
// Lazy loading implementation
import { IntersectionObserver } from './utils/intersection-observer';

const lazyImages = document.querySelectorAll('img[data-src]');

const imageObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.classList.remove('lazy');
            imageObserver.unobserve(img);
        }
    });
});

lazyImages.forEach(img => imageObserver.observe(img));
```

---

## Support and Resources

### Development Resources
- **WordPress Codex:** https://codex.wordpress.org/
- **Block Editor Handbook:** https://developer.wordpress.org/block-editor/
- **TMDB API Documentation:** https://developers.themoviedb.org/
- **Tailwind CSS Documentation:** https://tailwindcss.com/docs

### Code Quality Tools
- **PHPCS:** WordPress coding standards
- **PHPStan:** Static analysis
- **ESLint:** JavaScript linting
- **Jest:** JavaScript testing

### Community
- **GitHub Repository:** [repository-url]
- **Developer Forum:** [forum-url]
- **Documentation:** [docs-url]

---

*This developer guide provides comprehensive technical documentation for extending and customizing the TMU theme.*