# Step 05: Post Types Registration

## Purpose
Implement modern, object-oriented custom post type registration system that replicates the TMU plugin's post types with enhanced WordPress standards compliance and better organization.

## Overview
This step converts the plugin's post type registration from procedural functions to a clean OOP architecture with proper inheritance, namespace organization, and WordPress best practices.

## Dependencies from Previous Steps
- **[REQUIRED]** PSR-4 autoloading [FROM STEP 4] - Class autoloading system
- **[REQUIRED]** `includes/classes/ThemeCore.php` [FROM STEP 1] - Theme initialization
- **[REQUIRED]** Database tables [FROM STEP 3] - Post type data storage
- **[REQUIRED]** `includes/config/constants.php` [FROM STEP 1] - Theme constants
- **[REQUIRED]** Theme options system [FROM STEP 2] - Conditional registration

## Files Created in This Step
- **[CREATE NEW]** `includes/classes/PostTypes/PostTypeManager.php` - Main manager class
- **[CREATE NEW]** `includes/classes/PostTypes/AbstractPostType.php` - Base post type class
- **[CREATE NEW]** `includes/classes/PostTypes/Movie.php` - Movie post type
- **[CREATE NEW]** `includes/classes/PostTypes/TVShow.php` - TV show post type
- **[CREATE NEW]** `includes/classes/PostTypes/Drama.php` - Drama post type
- **[CREATE NEW]** `includes/classes/PostTypes/People.php` - People post type
- **[CREATE NEW]** `includes/classes/PostTypes/Video.php` - Video post type
- **[CREATE NEW]** `includes/classes/PostTypes/Season.php` - Season post type
- **[CREATE NEW]** `includes/classes/PostTypes/Episode.php` - Episode post type
- **[CREATE NEW]** `includes/classes/PostTypes/DramaEpisode.php` - Drama episode post type
- **[CREATE NEW]** `includes/classes/Admin/MenuOrganizer.php` - Admin menu organization
- **[CREATE NEW]** `includes/classes/Frontend/RewriteRules.php` - URL rewrite management
- **[CREATE NEW]** `includes/config/theme-options.php` - Theme options configuration
- **[CREATE NEW]** `tests/PostTypesTest.php` - Post types testing

## Tailwind CSS Status
**NOT APPLICABLE** - This step focuses on PHP post type registration and doesn't involve CSS

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

### Directory Structure with File Status
```
includes/classes/PostTypes/                     # [UPDATE DIR - STEP 5] Extend existing directory from Step 1
├── PostTypeManager.php      # [CREATE NEW - STEP 5] Main manager class - Orchestrates all post types
├── AbstractPostType.php     # [CREATE NEW - STEP 5] Base post type class - Shared functionality
├── Movie.php               # [CREATE NEW - STEP 5] Movie post type - Movies content type
├── TVShow.php              # [CREATE NEW - STEP 5] TV show post type - TV series content type
├── Drama.php               # [CREATE NEW - STEP 5] Drama post type - Drama series content type
├── People.php              # [CREATE NEW - STEP 5] People post type - Cast/crew content type
├── Video.php               # [CREATE NEW - STEP 5] Video post type - Video content type
├── Season.php              # [CREATE NEW - STEP 5] Season post type - TV/drama seasons
├── Episode.php             # [CREATE NEW - STEP 5] Episode post type - TV episodes
└── DramaEpisode.php        # [CREATE NEW - STEP 5] Drama episode post type - Drama episodes

includes/classes/Admin/                         # [UPDATE DIR - STEP 5] Extend existing directory from Step 1
└── MenuOrganizer.php       # [CREATE NEW - STEP 5] Admin menu organization - Nested menus

includes/classes/Frontend/                      # [CREATE DIR - STEP 5] Frontend functionality
└── RewriteRules.php        # [CREATE NEW - STEP 5] URL rewrite management - Custom permalinks

includes/config/                               # [UPDATE DIR - STEP 5] Extend existing directory from Step 1
└── theme-options.php       # [CREATE NEW - STEP 5] Theme options configuration - Post type settings

tests/                                         # [UPDATE DIR - STEP 5] Extend existing directory from Step 4
└── PostTypesTest.php       # [CREATE NEW - STEP 5] Post types testing - Unit tests
```

