# Step 1: Project Setup and File Structure

## 🎯 Goal
Set up a professional development environment and create a modern, scalable file structure for the TMU WordPress theme using OOP architecture, namespacing, and autoloading.

## 📋 What We'll Accomplish
- Set up local development environment
- Create theme directory structure
- Configure Composer for autoloading
- Establish coding standards and conventions
- Create essential theme files

---

## 🛠️ Development Environment Setup

### 1. Local WordPress Installation
Choose one of these options for your local development:

#### Option A: Local by Flywheel (Recommended)
```bash
# Download from: https://localwp.com/
# Create new site: "tmu-theme-dev"
# WordPress version: Latest
# PHP version: 7.4 or 8.x
# Database: MySQL 8.0
```

#### Option B: Docker Setup
```yaml
# docker-compose.yml
version: '3.8'
services:
  wordpress:
    image: wordpress:latest
    ports:
      - "8080:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress
      WORDPRESS_DB_NAME: wordpress
    volumes:
      - ./wp-content:/var/www/html/wp-content
  
  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress
      MYSQL_ROOT_PASSWORD: root
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

### 2. Required Tools Installation

#### Composer (for autoloading)
```bash
# Download from: https://getcomposer.org/download/
# Or via package manager:

# macOS (Homebrew)
brew install composer

# Ubuntu/Debian
sudo apt install composer

