# TMU Theme Setup Instructions

## Issues Fixed

### 1. Fatal Error: Call to private TMU\Admin\Settings\TMDBSettings::__construct()

**Problem**: The `AdminManager` class was trying to directly instantiate the `TMDBSettings` class, but its constructor is private.

**Fix**: Modified `AdminManager.php` to use the `getInstance()` method instead of direct instantiation:

```php
// Before (error)
$this->components['tmdb_settings'] = new Settings\TMDBSettings();

// After (fixed)
$this->components['tmdb_settings'] = Settings\TMDBSettings::getInstance();
```

### 2. Fatal Error: Call to undefined method TMU\Search\SearchManager::getInstance()

**Problem**: The `ThemeCore` class was trying to call `getInstance()` on the `SearchManager` class, but this method didn't exist.

**Fix**: Modified `SearchManager.php` to implement the singleton pattern with a `getInstance()` method:

```php
// Added to SearchManager.php
private static $instance = null;

public static function getInstance(): SearchManager {
    if (self::$instance === null) {
        self::$instance = new self();
    }
    return self::$instance;
}
```

Also updated other files that were directly instantiating `SearchManager`:

- `API/REST/SearchEndpoints.php`
- `Search/AjaxSearch.php`

### 3. Fatal Error: Class "TMU\Monitoring\PerformanceTracker" not found

**Problem**: The `PerformanceMonitor` class was trying to use the `PerformanceTracker` class, but this class didn't exist in the `includes/classes/Monitoring` directory.

**Fix**: Created the missing `PerformanceTracker.php` file in the `includes/classes/Monitoring` directory based on the existing implementation in `src/Monitoring/PerformanceTracker.php`.

### 4. Fatal Error: Class "TMU\Analytics\UserAnalytics" not found

**Problem**: The `AnalyticsManager` class was trying to use the `UserAnalytics` class, but this class didn't exist in the `includes/classes/Analytics` directory.

**Fix**: Created the missing `UserAnalytics.php` file in the `includes/classes/Analytics` directory based on the existing implementation in `src/Analytics/UserAnalytics.php`.

### 5. TypeError: TMU\Admin\Navigation\SubMenus::highlightActiveSubmenu(): Argument #1 ($submenu_file) must be of type string, null given

**Problem**: The `highlightActiveSubmenu` method in the `SubMenus` class was defined to accept a string parameter, but WordPress was calling it with a null value.

**Fix**: Modified the method signature and implementation to handle null values:

```php
// Before
public function highlightActiveSubmenu(string $submenu_file, string $parent_file): string {

// After
public function highlightActiveSubmenu(?string $submenu_file, ?string $parent_file): ?string {
    // Also added null checks in the method body
    if ($post_type && in_array($post_type, ['movie', 'tv', 'drama', 'people'])) {
        if ($submenu_file && strpos($submenu_file, $post_type) !== false) {
            // ...
        }
    }
}
```

## Required Setup Steps

To complete the theme setup, you need to run the following commands:

### 1. Install Node.js Dependencies

The theme uses Node.js for asset compilation with Webpack and Tailwind CSS. The `node_modules` directory is missing, so you need to install the dependencies:

```bash
cd tmu-theme
npm install
```

### 2. Build Theme Assets

After installing the dependencies, you need to build the assets (CSS and JavaScript files):

```bash
# For development (with file watching)
npm run dev

# For production (minified files)
npm run build:all
```

This will create the `assets/build` directory with compiled CSS and JavaScript files.

### 3. Verify Composer Dependencies

The Composer dependencies appear to be installed (the `vendor` directory exists), but you may want to ensure they're up-to-date:

```bash
cd tmu-theme
composer install
```

### 4. Check Theme Activation

After completing the above steps:

1. Activate the theme in WordPress admin
2. Check for any additional errors or warnings
3. Verify that all theme features are working correctly

## Theme Structure

The TMU theme follows a modern architecture:

