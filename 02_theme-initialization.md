# Step 2: Theme Initialization

## 🎯 Goal
Set up the complete theme initialization system using modern WordPress standards, including `functions.php` configuration, autoloader integration, and component management.

## 📋 What We'll Accomplish
- Create and configure `functions.php` 
- Integrate Composer autoloader
- Set up theme component system
- Configure WordPress theme support features
- Establish asset management system
- Implement proper error handling and debugging

---

## 🔧 Functions.php Setup

### Create `functions.php`
This is the main entry point for your theme. Create `functions.php` in the theme root:

```php
<?php
/**
 * TMU Theme Functions
 *
 * Main theme initialization and setup.
 *
 * @package TMUTheme
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define theme constants
define( 'TMU_THEME_VERSION', '1.0.0' );
define( 'TMU_THEME_PATH', get_template_directory() );
define( 'TMU_THEME_URL', get_template_directory_uri() );
define( 'TMU_THEME_ASSETS_URL', TMU_THEME_URL . '/assets' );

// Debug mode (set to false in production)
define( 'TMU_THEME_DEBUG', WP_DEBUG );

/**
 * Theme autoloader and initialization
 */
require_once TMU_THEME_PATH . '/vendor/autoload.php';

// Initialize the theme
add_action( 'after_setup_theme', 'tmu_theme_init', 0 );

/**
 * Initialize TMU Theme
 *
 * @return void
 */
function tmu_theme_init(): void {
    try {
        // Get theme instance and initialize
        $theme = \TMUTheme\Theme::get_instance();
        
        // Log successful initialization in debug mode
        if ( TMU_THEME_DEBUG ) {
            error_log( 'TMU Theme initialized successfully' );
        }
        
    } catch ( Exception $e ) {
        // Handle initialization errors gracefully
        add_action( 'admin_notices', function() use ( $e ) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>TMU Theme Error:</strong> ' . esc_html( $e->getMessage() ) . '</p>';
            echo '</div>';
        } );
        
        // Log error for debugging
        if ( TMU_THEME_DEBUG ) {
            error_log( 'TMU Theme initialization failed: ' . $e->getMessage() );
        }
    }
}

/**
 * Theme activation hook
 */
function tmu_theme_activation(): void {
    // Flush rewrite rules to ensure custom post types work
    flush_rewrite_rules();
    
    // Set default theme options
    tmu_set_default_options();
    
    // Log activation
    if ( TMU_THEME_DEBUG ) {
        error_log( 'TMU Theme activated' );
    }
}
add_action( 'after_switch_theme', 'tmu_theme_activation' );

/**
 * Theme deactivation hook
 */
function tmu_theme_deactivation(): void {
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Log deactivation
    if ( TMU_THEME_DEBUG ) {
        error_log( 'TMU Theme deactivated' );
    }
}
add_action( 'switch_theme', 'tmu_theme_deactivation' );

/**
 * Set default theme options
 *
 * @return void
 */
function tmu_set_default_options(): void {
    $defaults = [
        'tmu_enable_movies'     => true,
        'tmu_enable_tv_series'  => true,
        'tmu_enable_dramas'     => true,
        'tmu_enable_people'     => true,
        'tmu_tmdb_api_key'      => '',
        'tmu_auto_fetch_data'   => false,
        'tmu_enable_ratings'    => true,
        'tmu_enable_comments'   => true,
    ];
    
    foreach ( $defaults as $option => $value ) {
        if ( false === get_option( $option ) ) {
            add_option( $option, $value );
        }
    }
}

/**
 * Helper function to get theme option
 *
 * @param string $option Option name.
 * @param mixed  $default Default value.
 * @return mixed
 */
function tmu_get_option( string $option, $default = null ) {
    return get_option( $option, $default );
}

/**
 * Check if debug mode is enabled
 *
 * @return bool
 */
function tmu_is_debug(): bool {
    return defined( 'TMU_THEME_DEBUG' ) && TMU_THEME_DEBUG;
}

/**
 * Log debug messages
 *
 * @param string $message Log message.
 * @param string $level Log level (info, warning, error).
 * @return void
 */
function tmu_log( string $message, string $level = 'info' ): void {
    if ( tmu_is_debug() ) {
        error_log( sprintf( '[TMU Theme %s] %s', strtoupper( $level ), $message ) );
    }
}
```

---

## 🏗️ Enhanced Theme Class

Update `src/Theme.php` to include complete component management:

