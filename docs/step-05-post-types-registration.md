# Step 05: Post Types Registration

## Purpose
Implement modern, object-oriented custom post type registration system that replicates the TMU plugin's post types with enhanced WordPress standards compliance and better organization.

## Overview
This step converts the plugin's post type registration from procedural functions to a clean OOP architecture with proper inheritance, namespace organization, and WordPress best practices.

## Post Types Analysis from Plugin

### Primary Post Types
- `tv` - TV Shows/Series
- `movie` - Movies  
- `drama` - Drama Series
- `people` - Celebrities/Cast/Crew
- `video` - Video Content

### Nested Post Types
- `season` - TV Show Seasons (under TV Shows menu)
- `episode` - TV Show Episodes (under TV Shows menu)
- `drama-episode` - Drama Episodes (under Dramas menu)

## Architecture Implementation

### Directory Structure
```
includes/classes/PostTypes/
├── PostTypeManager.php      # Main manager class
├── AbstractPostType.php     # Base post type class
├── Movie.php               # Movie post type
├── TVShow.php              # TV show post type
├── Drama.php               # Drama post type
├── People.php              # People post type
├── Video.php               # Video post type
├── Season.php              # Season post type
├── Episode.php             # Episode post type
└── DramaEpisode.php        # Drama episode post type
```

### 1. Post Type Manager (`PostTypeManager.php`)
```php
<?php
namespace TMU\PostTypes;

class PostTypeManager {
    private static $instance = null;
    private $post_types = [];
    
    public static function getInstance(): PostTypeManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->initHooks();
        $this->registerPostTypes();
    }
    
    private function initHooks(): void {
        add_action('init', [$this, 'registerAllPostTypes']);
        add_action('admin_menu', [$this, 'organizeAdminMenus']);
    }
    
    private function registerPostTypes(): void {
        $this->post_types = [
            'movie' => new Movie(),
            'tv' => new TVShow(),
            'drama' => new Drama(),
            'people' => new People(),
            'video' => new Video(),
            'season' => new Season(),
            'episode' => new Episode(),
            'drama-episode' => new DramaEpisode(),
        ];
    }
    
    public function registerAllPostTypes(): void {
        foreach ($this->post_types as $post_type) {
            $post_type->register();
        }
    }
    
    public function organizeAdminMenus(): void {
        // Organize nested post types in admin menu
        foreach ($this->post_types as $post_type) {
            if (method_exists($post_type, 'organizeAdminMenu')) {
                $post_type->organizeAdminMenu();
            }
        }
    }
}
```

### 2. Abstract Base Class (`AbstractPostType.php`)
```php
<?php
namespace TMU\PostTypes;

abstract class AbstractPostType {
    protected $post_type;
    protected $labels = [];
    protected $args = [];
    protected $taxonomies = [];
    protected $supports = ['title', 'editor', 'thumbnail', 'comments'];
    
    abstract protected function getLabels(): array;
    abstract protected function getArgs(): array;
    
    public function register(): void {
        if ($this->shouldRegister()) {
            $this->labels = $this->getLabels();
            $this->args = $this->getArgs();
            
            register_post_type($this->post_type, array_merge($this->args, [
                'labels' => $this->labels,
                'supports' => $this->supports,
                'taxonomies' => $this->taxonomies,
            ]));
        }
    }
    
    protected function shouldRegister(): bool {
        return true; // Override in child classes for conditional registration
    }
    
    protected function getDefaultArgs(): array {
        return [
            'public' => true,
            'hierarchical' => false,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'show_in_rest' => false,
            'query_var' => true,
            'can_export' => true,
            'delete_with_user' => true,
            'has_archive' => true,
            'capability_type' => 'post',
            'rewrite' => ['with_front' => false, 'feeds' => false],
        ];
    }
}
```