- **PSR-4 Autoloading**: Classes are loaded automatically via the Composer autoloader
- **Singleton Pattern**: Many core classes use the singleton pattern with private constructors and `getInstance()` methods
- **Tailwind CSS**: Styling is done with Tailwind CSS utility classes
- **Webpack**: Asset bundling is handled by Webpack

## Common Issues

### Private Constructor Error

If you encounter similar errors with other classes, check if they have a private constructor. If they do, you should use their `getInstance()` method instead of direct instantiation:

```php
// Incorrect
$instance = new ClassName();

// Correct
$instance = ClassName::getInstance();
```

### Missing Singleton Pattern

If you encounter an "undefined method getInstance()" error, the class likely needs to be updated to implement the singleton pattern:

```php
// Add to the class
private static $instance = null;

public static function getInstance(): ClassName {
    if (self::$instance === null) {
        self::$instance = new self();
    }
    return self::$instance;
}
```

### Missing Classes

If you encounter a "Class not found" error, check if the class file exists in the correct location. If it doesn't, you may need to create it based on existing implementations in other directories (like `src/`).

### Type Errors

If you encounter a TypeError about argument types, check the method signature and make it more flexible by:

1. Using nullable types (`?string` instead of `string`)
2. Adding null checks in the method body
3. Using type coercion where appropriate

### Missing Assets

If styles or scripts are not loading properly, ensure you've built the assets:

```bash
npm run build:all
```

### PHP Version Compatibility

The theme requires PHP 7.4 or higher. Ensure your server meets this requirement. 

## Post Types, Taxonomies, and Blocks Fixes

### 6. Post Types Not Registering Properly

**Problem**: Post types were not being registered properly due to missing proper WordPress hooks and feature flag checks.

**Fix**:
- Updated `ThemeCore.php` to register post types on the `init` hook with proper priority
- Added feature flag checks in `PostTypeManager.php` to enable/disable post types via theme settings
- Implemented proper activation hooks to flush rewrite rules when the theme is activated

```php
// In ThemeCore.php
add_action('init', function() {
    PostTypes\PostTypeManager::getInstance();
}, 5);
```

### 7. Taxonomies Not Registering Properly

**Problem**: Taxonomies were not being registered properly due to missing proper WordPress hooks and activation handling.

**Fix**:
- Updated `ThemeCore.php` to register taxonomies on the `init` hook with proper priority
- Added checks in `AbstractTaxonomy.php` to prevent duplicate taxonomy registration
- Implemented proper activation hooks to flush rewrite rules when the theme is activated

```php
// In AbstractTaxonomy.php
if (!taxonomy_exists($this->taxonomy)) {
    register_taxonomy(
        $this->taxonomy,
        $this->object_types,
        $this->getArgs()
    );
}
```

### 8. Blocks Not Working Properly

**Problem**: Gutenberg blocks were not registering or loading assets correctly.

**Fix**:
- Created missing JavaScript files for blocks:
  - `assets/build/js/blocks.js` (frontend block functionality)
  - `assets/build/js/blocks-editor.js` (Gutenberg editor functionality)
- Updated `BaseBlock.php` to include proper registration method with feature flag checks
- Fixed asset loading in `BlockRegistry.php` to properly enqueue scripts and styles

## Additional Setup for Post Types, Taxonomies, and Blocks

### 1. Verify Post Types

After activating the theme, check that all custom post types are registered correctly:

```bash
# Using WP-CLI
wp post-type list
```

You should see the following custom post types:
- movie
- tv
- drama
- people
- video
- season
- episode
- drama-episode

### 2. Verify Taxonomies

Check that all taxonomies are registered correctly:

```bash
# Using WP-CLI
wp taxonomy list
```

### 3. Verify Blocks

The theme includes several custom Gutenberg blocks that should be available in the block editor. To ensure they're working correctly:

1. Create a new post or page
2. Open the block inserter
3. Look for the "TMU Blocks" category
4. Verify that blocks like "Movie Metadata", "TV Series Metadata", etc. are available

If blocks are not appearing, ensure that:
- You've built the JavaScript assets (`npm run build:all`)
- The block registration code is running properly
- There are no JavaScript errors in the browser console 