```php
<?php
/**
 * Main Theme Class
 *
 * @package TMUTheme
 * @since 1.0.0
 */

namespace TMUTheme;

use TMUTheme\Database\DatabaseManager;
use TMUTheme\PostTypes\PostTypeManager;
use TMUTheme\Taxonomies\TaxonomyManager;
use TMUTheme\Fields\FieldManager;
use TMUTheme\API\APIManager;
use TMUTheme\Admin\AdminManager;
use TMUTheme\Frontend\FrontendManager;
use TMUTheme\SEO\SEOManager;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Theme orchestrator class
 * 
 * Coordinates all theme functionality and manages component initialization
 */
class Theme {
    
    /**
     * Theme version
     */
    const VERSION = '1.0.0';
    
    /**
     * Minimum WordPress version required
     */
    const MIN_WP_VERSION = '5.8';
    
    /**
     * Minimum PHP version required
     */
    const MIN_PHP_VERSION = '7.4';
    
    /**
     * Theme instance
     *
     * @var Theme
     */
    private static $instance = null;
    
    /**
     * Theme components
     *
     * @var array
     */
    private $components = [];
    
    /**
     * Initialization status
     *
     * @var bool
     */
    private $initialized = false;
    
    /**
     * Get theme instance (Singleton pattern)
     *
     * @return Theme
     */
    public static function get_instance(): Theme {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->check_requirements();
        $this->init_hooks();
    }
    
    /**
     * Check system requirements
     *
     * @return void
     * @throws \Exception If requirements are not met.
     */
    private function check_requirements(): void {
        // Check WordPress version
        if ( version_compare( get_bloginfo( 'version' ), self::MIN_WP_VERSION, '<' ) ) {
            throw new \Exception( 
                sprintf( 
                    'WordPress %s or higher is required. Current version: %s', 
                    self::MIN_WP_VERSION, 
                    get_bloginfo( 'version' ) 
                ) 
            );
        }
        
        // Check PHP version
        if ( version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '<' ) ) {
            throw new \Exception( 
                sprintf( 
                    'PHP %s or higher is required. Current version: %s', 
                    self::MIN_PHP_VERSION, 
                    PHP_VERSION 
                ) 
            );
        }
        
        // Check required PHP extensions
        $required_extensions = [ 'curl', 'json', 'mbstring' ];
        foreach ( $required_extensions as $extension ) {
            if ( ! extension_loaded( $extension ) ) {
                throw new \Exception( "Required PHP extension '{$extension}' is not loaded." );
            }
        }
    }
    
    /**
     * Initialize WordPress hooks
     *
     * @return void
     */
    private function init_hooks(): void {
        add_action( 'after_setup_theme', [ $this, 'setup_theme' ], 10 );
        add_action( 'init', [ $this, 'load_textdomain' ], 0 );
        add_action( 'init', [ $this, 'init_components' ], 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'wp_head', [ $this, 'add_theme_meta' ] );
    }
    
    /**
     * Theme setup
     *
     * @return void
     */
    public function setup_theme(): void {
        if ( $this->initialized ) {
            return;
        }
        
        // Add theme support
        $this->add_theme_support();
        
        // Register navigation menus
        $this->register_menus();
        
        // Add image sizes
        $this->add_image_sizes();
        
        // Set content width
        $this->set_content_width();
        
        $this->initialized = true;
        
        // Hook for theme setup completion
        do_action( 'tmu_theme_setup_complete' );
    }
    
    /**
     * Add theme support features
     *
     * @return void
     */
    private function add_theme_support(): void {
        // Basic WordPress features
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'automatic-feed-links' );
        
        // HTML5 support
        add_theme_support( 'html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script'
        ] );
        
        // Custom logo
        add_theme_support( 'custom-logo', [
            'height'      => 100,
            'width'       => 400,
            'flex-height' => true,
            'flex-width'  => true,
        ] );
        
        // Selective refresh for widgets
        add_theme_support( 'customize-selective-refresh-widgets' );
        
        // Custom background
        add_theme_support( 'custom-background', [
            'default-color' => 'ffffff',
        ] );
        
        // Editor styles
        add_theme_support( 'editor-styles' );
        add_editor_style( 'assets/css/editor-style.css' );
        
        // Wide alignment for Gutenberg
        add_theme_support( 'align-wide' );
        
        // Responsive embeds
        add_theme_support( 'responsive-embeds' );
        
        // Custom spacing for Gutenberg
        add_theme_support( 'custom-spacing' );
        
        // Custom line height
        add_theme_support( 'custom-line-height' );
    }
    
    /**
     * Register navigation menus
     *
     * @return void
     */
    private function register_menus(): void {
        register_nav_menus( [
            'primary'   => esc_html__( 'Primary Menu', 'tmu-theme' ),
            'footer'    => esc_html__( 'Footer Menu', 'tmu-theme' ),
            'social'    => esc_html__( 'Social Links', 'tmu-theme' ),
            'mobile'    => esc_html__( 'Mobile Menu', 'tmu-theme' ),
        ] );
    }
    
    /**
     * Add custom image sizes
     *
     * @return void
     */
    private function add_image_sizes(): void {
        // Movie poster sizes
        add_image_size( 'movie-poster-small', 150, 225, true );
        add_image_size( 'movie-poster-medium', 300, 450, true );
        add_image_size( 'movie-poster-large', 500, 750, true );
        
        // Backdrop sizes
        add_image_size( 'movie-backdrop-small', 400, 225, true );
        add_image_size( 'movie-backdrop-medium', 800, 450, true );
        add_image_size( 'movie-backdrop-large', 1200, 675, true );
        
        // Person profile sizes
        add_image_size( 'person-profile-small', 100, 150, true );
        add_image_size( 'person-profile-medium', 200, 300, true );
        add_image_size( 'person-profile-large', 300, 450, true );
    }
    
    /**
     * Set content width
     *
     * @return void
     */
    private function set_content_width(): void {
        $GLOBALS['content_width'] = apply_filters( 'tmu_content_width', 1200 );
    }
    
    /**
     * Initialize theme components
     *
     * @return void
     */
    public function init_components(): void {
        try {
            // Initialize database manager first
            $this->components['database'] = new DatabaseManager();
            
            // Initialize post types and taxonomies
            $this->components['post_types'] = new PostTypeManager();
            $this->components['taxonomies'] = new TaxonomyManager();
            
            // Initialize custom fields
            $this->components['fields'] = new FieldManager();
            
            // Initialize API manager
            $this->components['api'] = new APIManager();
            
            // Initialize admin interface
            if ( is_admin() ) {
                $this->components['admin'] = new AdminManager();
            }
            
            // Initialize frontend features
            if ( ! is_admin() ) {
                $this->components['frontend'] = new FrontendManager();
            }
            
            // Initialize SEO features
            $this->components['seo'] = new SEOManager();
            
            // Hook for components initialization completion
            do_action( 'tmu_components_initialized', $this->components );
            
        } catch ( \Exception $e ) {
            // Log component initialization errors
            if ( defined( 'TMU_THEME_DEBUG' ) && TMU_THEME_DEBUG ) {
                error_log( 'TMU Theme component initialization failed: ' . $e->getMessage() );
            }
            
            // Show admin notice for critical errors
            add_action( 'admin_notices', function() use ( $e ) {
                echo '<div class="notice notice-error">';
                echo '<p><strong>TMU Theme:</strong> Failed to initialize components. ' . esc_html( $e->getMessage() ) . '</p>';
                echo '</div>';
            } );
        }
    }
    
    /**
     * Load theme textdomain
     *
     * @return void
     */
    public function load_textdomain(): void {
        load_theme_textdomain( 'tmu-theme', get_template_directory() . '/languages' );
    }
    
    /**
     * Enqueue frontend assets
     *
     * @return void
     */
    public function enqueue_assets(): void {
        // Main stylesheet
        wp_enqueue_style(
            'tmu-theme-style',
            get_stylesheet_uri(),
            [],
            self::VERSION
        );
        
        // Main CSS
        wp_enqueue_style(
            'tmu-theme-main',
            $this->get_asset_url( 'css/main.css' ),
            [ 'tmu-theme-style' ],
            self::VERSION
        );
        
        // Main JavaScript
        wp_enqueue_script(
            'tmu-theme-script',
            $this->get_asset_url( 'js/main.js' ),
            [ 'jquery' ],
            self::VERSION,
            true
        );
        
        // AJAX handling
        wp_enqueue_script(
            'tmu-theme-ajax',
            $this->get_asset_url( 'js/ajax.js' ),
            [ 'jquery', 'tmu-theme-script' ],
            self::VERSION,
            true
        );
        
        // Localize scripts
        wp_localize_script( 'tmu-theme-script', 'tmuTheme', [
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'tmu_theme_nonce' ),
            'themeUrl'    => get_template_directory_uri(),
            'assetsUrl'   => $this->get_asset_url(),
            'strings'     => [
                'loading'     => esc_html__( 'Loading...', 'tmu-theme' ),
                'error'       => esc_html__( 'An error occurred.', 'tmu-theme' ),
                'tryAgain'    => esc_html__( 'Please try again.', 'tmu-theme' ),
                'noResults'   => esc_html__( 'No results found.', 'tmu-theme' ),
            ],
            'options'     => [
                'enableAjax'  => tmu_get_option( 'tmu_enable_ajax', true ),
                'enableDebug' => tmu_is_debug(),
            ],
        ] );
        
        // Conditional assets
        if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
            wp_enqueue_script( 'comment-reply' );
        }
        
        // Rating system assets
        if ( tmu_get_option( 'tmu_enable_ratings', true ) ) {
            wp_enqueue_script(
                'tmu-theme-rating',
                $this->get_asset_url( 'js/rating.js' ),
                [ 'jquery' ],
                self::VERSION,
                true
            );
        }
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook_suffix The current admin page hook suffix.
     * @return void
     */
    public function enqueue_admin_assets( string $hook_suffix ): void {
        // Admin styles
        wp_enqueue_style(
            'tmu-theme-admin-style',
            $this->get_asset_url( 'css/admin.css' ),
            [],
            self::VERSION
        );
        
        // Admin JavaScript
        wp_enqueue_script(
            'tmu-theme-admin-script',
            $this->get_asset_url( 'js/admin.js' ),
            [ 'jquery' ],
            self::VERSION,
            true
        );
        
        // Media scripts for image uploads
        if ( in_array( $hook_suffix, [ 'post.php', 'post-new.php' ] ) ) {
            wp_enqueue_media();
        }
        
        // Localize admin script
        wp_localize_script( 'tmu-theme-admin-script', 'tmuAdmin', [
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'tmu_admin_nonce' ),
            'strings'  => [
                'confirm'     => esc_html__( 'Are you sure?', 'tmu-theme' ),
                'saved'       => esc_html__( 'Settings saved.', 'tmu-theme' ),
                'error'       => esc_html__( 'An error occurred.', 'tmu-theme' ),
                'processing'  => esc_html__( 'Processing...', 'tmu-theme' ),
            ],
        ] );
    }
    
    /**
     * Add theme meta tags to head
     *
     * @return void
     */
    public function add_theme_meta(): void {
        echo '<meta name="generator" content="TMU Theme ' . esc_attr( self::VERSION ) . '">' . "\n";
        
        // Viewport meta tag for responsive design
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
        
        // Theme color for mobile browsers
        echo '<meta name="theme-color" content="#1a1a1a">' . "\n";
    }
    
    /**
     * Get component instance
     *
     * @param string $component Component name.
     * @return object|null
     */
    public function get_component( string $component ) {
        return $this->components[ $component ] ?? null;
    }
    
    /**
     * Check if component is loaded
     *
     * @param string $component Component name.
     * @return bool
     */
    public function has_component( string $component ): bool {
        return isset( $this->components[ $component ] );
    }
    
    /**
     * Get theme version
     *
     * @return string
     */
    public function get_version(): string {
        return self::VERSION;
    }
    
    /**
     * Get theme path
     *
     * @param string $path Optional path to append.
     * @return string
     */
    public function get_path( string $path = '' ): string {
        return get_template_directory() . ( $path ? '/' . ltrim( $path, '/' ) : '' );
    }
    
    /**
     * Get theme URL
     *
     * @param string $path Optional path to append.
     * @return string
     */
    public function get_url( string $path = '' ): string {
        return get_template_directory_uri() . ( $path ? '/' . ltrim( $path, '/' ) : '' );
    }
    
    /**
     * Get asset URL
     *
     * @param string $path Asset path.
     * @return string
     */
    public function get_asset_url( string $path = '' ): string {
        $base_url = get_template_directory_uri() . '/assets';
        return $path ? $base_url . '/' . ltrim( $path, '/' ) : $base_url;
    }
    
    /**
     * Check if theme is initialized
     *
     * @return bool
     */
    public function is_initialized(): bool {
        return $this->initialized;
    }
}
```