### **Dependencies from Previous Steps:**
- **[REQUIRED]** `includes/classes/ThemeCore.php` [FROM STEP 1] - Theme initialization
- **[REQUIRED]** PSR-4 autoloading [FROM STEP 4] - Class autoloading
- **[REQUIRED]** Database tables [FROM STEP 3] - Data storage
- **[REQUIRED]** Helper functions [FROM STEP 4] - Utility functions
- **[REQUIRED]** Theme options system [FROM STEP 2] - Configuration

### **Files Created in Future Steps:**
- **`includes/classes/Taxonomies/TaxonomyManager.php`** - [CREATE NEW - STEP 6] Taxonomy registration
- **`includes/classes/Fields/FieldManager.php`** - [CREATE NEW - STEP 7] Meta fields system
- **`includes/classes/Admin/MetaBoxes.php`** - [CREATE NEW - STEP 8] Admin meta boxes

### 1. Post Type Manager (`PostTypeManager.php`)
**File Status**: [CREATE NEW - STEP 5]
**File Path**: `tmu-theme/includes/classes/PostTypes/PostTypeManager.php`
**Purpose**: Main manager class that orchestrates all post type registration and organization
**Dependencies**: 
- [DEPENDS ON] Individual post type classes [CREATE NEW - STEP 5] - Movie, TVShow, Drama, etc.
- [DEPENDS ON] WordPress `register_post_type()` function - Core registration
- [DEPENDS ON] Theme options [FROM STEP 2] - Conditional registration
- [DEPENDS ON] PSR-4 autoloading [FROM STEP 4] - Class loading
**Integration**: Central management of all TMU post types
**Used By**: 
- `includes/classes/ThemeCore.php` [FROM STEP 1] - Theme initialization
- Admin menu organization [CREATE NEW - STEP 5] - Menu structure
- URL rewrite system [CREATE NEW - STEP 5] - Permalink management
**Features**: 
- Singleton pattern for single instance
- Conditional post type registration based on settings
- Admin menu organization
- Hook management for WordPress integration
**AI Action**: Create singleton manager class that handles all post type registration

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
**File Status**: [CREATE NEW - STEP 5]
**File Path**: `tmu-theme/includes/classes/PostTypes/AbstractPostType.php`
**Purpose**: Base abstract class providing shared functionality for all post type implementations
**Dependencies**: 
- [DEPENDS ON] WordPress `register_post_type()` function - Core registration
- [DEPENDS ON] WordPress `get_option()` function - Settings retrieval
- [DEPENDS ON] Theme options system [FROM STEP 2] - Conditional registration
**Integration**: Base class for all TMU post types
**Used By**: 
- `Movie.php` [CREATE NEW - STEP 5] - Movie post type
- `TVShow.php` [CREATE NEW - STEP 5] - TV show post type
- `Drama.php` [CREATE NEW - STEP 5] - Drama post type
- `People.php` [CREATE NEW - STEP 5] - People post type
- `Video.php` [CREATE NEW - STEP 5] - Video post type
- `Season.php` [CREATE NEW - STEP 5] - Season post type
- `Episode.php` [CREATE NEW - STEP 5] - Episode post type
- `DramaEpisode.php` [CREATE NEW - STEP 5] - Drama episode post type
**Features**: 
- Abstract methods for labels and args
- Default arguments for post types
- Conditional registration logic
- Shared functionality across all post types
**AI Action**: Create abstract base class with shared post type functionality

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
**File Status**: [CREATE NEW - STEP 5]
**File Path**: `tmu-theme/includes/classes/PostTypes/Movie.php`
**Purpose**: Movie post type implementation with custom labels, args, and taxonomies
**Dependencies**: 
- [EXTENDS] `AbstractPostType.php` [CREATE NEW - STEP 5] - Base post type functionality
- [DEPENDS ON] WordPress `__()` function - Translation support
- [DEPENDS ON] `get_option()` function - Settings retrieval
- [DEPENDS ON] Taxonomies [CREATE NEW - STEP 6] - Genre, country, language, year
**Integration**: Movie content type for the TMU theme
**Used By**: 
- `PostTypeManager.php` [CREATE NEW - STEP 5] - Registration management
- Movie templates [CREATE NEW - STEP 10] - Frontend display
- Movie admin interface [CREATE NEW - STEP 8] - Backend management
**Features**: 
- Custom labels and descriptions
- Specific taxonomies (genre, country, language, year)
- Conditional registration based on theme settings
- Admin menu customization
**AI Action**: Create movie post type class extending AbstractPostType

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
**File Status**: [CREATE NEW - STEP 5]
**File Path**: `tmu-theme/includes/classes/PostTypes/TVShow.php`
**Purpose**: TV Show post type implementation with hierarchical support for seasons and episodes
**Dependencies**: 
- [EXTENDS] `AbstractPostType.php` [CREATE NEW - STEP 5] - Base post type functionality
- [DEPENDS ON] WordPress `__()` function - Translation support
- [DEPENDS ON] `get_option()` function - Settings retrieval
- [DEPENDS ON] Taxonomies [CREATE NEW - STEP 6] - Genre, country, language, year, network
**Integration**: TV Show content type for the TMU theme
**Used By**: 
- `PostTypeManager.php` [CREATE NEW - STEP 5] - Registration management
- `Season.php` [CREATE NEW - STEP 5] - Child post type relationship
- `Episode.php` [CREATE NEW - STEP 5] - Child post type relationship
- TV show templates [CREATE NEW - STEP 10] - Frontend display
**Features**: 
- Hierarchical support for seasons and episodes
- Network taxonomy for TV shows
- Conditional registration based on theme settings
- Parent menu for seasons and episodes
**AI Action**: Create TV show post type class extending AbstractPostType

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
**File Status**: [CREATE NEW - STEP 5]
**File Path**: `tmu-theme/includes/classes/PostTypes/Season.php`
**Purpose**: Season post type implementation nested under TV Shows with custom admin menu placement
**Dependencies**: 
- [EXTENDS] `AbstractPostType.php` [CREATE NEW - STEP 5] - Base post type functionality
- [DEPENDS ON] `TVShow.php` [CREATE NEW - STEP 5] - Parent post type relationship
- [DEPENDS ON] WordPress `__()` function - Translation support
- [DEPENDS ON] `get_option()` function - Settings retrieval
**Integration**: Season content type nested under TV Shows
**Used By**: 
- `PostTypeManager.php` [CREATE NEW - STEP 5] - Registration management
- `Episode.php` [CREATE NEW - STEP 5] - Child post type relationship
- Season templates [CREATE NEW - STEP 10] - Frontend display
- Admin menu organization [CREATE NEW - STEP 5] - Nested menu structure
**Features**: 
- Nested under TV Shows admin menu
- Custom permalink structure with TV show relationship
- Conditional registration based on TV series settings
- Hierarchical relationship with episodes
**AI Action**: Create season post type class with nested admin menu structure

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
**File Status**: [CREATE NEW - STEP 5]
**File Path**: `tmu-theme/includes/config/theme-options.php`
**Purpose**: Theme options configuration for conditional post type registration
**Dependencies**: 
- [DEPENDS ON] WordPress `get_option()` function - Settings retrieval
- [DEPENDS ON] Theme settings system [FROM STEP 2] - Settings management
**Integration**: Configuration for post type conditional registration
**Used By**: 
- Individual post type classes [CREATE NEW - STEP 5] - Conditional registration
- Settings page [CREATE NEW - STEP 8] - Admin interface
- Theme configuration [FROM STEP 2] - Settings management
**Features**: 
- Boolean settings for each post type
- Default values for new installations
- Setting descriptions for admin interface
**AI Action**: Create theme options configuration file

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
**File Status**: [CREATE NEW - STEP 5]
**File Path**: `tmu-theme/includes/classes/Admin/MenuOrganizer.php`
**Purpose**: Admin menu organization for nested post types (seasons under TV shows, episodes under dramas)
**Dependencies**: 
- [DEPENDS ON] WordPress `add_submenu_page()` function - Menu management
- [DEPENDS ON] WordPress `add_action()` function - Hook management
- [DEPENDS ON] Theme options [FROM STEP 2] - Conditional menu organization
- [DEPENDS ON] Post type registration [CREATE NEW - STEP 5] - Menu structure
**Integration**: Admin menu organization for hierarchical post types
**Used By**: 
- `PostTypeManager.php` [CREATE NEW - STEP 5] - Menu organization
- WordPress admin interface - Menu display
**Features**: 
- Nested menu structure for seasons and episodes
- Conditional menu organization based on enabled post types
- Proper menu ordering and hierarchy
**AI Action**: Create admin menu organizer class

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
**File Status**: [CREATE NEW - STEP 5]
**File Path**: `tmu-theme/includes/classes/Frontend/RewriteRules.php`
**Purpose**: Custom URL rewrite rules for nested post types and custom permalink structures
**Dependencies**: 
- [DEPENDS ON] WordPress `add_rewrite_rule()` function - URL rewriting
- [DEPENDS ON] WordPress `add_filter()` function - Hook management
- [DEPENDS ON] WordPress `get_post_meta()` function - Meta data retrieval
- [DEPENDS ON] Post type registration [CREATE NEW - STEP 5] - URL structure
**Integration**: Custom permalink structure for TMU post types
**Used By**: 
- `PostTypeManager.php` [CREATE NEW - STEP 5] - URL management
- WordPress rewrite system - URL routing
- Frontend templates [CREATE NEW - STEP 10] - URL generation
**Features**: 
- Custom permalink structure for seasons (e.g., /show-name/season1/)
- Custom permalink structure for episodes
- Relationship-based URL generation
- SEO-friendly URL structures
**AI Action**: Create rewrite rules class for custom permalinks

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
**File Status**: [CREATE NEW - STEP 5]
**File Path**: `tmu-theme/tests/PostTypesTest.php`
**Purpose**: Unit tests for post type registration and functionality
**Dependencies**: 
- [DEPENDS ON] PHPUnit framework [FROM STEP 4] - Testing framework
- [DEPENDS ON] `PostTypeManager.php` [CREATE NEW - STEP 5] - Class under test
- [DEPENDS ON] WordPress `post_type_exists()` function - Testing post type registration
- [DEPENDS ON] WordPress `update_option()` function - Testing settings
**Integration**: Testing framework for post type validation
**Used By**: 
- Development testing - Quality assurance
- CI/CD pipelines - Automated testing
- Composer test scripts [FROM STEP 4] - Test execution
**Features**: 
- Post type registration verification
- Conditional registration testing
- Settings-based registration testing
- Post type existence validation
**AI Action**: Create comprehensive post type test suite

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
**File Status**: [UPDATE - STEP 5]
**File Path**: `tmu-theme/includes/classes/ThemeCore.php`
**Purpose**: Integration of post type manager into the main theme initialization
**Dependencies**: 
- [DEPENDS ON] `PostTypeManager.php` [CREATE NEW - STEP 5] - Post type management
- [DEPENDS ON] PSR-4 autoloading [FROM STEP 4] - Class loading
- [DEPENDS ON] Theme initialization [FROM STEP 1] - Core theme setup
**Integration**: Main theme initialization with post type registration
**Used By**: 
- `functions.php` [FROM STEP 1] - Theme bootstrap
- WordPress theme system - Theme activation
**Features**: 
- Post type manager initialization
- Dependency loading
- Theme startup sequence
**AI Action**: Update ThemeCore to include post type manager

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

