# Step 01: Project Setup and Structure

## Purpose
Establish the foundational directory structure and development environment for the TMU theme, ensuring a clean, maintainable, and WordPress-standards-compliant architecture with **Tailwind CSS** as the primary styling framework.

## Overview
This step involves creating the theme's directory structure, setting up the development environment with **Tailwind CSS**, and establishing the foundation for a modern, object-oriented WordPress theme that will replace the TMU plugin functionality.

## Directory Structure

### Root Theme Structure
```
tmu-theme/
├── style.css                  # [CREATE NEW - STEP 1] Theme identification file - Required by WordPress
├── index.php                  # [CREATE NEW - STEP 10] Fallback template - WordPress requirement
├── functions.php              # [CREATE NEW - STEP 1] Theme bootstrap - Core theme initialization
├── package.json               # [CREATE NEW - STEP 1] Node.js dependencies - FIRST TIME Tailwind CSS setup
├── tailwind.config.js         # [CREATE NEW - STEP 1] Tailwind CSS configuration - FIRST TIME setup
├── webpack.config.js          # [CREATE NEW - STEP 1] Asset bundling configuration - FIRST TIME setup
├── .babelrc                   # [CREATE NEW - STEP 1] Babel configuration - Modern JS transpilation
├── composer.json              # [CREATE NEW - STEP 1] PHP dependency management - PSR-4 autoloading
├── composer.lock              # [AUTO-GENERATED] Dependency lock file - Created by Composer
├── README.md                  # [CREATE NEW - STEP 1] Theme documentation - Project overview
├── .gitignore                 # [CREATE NEW - STEP 1] Git ignore rules - Version control setup
├── screenshot.png             # [CREATE NEW - STEP 1] Theme screenshot - WordPress admin display
├── rtl.css                    # [OPTIONAL - STEP 1] RTL support - Only if RTL languages needed
├── assets/                    # [CREATE DIR - STEP 1] Static assets directory
│   ├── src/                  # [CREATE DIR - STEP 1] Source files - FIRST TIME Tailwind setup
│   │   ├── css/              # [CREATE DIR - STEP 1] CSS source directory
│   │   │   ├── main.css      # [CREATE NEW - STEP 1] Main Tailwind CSS file - FIRST TIME implementation
│   │   │   ├── admin.css     # [CREATE NEW - STEP 1] Admin styles with Tailwind - FIRST TIME setup
│   │   │   └── components/   # [CREATE DIR - STEP 1] Component-specific styles - Future use
│   │   └── js/               # [CREATE DIR - STEP 1] JavaScript source directory
│   │       ├── main.js       # [CREATE NEW - STEP 1] Main JavaScript file - Alpine.js integration
│   │       ├── admin.js      # [CREATE NEW - STEP 1] Admin functionality - TMDB integration prep
│   │       └── modules/      # [CREATE DIR - STEP 1] JavaScript modules - Future expansion
│   ├── build/                # [CREATE DIR - STEP 1] Compiled assets - Webpack output
│   │   ├── css/              # [CREATE DIR - STEP 1] Compiled CSS directory
│   │   │   ├── main.css      # [AUTO-GENERATED] Compiled main CSS - Webpack creates this
│   │   │   └── admin.css     # [AUTO-GENERATED] Compiled admin CSS - Webpack creates this
│   │   └── js/               # [CREATE DIR - STEP 1] Compiled JS directory
│   │       ├── main.js       # [AUTO-GENERATED] Compiled main JS - Webpack creates this
│   │       └── admin.js      # [AUTO-GENERATED] Compiled admin JS - Webpack creates this
│   ├── images/               # [CREATE DIR - STEP 1] Images directory
│   │   ├── icons/            # [CREATE DIR - STEP 1] SVG icons - Theme icons
│   │   └── placeholders/     # [CREATE DIR - STEP 1] Placeholder images - Default movie posters
│   └── fonts/                # [CREATE DIR - STEP 1] Custom fonts - If custom fonts needed
├── templates/                 # [CREATE DIR - STEP 1] Template files - WordPress template hierarchy
│   ├── archive/              # [CREATE DIR - STEP 10] Archive templates - Created in frontend step
│   ├── single/               # [CREATE DIR - STEP 10] Single post templates - Created in frontend step
│   ├── parts/                # [CREATE DIR - STEP 1] Template parts - Component system
│   │   ├── components/       # [CREATE DIR - STEP 1] Reusable components - Tailwind-based components
│   │   ├── header/           # [CREATE DIR - STEP 1] Header components - Header variations
│   │   ├── footer/           # [CREATE DIR - STEP 1] Footer components - Footer variations
│   │   └── content/          # [CREATE DIR - STEP 1] Content components - Content display components
│   ├── blocks/               # [CREATE DIR - STEP 7] Gutenberg block templates - Custom blocks
│   └── search/               # [CREATE DIR - STEP 12] Search templates - Search functionality
├── includes/                  # [CREATE DIR - STEP 1] Core functionality - PHP classes and logic
│   ├── classes/              # [CREATE DIR - STEP 1] Class files - PSR-4 autoloaded classes
│   │   ├── ThemeCore.php     # [CREATE NEW - STEP 1] Main theme class - Core theme functionality
│   │   ├── Admin/           # [CREATE DIR - STEP 1] Admin-related classes - Created in Step 8
│   │   ├── API/             # [CREATE DIR - STEP 1] API integration classes - Created in Step 9
│   │   ├── Database/        # [CREATE DIR - STEP 1] Database interaction classes - Created in Step 3
│   │   ├── Frontend/        # [CREATE DIR - STEP 1] Frontend classes - Created in Step 10
│   │   ├── PostTypes/       # [CREATE DIR - STEP 1] Post type classes - Created in Step 5
│   │   ├── Taxonomies/      # [CREATE DIR - STEP 1] Taxonomy classes - Created in Step 6
│   │   ├── Blocks/          # [CREATE DIR - STEP 1] Gutenberg block classes - Created in Step 7
│   │   ├── Fields/          # [CREATE DIR - STEP 1] Custom fields classes - Created in Step 8
│   │   └── Utils/           # [CREATE DIR - STEP 1] Utility classes - Helper functions
│   ├── config/              # [CREATE DIR - STEP 1] Configuration files - Theme configuration
│   │   ├── constants.php    # [CREATE NEW - STEP 1] Theme constants - Central configuration
│   │   ├── database.php     # [CREATE NEW - STEP 1] Database configuration - Table schemas
│   │   └── assets.php       # [CREATE NEW - STEP 1] Asset configuration - Asset management
│   ├── migrations/          # [CREATE DIR - STEP 1] Database migrations - Created in Step 3
│   └── helpers/             # [CREATE DIR - STEP 1] Helper functions - Utility functions
├── languages/               # [CREATE DIR - STEP 1] Translation files - i18n support
└── vendor/                  # [AUTO-GENERATED] Composer dependencies - Composer creates this
```

### **File Status Legend:**
- **[CREATE NEW - STEP X]**: Create this file in the specified step
- **[CREATE DIR - STEP X]**: Create this directory in the specified step
- **[AUTO-GENERATED]**: File is created automatically by build tools
- **[UPDATE - STEP X]**: Modify existing file in the specified step
- **[REFERENCE ONLY]**: File mentioned for context, no action needed
- **[OPTIONAL]**: File is optional based on requirements
- **[FIRST TIME]**: This is the first implementation of this technology

