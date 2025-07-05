# TMU Theme Development Documentation

## Project Overview

This documentation outlines the complete conversion of the existing **TMU Plugin** into a modern, well-structured **WordPress theme** named **TMU**. The new theme will preserve 100% of the existing plugin's functionality while implementing modern coding standards, OOP architecture, and clean file structure.

## Project Context

- **Source**: TMU Plugin (`tmu-plugin` directory)
- **Target**: TMU WordPress Theme
- **Development Environment**: WordPress local setup with Cursor editor
- **Database**: Existing tables must be preserved and utilized
- **Architecture**: Modern OOP, namespaced, component-based structure

## Database Schema Analysis

Based on the plugin analysis, the following database tables exist and must be preserved:

### Core Tables
- `wp_tmu_movies` - Movie metadata
- `wp_tmu_people` - People/celebrities data
- `wp_tmu_dramas` - Drama series metadata
- `wp_tmu_tv_series` - TV shows metadata
- `wp_tmu_videos` - Video content
- `wp_tmu_seo_options` - SEO configuration

### Relationship Tables
- `wp_tmu_movies_cast` - Movie cast relationships
- `wp_tmu_movies_crew` - Movie crew relationships
- `wp_tmu_dramas_cast` - Drama cast relationships
- `wp_tmu_dramas_crew` - Drama crew relationships
- `wp_tmu_tv_series_cast` - TV series cast relationships
- `wp_tmu_tv_series_crew` - TV series crew relationships

### Episode/Season Tables
- `wp_tmu_tv_series_episodes` - TV show episodes
- `wp_tmu_tv_series_seasons` - TV show seasons
- `wp_tmu_dramas_episodes` - Drama episodes
- `wp_tmu_dramas_seasons` - Drama seasons

### Extended Tables
- Enhanced `wp_comments` with `comment_rating` and `parent_post_id` columns
- Enhanced `wp_posts` with `seo_title`, `seo_description`, `meta_keywords` columns

## Post Types Analysis

### Primary Post Types
1. **TV Show** (`tv`) - TV series/shows
2. **Movie** (`movie`) - Movies
3. **Drama** (`drama`) - Drama series
4. **People** (`people`) - Celebrities/cast/crew
5. **Video** (`video`) - Video content

### Nested Post Types
1. **Season** (`season`) - TV show seasons (child of TV shows)
2. **Episode** (`episode`) - TV show episodes (child of TV shows)
3. **Drama Episode** (`drama-episode`) - Drama episodes (child of dramas)

## Taxonomies Analysis

### Universal Taxonomies
- `genre` - Content genres
- `country` - Countries
- `language` - Languages
- `by-year` - Release years

### Specific Taxonomies
- `network` - TV networks (TV shows only)
- `channel` - TV channels (Dramas only)
- `keyword` - Keywords (Movies & TV shows, excluded when dramas enabled)
- `nationality` - People nationalities

## Key Features Analysis

### TMDB Integration
- API integration for fetching movie/TV/people data
- Automated data updates and synchronization
- Image and metadata management
- Credit processing and cast/crew relationships

### SEO Features
- Custom SEO titles and descriptions
- Meta keywords management
- Schema markup generation
- Sitemap integration
- Rank Math compatibility

### Advanced Features
- Rating system with TMDB integration
- Comment system with ratings
- Advanced search functionality
- AJAX-powered modules
- Image galleries and video management
- Trending content management
- Birthday tracking for celebrities
- Schedule management for dramas

## Tools and Setup

- **Development Environment**: WordPress local installation
- **Code Editor**: Cursor
- **PHP Version**: 7.4+
- **WordPress Version**: 6.0+
- **Database**: MySQL/MariaDB
- **Architecture**: Object-Oriented PHP with namespacing
- **Autoloading**: Composer PSR-4 autoloading
- **Coding Standards**: WordPress Coding Standards
- **CSS Framework**: Custom CSS with BEM methodology

## Documentation Structure

This documentation is organized into detailed step-by-step guides, each focusing on specific aspects of the theme development:

### Phase 1: Foundation & Setup
- [01 - Project Setup and Structure](./01_project-setup-and-structure.md)
- [02 - Theme Initialization](./02_theme-initialization.md)
- [03 - Database Migration System](./03_database-migration-system.md)
- [04 - Autoloading and Namespace Setup](./04_autoloading-and-namespace-setup.md)