# Windows (Chocolatey)
choco install composer
```

#### WP-CLI (Optional but recommended)
```bash
# Download from: https://wp-cli.org/
curl -O https://raw.githubusercontent.com/wp-cli/wp-cli/v2.8.1/utils/wp-completion.bash
```

---

## 📂 Theme Directory Structure

Create the following directory structure in `wp-content/themes/tmu-theme/`:

```
tmu-theme/
├── style.css                          # Required WordPress theme file
├── index.php                          # Required fallback template
├── functions.php                      # Theme initialization
├── screenshot.png                     # Theme preview (1200x900px)
├── composer.json                      # Autoloading configuration
├── composer.lock                      # Composer lock file (auto-generated)
├── vendor/                            # Composer dependencies (auto-generated)
├── src/                              # Source code (PSR-4 autoloaded)
│   ├── Theme.php                     # Main theme orchestrator
│   ├── Database/
│   │   ├── DatabaseManager.php      # Database operations
│   │   ├── TableCreator.php         # Custom table creation
│   │   └── Migration.php            # Data migration utilities
│   ├── PostTypes/
│   │   ├── PostTypeManager.php      # CPT registration manager
│   │   ├── Movie.php                # Movie post type
│   │   ├── TVSeries.php             # TV series post type
│   │   ├── Drama.php                # Drama post type
│   │   ├── Episode.php              # Episode post type
│   │   ├── Season.php               # Season post type
│   │   ├── Person.php               # Person post type
│   │   └── Video.php                # Video post type
│   ├── Taxonomies/
│   │   ├── TaxonomyManager.php      # Taxonomy registration manager
│   │   ├── Genre.php                # Genre taxonomy
│   │   ├── Country.php              # Country taxonomy
│   │   ├── Language.php             # Language taxonomy
│   │   ├── Network.php              # Network taxonomy
│   │   ├── ByYear.php               # Year taxonomy
│   │   └── Keyword.php              # Keyword taxonomy
│   ├── Fields/
│   │   ├── FieldManager.php         # Metabox registration manager
│   │   ├── MovieFields.php          # Movie custom fields
│   │   ├── TVSeriesFields.php       # TV series custom fields
│   │   ├── DramaFields.php          # Drama custom fields
│   │   ├── EpisodeFields.php        # Episode custom fields
│   │   ├── SeasonFields.php         # Season custom fields
│   │   ├── PersonFields.php         # Person custom fields
│   │   └── BaseFields.php           # Base field functionality
│   ├── API/
│   │   ├── TMDBClient.php           # TMDB API client
│   │   ├── MovieAPI.php             # Movie API operations
│   │   ├── TVSeriesAPI.php          # TV series API operations
│   │   ├── DramaAPI.php             # Drama API operations
│   │   ├── PersonAPI.php            # Person API operations
│   │   └── APIManager.php           # API coordination
│   ├── Admin/
│   │   ├── AdminManager.php         # Admin interface manager
│   │   ├── ListTable.php            # Custom list table columns
│   │   ├── MetaBoxes.php            # Admin metabox management
│   │   ├── Settings.php             # Theme settings page
│   │   └── Notices.php              # Admin notices
│   ├── Frontend/
│   │   ├── FrontendManager.php      # Frontend display manager
│   │   ├── TemplateLoader.php       # Template loading logic
│   │   ├── ShortcodeManager.php     # Shortcode registration
│   │   └── Assets.php               # Frontend asset management
│   ├── SEO/
│   │   ├── SEOManager.php           # SEO functionality manager
│   │   ├── SchemaMarkup.php         # Structured data
│   │   ├── MetaTags.php             # Meta tag generation
│   │   └── Sitemap.php              # XML sitemap generation
│   └── Utils/
│       ├── Validator.php            # Input validation
│       ├── Sanitizer.php            # Data sanitization
│       ├── ImageProcessor.php       # Image handling
│       ├── CacheManager.php         # Caching utilities
│       └── Logger.php               # Logging functionality
├── templates/                        # WordPress template files
│   ├── single-movie.php             # Single movie template
│   ├── single-tv-series.php         # Single TV series template
│   ├── single-drama.php             # Single drama template
│   ├── single-episode.php           # Single episode template
│   ├── single-season.php            # Single season template
│   ├── single-person.php            # Single person template
│   ├── single-video.php             # Single video template
│   ├── archive-movie.php            # Movie archive template
│   ├── archive-tv-series.php        # TV series archive template
│   ├── archive-drama.php            # Drama archive template
│   ├── archive-person.php           # Person archive template
│   ├── taxonomy-genre.php           # Genre taxonomy template
│   ├── taxonomy-country.php         # Country taxonomy template
│   ├── taxonomy-language.php        # Language taxonomy template
│   ├── taxonomy-network.php         # Network taxonomy template
│   ├── taxonomy-by-year.php         # Year taxonomy template
│   ├── taxonomy-keyword.php         # Keyword taxonomy template
│   ├── search.php                   # Search results template
│   ├── 404.php                      # Not found template
│   └── parts/                       # Template parts
│       ├── header-movie.php         # Movie header section
│       ├── cast-crew.php            # Cast and crew display
│       ├── rating-system.php        # Rating display
│       ├── video-gallery.php        # Video gallery
│       ├── image-gallery.php        # Image gallery
│       ├── related-content.php      # Related content
│       └── breadcrumbs.php          # Breadcrumb navigation
├── assets/                          # Static assets
│   ├── css/
│   │   ├── main.css                 # Main stylesheet
│   │   ├── admin.css                # Admin styles
│   │   ├── single-movie.css         # Movie-specific styles
│   │   ├── archive.css              # Archive page styles
│   │   ├── components.css           # Reusable components
│   │   └── utilities.css            # Utility classes
│   ├── js/
│   │   ├── main.js                  # Main JavaScript
│   │   ├── admin.js                 # Admin JavaScript
│   │   ├── ajax.js                  # AJAX functionality
│   │   ├── rating.js                # Rating system
│   │   ├── gallery.js               # Image/video galleries
│   │   └── api-integration.js       # TMDB API frontend
│   └── images/
│       ├── no-poster.webp           # Default poster placeholder
│       ├── no-image.webp            # Default image placeholder
│       ├── loading.gif              # Loading animation
│       └── icons/                   # Theme icons
├── languages/                       # Translation files
│   ├── tmu-theme.pot               # Translation template
│   └── tmu-theme-textdomain.json   # Translation metadata
└── docs/                           # Additional documentation
    ├── api-reference.md            # API documentation
    ├── hooks-filters.md            # Available hooks and filters
    └── customization-guide.md      # Customization guidelines