### Detailed Class Structure
```
includes/classes/
├── Admin/                   # [CREATE DIR - STEP 1] Admin functionality directory
│   ├── AdminInterface.php  # [CREATE NEW - STEP 8] Admin UI management - Dashboard customization
│   ├── MetaBoxes.php       # [CREATE NEW - STEP 8] Meta box registration - Custom post fields UI
│   ├── AdminColumns.php    # [CREATE NEW - STEP 8] Custom admin columns - List table customization
│   ├── BulkActions.php     # [CREATE NEW - STEP 8] Bulk action handlers - Mass operations
│   └── Settings.php        # [CREATE NEW - STEP 2] Theme settings page - Configuration panel
├── API/                     # [CREATE DIR - STEP 1] API integration directory
│   ├── TMDBClient.php      # [CREATE NEW - STEP 9] TMDB API client - Core API communication
│   ├── DataProcessor.php   # [CREATE NEW - STEP 9] API data processing - Response handling
│   ├── ImageManager.php    # [CREATE NEW - STEP 9] Image handling - TMDB image management
│   └── CreditProcessor.php # [CREATE NEW - STEP 9] Cast/crew processing - Credits management
├── Database/                # [CREATE DIR - STEP 1] Database operations directory
│   ├── Migration.php       # [CREATE NEW - STEP 3] Database migration handler - Table creation/updates
│   ├── Schema.php          # [CREATE NEW - STEP 3] Database schema definitions - Table structures
│   ├── QueryBuilder.php    # [CREATE NEW - STEP 3] Custom query builder - Complex queries
│   └── DataManager.php     # [CREATE NEW - STEP 3] Data management operations - CRUD operations
├── Frontend/                # [CREATE DIR - STEP 1] Frontend functionality directory
│   ├── TemplateLoader.php  # [CREATE NEW - STEP 10] Template loading logic - Template hierarchy
│   ├── AssetManager.php    # [CREATE NEW - STEP 1] Asset enqueueing - Tailwind CSS & JS management
│   ├── SearchHandler.php   # [CREATE NEW - STEP 12] Search functionality - Advanced search
│   └── AjaxHandler.php     # [CREATE NEW - STEP 12] AJAX request handling - Frontend interactions
├── PostTypes/               # [CREATE DIR - STEP 1] Post type management directory
│   ├── PostTypeManager.php # [CREATE NEW - STEP 5] Post type registration manager - Central registry
│   ├── Movie.php           # [CREATE NEW - STEP 5] Movie post type - Films management
│   ├── TVShow.php          # [CREATE NEW - STEP 5] TV show post type - Series management
│   ├── Drama.php           # [CREATE NEW - STEP 5] Drama post type - Drama series management
│   ├── People.php          # [CREATE NEW - STEP 5] People post type - Cast/crew profiles
│   ├── Season.php          # [CREATE NEW - STEP 5] Season post type - TV seasons
│   ├── Episode.php         # [CREATE NEW - STEP 5] Episode post type - TV episodes
│   └── Video.php           # [CREATE NEW - STEP 5] Video post type - Trailers/clips
├── Taxonomies/              # [CREATE DIR - STEP 1] Taxonomy management directory
│   ├── TaxonomyManager.php # [CREATE NEW - STEP 6] Taxonomy registration manager - Central registry
│   ├── Genre.php           # [CREATE NEW - STEP 6] Genre taxonomy - Content categorization
│   ├── Country.php         # [CREATE NEW - STEP 6] Country taxonomy - Origin classification
│   ├── Language.php        # [CREATE NEW - STEP 6] Language taxonomy - Language classification
│   └── Network.php         # [CREATE NEW - STEP 6] Network taxonomy - Broadcasting networks
├── Blocks/                  # [CREATE DIR - STEP 1] Gutenberg blocks directory
│   ├── BlockManager.php    # [CREATE NEW - STEP 7] Block registration manager - Gutenberg integration
│   ├── MovieBlock.php      # [CREATE NEW - STEP 7] Movie display block - Movie showcase
│   ├── SearchBlock.php     # [CREATE NEW - STEP 7] Search form block - Search functionality
│   └── RatingBlock.php     # [CREATE NEW - STEP 7] Rating display block - Rating system
├── Fields/                  # [CREATE DIR - STEP 1] Custom fields directory
│   ├── FieldManager.php    # [CREATE NEW - STEP 8] Custom fields manager - Meta field system
│   ├── MetaField.php       # [CREATE NEW - STEP 8] Base meta field class - Field foundation
│   ├── MovieFields.php     # [CREATE NEW - STEP 8] Movie-specific fields - Movie metadata
│   ├── TVShowFields.php    # [CREATE NEW - STEP 8] TV show-specific fields - Series metadata
│   └── PeopleFields.php    # [CREATE NEW - STEP 8] People-specific fields - Person metadata
├── Utils/                   # [CREATE DIR - STEP 1] Utility classes directory
│   ├── Logger.php          # [CREATE NEW - STEP 1] Logging functionality - Debug/error logging
│   ├── Validator.php       # [CREATE NEW - STEP 1] Data validation - Input validation
│   ├── Sanitizer.php       # [CREATE NEW - STEP 1] Data sanitization - Security cleaning
│   └── Helper.php          # [CREATE NEW - STEP 1] General helper functions - Utility methods
└── ThemeCore.php           # [CREATE NEW - STEP 1] Main theme class - Core theme initialization
```

### **Class Implementation Dependencies:**
- **Step 1 Files**: Required for basic theme functionality and Tailwind CSS setup
- **Step 3 Files**: Database operations depend on constants.php from Step 1
- **Step 5 Files**: Post types depend on Database classes from Step 3
- **Step 6 Files**: Taxonomies depend on PostTypes from Step 5
- **Step 7 Files**: Blocks depend on PostTypes and Taxonomies
- **Step 8 Files**: Admin features depend on all content types
- **Step 9 Files**: API integration depends on Database and PostTypes
- **Step 10 Files**: Frontend depends on all previous steps
- **Step 12 Files**: Search depends on Frontend and PostTypes

## Core Files Setup

### 1. Theme Identification (`style.css`)
**File Status**: [CREATE NEW - STEP 1]
**File Path**: `tmu-theme/style.css`
**Purpose**: WordPress theme identification and basic info - REQUIRED by WordPress
**Dependencies**: None - This is the first file WordPress looks for
**Tailwind Status**: REFERENCED - Mentions Tailwind in description, actual CSS loaded via functions.php

```css
/*
Theme Name: TMU
Description: Modern Movie & TV Database Theme - A comprehensive entertainment content management system with TMDB integration, powered by Tailwind CSS
Version: 1.0.0
Author: TheMovieUpdates
Author URI: https://www.themovieupdates.com
Text Domain: tmu
Domain Path: /languages
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
License: MIT
License URI: https://opensource.org/licenses/MIT
Tags: movies, tv-shows, entertainment, database, tmdb, tailwind

This theme replaces the TMU plugin with modern WordPress theme architecture and Tailwind CSS.
*/

/* Main theme styles will be loaded via functions.php */
/* Tailwind CSS is compiled and loaded from assets/build/css/main.css */
```

### 2. Theme Bootstrap (`functions.php`)
**File Status**: [CREATE NEW - STEP 1]
**File Path**: `tmu-theme/functions.php`
**Purpose**: Theme bootstrap and initialization - REQUIRED by WordPress
**Dependencies**: 
- Requires `includes/config/constants.php` [CREATE NEW - STEP 1]
- Requires `includes/classes/ThemeCore.php` [CREATE NEW - STEP 1]
- Requires `vendor/autoload.php` [AUTO-GENERATED by Composer]
**Tailwind Status**: INTEGRATES - Loads ThemeCore which manages Tailwind CSS assets