---

## 📁 Basic Asset Files

### Create `assets/css/main.css`
Create the main stylesheet foundation:

```css
/*!
 * TMU Theme Main Styles
 * Version: 1.0.0
 */

/* ==========================================================================
   CSS Reset and Normalize
   ========================================================================== */

*,
*::before,
*::after {
    box-sizing: border-box;
}

html {
    line-height: 1.15;
    -webkit-text-size-adjust: 100%;
}

body {
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    font-size: 1rem;
    line-height: 1.6;
    color: #333;
    background-color: #fff;
}

/* ==========================================================================
   Layout Components
   ========================================================================== */

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.site-main {
    min-height: 70vh;
}

/* ==========================================================================
   Typography
   ========================================================================== */

h1, h2, h3, h4, h5, h6 {
    margin: 0 0 1rem 0;
    font-weight: 600;
    line-height: 1.2;
}

h1 { font-size: 2.5rem; }
h2 { font-size: 2rem; }
h3 { font-size: 1.75rem; }
h4 { font-size: 1.5rem; }
h5 { font-size: 1.25rem; }
h6 { font-size: 1rem; }

p {
    margin: 0 0 1rem 0;
}

a {
    color: #007cba;
    text-decoration: none;
    transition: color 0.3s ease;
}

a:hover,
a:focus {
    color: #005a87;
}

/* ==========================================================================
   Components
   ========================================================================== */

.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 500;
    text-align: center;
    text-decoration: none;
    border: 1px solid transparent;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    color: #fff;
    background-color: #007cba;
    border-color: #007cba;
}

.btn-primary:hover {
    background-color: #005a87;
    border-color: #005a87;
}

/* ==========================================================================
   Post Grid
   ========================================================================== */

.posts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.post-card {
    border: 1px solid #e0e0e0;
    border-radius: 0.5rem;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.post-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.post-thumbnail img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.post-content {
    padding: 1.5rem;
}

.post-title {
    margin-bottom: 0.5rem;
}

.post-title a {
    color: #333;
}

.post-meta {
    color: #666;
    font-size: 0.875rem;
    margin-bottom: 1rem;
}

.post-excerpt {
    margin-bottom: 1rem;
}

.read-more {
    font-weight: 500;
    color: #007cba;
}

/* ==========================================================================
   Responsive Design
   ========================================================================== */

@media (max-width: 768px) {
    .container {
        padding: 0 15px;
    }
    
    .posts-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    h1 { font-size: 2rem; }
    h2 { font-size: 1.75rem; }
    h3 { font-size: 1.5rem; }
}

/* ==========================================================================
   Utility Classes
   ========================================================================== */

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.text-center { text-align: center; }
.text-left { text-align: left; }
.text-right { text-align: right; }

.mb-0 { margin-bottom: 0; }
.mb-1 { margin-bottom: 0.5rem; }
.mb-2 { margin-bottom: 1rem; }
.mb-3 { margin-bottom: 1.5rem; }
.mb-4 { margin-bottom: 2rem; }

.mt-0 { margin-top: 0; }
.mt-1 { margin-top: 0.5rem; }
.mt-2 { margin-top: 1rem; }
.mt-3 { margin-top: 1.5rem; }
.mt-4 { margin-top: 2rem; }
```