## AI Implementation Instructions for Step 5

### **Prerequisites Check**
Before implementing Step 5, verify these files exist from previous steps:
- **[REQUIRED]** PSR-4 autoloading [FROM STEP 4] - Class autoloading system
- **[REQUIRED]** `includes/classes/ThemeCore.php` [FROM STEP 1] - Theme initialization
- **[REQUIRED]** Database tables [FROM STEP 3] - Post type data storage
- **[REQUIRED]** Helper functions [FROM STEP 4] - Utility functions
- **[REQUIRED]** Theme options system [FROM STEP 2] - Settings management

### **Implementation Order for AI Models**

#### **Phase 1: Create Directories** (Required First)
```bash
mkdir -p tmu-theme/includes/classes/PostTypes
mkdir -p tmu-theme/includes/classes/Frontend
# Admin directory already exists from previous steps
```

#### **Phase 2: Base Classes** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/PostTypes/AbstractPostType.php` - Base functionality
2. **[CREATE SECOND]** `includes/classes/PostTypes/PostTypeManager.php` - Manager class

#### **Phase 3: Individual Post Type Classes** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/PostTypes/Movie.php` - Movie post type
2. **[CREATE SECOND]** `includes/classes/PostTypes/TVShow.php` - TV show post type
3. **[CREATE THIRD]** `includes/classes/PostTypes/Drama.php` - Drama post type
4. **[CREATE FOURTH]** `includes/classes/PostTypes/People.php` - People post type
5. **[CREATE FIFTH]** `includes/classes/PostTypes/Video.php` - Video post type
6. **[CREATE SIXTH]** `includes/classes/PostTypes/Season.php` - Season post type
7. **[CREATE SEVENTH]** `includes/classes/PostTypes/Episode.php` - Episode post type
8. **[CREATE EIGHTH]** `includes/classes/PostTypes/DramaEpisode.php` - Drama episode post type