```php
<?php
/**
 * TMU Theme Bootstrap
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Theme constants
define('TMU_VERSION', '1.0.0');
define('TMU_THEME_DIR', get_template_directory());
define('TMU_THEME_URL', get_template_directory_uri());
define('TMU_INCLUDES_DIR', TMU_THEME_DIR . '/includes');
define('TMU_ASSETS_URL', TMU_THEME_URL . '/assets');
define('TMU_ASSETS_BUILD_URL', TMU_ASSETS_URL . '/build'); // Webpack output for Tailwind CSS

// Load configuration - Required for theme constants
require_once TMU_INCLUDES_DIR . '/config/constants.php';

// Composer autoloader - Required for PSR-4 namespacing
if (file_exists(TMU_THEME_DIR . '/vendor/autoload.php')) {
    require_once TMU_THEME_DIR . '/vendor/autoload.php';
}

// Theme initialization - Main theme class that manages everything
require_once TMU_INCLUDES_DIR . '/classes/ThemeCore.php';

// Initialize theme - This starts Tailwind CSS asset loading
if (class_exists('TMU\\ThemeCore')) {
    TMU\\ThemeCore::getInstance();
}
```

### 3. Node.js Configuration (`package.json`)
**File Status**: [CREATE NEW - STEP 1]
**File Path**: `tmu-theme/package.json`
**Purpose**: Node.js dependencies and build scripts for Tailwind CSS - FIRST TIME SETUP
**Dependencies**: None - This is the foundation for frontend build process
**Tailwind Status**: FIRST TIME IMPLEMENTATION - Sets up Tailwind CSS compilation
**Build Process**: Creates `assets/build/` directory with compiled CSS/JS

```json
{
  "name": "tmu-wordpress-theme",
  "version": "1.0.0",
  "description": "Modern Movie & TV Database WordPress Theme with Tailwind CSS",
  "scripts": {
    "dev": "webpack --mode development --watch",
    "build": "webpack --mode production",
    "build:css": "tailwindcss -i ./assets/src/css/main.css -o ./assets/build/css/main.css --watch",
    "build:css:prod": "tailwindcss -i ./assets/src/css/main.css -o ./assets/build/css/main.css --minify"
  },
  "devDependencies": {
    "@babel/core": "^7.23.0",
    "@babel/preset-env": "^7.23.0",
    "autoprefixer": "^10.4.16",
    "babel-loader": "^9.1.3",
    "css-loader": "^6.8.1",
    "mini-css-extract-plugin": "^2.7.6",
    "postcss": "^8.4.31",
    "postcss-loader": "^7.3.3",
    "tailwindcss": "^3.4.0",
    "webpack": "^5.89.0",
    "webpack-cli": "^5.1.4"
  },
  "dependencies": {
    "alpinejs": "^3.13.0"
  }
}
```

### 4. Tailwind CSS Configuration (`tailwind.config.js`)
**File Status**: [CREATE NEW - STEP 1]
**File Path**: `tmu-theme/tailwind.config.js`
**Purpose**: Tailwind CSS configuration for TMU theme - FIRST TIME SETUP
**Dependencies**: 
- Requires `package.json` [CREATE NEW - STEP 1] for Tailwind installation
- Scans `templates/**/*.php` [CREATE DIR - STEP 1] for classes
- Scans `includes/**/*.php` [CREATE DIR - STEP 1] for classes
**Tailwind Status**: FIRST TIME IMPLEMENTATION - Core Tailwind configuration
**Custom Features**: TMU-specific colors, movie poster aspect ratios, custom spacing

```javascript
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./templates/**/*.php",    // [CREATE DIR - STEP 1] Template files
    "./includes/**/*.php",     // [CREATE DIR - STEP 1] PHP class files
    "./assets/src/js/**/*.js", // [CREATE DIR - STEP 1] JavaScript files
    "./*.php"                  // Root PHP files (functions.php, etc.)
  ],
  theme: {
    extend: {
      colors: {
        // TMU Brand Colors - FIRST TIME IMPLEMENTATION
        'tmu-primary': '#1e40af',    // Blue for primary actions
        'tmu-secondary': '#dc2626',  // Red for secondary actions
        'tmu-accent': '#059669',     // Green for success states
        'tmu-dark': '#1f2937',       // Dark gray for text
        'tmu-light': '#f9fafb',      // Light gray for backgrounds
        'tmu-yellow': '#f59e0b',     // Yellow for ratings/stars
        'tmu-purple': '#7c3aed'      // Purple for special elements
      },
      fontFamily: {
        // Custom font stacks for better typography
        'sans': ['Inter', 'system-ui', 'sans-serif'],
        'serif': ['Merriweather', 'serif'],
        'mono': ['JetBrains Mono', 'monospace']
      },
      spacing: {
        // Custom spacing for movie/TV layouts
        '18': '4.5rem',
        '88': '22rem',
        '112': '28rem',
        '128': '32rem'
      },
      screens: {
        // Additional breakpoints for responsive design
        'xs': '475px',
        '3xl': '1920px'
      },
      aspectRatio: {
        // Movie poster specific aspect ratios - CUSTOM FOR TMU
        'movie': '2/3',      // Standard movie poster ratio
        'poster': '27/40'    // TMDB poster ratio
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),        // Form styling
    require('@tailwindcss/typography'),   // Rich text content
    require('@tailwindcss/aspect-ratio')  // Aspect ratio utilities
  ],
}
```

### 5. Webpack Configuration (`webpack.config.js`)
**File Path**: `tmu-theme/webpack.config.js` (CREATE NEW)
**Purpose**: Asset bundling and compilation

```javascript
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';
  
  return {
    entry: {
      main: './assets/src/js/main.js',
      admin: './assets/src/js/admin.js'
    },
    output: {
      path: path.resolve(__dirname, 'assets/build'),
      filename: 'js/[name].js',
      clean: true
    },
    module: {
      rules: [
        {
          test: /\.js$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-env']
            }
          }
        },
        {
          test: /\.css$/,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
            {
              loader: 'postcss-loader',
              options: {
                postcssOptions: {
                  plugins: [
                    require('tailwindcss'),
                    require('autoprefixer'),
                  ]
                }
              }
            }
          ]
        }
      ]
    },
    plugins: [
      new MiniCssExtractPlugin({
        filename: 'css/[name].css'
      })
    ],
    optimization: {
      minimize: isProduction
    },
    devtool: isProduction ? false : 'source-map'
  };
};
```

### 6. Composer Configuration (`composer.json`)
**File Path**: `tmu-theme/composer.json` (CREATE NEW)
**Purpose**: PHP dependency management

```json
{
    "name": "tmu/wordpress-theme",
    "description": "Modern Movie & TV Database WordPress Theme",
    "type": "wordpress-theme",
    "version": "1.0.0",
    "license": "MIT",
    "authors": [
        {
            "name": "Your Name",
            "email": "your.email@example.com"
        }
    ],
    "require": {
        "php": ">=7.4",
        "composer/installers": "^1.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.0",
        "squizlabs/php_codesniffer": "^3.6",
        "wp-coding-standards/wpcs": "^2.3"
    },
    "autoload": {
        "psr-4": {
            "TMU\\": "includes/classes/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "TMU\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "phpunit",
        "cs-check": "phpcs --standard=WordPress includes/",
        "cs-fix": "phpcbf --standard=WordPress includes/"
    },
    "config": {
        "allow-plugins": {
            "composer/installers": true
        }
    }
}
```

### 7. Main Tailwind CSS File (`assets/src/css/main.css`)
**File Status**: [CREATE NEW - STEP 1]
**File Path**: `tmu-theme/assets/src/css/main.css`
**Purpose**: Main Tailwind CSS file with custom styles - FIRST TIME IMPLEMENTATION
**Dependencies**: 
- Requires `tailwind.config.js` [CREATE NEW - STEP 1] for configuration
- Requires `package.json` [CREATE NEW - STEP 1] for Tailwind installation
**Tailwind Status**: FIRST TIME IMPLEMENTATION - Core Tailwind CSS setup
**Output**: Compiled to `assets/build/css/main.css` by Webpack
**Used By**: Loaded in `ThemeCore.php` [CREATE NEW - STEP 1] via `wp_enqueue_style`

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Custom Base Styles - FIRST TIME IMPLEMENTATION */
@layer base {
  body {
    @apply font-sans text-gray-900 antialiased;
  }
  
  h1, h2, h3, h4, h5, h6 {
    @apply font-semibold text-tmu-dark; /* Uses custom TMU color */
  }
  
  a {
    @apply text-tmu-primary hover:text-tmu-secondary transition-colors; /* TMU brand colors */
  }
  
  img {
    @apply max-w-full h-auto;
  }
}