### Phase 2: Core Content Management
- [05 - Post Types Registration](./05_post-types-registration.md)
- [06 - Taxonomies Registration](./06_taxonomies-registration.md)
- [07 - Custom Fields System](./07_custom-fields-system.md)
- [08 - Admin UI and Meta Boxes](./08_admin-ui-and-meta-boxes.md)

### Phase 3: Data Integration & API
- [09 - TMDB API Integration](./09_tmdb-api-integration.md)
- [10 - Data Processing and Storage](./10_data-processing-and-storage.md)
- [11 - Cast and Crew Management](./11_cast-and-crew-management.md)
- [12 - Media Management System](./12_media-management-system.md)

### Phase 4: Frontend & Templates
- [13 - Template Hierarchy](./13_template-hierarchy.md)
- [14 - Single Post Templates](./14_single-post-templates.md)
- [15 - Archive Templates](./15_archive-templates.md)
- [16 - Search and Filtering](./16_search-and-filtering.md)

### Phase 5: Advanced Features
- [17 - Rating and Comment System](./17_rating-and-comment-system.md)
- [18 - AJAX Modules](./18_ajax-modules.md)
- [19 - SEO Implementation](./19_seo-implementation.md)
- [20 - Schema Markup](./20_schema-markup.md)

### Phase 6: Performance & Optimization
- [21 - Caching Strategy](./21_caching-strategy.md)
- [22 - Image Optimization](./22_image-optimization.md)
- [23 - Performance Optimization](./23_performance-optimization.md)
- [24 - Security Implementation](./24_security-implementation.md)

### Phase 7: Specialized Features
- [25 - Shortcodes System](./25_shortcodes-system.md)
- [26 - Schedule Management](./26_schedule-management.md)
- [27 - Trending System](./27_trending-system.md)
- [28 - Birthday Tracking](./28_birthday-tracking.md)

### Phase 8: Styling & UI
- [29 - CSS Architecture](./29_css-architecture.md)
- [30 - Responsive Design](./30_responsive-design.md)
- [31 - Component Styling](./31_component-styling.md)
- [32 - Admin Interface Styling](./32_admin-interface-styling.md)

### Phase 9: Testing & Quality
- [33 - Testing Strategy](./33_testing-strategy.md)
- [34 - Code Quality Standards](./34_code-quality-standards.md)
- [35 - Migration Testing](./35_migration-testing.md)
- [36 - Performance Testing](./36_performance-testing.md)

### Phase 10: Deployment & Documentation
- [37 - Theme Packaging](./37_theme-packaging.md)
- [38 - Installation Guide](./38_installation-guide.md)
- [39 - User Documentation](./39_user-documentation.md)
- [40 - Developer Documentation](./40_developer-documentation.md)

## Key Principles

### WordPress Standards
- Follow WordPress Coding Standards
- Use WordPress hooks and filters exclusively
- Implement proper sanitization and validation
- Follow WordPress template hierarchy

### Modern PHP Practices
- Object-Oriented Programming
- Namespacing and autoloading
- Type declarations and return types
- Error handling and logging
- Security best practices

### Performance Considerations
- Lazy loading implementation
- Database query optimization
- Caching strategies
- Image optimization
- Minification and compression

### Maintainability
- Modular architecture
- Clear documentation
- Consistent code style
- Separation of concerns
- Version control best practices

## Success Criteria

1. **100% Functionality Preservation**: All existing plugin features must work identically
2. **Database Compatibility**: Existing data must be preserved and accessible
3. **Performance Improvement**: Theme should perform better than the original plugin
4. **Modern Code Standards**: All code must follow modern PHP and WordPress standards
5. **Extensibility**: Architecture should support future enhancements
6. **User Experience**: Admin and frontend experience should be improved
7. **SEO Maintenance**: All SEO features must be preserved and enhanced

## Development Timeline

The development is organized into 10 phases, with each phase building upon the previous ones. The documentation provides detailed instructions for each step, ensuring a systematic and thorough conversion process.

## Next Steps

Begin with [Phase 1: Foundation & Setup](./01_project-setup-and-structure.md) to establish the project structure and development environment.

---

*This documentation serves as the complete guide for converting the TMU plugin into a modern WordPress theme while preserving all functionality and improving code quality.*