#### **Phase 4: Supporting Classes** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/Admin/MenuOrganizer.php` - Admin menu organization
2. **[CREATE SECOND]** `includes/classes/Frontend/RewriteRules.php` - URL rewrite rules
3. **[CREATE THIRD]** `includes/config/theme-options.php` - Theme options configuration

#### **Phase 5: Testing** (Exact Order)
1. **[CREATE FIRST]** `tests/PostTypesTest.php` - Post type tests

#### **Phase 6: Integration** (Final)
1. **[UPDATE]** `includes/classes/ThemeCore.php` - Include post type manager

### **Key Implementation Notes**
- **Inheritance Structure**: AbstractPostType must be created before individual post types
- **Conditional Registration**: All post types check theme options before registration
- **Admin Menu Organization**: Nested post types (seasons, episodes) appear under parent menus
- **URL Structure**: Custom permalink structures for hierarchical relationships
- **Testing**: Comprehensive test coverage for all post type functionality

### **Critical Dependencies**
- **WordPress Functions**: `register_post_type()`, `add_action()`, `add_filter()`
- **Theme Options**: Settings for conditional post type registration
- **Database Tables**: TMU tables from Step 3 for post type data
- **Autoloading**: PSR-4 autoloading for class loading

### **Post Type Hierarchy**
```
Primary Post Types:
├── Movie (independent)
├── TVShow (parent)
│   ├── Season (child of TVShow)
│   └── Episode (child of Season)
├── Drama (parent)
│   └── DramaEpisode (child of Drama)
├── People (independent)
└── Video (independent)
```