/* Custom Components - TMU-specific UI components */
@layer components {
  .btn {
    @apply px-4 py-2 rounded-lg font-medium text-sm transition-all duration-200;
  }
  
  .btn-primary {
    @apply btn bg-tmu-primary text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500;
  }
  
  .btn-secondary {
    @apply btn bg-tmu-secondary text-white hover:bg-red-700 focus:ring-2 focus:ring-red-500;
  }
  
  .card {
    @apply bg-white rounded-lg shadow-md overflow-hidden;
  }
  
  .card-header {
    @apply px-6 py-4 border-b border-gray-200;
  }
  
  .card-body {
    @apply px-6 py-4;
  }
  
  /* Movie-specific components - CUSTOM FOR TMU */
  .movie-poster {
    @apply aspect-movie bg-gray-200 rounded-lg overflow-hidden; /* Uses custom aspect ratio */
  }
  
  .rating-stars {
    @apply flex items-center space-x-1;
  }
  
  .rating-star {
    @apply w-4 h-4 text-tmu-yellow; /* TMU yellow for ratings */
  }
  
  .genre-tag {
    @apply inline-block px-2 py-1 text-xs font-medium bg-tmu-light text-tmu-dark rounded-full;
  }
  
  /* Search and filter components - Used in Step 12 */
  .search-form {
    @apply flex items-center space-x-2;
  }
  
  .search-input {
    @apply flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-tmu-primary focus:border-transparent;
  }
  
  .filter-dropdown {
    @apply relative inline-block text-left;
  }
  
  /* Pagination components - Used in frontend templates */
  .pagination {
    @apply flex items-center justify-center space-x-2 mt-8;
  }
  
  .pagination-link {
    @apply px-3 py-2 text-sm border border-gray-300 rounded hover:bg-gray-50;
  }
  
  .pagination-current {
    @apply pagination-link bg-tmu-primary text-white border-tmu-primary;
  }
}

/* Custom Utilities - Additional helper classes */
@layer utilities {
  .text-balance {
    text-wrap: balance;
  }
  
  .scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
  }
  
  .scrollbar-hide::-webkit-scrollbar {
    display: none;
  }
}
```

### 8. Admin CSS File (`assets/src/css/admin.css`)
**File Path**: `tmu-theme/assets/src/css/admin.css` (CREATE NEW)
**Purpose**: Admin-specific styles with Tailwind

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Admin-specific components */
@layer components {
  .admin-card {
    @apply bg-white border border-gray-200 rounded-lg shadow-sm;
  }
  
  .admin-header {
    @apply px-4 py-3 border-b border-gray-200 bg-gray-50;
  }
  
  .admin-body {
    @apply px-4 py-3;
  }
  
  .admin-btn {
    @apply px-3 py-1 text-sm font-medium rounded border;
  }
  
  .admin-btn-primary {
    @apply admin-btn bg-blue-600 text-white border-blue-600 hover:bg-blue-700;
  }
  
  .admin-btn-secondary {
    @apply admin-btn bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200;
  }
  
  .admin-table {
    @apply w-full border-collapse;
  }
  
  .admin-table th {
    @apply px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50;
  }
  
  .admin-table td {
    @apply px-4 py-2 text-sm text-gray-900 border-b border-gray-200;
  }
  
  .admin-form-group {
    @apply mb-4;
  }
  
  .admin-label {
    @apply block text-sm font-medium text-gray-700 mb-1;
  }
  
  .admin-input {
    @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500;
  }
  
  .admin-select {
    @apply admin-input;
  }
  
  .admin-textarea {
    @apply admin-input min-h-[100px] resize-vertical;
  }
  
  .meta-box {
    @apply admin-card mb-4;
  }
  
  .meta-box-header {
    @apply admin-header font-semibold;
  }
  
  .meta-box-body {
    @apply admin-body;
  }
  
  .tmdb-sync-button {
    @apply inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500;
  }
  
  .loading-spinner {
    @apply inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin;
  }
}
```

### 9. Main JavaScript File (`assets/src/js/main.js`)
**File Path**: `tmu-theme/assets/src/js/main.js` (CREATE NEW)
**Purpose**: Main frontend JavaScript functionality

```javascript
// Import Alpine.js for interactivity
import Alpine from 'alpinejs';

// Import main CSS
import '../css/main.css';

// Initialize Alpine.js
window.Alpine = Alpine;
Alpine.start();

// Main theme functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize theme components
    initializeSearch();
    initializeFilters();
    initializeLoadMore();
    initializeRating();
    initializeLazyLoading();
});

// Search functionality
function initializeSearch() {
    const searchForm = document.querySelector('.tmu-search-form');
    if (!searchForm) return;
    
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const query = this.querySelector('input[name="s"]').value;
        if (query.length < 2) return;
        
        // Implement AJAX search
        performSearch(query);
    });
}

// Filter functionality
function initializeFilters() {
    const filterDropdowns = document.querySelectorAll('.filter-dropdown');
    filterDropdowns.forEach(dropdown => {
        dropdown.addEventListener('change', function() {
            applyFilters();
        });
    });
}

// Load more functionality
function initializeLoadMore() {
    const loadMoreBtn = document.querySelector('.load-more-btn');
    if (!loadMoreBtn) return;
    
    loadMoreBtn.addEventListener('click', function() {
        const page = parseInt(this.dataset.page) + 1;
        loadMoreContent(page);
    });
}

// Rating functionality
function initializeRating() {
    const ratingStars = document.querySelectorAll('.rating-interactive');
    ratingStars.forEach(rating => {
        rating.addEventListener('click', function(e) {
            if (e.target.classList.contains('star')) {
                const value = parseInt(e.target.dataset.value);
                submitRating(this.dataset.postId, value);
            }
        });
    });
}

// Lazy loading for images
function initializeLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
}

// AJAX functions
function performSearch(query) {
    fetch(`${tmu_ajax.ajaxurl}?action=tmu_search&s=${encodeURIComponent(query)}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `nonce=${tmu_ajax.nonce}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateSearchResults(data.data);
        }
    })
    .catch(error => console.error('Search error:', error));
}

function applyFilters() {
    const filters = {};
    document.querySelectorAll('.filter-dropdown').forEach(dropdown => {
        if (dropdown.value) {
            filters[dropdown.name] = dropdown.value;
        }
    });
    
    const params = new URLSearchParams(filters);
    window.location.search = params.toString();
}

function loadMoreContent(page) {
    const loadMoreBtn = document.querySelector('.load-more-btn');
    loadMoreBtn.textContent = 'Loading...';
    loadMoreBtn.disabled = true;
    
    fetch(`${tmu_ajax.ajaxurl}?action=tmu_load_more&page=${page}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `nonce=${tmu_ajax.nonce}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            appendContent(data.data.content);
            loadMoreBtn.dataset.page = page;
            loadMoreBtn.textContent = 'Load More';
            loadMoreBtn.disabled = false;
            
            if (!data.data.has_more) {
                loadMoreBtn.style.display = 'none';
            }
        }
    })
    .catch(error => {
        console.error('Load more error:', error);
        loadMoreBtn.textContent = 'Load More';
        loadMoreBtn.disabled = false;
    });
}

function submitRating(postId, rating) {
    fetch(`${tmu_ajax.ajaxurl}?action=tmu_submit_rating`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `post_id=${postId}&rating=${rating}&nonce=${tmu_ajax.nonce}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateRatingDisplay(postId, data.data);
        }
    })
    .catch(error => console.error('Rating error:', error));
}