## Additional Fixes (Latest)

### 9. Fatal Error: Call to undefined function get_theme_file_version()

**Problem**: The QuickStats.php file was trying to use a function called `get_theme_file_version()` that didn't exist.

**Fix**: 
- Created the missing `get_theme_file_version()` function in the helpers/functions.php file
- Updated references in QuickStats.php, QuickActions.php, and TMDBBox.php to use the global function with proper namespacing

```php
// Added to helpers/functions.php
function get_theme_file_version(string $file_path): string {
    $absolute_path = get_template_directory() . '/' . $file_path;
    
    if (file_exists($absolute_path)) {
        return (string) filemtime($absolute_path);
    }
    
    return TMU_VERSION ?? '1.0.0';
}
```

### 10. Post Types Not Enabled Properly

**Problem**: Post types were not being enabled properly due to incorrect configuration checks.

**Fix**:
- Updated all post type classes (Movie, TVShow, Drama, People) to check the configuration file first
- Fixed the `shouldRegister()` method in each class to use proper default values
- Added proper configuration loading in PostTypeManager

### 11. Taxonomy Registration Issues

**Problem**: Taxonomies were not being registered properly due to missing configuration and incorrect initialization.

**Fix**:
- Updated the TaxonomyManager class to properly load taxonomy configuration
- Fixed the Genre taxonomy's `shouldRegister()` method to check configuration first
- Added proper default values for taxonomy configuration

These fixes ensure that all post types and taxonomies are properly registered and enabled based on the theme configuration. 

## Additional Fixes (2023-07-08)

### 12. Missing sidebar.php File

**Problem**: The theme was generating a deprecated warning because it was calling `get_sidebar()` but didn't have a sidebar.php file.

**Fix**:
- Created a sidebar.php file with proper widget area registration
- Added widget registration code to functions.php

### 13. Post Types and Taxonomies Registration Timing Issues

**Problem**: Post types and taxonomies weren't registering properly due to initialization timing issues in WordPress.

**Fix**:
- Modified ThemeCore.php to initialize post types and taxonomies directly, not just through the init hook
- Updated PostTypeManager.php to avoid registering post types too early in the constructor
- Updated TaxonomyManager.php to avoid registering taxonomies too early in the constructor
- Added proper hook priorities to ensure correct registration order (taxonomies before post types)
- Fixed the rewrite rules flushing mechanism to only flush when needed

### 14. Block Registration Timing Issues

**Problem**: Blocks weren't registering properly due to initialization timing issues.

**Fix**:
- Updated BlockRegistry.php to register blocks with a priority of 15 on the init hook (after post types and taxonomies)
- Organized hook registration for better clarity

## Troubleshooting Common Issues

If you continue to experience issues with post types, taxonomies, or blocks not registering properly:

1. **Clear WordPress Cache**: Navigate to Settings > Permalinks and click "Save Changes" to flush rewrite rules.

2. **Deactivate and Reactivate the Theme**: This will trigger the activation hooks to run again.

3. **Check Debug Logs**: Enable WP_DEBUG in wp-config.php and check the debug.log file for any errors.

4. **Verify Configuration Files**: Make sure the config files in includes/config/ directory have the correct settings.

5. **Check for Plugin Conflicts**: Temporarily deactivate all plugins to see if there's a conflict. 

## Additional Fixes (2023-07-15)

### 15. Post Types and Taxonomies Not Showing in Admin

**Problem**: Post types and taxonomies were being registered correctly according to logs but were not showing up in the WordPress admin interface.

**Fix**:
1. Added comprehensive debug logging throughout the registration process to track exactly what was happening
2. Modified the registration order to ensure taxonomies are registered before post types
3. Updated hook priorities to ensure proper registration timing
4. Added direct registration fallbacks when the standard registration methods failed
5. Added explicit checks to verify if post types and taxonomies were properly registered in WordPress