### **Testing Requirements**
1. **Registration Test** - Verify all post types register correctly
2. **Conditional Test** - Verify settings-based registration
3. **Menu Test** - Verify admin menu organization
4. **Hierarchy Test** - Verify parent-child relationships
5. **URL Test** - Verify custom permalink structures

### **Integration Points**
- **ThemeCore.php** - Initializes PostTypeManager
- **Theme Options** - Controls conditional registration
- **Database Tables** - Stores post type data
- **Admin Interface** - Manages post type content
- **Frontend Templates** - Displays post type content

### **Common Issues and Solutions**
1. **Post Type Not Registered**: Check theme options and conditional registration
2. **Menu Structure Wrong**: Verify MenuOrganizer implementation
3. **Permalinks Not Working**: Flush rewrite rules after registration
4. **Class Not Found**: Verify PSR-4 autoloading and namespace

### **Verification Commands**
```bash
# Run post type tests
composer test tests/PostTypesTest.php

# Check registered post types in WordPress admin
# Go to WordPress admin and verify post types appear correctly

# Test conditional registration
# Toggle theme options and verify post types enable/disable
```

### **Post-Implementation Checklist**
- [ ] All post type classes created
- [ ] PostTypeManager implemented
- [ ] AbstractPostType base class functional
- [ ] Admin menu organization working
- [ ] URL rewrite rules active
- [ ] Theme options integration complete
- [ ] Tests passing
- [ ] ThemeCore integration complete

---

This modern OOP approach provides better maintainability, extensibility, and follows WordPress coding standards while preserving all original plugin functionality.

**Step 5 Status**: ✅ READY FOR AI IMPLEMENTATION
**Dependencies**: Steps 1-4 must be completed
**Next Step**: Step 6 - Taxonomies Registration