### Create `assets/js/main.js`
Create the main JavaScript file:

```javascript
/*!
 * TMU Theme Main Scripts
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    // Theme object
    window.TMUTheme = window.TMUTheme || {};

    // Initialize theme
    TMUTheme.init = function() {
        TMUTheme.utils.init();
        TMUTheme.components.init();
        
        // Log initialization in debug mode
        if (window.tmuTheme && window.tmuTheme.options.enableDebug) {
            console.log('TMU Theme JavaScript initialized');
        }
    };

    // Utility functions
    TMUTheme.utils = {
        init: function() {
            this.setupAjax();
            this.setupEventHandlers();
        },

        setupAjax: function() {
            // Set up AJAX defaults
            if (window.tmuTheme) {
                $.ajaxSetup({
                    data: {
                        nonce: window.tmuTheme.nonce
                    }
                });
            }
        },

        setupEventHandlers: function() {
            // Global click handler for AJAX actions
            $(document).on('click', '[data-ajax-action]', this.handleAjaxAction);
            
            // Handle responsive images
            this.setupLazyLoading();
        },

        handleAjaxAction: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var action = $button.data('ajax-action');
            var data = $button.data() || {};
            
            if (!action || !window.tmuTheme) {
                return;
            }
            
            // Add loading state
            $button.addClass('loading').prop('disabled', true);
            
            // Prepare AJAX data
            var ajaxData = {
                action: action,
                nonce: window.tmuTheme.nonce
            };
            
            // Merge additional data
            $.extend(ajaxData, data);
            
            // Make AJAX request
            $.post(window.tmuTheme.ajaxUrl, ajaxData)
                .done(function(response) {
                    if (response.success) {
                        TMUTheme.utils.showMessage(response.data.message || 'Success', 'success');
                        $button.trigger('tmu:ajax:success', [response.data]);
                    } else {
                        TMUTheme.utils.showMessage(response.data || 'An error occurred', 'error');
                    }
                })
                .fail(function() {
                    TMUTheme.utils.showMessage(window.tmuTheme.strings.error, 'error');
                })
                .always(function() {
                    $button.removeClass('loading').prop('disabled', false);
                });
        },

        setupLazyLoading: function() {
            // Basic lazy loading for images
            if ('IntersectionObserver' in window) {
                var imageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.classList.remove('lazy');
                                observer.unobserve(img);
                            }
                        }
                    });
                });

                document.querySelectorAll('img[data-src]').forEach(function(img) {
                    img.classList.add('lazy');
                    imageObserver.observe(img);
                });
            }
        },

        showMessage: function(message, type) {
            type = type || 'info';
            
            var $notice = $('<div class="tmu-notice tmu-notice-' + type + '">' + message + '</div>');
            
            $('body').prepend($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            }, 5000);
        },

        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    };

    // Component initialization
    TMUTheme.components = {
        init: function() {
            this.initNavigation();
            this.initSearch();
            this.initModals();
        },

        initNavigation: function() {
            // Mobile menu toggle
            $('.mobile-menu-toggle').on('click', function() {
                $(this).toggleClass('active');
                $('.primary-navigation').toggleClass('active');
            });

            // Dropdown menus
            $('.menu-item-has-children > a').on('click', function(e) {
                if ($(window).width() < 768) {
                    e.preventDefault();
                    $(this).parent().toggleClass('open');
                }
            });
        },

        initSearch: function() {
            var searchForm = $('.search-form');
            var searchInput = searchForm.find('input[type="search"]');
            
            if (searchInput.length) {
                var debouncedSearch = TMUTheme.utils.debounce(function() {
                    var query = searchInput.val();
                    if (query.length > 2) {
                        TMUTheme.components.performSearch(query);
                    }
                }, 300);

                searchInput.on('input', debouncedSearch);
            }
        },

        performSearch: function(query) {
            if (!window.tmuTheme) return;

            $.post(window.tmuTheme.ajaxUrl, {
                action: 'tmu_ajax_search',
                query: query,
                nonce: window.tmuTheme.nonce
            })
            .done(function(response) {
                if (response.success) {
                    $('.search-results').html(response.data.html);
                }
            });
        },

        initModals: function() {
            // Modal triggers
            $('[data-modal]').on('click', function(e) {
                e.preventDefault();
                var modalId = $(this).data('modal');
                TMUTheme.components.openModal(modalId);
            });

            // Close modal
            $(document).on('click', '.modal-close, .modal-overlay', function() {
                TMUTheme.components.closeModal();
            });

            // ESC key to close modal
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) {
                    TMUTheme.components.closeModal();
                }
            });
        },

        openModal: function(modalId) {
            var $modal = $('#' + modalId);
            if ($modal.length) {
                $modal.addClass('active');
                $('body').addClass('modal-open');
            }
        },

        closeModal: function() {
            $('.modal').removeClass('active');
            $('body').removeClass('modal-open');
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        TMUTheme.init();
    });

    // Re-initialize on AJAX complete (for dynamic content)
    $(document).ajaxComplete(function() {
        TMUTheme.components.init();
    });

})(jQuery);
```

---

## ✅ Verification Checklist

After completing this step, verify:

- [ ] `functions.php` is created and loads without errors
- [ ] Composer autoloader is properly integrated
- [ ] Theme constants are defined correctly
- [ ] Main Theme class is enhanced with component management
- [ ] Error handling and logging are implemented
- [ ] Asset enqueuing system is working
- [ ] Basic CSS and JavaScript files are created
- [ ] Theme appears in WordPress admin under Appearance > Themes
- [ ] No PHP errors in debug log
- [ ] JavaScript console shows no errors

---

## 🔍 Testing the Setup

1. **Activate the theme** in WordPress admin
2. **Check the frontend** - ensure basic styling is applied
3. **Check browser console** - ensure no JavaScript errors
4. **Check WordPress debug log** - ensure no PHP errors
5. **Verify asset loading** - check that CSS and JS files load correctly

---

## 🎯 Next Step

Once your theme initialization is complete and tested, proceed to **[Step 3: Database Alignment](03_database-alignment.md)** to set up database compatibility and custom table management.

---

*Estimated time for this step: 45-60 minutes*