```php
// In ThemeCore.php - Register taxonomies BEFORE post types with proper priorities
add_action('init', function() use ($taxonomy_manager) {
    // Force taxonomies to register again on init with priority 1
    tmu_log("Registering taxonomies via init hook with priority 1", 'debug');
    $taxonomy_manager->registerTaxonomies();
}, 1);

add_action('init', function() use ($post_type_manager) {
    // Force post types to register again on init with priority 5 (after taxonomies)
    tmu_log("Registering post types via init hook with priority 5", 'debug');
    $post_type_manager->registerAllPostTypes();
}, 5);
```

6. Added a debug function to check registration status after WordPress is fully loaded:

```php
function tmu_debug_registrations(): array {
    global $wp_post_types, $wp_taxonomies;
    
    $result = [
        'post_types' => [],
        'taxonomies' => [],
    ];
    
    // Check post types
    $expected_post_types = [
        'movie', 'tv', 'drama', 'people', 'video', 'season', 'episode', 'drama-episode'
    ];
    
    foreach ($expected_post_types as $post_type) {
        $result['post_types'][$post_type] = [
            'registered' => post_type_exists($post_type),
            'object' => isset($wp_post_types[$post_type]) ? $wp_post_types[$post_type] : null,
        ];
    }
    
    // Check taxonomies
    $expected_taxonomies = [
        'genre', 'country', 'language', 'network', 'production-company', 
        'by-year', 'profession', 'nationality'
    ];
    
    foreach ($expected_taxonomies as $taxonomy) {
        $result['taxonomies'][$taxonomy] = [
            'registered' => taxonomy_exists($taxonomy),
            'object' => isset($wp_taxonomies[$taxonomy]) ? $wp_taxonomies[$taxonomy] : null,
        ];
    }
    
    return $result;
}
```

7. Modified the `shouldRegister()` methods to always return true during debugging:

```php
protected function shouldRegister(): bool {
    // DEBUG: Always enable the post type for debugging
    tmu_log("Movie post type shouldRegister called - returning TRUE", 'debug');
    return true;
}
```

8. Added forced flushing of rewrite rules at strategic points to ensure proper URL handling.

## Troubleshooting Post Types and Taxonomies

If you continue to experience issues with post types or taxonomies not appearing in the WordPress admin:

1. **Check Debug Logs**: Enable WP_DEBUG and check the logs for any registration errors.

2. **Verify Registration Timing**: Ensure taxonomies are registered before post types.

3. **Check for Plugin Conflicts**: Some plugins might interfere with custom post type registration.

4. **Reset Permalinks**: Go to Settings > Permalinks and click "Save Changes" to flush rewrite rules.

5. **Check Database Options**: Verify that the options `tmu_movies`, `tmu_tv_series`, etc. are set to "on" in the options table.

6. **Use the Debug Function**: Call `tmu_debug_registrations()` to check the registration status of all post types and taxonomies.

7. **Check for Capability Issues**: Make sure the current user has the proper capabilities to view and manage the custom post types. 

## Additional Fixes (2023-07-20)

### 16. PHP Warnings and Fatal Errors with Class References

**Problem**: Several PHP warnings and fatal errors were occurring due to:
1. Private magic method `__wakeup()` in TaxonomyManager.php (must be public)
2. Missing backslashes before WordPress core class names (WP_Query, WP_Post, WP_Term, WP_User)

**Fix**:
1. Changed the visibility of `__wakeup()` method in TaxonomyManager.php from private to public:

```php
// Before (error)
private function __wakeup() {}

// After (fixed)
public function __wakeup() {}
```

2. Fixed type hints in method signatures by adding leading backslashes to WordPress core classes:

```php
// Before (error)
public function handleSorting(WP_Query $query): void

// After (fixed)
public function handleSorting(\WP_Query $query): void
```

3. Fixed all occurrences of WordPress core classes in method parameters and return types:
   - WP_Query → \WP_Query
   - WP_Post → \WP_Post
   - WP_Post_Type → \WP_Post_Type
   - WP_Term → \WP_Term
   - WP_User → \WP_User

These fixes ensure proper type checking and prevent PHP warnings and fatal errors when using WordPress core classes in type declarations. 