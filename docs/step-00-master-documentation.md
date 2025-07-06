# TMU Theme Development Documentation

## Project Overview

This documentation outlines the complete conversion of the existing **TMU Plugin** into a modern, well-structured **WordPress theme** named **TMU**. The new theme will preserve 100% of the existing plugin's functionality while implementing modern coding standards, OOP architecture, **Tailwind CSS** styling framework, and clean file structure.

## Project Context

- **Source**: TMU Plugin (`tmu-plugin` directory)
- **Target**: TMU WordPress Theme
- **Development Environment**: WordPress local setup with Cursor editor
- **Styling Framework**: **Tailwind CSS** (v3.4+)
- **Database**: Existing tables must be preserved and utilized
- **Architecture**: Modern OOP, namespaced, component-based structure

## Complete Documentation Steps

### Phase 1: Foundation and Setup
1. **[Step 01: Project Setup and Structure](./step-01-project-setup-and-structure.md)** ✅
   - Theme directory structure and organization
   - Development environment configuration with Tailwind CSS
   - Core constants and initialization files
   - Basic theme files and templates

2. **[Step 02: Theme Initialization](./step-02-theme-initialization.md)** ✅
   - Theme activation/deactivation handling
   - Settings migration from plugin
   - Feature toggles management
   - Admin interface setup with Tailwind CSS

3. **[Step 03: Database Migration System](./step-03-database-migration-system.md)** ✅
   - Custom table preservation strategy
   - Migration scripts for fresh installations
   - Data integrity verification
   - Backup and recovery procedures

4. **[Step 04: Autoloading and Namespace Setup](./step-04-autoloading-and-namespace-setup.md)** ✅
   - PSR-4 compliant autoloading with Composer
   - Modern namespace organization
   - Development tools integration
   - Helper functions and utilities

### Phase 2: Core Content Management
5. **[Step 05: Post Types Registration](./step-05-post-types-registration.md)** ✅
   - Modern OOP post type system
   - Conditional registration based on settings
   - Hierarchical relationships (seasons, episodes)
   - Admin menu organization

6. **[Step 06: Taxonomies Registration](./step-06-taxonomies-registration.md)** ✅
   - Universal and specific taxonomies
   - Conditional logic for feature-based registration
   - Term seeding and management
   - Custom rewrite rules

7. **[Step 07: Gutenberg Block System](./step-07-gutenberg-block-system.md)** ✅
   - Modern Gutenberg blocks with Tailwind CSS
   - Custom blocks for TMU content types
   - Block editor integration
   - Dynamic block rendering

8. **[Step 08: Admin UI and Meta Boxes](./step-08-admin-ui-and-meta-boxes.md)** ✅
   - Enhanced admin columns and sorting
   - TMDB integration meta boxes
   - Dashboard widgets and quick actions
   - Improved admin user experience

### Phase 3: External Integration and Frontend
9. **[Step 09: TMDB API Integration](./step-09-tmdb-api-integration.md)** 🚀
   - Modern API client with caching
   - Automated data synchronization
   - Image and metadata management
   - Error handling and rate limiting

10. **[Step 10: Frontend Templates](./step-10-frontend-templates.md)** 🚀
    - Template hierarchy and organization with Tailwind CSS
    - Component-based template system
    - Responsive design implementation
    - Search and filtering interfaces

11. **[Step 11: SEO and Schema Markup](./step-11-seo-and-schema.md)** 🚀
    - Structured data implementation
    - OpenGraph and Twitter cards
    - Breadcrumb navigation
    - SEO optimization features

### Phase 4: Advanced Features and Performance
12. **[Step 12: Search and Filtering](./step-12-search-and-filtering.md)** 🚀
    - Advanced search functionality
    - AJAX-powered filtering with Tailwind CSS
    - Faceted search implementation
    - Search result optimization

13. **[Step 13: Performance Optimization](./step-13-performance-optimization.md)** 🚀
    - Object caching strategies
    - Database query optimization
    - Image optimization and lazy loading
    - CDN integration support

14. **[Step 14: Security and Accessibility](./step-14-security-and-accessibility.md)** 🚀
    - Data sanitization and validation
    - CSRF protection implementation
    - User capability management
    - Security best practices

15. **[Step 15: Testing and Quality Assurance](./step-15-testing-and-quality-assurance.md)** 🚀
    - Unit and integration testing
    - Performance testing
    - Accessibility compliance
    - Cross-browser compatibility

## Technology Stack

### Core Technologies
- **WordPress**: 6.0+ (modern WordPress features)
- **PHP**: 7.4+ (modern PHP with type hints)
- **Tailwind CSS**: 3.4+ (utility-first CSS framework)
- **JavaScript**: ES6+ (modern JavaScript)
- **Composer**: Dependency management
- **PSR-4**: Autoloading standard

### Development Tools
- **Node.js**: 18+ (for Tailwind CSS compilation)
- **npm/Yarn**: Package management
- **Webpack/Vite**: Asset bundling
- **PHPUnit**: Testing framework
- **PHPCS**: Code standards
- **Git**: Version control

## Database Schema Analysis

Based on the plugin analysis, the following database tables exist and must be preserved:

### Core Content Tables
- `wp_tmu_movies` - Movie data storage
- `wp_tmu_tv_series` - TV series information
- `wp_tmu_dramas` - Drama series data
- `wp_tmu_people` - Cast and crew information
- `wp_tmu_videos` - Video content metadata

### Relationship Tables
- `wp_tmu_movies_cast` - Movie cast relationships
- `wp_tmu_movies_crew` - Movie crew relationships  
- `wp_tmu_tv_series_cast` - TV series cast relationships
- `wp_tmu_tv_series_crew` - TV series crew relationships
- `wp_tmu_dramas_cast` - Drama cast relationships
- `wp_tmu_dramas_crew` - Drama crew relationships