// Helper functions
function updateSearchResults(results) {
    const container = document.querySelector('.search-results');
    container.innerHTML = results;
}

function appendContent(content) {
    const container = document.querySelector('.content-grid');
    container.insertAdjacentHTML('beforeend', content);
}

function updateRatingDisplay(postId, ratingData) {
    const ratingElement = document.querySelector(`[data-post-id="${postId}"] .rating-display`);
    if (ratingElement) {
        ratingElement.innerHTML = ratingData.html;
    }
}
```

### 10. Git Configuration (`.gitignore`)
**File Path**: `tmu-theme/.gitignore` (CREATE NEW)
**Purpose**: Git ignore rules for the theme

```gitignore
# WordPress
wp-config.php
wp-content/uploads/
wp-content/cache/
wp-content/backup*
wp-content/advanced-cache.php
wp-content/wp-cache-config.php

# Node.js
node_modules/
npm-debug.log*
yarn-debug.log*
yarn-error.log*

# Build artifacts
/assets/build/
/dist/
*.min.css
*.min.js

# Dependency directories
vendor/

# Environment files
.env
.env.local
.env.development.local
.env.test.local
.env.production.local

# IDE
.vscode/
.idea/
*.swp
*.swo
*~

# OS
.DS_Store
Thumbs.db

# Logs
*.log

# Cache
.cache/
.parcel-cache/

# Testing
coverage/
.nyc_output/

# Optional: Remove if you want to commit lock files
package-lock.json
yarn.lock
composer.lock
```

## Development Environment Setup

### 1. WordPress Local Development
```bash
# Recommended local development setup
# Using Local by Flywheel, XAMPP, or similar

# Theme directory structure
wp-content/
└── themes/
    └── tmu/
        ├── (theme files as outlined above)
```

### 2. Composer Installation
```bash
# Navigate to theme directory
cd wp-content/themes/tmu

# Install dependencies
composer install

# For development
composer install --dev
```

### 3. Development Tools Setup
```bash
# Install PHP CodeSniffer for WordPress
composer global require "squizlabs/php_codesniffer=*"
composer global require wp-coding-standards/wpcs

# Configure PHPCS
phpcs --config-set installed_paths ~/.composer/vendor/wp-coding-standards/wpcs
phpcs --config-set default_standard WordPress
```

## Theme Core Class Foundation

### Main Theme Class (`includes/classes/ThemeCore.php`)
**File Path**: `tmu-theme/includes/classes/ThemeCore.php` (CREATE NEW)
**Purpose**: Main theme class with Tailwind CSS integration

```php
<?php
/**
 * TMU Theme Core Class
 *
 * @package TMU
 * @version 1.0.0
 */

namespace TMU;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Theme Core Class
 */
class ThemeCore {
    
    /**
     * Theme instance
     *
     * @var ThemeCore
     */
    private static $instance = null;
    
    /**
     * Theme version
     *
     * @var string
     */
    private $version = TMU_VERSION;
    
    /**
     * Get theme instance
     *
     * @return ThemeCore
     */
    public static function getInstance(): ThemeCore {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->initHooks();
        $this->loadDependencies();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void {
        add_action('init', [$this, 'initTheme']);
        add_action('after_setup_theme', [$this, 'themeSetup']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }
    
    /**
     * Load required dependencies
     */
    private function loadDependencies(): void {
        // Load configuration files
        require_once TMU_INCLUDES_DIR . '/config/constants.php';
        require_once TMU_INCLUDES_DIR . '/config/database.php';
        require_once TMU_INCLUDES_DIR . '/config/assets.php';
        
        // Load managers
        require_once TMU_INCLUDES_DIR . '/classes/Database/Migration.php';
        require_once TMU_INCLUDES_DIR . '/classes/PostTypes/PostTypeManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/Taxonomies/TaxonomyManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/Blocks/BlockManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/Admin/AdminInterface.php';
        require_once TMU_INCLUDES_DIR . '/classes/API/TMDBClient.php';
        require_once TMU_INCLUDES_DIR . '/classes/Frontend/TemplateLoader.php';
        require_once TMU_INCLUDES_DIR . '/classes/Frontend/AssetManager.php';
    }
    
    /**
     * Initialize theme functionality
     */
    public function initTheme(): void {
        // Initialize managers
        Database\Migration::getInstance();
        PostTypes\PostTypeManager::getInstance();
        Taxonomies\TaxonomyManager::getInstance();
        Blocks\BlockManager::getInstance();
        Admin\AdminInterface::getInstance();
        API\TMDBClient::getInstance();
        Frontend\TemplateLoader::getInstance();
        Frontend\AssetManager::getInstance();
    }
    
    /**
     * Theme setup
     */
    public function themeSetup(): void {
        // Add theme support
        add_theme_support('post-thumbnails');
        add_theme_support('title-tag');
        add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
        add_theme_support('customize-selective-refresh-widgets');
        add_theme_support('editor-styles');
        add_theme_support('wp-block-styles');
        add_theme_support('responsive-embeds');
        
        // Set image sizes for movie posters and media
        add_image_size('tmu-poster-small', 185, 278, true);    // Movie poster small
        add_image_size('tmu-poster-medium', 300, 450, true);   // Movie poster medium
        add_image_size('tmu-poster-large', 500, 750, true);    // Movie poster large
        add_image_size('tmu-backdrop-small', 533, 300, true);  // Backdrop small
        add_image_size('tmu-backdrop-medium', 800, 450, true); // Backdrop medium
        add_image_size('tmu-backdrop-large', 1280, 720, true); // Backdrop large
        
        // Load text domain
        load_theme_textdomain('tmu', TMU_THEME_DIR . '/languages');
        
        // Register nav menus
        register_nav_menus([
            'primary' => __('Primary Menu', 'tmu'),
            'footer' => __('Footer Menu', 'tmu'),
            'mobile' => __('Mobile Menu', 'tmu'),
        ]);
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueueAssets(): void {
        // Main stylesheet (compiled Tailwind CSS)
        wp_enqueue_style(
            'tmu-main-style',
            TMU_ASSETS_BUILD_URL . '/css/main.css',
            [],
            $this->version
        );
        
        // Main JavaScript (compiled)
        wp_enqueue_script(
            'tmu-main-script',
            TMU_ASSETS_BUILD_URL . '/js/main.js',
            ['jquery'],
            $this->version,
            true
        );
        
        // Localize scripts with AJAX data
        wp_localize_script('tmu-main-script', 'tmu_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmu_ajax_nonce'),
            'loading_text' => __('Loading...', 'tmu'),
            'error_text' => __('Something went wrong. Please try again.', 'tmu'),
            'api_url' => home_url('/wp-json/tmu/v1/'),
        ]);
        
        // Enqueue Alpine.js for enhanced interactivity
        wp_enqueue_script(
            'alpinejs',
            'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js',
            [],
            '3.13.0',
            true
        );
        
        // Add defer attribute to Alpine.js
        add_filter('script_loader_tag', function($tag, $handle) {
            if ($handle === 'alpinejs') {
                return str_replace(' src', ' defer src', $tag);
            }
            return $tag;
        }, 10, 2);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueueAdminAssets(): void {
        // Admin stylesheet (compiled Tailwind CSS)
        wp_enqueue_style(
            'tmu-admin-style',
            TMU_ASSETS_BUILD_URL . '/css/admin.css',
            [],
            $this->version
        );
        
        // Admin JavaScript
        wp_enqueue_script(
            'tmu-admin-script',
            TMU_ASSETS_BUILD_URL . '/js/admin.js',
            ['jquery', 'wp-api'],
            $this->version,
            true
        );
        
        // Localize admin scripts
        wp_localize_script('tmu-admin-script', 'tmu_admin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmu_admin_nonce'),
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'api_url' => home_url('/wp-json/tmu/v1/'),
            'tmdb_api_key' => get_option('tmu_tmdb_api_key', ''),
            'strings' => [
                'sync_success' => __('Data synchronized successfully!', 'tmu'),
                'sync_error' => __('Error synchronizing data. Please try again.', 'tmu'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'tmu'),
                'loading' => __('Loading...', 'tmu'),
            ],
        ]);
    }
    