### 3. Movie Post Type (`Movie.php`)
```php
<?php
namespace TMU\PostTypes;

class Movie extends AbstractPostType {
    protected $post_type = 'movie';
    protected $taxonomies = ['by-year', 'genre', 'country', 'language'];
    
    protected function getLabels(): array {
        return [
            'name' => __('Movies', 'tmu'),
            'singular_name' => __('Movie', 'tmu'),
            'add_new' => __('Add New Movie', 'tmu'),
            'add_new_item' => __('Add New Movie', 'tmu'),
            'edit_item' => __('Edit Movie', 'tmu'),
            'new_item' => __('New Movie', 'tmu'),
            'view_item' => __('View Movie', 'tmu'),
            'search_items' => __('Search Movies', 'tmu'),
            'not_found' => __('No movies found.', 'tmu'),
            'not_found_in_trash' => __('No movies found in Trash.', 'tmu'),
            'all_items' => __('All Movies', 'tmu'),
            'menu_name' => __('Movies', 'tmu'),
        ];
    }
    
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'menu_position' => 5,
            'menu_icon' => 'dashicons-format-video',
            'description' => __('Movie database management', 'tmu'),
        ]);
    }
    
    protected function shouldRegister(): bool {
        return get_option('tmu_movies', 'off') === 'on';
    }
}
```

### 4. TV Show Post Type (`TVShow.php`)
```php
<?php
namespace TMU\PostTypes;

class TVShow extends AbstractPostType {
    protected $post_type = 'tv';
    protected $taxonomies = ['by-year', 'genre', 'country', 'language', 'network'];
    
    protected function getLabels(): array {
        return [
            'name' => __('TV Shows', 'tmu'),
            'singular_name' => __('TV Show', 'tmu'),
            'add_new' => __('Add New TV Show', 'tmu'),
            'menu_name' => __('TV Shows', 'tmu'),
            // ... other labels
        ];
    }
    
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'menu_position' => 4,
            'menu_icon' => 'dashicons-video-alt3',
        ]);
    }
    
    protected function shouldRegister(): bool {
        return get_option('tmu_tv_series', 'off') === 'on';
    }
}
```

### 5. Season Post Type (`Season.php`)
```php
<?php
namespace TMU\PostTypes;

class Season extends AbstractPostType {
    protected $post_type = 'season';
    protected $supports = ['title', 'editor', 'thumbnail'];
    
    protected function getLabels(): array {
        return [
            'name' => __('Seasons', 'tmu'),
            'singular_name' => __('Season', 'tmu'),
            'add_new' => __('Add New Season', 'tmu'),
            'menu_name' => __('Seasons', 'tmu'),
            'all_items' => __('All Seasons', 'tmu'),
        ];
    }
    
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'has_archive' => false,
            'show_in_menu' => 'edit.php?post_type=tv',
            'menu_icon' => 'dashicons-admin-generic',
        ]);
    }
    
    protected function shouldRegister(): bool {
        return get_option('tmu_tv_series', 'off') === 'on';
    }
}
```

## Theme Options Integration

### Settings Configuration (`includes/config/theme-options.php`)
```php
<?php
return [
    'tmu_movies' => [
        'default' => 'off',
        'type' => 'boolean',
        'description' => 'Enable Movies post type'
    ],
    'tmu_tv_series' => [
        'default' => 'off', 
        'type' => 'boolean',
        'description' => 'Enable TV Series post type'
    ],
    'tmu_dramas' => [
        'default' => 'off',
        'type' => 'boolean', 
        'description' => 'Enable Dramas post type'
    ],
];
```

## Admin Menu Organization