```

---

## 🔧 Essential File Creation

### 1. Create composer.json
Create `composer.json` in the theme root:

```json
{
    "name": "your-name/tmu-theme",
    "description": "Modern WordPress theme for movie, TV series, and drama websites with TMDB integration",
    "type": "wordpress-theme",
    "license": "GPL-2.0-or-later",
    "authors": [
        {
            "name": "WeGreen",
            "email": "info@wegreenkw.com"
        }
    ],
    "require": {
        "php": ">=7.4"
    },
    "autoload": {
        "psr-4": {
            "TMUTheme\\": "src/"
        }
    },
    "config": {
        "optimize-autoloader": true,
        "classmap-authoritative": true
    },
    "scripts": {
        "post-autoload-dump": [
            "echo 'Autoloader optimized for production'"
        ]
    }
}
```

### 2. Create style.css (WordPress Required)
Create `style.css` with WordPress theme headers:

```css
/*
Theme Name: TMU Theme
Description: Professional WordPress theme for movie, TV series, and drama websites with TMDB API integration. Features custom post types, advanced fields, SEO optimization, and responsive design.
Author: Your Name
Author URI: https://yourwebsite.com
Version: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: tmu-theme
Domain Path: /languages
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Tags: entertainment, movies, tv-series, dramas, custom-post-types, api-integration

TMU Theme is a comprehensive solution for entertainment websites.
Built with modern WordPress standards, OOP architecture, and performance in mind.
*/

/* Basic reset and foundation styles will be imported from assets/css/main.css */
@import url('assets/css/main.css');
```

### 3. Create index.php (WordPress Required)
Create `index.php` as a fallback template:

```php
<?php
/**
 * Fallback template for TMU Theme
 *
 * @package TMUTheme
 * @since 1.0.0
 */

get_header(); ?>

<main id="main" class="site-main">
    <div class="container">
        <?php if ( have_posts() ) : ?>
            <div class="posts-grid">
                <?php while ( have_posts() ) : the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
                        <?php if ( has_post_thumbnail() ) : ?>
                            <div class="post-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail( 'medium' ); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="post-content">
                            <header class="post-header">
                                <h2 class="post-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>
                                <div class="post-meta">
                                    <time datetime="<?php echo get_the_date( 'c' ); ?>">
                                        <?php echo get_the_date(); ?>
                                    </time>
                                </div>
                            </header>
                            
                            <div class="post-excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                            
                            <footer class="post-footer">
                                <a href="<?php the_permalink(); ?>" class="read-more">
                                    <?php esc_html_e( 'Read More', 'tmu-theme' ); ?>
                                </a>
                            </footer>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            
            <?php the_posts_pagination(); ?>
            
        <?php else : ?>
            <div class="no-posts">
                <h1><?php esc_html_e( 'Nothing Found', 'tmu-theme' ); ?></h1>
                <p><?php esc_html_e( 'It looks like nothing was found at this location.', 'tmu-theme' ); ?></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
```

### 4. Initialize Composer Autoloading
Run this command in your theme directory:

```bash
# Navigate to theme directory
cd wp-content/themes/tmu-theme

# Install composer dependencies and generate autoloader
composer install --optimize-autoloader
```

### 5. Create Main Theme Class
Create `src/Theme.php`:

```php
<?php
/**
 * Main Theme Class
 *
 * @package TMUTheme
 * @since 1.0.0
 */