    /**
     * Get theme version
     *
     * @return string
     */
    public function getVersion(): string {
        return $this->version;
    }
}
```

## Configuration Files

### Constants Configuration (`includes/config/constants.php`)
**File Path**: `tmu-theme/includes/config/constants.php` (CREATE NEW)
**Purpose**: Define theme constants and configuration

```php
<?php
/**
 * TMU Theme Constants
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Theme configuration constants
define('TMU_THEME_NAME', 'TMU');
define('TMU_THEME_SLUG', 'tmu');
define('TMU_TEXT_DOMAIN', 'tmu');

// Database table names (preserving existing plugin tables)
define('TMU_MOVIES_TABLE', 'tmu_movies');
define('TMU_TV_SERIES_TABLE', 'tmu_tv_series');
define('TMU_DRAMAS_TABLE', 'tmu_dramas');
define('TMU_PEOPLE_TABLE', 'tmu_people');
define('TMU_VIDEOS_TABLE', 'tmu_videos');
define('TMU_MOVIES_CAST_TABLE', 'tmu_movies_cast');
define('TMU_MOVIES_CREW_TABLE', 'tmu_movies_crew');
define('TMU_TV_SERIES_CAST_TABLE', 'tmu_tv_series_cast');
define('TMU_TV_SERIES_CREW_TABLE', 'tmu_tv_series_crew');
define('TMU_TV_SERIES_SEASONS_TABLE', 'tmu_tv_series_seasons');
define('TMU_TV_SERIES_EPISODES_TABLE', 'tmu_tv_series_episodes');
define('TMU_DRAMAS_CAST_TABLE', 'tmu_dramas_cast');
define('TMU_DRAMAS_CREW_TABLE', 'tmu_dramas_crew');
define('TMU_DRAMAS_SEASONS_TABLE', 'tmu_dramas_seasons');
define('TMU_DRAMAS_EPISODES_TABLE', 'tmu_dramas_episodes');
define('TMU_SEO_OPTIONS_TABLE', 'tmu_seo_options');

// Post types
define('TMU_MOVIE_POST_TYPE', 'movie');
define('TMU_TV_SERIES_POST_TYPE', 'tv-series');
define('TMU_DRAMA_POST_TYPE', 'drama');
define('TMU_PEOPLE_POST_TYPE', 'people');
define('TMU_VIDEO_POST_TYPE', 'video');
define('TMU_SEASON_POST_TYPE', 'season');
define('TMU_EPISODE_POST_TYPE', 'episode');
define('TMU_DRAMA_EPISODE_POST_TYPE', 'drama-episode');

// Taxonomies
define('TMU_GENRE_TAXONOMY', 'genre');
define('TMU_COUNTRY_TAXONOMY', 'country');
define('TMU_LANGUAGE_TAXONOMY', 'language');
define('TMU_NETWORK_TAXONOMY', 'network');
define('TMU_YEAR_TAXONOMY', 'year');
define('TMU_KEYWORDS_TAXONOMY', 'keywords');
define('TMU_NATIONALITY_TAXONOMY', 'nationality');

// TMDB API configuration
define('TMU_TMDB_API_BASE_URL', 'https://api.themoviedb.org/3/');
define('TMU_TMDB_IMAGE_BASE_URL', 'https://image.tmdb.org/t/p/');
define('TMU_TMDB_POSTER_SIZES', ['w92', 'w154', 'w185', 'w342', 'w500', 'w780', 'original']);
define('TMU_TMDB_BACKDROP_SIZES', ['w300', 'w780', 'w1280', 'original']);

// Cache configuration
define('TMU_CACHE_GROUP', 'tmu_cache');
define('TMU_CACHE_EXPIRATION', 3600); // 1 hour
define('TMU_TRANSIENT_EXPIRATION', 86400); // 24 hours

// Pagination
define('TMU_POSTS_PER_PAGE', 20);
define('TMU_LOAD_MORE_POSTS', 10);

// Image sizes
define('TMU_POSTER_SIZES', [
    'small' => [185, 278],
    'medium' => [300, 450],
    'large' => [500, 750]
]);

define('TMU_BACKDROP_SIZES', [
    'small' => [533, 300],
    'medium' => [800, 450],
    'large' => [1280, 720]
]);
```

### Database Configuration (`includes/config/database.php`)
**File Path**: `tmu-theme/includes/config/database.php` (CREATE NEW)
**Purpose**: Database schema and configuration

```php
<?php
/**
 * TMU Database Configuration
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database schema for TMU tables
 * This preserves the existing plugin database structure
 */