### Menu Organizer (`includes/classes/Admin/MenuOrganizer.php`)
```php
<?php
namespace TMU\Admin;

class MenuOrganizer {
    public function init(): void {
        add_action('admin_menu', [$this, 'organizeDramaMenus'], 20);
    }
    
    public function organizeDramaMenus(): void {
        if (get_option('tmu_dramas') === 'on') {
            add_submenu_page(
                'edit.php?post_type=drama',
                __('All Episodes', 'tmu'),
                __('All Episodes', 'tmu'),
                'manage_options',
                'edit.php?post_type=drama-episode'
            );
            
            add_submenu_page(
                'edit.php?post_type=drama',
                __('Add New Episode', 'tmu'),
                __('Add New Episode', 'tmu'),
                'manage_options',
                'post-new.php?post_type=drama-episode'
            );
        }
    }
}
```

## URL Rewrite Management

### Rewrite Rules (`includes/classes/Frontend/RewriteRules.php`)
```php
<?php
namespace TMU\Frontend;

class RewriteRules {
    public function init(): void {
        add_action('init', [$this, 'addRewriteRules']);
        add_filter('post_type_link', [$this, 'customPostTypeLinks'], 10, 2);
    }
    
    public function addRewriteRules(): void {
        // Custom rewrite rules for nested post types
        add_rewrite_rule(
            '^([^/]+)/season([0-9]+)/?$',
            'index.php?post_type=season&name=$matches[1]&season_no=$matches[2]',
            'top'
        );
    }
    
    public function customPostTypeLinks(string $link, \WP_Post $post): string {
        if ($post->post_type === 'season') {
            // Custom permalink structure for seasons
            $tv_show_id = get_post_meta($post->ID, 'series_name', true);
            $season_no = get_post_meta($post->ID, 'season_no', true);
            
            if ($tv_show_id && $season_no) {
                $tv_show = get_post($tv_show_id);
                if ($tv_show) {
                    return home_url("/{$tv_show->post_name}/season{$season_no}/");
                }
            }
        }
        
        return $link;
    }
}
```

## Testing Framework

### Post Types Test (`tests/PostTypesTest.php`)
```php
<?php
namespace TMU\Tests;

use PHPUnit\Framework\TestCase;
use TMU\PostTypes\PostTypeManager;

class PostTypesTest extends TestCase {
    public function testPostTypesRegistration(): void {
        // Enable all post types
        update_option('tmu_movies', 'on');
        update_option('tmu_tv_series', 'on');
        update_option('tmu_dramas', 'on');
        
        $manager = PostTypeManager::getInstance();
        $manager->registerAllPostTypes();
        
        // Verify post types are registered
        $this->assertTrue(post_type_exists('movie'));
        $this->assertTrue(post_type_exists('tv'));
        $this->assertTrue(post_type_exists('drama'));
        $this->assertTrue(post_type_exists('people'));
    }
    
    public function testConditionalRegistration(): void {
        // Disable movies
        update_option('tmu_movies', 'off');
        
        $manager = PostTypeManager::getInstance();
        $manager->registerAllPostTypes();
        
        // Verify movie post type is not registered
        $this->assertFalse(post_type_exists('movie'));
    }
}
```

## Integration with Theme Core

### Loading in ThemeCore (`includes/classes/ThemeCore.php`)
```php
// In the loadDependencies method:
require_once TMU_INCLUDES_DIR . '/classes/PostTypes/PostTypeManager.php';

// In the initTheme method:
PostTypes\PostTypeManager::getInstance();
```

## Next Steps

1. **[Step 06: Taxonomies Registration](./06_taxonomies-registration.md)** - Register custom taxonomies
2. **[Step 07: Custom Fields System](./07_custom-fields-system.md)** - Implement meta fields
3. **[Step 08: Admin UI and Meta Boxes](./08_admin-ui-and-meta-boxes.md)** - Create admin interface

## Verification Checklist

- [ ] PostTypeManager class implemented
- [ ] AbstractPostType base class created
- [ ] All post type classes implemented
- [ ] Conditional registration working
- [ ] Admin menu organization functional
- [ ] URL rewrite rules configured
- [ ] Testing framework operational
- [ ] Integration with theme core complete

---

This modern OOP approach provides better maintainability, extensibility, and follows WordPress coding standards while preserving all original plugin functionality.