namespace TMUTheme;

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
     */
    private function check_requirements(): void {
        // Check WordPress version
        if ( version_compare( get_bloginfo( 'version' ), self::MIN_WP_VERSION, '<' ) ) {
            add_action( 'admin_notices', [ $this, 'wp_version_notice' ] );
            return;
        }
        
        // Check PHP version
        if ( version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '<' ) ) {
            add_action( 'admin_notices', [ $this, 'php_version_notice' ] );
            return;
        }
    }
    
    /**
     * Initialize WordPress hooks
     *
     * @return void
     */
    private function init_hooks(): void {
        add_action( 'after_setup_theme', [ $this, 'setup_theme' ] );
        add_action( 'init', [ $this, 'load_textdomain' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }
    
    /**
     * Theme setup
     *
     * @return void
     */
    public function setup_theme(): void {
        // Add theme support
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script'
        ] );
        add_theme_support( 'custom-logo' );
        add_theme_support( 'customize-selective-refresh-widgets' );
        
        // Register navigation menus
        register_nav_menus( [
            'primary' => esc_html__( 'Primary Menu', 'tmu-theme' ),
            'footer'  => esc_html__( 'Footer Menu', 'tmu-theme' ),
        ] );
        
        // Set content width
        $GLOBALS['content_width'] = 1200;
        
        // Initialize theme components
        $this->init_components();
    }
    
    /**
     * Initialize theme components
     *
     * @return void
     */
    private function init_components(): void {
        // Component initialization will be added in subsequent steps
        // This method will be expanded as we build the theme
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
        
        // Main JavaScript
        wp_enqueue_script(
            'tmu-theme-script',
            get_template_directory_uri() . '/assets/js/main.js',
            [ 'jquery' ],
            self::VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script( 'tmu-theme-script', 'tmuTheme', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'tmu_theme_nonce' ),
            'strings' => [
                'loading' => esc_html__( 'Loading...', 'tmu-theme' ),
                'error'   => esc_html__( 'An error occurred.', 'tmu-theme' ),
            ],
        ] );
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
            get_template_directory_uri() . '/assets/css/admin.css',
            [],
            self::VERSION
        );
        
        // Admin JavaScript
        wp_enqueue_script(
            'tmu-theme-admin-script',
            get_template_directory_uri() . '/assets/js/admin.js',
            [ 'jquery' ],
            self::VERSION,
            true
        );
    }
    
    /**
     * WordPress version notice
     *
     * @return void
     */
    public function wp_version_notice(): void {
        echo '<div class="notice notice-error">';
        echo '<p>' . sprintf(
            esc_html__( 'TMU Theme requires WordPress %s or higher. Please update WordPress.', 'tmu-theme' ),
            self::MIN_WP_VERSION
        ) . '</p>';
        echo '</div>';
    }
    
    /**
     * PHP version notice
     *
     * @return void
     */
    public function php_version_notice(): void {
        echo '<div class="notice notice-error">';
        echo '<p>' . sprintf(
            esc_html__( 'TMU Theme requires PHP %s or higher. Please update PHP.', 'tmu-theme' ),
            self::MIN_PHP_VERSION
        ) . '</p>';
        echo '</div>';
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
}
```

---

## 🚀 Initial Setup Commands

Run these commands to set up your development environment:

```bash
# 1. Navigate to your WordPress themes directory
cd /path/to/your/wordpress/wp-content/themes/

# 2. Create theme directory
mkdir tmu-theme && cd tmu-theme

# 3. Initialize Composer
composer init
# Follow prompts or use the composer.json content provided above

# 4. Install dependencies and generate autoloader
composer install --optimize-autoloader

# 5. Create basic asset directories
mkdir -p assets/{css,js,images}
mkdir -p templates/parts
mkdir -p languages

# 6. Create basic CSS file
touch assets/css/main.css

# 7. Create basic JavaScript file
touch assets/js/main.js
```

---

## ✅ Verification Checklist

After completing this step, verify:

- [ ] Local WordPress environment is running
- [ ] Theme directory structure is created
- [ ] Composer is installed and working
- [ ] `composer.json` is configured correctly
- [ ] Autoloader is generated (`vendor/autoload.php` exists)
- [ ] Required theme files (`style.css`, `index.php`) are created
- [ ] Main Theme class is created with proper namespace
- [ ] Directory permissions are correct (readable/writable)

---

## 🔍 Troubleshooting

### Common Issues

**Composer not found:**
```bash
# Install Composer globally
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

**Permission issues:**
```bash
# Fix permissions (macOS/Linux)
sudo chmod -R 755 wp-content/themes/tmu-theme/
sudo chown -R $(whoami) wp-content/themes/tmu-theme/
```

**Autoloader not working:**
```bash
# Regenerate autoloader
composer dump-autoload --optimize
```

---

## 🎯 Next Step

Once your project structure is set up and verified, proceed to **[Step 2: Theme Initialization](02_theme-initialization.md)** to configure the theme's core functionality and WordPress integration.

---

*Estimated time for this step: 30-45 minutes*