return [
    'tables' => [
        'tmu_movies' => [
            'fields' => [
                'ID' => 'bigint(20) UNSIGNED NOT NULL',
                'tmdb_id' => 'bigint(20) DEFAULT NULL',
                'release_date' => 'text DEFAULT NULL',
                'release_timestamp' => 'bigint(20) DEFAULT NULL',
                'original_title' => 'text DEFAULT NULL',
                'tagline' => 'text DEFAULT NULL',
                'production_house' => 'text DEFAULT NULL',
                'streaming_platforms' => 'text DEFAULT NULL',
                'runtime' => 'bigint(20) DEFAULT NULL',
                'certification' => 'text DEFAULT NULL',
                'revenue' => 'bigint(20) DEFAULT NULL',
                'budget' => 'bigint(20) DEFAULT NULL',
                'star_cast' => 'text DEFAULT NULL',
                'credits' => 'longtext DEFAULT NULL',
                'credits_temp' => 'longtext DEFAULT NULL',
                'videos' => 'text DEFAULT NULL',
                'images' => 'text DEFAULT NULL',
                'average_rating' => 'DECIMAL(10,1) DEFAULT 0',
                'vote_count' => 'bigint(20) DEFAULT 0',
                'popularity' => 'DECIMAL(10,1) DEFAULT 0',
                'total_average_rating' => 'DECIMAL(10,1) DEFAULT 0',
                'total_vote_count' => 'bigint(20) NOT NULL DEFAULT 0'
            ],
            'keys' => [
                'PRIMARY KEY (ID)',
                'FOREIGN KEY (ID) REFERENCES {prefix}posts(ID) ON DELETE CASCADE'
            ]
        ],
        'tmu_tv_series' => [
            'fields' => [
                'ID' => 'bigint(20) UNSIGNED NOT NULL',
                'tmdb_id' => 'bigint(20) DEFAULT NULL',
                'release_date' => 'text DEFAULT NULL',
                'release_timestamp' => 'bigint(20) DEFAULT NULL',
                'original_title' => 'text DEFAULT NULL',
                'finished' => 'text DEFAULT NULL',
                'tagline' => 'text DEFAULT NULL',
                'production_house' => 'text DEFAULT NULL',
                'streaming_platforms' => 'text DEFAULT NULL',
                'schedule_time' => 'text DEFAULT NULL',
                'runtime' => 'bigint(20) DEFAULT NULL',
                'certification' => 'text DEFAULT NULL',
                'revenue' => 'bigint(20) DEFAULT NULL',
                'budget' => 'bigint(20) DEFAULT NULL',
                'star_cast' => 'text DEFAULT NULL',
                'credits' => 'longtext DEFAULT NULL',
                'credits_temp' => 'longtext DEFAULT NULL',
                'videos' => 'text DEFAULT NULL',
                'images' => 'text DEFAULT NULL',
                'seasons' => 'text DEFAULT NULL',
                'last_season' => 'bigint(20) DEFAULT NULL',
                'last_episode' => 'bigint(20) DEFAULT NULL',
                'average_rating' => 'DECIMAL(10,1) DEFAULT 0',
                'vote_count' => 'bigint(20) DEFAULT 0',
                'popularity' => 'DECIMAL(10,1) DEFAULT 0',
                'where_to_watch' => 'text DEFAULT NULL',
                'total_average_rating' => 'DECIMAL(10,1) DEFAULT 0',
                'total_vote_count' => 'bigint(20) NOT NULL DEFAULT 0'
            ],
            'keys' => [
                'PRIMARY KEY (ID)',
                'FOREIGN KEY (ID) REFERENCES {prefix}posts(ID) ON DELETE CASCADE'
            ]
        ],
        'tmu_dramas' => [
            'fields' => [
                'ID' => 'bigint(20) UNSIGNED NOT NULL',
                'tmdb_id' => 'bigint(20) DEFAULT NULL',
                'release_date' => 'text DEFAULT NULL',
                'release_timestamp' => 'bigint(20) DEFAULT NULL',
                'original_title' => 'text DEFAULT NULL',
                'finished' => 'text DEFAULT NULL',
                'tagline' => 'text DEFAULT NULL',
                'seo_genre' => 'BIGINT(20) NULL DEFAULT NULL',
                'production_house' => 'text DEFAULT NULL',
                'streaming_platforms' => 'text DEFAULT NULL',
                'schedule_day' => 'text DEFAULT NULL',
                'schedule_time' => 'text DEFAULT NULL',
                'schedule_timestamp' => 'bigint(20) DEFAULT NULL',
                'runtime' => 'bigint(20) DEFAULT NULL',
                'certification' => 'text DEFAULT NULL',
                'star_cast' => 'text DEFAULT NULL',
                'credits' => 'longtext DEFAULT NULL',
                'credits_temp' => 'longtext DEFAULT NULL',
                'videos' => 'text DEFAULT NULL',
                'images' => 'text DEFAULT NULL',
                'average_rating' => 'DECIMAL(10,1) DEFAULT 0',
                'vote_count' => 'bigint(20) DEFAULT 0',
                'popularity' => 'DECIMAL(10,1) DEFAULT 0',
                'where_to_watch' => 'text DEFAULT NULL',
                'total_average_rating' => 'DECIMAL(10,1) DEFAULT 0',
                'total_vote_count' => 'bigint(20) NOT NULL DEFAULT 0'
            ],
            'keys' => [
                'PRIMARY KEY (ID)',
                'FOREIGN KEY (ID) REFERENCES {prefix}posts(ID) ON DELETE CASCADE'
            ]
        ],
        'tmu_people' => [
            'fields' => [
                'ID' => 'bigint(20) UNSIGNED NOT NULL',
                'name' => 'text DEFAULT NULL',
                'date_of_birth' => 'text DEFAULT NULL',
                'gender' => 'text DEFAULT NULL',
                'nick_name' => 'text DEFAULT NULL',
                'marital_status' => 'text DEFAULT NULL',
                'basic' => 'text DEFAULT NULL',
                'videos' => 'text DEFAULT NULL',
                'photos' => 'text DEFAULT NULL',
                'profession' => 'text DEFAULT NULL',
                'net_worth' => 'bigint(20) DEFAULT NULL',
                'tmdb_id' => 'bigint(20) DEFAULT NULL',
                'birthplace' => 'text DEFAULT NULL',
                'dead_on' => 'text DEFAULT NULL',
                'social_media_account' => 'text DEFAULT NULL',
                'no_movies' => 'bigint(20) DEFAULT NULL',
                'no_tv_series' => 'bigint(20) DEFAULT NULL',
                'no_dramas' => 'bigint(20) DEFAULT NULL',
                'known_for' => 'text DEFAULT NULL',
                'popularity' => 'DECIMAL(10,1) DEFAULT 0'
            ],
            'keys' => [
                'PRIMARY KEY (ID)',
                'FOREIGN KEY (ID) REFERENCES {prefix}posts(ID) ON DELETE CASCADE'
            ]
        ],
        // Additional tables for relationships and episodes
        'tmu_movies_cast' => [
            'fields' => [
                'ID' => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                'movie' => 'bigint(20) UNSIGNED NOT NULL',
                'person' => 'bigint(20) UNSIGNED NOT NULL',
                'job' => 'varchar(255) DEFAULT NULL',
                'release_year' => 'bigint(20) DEFAULT NULL'
            ],
            'keys' => [
                'PRIMARY KEY (ID)',
                'FOREIGN KEY (movie) REFERENCES {prefix}posts(ID) ON DELETE CASCADE',
                'FOREIGN KEY (person) REFERENCES {prefix}posts(ID) ON DELETE CASCADE'
            ]
        ],
        // ... (other relationship tables would follow the same pattern)
    ],
    'post_meta_fields' => [
        'movies' => [
            'seo_title' => 'text',
            'seo_description' => 'text',
            'meta_keywords' => 'text'
        ],
        'tv_series' => [
            'seo_title' => 'text',
            'seo_description' => 'text',
            'meta_keywords' => 'text'
        ],
        'dramas' => [
            'seo_title' => 'text',
            'seo_description' => 'text',
            'meta_keywords' => 'text'
        ],
        'people' => [
            'seo_title' => 'text',
            'seo_description' => 'text',
            'meta_keywords' => 'text'
        ]
    ]
];
```

### Asset Configuration (`includes/config/assets.php`)
**File Path**: `tmu-theme/includes/config/assets.php` (CREATE NEW)
**Purpose**: Asset management configuration

```php
<?php
/**
 * TMU Asset Configuration
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Asset configuration for TMU theme
 */
return [
    'css' => [
        'main' => [
            'src' => 'main.css',
            'deps' => [],
            'version' => TMU_VERSION,
            'media' => 'all'
        ],
        'admin' => [
            'src' => 'admin.css',
            'deps' => [],
            'version' => TMU_VERSION,
            'media' => 'all'
        ]
    ],
    'js' => [
        'main' => [
            'src' => 'main.js',
            'deps' => ['jquery'],
            'version' => TMU_VERSION,
            'in_footer' => true
        ],
        'admin' => [
            'src' => 'admin.js',
            'deps' => ['jquery', 'wp-api'],
            'version' => TMU_VERSION,
            'in_footer' => true
        ]
    ],
    'inline_styles' => [
        'tmu-custom-props' => [
            'handle' => 'tmu-main-style',
            'css' => '
                :root {
                    --tmu-primary: #1e40af;
                    --tmu-secondary: #dc2626;
                    --tmu-accent: #059669;
                    --tmu-dark: #1f2937;
                    --tmu-light: #f9fafb;
                    --tmu-yellow: #f59e0b;
                    --tmu-purple: #7c3aed;
                }
            '
        ]
    ],
    'preload' => [
        'fonts' => [
            'inter' => 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
            'merriweather' => 'https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700&display=swap'
        ]
    ]
];
```

## Integration with Existing Plugin Data

### Database Table Preservation
The theme must be designed to use the existing database tables created by the plugin:

1. **No Schema Changes**: The theme should work with existing table structures
2. **Data Migration**: Provide migration scripts for fresh installations
3. **Backward Compatibility**: Ensure existing data remains accessible
4. **Performance**: Optimize queries while maintaining data integrity

### Plugin Feature Mapping
Each plugin feature must be mapped to theme components:

- **Post Types**: Plugin CPT registration → Theme CPT classes (`includes/classes/PostTypes/`)
- **Taxonomies**: Plugin taxonomy registration → Theme taxonomy classes (`includes/classes/Taxonomies/`)
- **Meta Fields**: Plugin Meta Box fields → Theme custom field classes (`includes/classes/Fields/`)
- **TMDB Integration**: Plugin API calls → Theme API service classes (`includes/classes/API/`)
- **Admin Interface**: Plugin admin pages → Theme admin classes (`includes/classes/Admin/`)
- **Frontend Templates**: Plugin template files → Theme template system (`templates/`)

## Implementation Instructions

### **CRITICAL: File Creation Order for AI Models**

The following order MUST be followed to ensure dependencies are met:

#### **Phase 1: Directory Structure (REQUIRED FIRST)**
```bash
# Create main theme directory
mkdir wp-content/themes/tmu