### Hierarchical Tables
- `wp_tmu_tv_series_seasons` - TV series seasons
- `wp_tmu_tv_series_episodes` - TV series episodes
- `wp_tmu_dramas_seasons` - Drama seasons
- `wp_tmu_dramas_episodes` - Drama episodes

### Additional Tables
- `wp_tmu_seo_options` - SEO configuration data
- `wp_tmu_api_cache` - TMDB API response caching (to be implemented)

## File Structure Overview

```
tmu-theme/
├── style.css                          # Theme identification
├── functions.php                      # Theme bootstrap
├── package.json                       # Node.js dependencies
├── tailwind.config.js                 # Tailwind CSS configuration
├── webpack.config.js                  # Asset bundling
├── composer.json                      # PHP dependencies
├── assets/
│   ├── src/
│   │   ├── css/
│   │   │   ├── main.css              # Main Tailwind CSS file
│   │   │   └── admin.css             # Admin styles
│   │   └── js/
│   │       ├── main.js               # Main JavaScript
│   │       └── admin.js              # Admin JavaScript
│   └── build/                        # Compiled assets
├── includes/
│   ├── classes/                      # Core classes with namespace TMU
│   │   ├── ThemeCore.php            # Main theme class
│   │   ├── Admin/                    # Admin functionality
│   │   ├── API/                      # TMDB API integration
│   │   ├── Database/                 # Database operations
│   │   ├── Frontend/                 # Frontend functionality
│   │   ├── PostTypes/               # Post type management
│   │   ├── Taxonomies/              # Taxonomy management
│   │   ├── Blocks/                   # Gutenberg blocks
│   │   └── Utils/                    # Utility classes
│   ├── migrations/                   # Database migrations
│   └── config/                       # Configuration files
├── templates/
│   ├── archive/                      # Archive templates
│   ├── single/                       # Single post templates
│   ├── parts/                        # Template parts
│   └── blocks/                       # Block templates
└── languages/                        # Translation files
```

## Implementation Approach

### 1. Tailwind CSS Integration
- **File**: `assets/src/css/main.css` - Main Tailwind CSS file
- **Config**: `tailwind.config.js` - Tailwind configuration
- **Build**: `webpack.config.js` - Asset compilation
- **Output**: `assets/build/` - Compiled CSS and JS

### 2. Component-Based Architecture
- **File**: `includes/classes/Frontend/ComponentManager.php` - Component management
- **Directory**: `templates/parts/components/` - Reusable components
- **Usage**: All UI components use Tailwind utility classes

### 3. Modern WordPress Integration
- **File**: `includes/classes/PostTypes/PostTypeManager.php` - Post type registration
- **File**: `includes/classes/Taxonomies/TaxonomyManager.php` - Taxonomy registration
- **File**: `includes/classes/Blocks/BlockManager.php` - Gutenberg block registration

### 4. Database Integration
- **File**: `includes/classes/Database/Migration.php` - Database migrations
- **File**: `includes/classes/Database/QueryBuilder.php` - Custom queries
- **Preservation**: All existing table structures maintained

## Quality Assurance Standards

### Code Standards
- **WordPress Coding Standards**: Enforced via PHPCS
- **PSR-4 Autoloading**: Namespace organization
- **Type Hints**: PHP 7.4+ features
- **Documentation**: PHPDoc blocks

### CSS Standards
- **Tailwind CSS**: Utility-first approach
- **Responsive Design**: Mobile-first methodology
- **Accessibility**: WCAG 2.1 AA compliance
- **Performance**: Optimized builds

### Testing Standards
- **Unit Tests**: PHPUnit testing
- **Integration Tests**: WordPress testing framework
- **Performance Tests**: Load testing
- **Accessibility Tests**: Automated testing

## Development Workflow

### 1. Asset Compilation
```bash
# Install dependencies
npm install

# Development build
npm run dev

# Production build
npm run build

# Watch mode
npm run watch
```

### 2. PHP Development
```bash
# Install PHP dependencies
composer install

# Run tests
composer test

# Check code standards
composer cs-check
```

### 3. Theme Activation
```bash
# Activate theme
wp theme activate tmu

# Run database migrations
wp tmu migrate

# Update permalinks
wp rewrite flush
```

## Migration Strategy

### Phase 1: Setup (Steps 1-4)
- **Duration**: 2-3 days
- **Focus**: Infrastructure and foundation
- **Files**: Core theme files, autoloading, configuration

### Phase 2: Content Types (Steps 5-8)
- **Duration**: 3-4 days
- **Focus**: Post types, taxonomies, admin interface
- **Files**: Registration classes, admin components

### Phase 3: Frontend (Steps 9-12)
- **Duration**: 4-5 days
- **Focus**: Templates, API integration, SEO
- **Files**: Template files, API classes, frontend components

### Phase 4: Optimization (Steps 13-15)
- **Duration**: 2-3 days
- **Focus**: Performance, security, testing
- **Files**: Optimization classes, test files

## Success Metrics

### Technical Metrics
- **Performance**: < 2s page load time
- **SEO**: Core Web Vitals optimization
- **Accessibility**: WCAG 2.1 AA compliance
- **Code Quality**: 90%+ test coverage

### User Experience
- **Admin Interface**: Intuitive content management
- **Frontend**: Responsive design across devices
- **Search**: Advanced filtering capabilities
- **TMDB Integration**: Seamless data synchronization

---

**Legend**: ✅ Completed | 🚀 In Progress | 📋 Planned

This comprehensive documentation ensures a successful conversion from plugin to theme while maintaining all functionality and implementing modern development practices with Tailwind CSS.