# Create all required directories
mkdir -p wp-content/themes/tmu/{assets/src/{css,js},assets/build/{css,js},includes/classes/{Admin,API,Database,Frontend,PostTypes,Taxonomies,Blocks,Utils},includes/config,includes/migrations,includes/helpers,templates/{archive,single,parts/{components,header,footer,content},blocks,search},languages}
```

#### **Phase 2: Configuration Files (EXACT ORDER REQUIRED)**
1. **[CREATE FIRST]** `package.json` - FIRST TIME Tailwind setup
2. **[CREATE SECOND]** `tailwind.config.js` - Depends on package.json
3. **[CREATE THIRD]** `webpack.config.js` - Asset bundling setup
4. **[CREATE FOURTH]** `composer.json` - PHP dependencies
5. **[CREATE FIFTH]** `.gitignore` - Version control
6. **[CREATE SIXTH]** `style.css` - Theme identification

#### **Phase 3: Core Configuration (EXACT ORDER REQUIRED)**
1. **[CREATE FIRST]** `includes/config/constants.php` - Theme constants
2. **[CREATE SECOND]** `includes/config/database.php` - Database schemas  
3. **[CREATE THIRD]** `includes/config/assets.php` - Asset configuration

#### **Phase 4: Asset Files (EXACT ORDER REQUIRED)**
1. **[CREATE FIRST]** `assets/src/css/main.css` - FIRST TIME Tailwind implementation
2. **[CREATE SECOND]** `assets/src/css/admin.css` - Admin Tailwind styles
3. **[CREATE THIRD]** `assets/src/js/main.js` - Frontend JavaScript
4. **[CREATE FOURTH]** `assets/src/js/admin.js` - Admin JavaScript

#### **Phase 5: Core PHP Classes (EXACT ORDER REQUIRED)**
1. **[CREATE FIRST]** `includes/classes/ThemeCore.php` - Main theme class
2. **[CREATE SECOND]** `functions.php` - Depends on ThemeCore.php

### Step-by-Step Setup Process

#### 1. Create Theme Directory Structure
**Location**: `wp-content/themes/tmu/`
**Action**: Create all directories as specified above
**Status**: [REQUIRED FIRST] - No dependencies

#### 2. Create Configuration Files
**Files to Create**:
- `style.css` - Theme identification
- `functions.php` - Theme bootstrap
- `package.json` - Node.js dependencies
- `tailwind.config.js` - Tailwind CSS configuration
- `webpack.config.js` - Asset bundling
- `composer.json` - PHP dependencies
- `.gitignore` - Git ignore rules

#### 3. Create Core PHP Files
**Files to Create**:
- `includes/classes/ThemeCore.php` - Main theme class
- `includes/config/constants.php` - Theme constants
- `includes/config/database.php` - Database configuration
- `includes/config/assets.php` - Asset configuration

#### 4. Create Asset Files
**Files to Create**:
- `assets/src/css/main.css` - Main Tailwind CSS file
- `assets/src/css/admin.css` - Admin Tailwind CSS file
- `assets/src/js/main.js` - Main JavaScript file
- `assets/src/js/admin.js` - Admin JavaScript file

#### 5. Install Dependencies
**Commands to Run**:
```bash
# Navigate to theme directory
cd wp-content/themes/tmu

# Install Node.js dependencies
npm install

# Install PHP dependencies
composer install

# Build assets for development
npm run dev

# Or build for production
npm run build
```

#### 6. Initialize Git Repository
**Commands to Run**:
```bash
# Initialize git repository
git init

# Add all files
git add .

# Initial commit
git commit -m "Initial TMU theme setup with Tailwind CSS"
```

### Development Workflow

#### Asset Development
**Development Mode**:
```bash
# Watch for changes and rebuild automatically
npm run dev
```

**Production Build**:
```bash
# Build optimized assets for production
npm run build
```

#### CSS Development with Tailwind
**Main CSS Structure**:
- `assets/src/css/main.css` - Main Tailwind file with custom components
- `assets/src/css/admin.css` - Admin-specific Tailwind styles
- Components are built using Tailwind utility classes
- Custom components are defined in `@layer components`

#### JavaScript Development
**Main JS Structure**:
- `assets/src/js/main.js` - Frontend functionality with Alpine.js
- `assets/src/js/admin.js` - Admin functionality
- Modern ES6+ JavaScript with Babel compilation
- AJAX functionality for theme features

### Theme Activation Process

#### 1. Database Migration
**File**: `includes/classes/Database/Migration.php` (to be created in Step 3)
**Purpose**: Handle database table creation and migration

#### 2. Settings Migration
**Process**: Transfer existing plugin settings to theme options
**Location**: Theme activation hooks in `ThemeCore.php`

#### 3. Rewrite Rules
**Action**: Flush rewrite rules for custom post types and taxonomies
**Implementation**: WordPress `flush_rewrite_rules()` function

## Best Practices Implementation

### 1. WordPress Coding Standards
- Follow WordPress PHP, CSS, and JavaScript coding standards
- Use proper WordPress hooks and filters
- Implement proper sanitization and validation
- Use WordPress coding conventions for file naming

### 2. Tailwind CSS Best Practices
- Use utility-first approach for styling
- Create custom components in `@layer components`
- Utilize Tailwind's responsive design utilities
- Implement dark mode support using Tailwind's dark mode features

### 3. Modern JavaScript Practices
- Use ES6+ features with Babel transpilation
- Implement Alpine.js for reactive components
- Use modern fetch API for AJAX requests
- Follow module-based architecture

### 2. Security Considerations
- Sanitize all user inputs
- Validate data before processing
- Use nonces for form submissions
- Implement proper capability checks
- Escape output data appropriately

### 3. Performance Optimization
- Lazy load assets when possible
- Minimize database queries
- Use WordPress caching mechanisms
- Optimize image loading
- Implement proper asset minification

### 4. Accessibility
- Follow WCAG guidelines
- Implement proper ARIA attributes
- Ensure keyboard navigation
- Provide alternative text for images
- Use semantic HTML structure

## Testing Strategy

### 1. Unit Testing
- Set up PHPUnit for testing
- Test all class methods
- Mock external dependencies
- Test edge cases and error conditions

### 2. Integration Testing
- Test WordPress hooks integration
- Test database operations
- Test API integrations
- Test admin interface functionality

### 3. Manual Testing
- Test theme activation/deactivation
- Test data migration
- Test frontend functionality
- Test admin interface
- Test responsive design

## Documentation Requirements

### 1. Code Documentation
- Document all classes and methods
- Use PHPDoc standards
- Include usage examples
- Document hooks and filters

### 2. User Documentation
- Installation guide
- Configuration instructions
- Feature explanations
- Troubleshooting guide

## Next Steps

After completing this step, proceed to:
1. **[Step 02: Theme Initialization](./02_theme-initialization.md)** - Set up theme activation and initialization
2. **[Step 03: Database Migration System](./03_database-migration-system.md)** - Create database migration system
3. **[Step 04: Autoloading and Namespace Setup](./04_autoloading-and-namespace-setup.md)** - Configure autoloading and namespaces

## Verification Checklist

- [ ] Theme directory structure created
- [ ] Core theme files implemented
- [ ] Composer configuration set up
- [ ] Development environment configured
- [ ] Theme core class implemented
- [ ] Git repository initialized
- [ ] Documentation structure established
- [ ] Testing framework prepared

---

This step establishes the foundation for a modern, maintainable WordPress theme that will successfully replace the TMU plugin while preserving all existing functionality.