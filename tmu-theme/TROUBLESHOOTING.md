# TMU Theme Troubleshooting Guide

## Overview
This guide provides solutions to common issues encountered when using the TMU theme, including installation problems, configuration issues, performance problems, and integration errors.

## Table of Contents
1. [Installation Issues](#installation-issues)
2. [Configuration Problems](#configuration-problems)
3. [TMDB Integration Issues](#tmdb-integration-issues)
4. [Performance Problems](#performance-problems)
5. [Display Issues](#display-issues)
6. [Database Issues](#database-issues)
7. [Security Issues](#security-issues)
8. [Migration Problems](#migration-problems)
9. [Block Editor Issues](#block-editor-issues)
10. [Development Problems](#development-problems)

## Installation Issues

### Theme Installation Fails

**Problem:** Theme fails to install or activate.

**Symptoms:**
- Error messages during installation
- Theme not appearing in admin
- White screen after activation

**Solutions:**

1. **Check PHP Version**
   ```bash
   # Check current PHP version
   php -v
   ```
   - Ensure PHP 7.4 or higher
   - Update PHP if necessary

2. **Verify File Permissions**
   ```bash
   # Set correct permissions
   chmod -R 755 wp-content/themes/tmu
   chown -R www-data:www-data wp-content/themes/tmu
   ```

3. **Check WordPress Version**
   - Ensure WordPress 6.0 or higher
   - Update WordPress if needed

4. **Memory Limit**
   ```php
   // Add to wp-config.php
   ini_set('memory_limit', '256M');
   define('WP_MEMORY_LIMIT', '256M');
   ```

### Missing Dependencies

**Problem:** Theme dependencies not installed.

**Symptoms:**
- Fatal errors mentioning missing classes
- Features not working
- Admin notices about missing components

**Solutions:**

1. **Install Composer Dependencies**
   ```bash
   cd wp-content/themes/tmu
   composer install --no-dev
   ```

2. **Build Assets**
   ```bash
   npm install
   npm run build
   ```

3. **Check Required Plugins**
   - Ensure no conflicting plugins
   - Deactivate unnecessary plugins

## Configuration Problems

### TMDB API Not Working

**Problem:** TMDB integration fails or returns errors.

**Symptoms:**
- No movie data loading
- API error messages
- Empty search results

**Solutions:**

1. **Verify API Key**
   ```php
   // Check in wp-admin > TMU Settings
   $api_key = get_option('tmu_tmdb_api_key');
   if (empty($api_key)) {
       // API key not set
   }
   ```

2. **Test API Connection**
   ```bash
   # Test API endpoint
   curl "https://api.themoviedb.org/3/configuration?api_key=YOUR_API_KEY"
   ```

3. **Check Rate Limits**
   - TMDB allows 40 requests per 10 seconds
   - Implement proper rate limiting

4. **Firewall Issues**
   ```bash
   # Check if server can reach TMDB
   curl -I https://api.themoviedb.org
   ```

### Custom Post Types Not Showing

**Problem:** Movies, TV series, or other content types not visible.

**Symptoms:**
- Post types missing from admin menu
- 404 errors on content pages
- Search not finding content

**Solutions:**

1. **Flush Rewrite Rules**
   ```php
   // Add to functions.php temporarily
   add_action('init', 'flush_rewrite_rules');
   ```

2. **Check Post Type Settings**
   ```php
   // Verify in wp-admin > TMU Settings
   $enabled = get_option('tmu_movies', 'on');
   ```

3. **Verify Database Tables**
   ```sql
   SHOW TABLES LIKE 'wp_tmu_%';
   ```

### Theme Settings Reset

**Problem:** Theme settings revert to defaults.

**Symptoms:**
- Configuration lost after updates
- Settings not saving
- Features disabled unexpectedly

**Solutions:**

1. **Check Database Options**
   ```sql
   SELECT * FROM wp_options WHERE option_name LIKE 'tmu_%';
   ```

2. **Backup/Restore Settings**
   ```php
   // Export settings
   $settings = get_option('tmu_settings');
   file_put_contents('tmu_settings_backup.json', json_encode($settings));
   
   // Import settings
   $settings = json_decode(file_get_contents('tmu_settings_backup.json'), true);
   update_option('tmu_settings', $settings);
   ```

## TMDB Integration Issues

### Search Not Working

**Problem:** TMDB search returns no results or errors.

**Symptoms:**
- Empty search results
- Timeout errors
- Invalid response format

**Solutions:**

1. **Check Search Parameters**
   ```php
   // Debug search query
   $query = sanitize_text_field($_GET['search']);
   error_log("TMU Search Query: " . $query);
   ```

2. **Verify API Response**
   ```php
   // Log API responses
   add_action('tmu_tmdb_response', function($response, $endpoint) {
       error_log("TMDB Response for {$endpoint}: " . print_r($response, true));
   }, 10, 2);
   ```

3. **Handle Special Characters**
   ```php
   // Properly encode search terms
   $encoded_query = urlencode($search_term);
   ```

### Image Loading Issues

**Problem:** TMDB images not displaying or loading slowly.

**Symptoms:**
- Broken image links
- Slow page loading
- 404 errors for images

**Solutions:**

1. **Check Image URLs**
   ```php
   // Verify image configuration
   $image_url = tmu_get_tmdb_image_url($poster_path, 'w500');
   if (!$image_url) {
       // Handle missing images
   }
   ```

2. **Implement Lazy Loading**
   ```javascript
   // Add lazy loading for better performance
   const images = document.querySelectorAll('img[data-src]');
   // ... lazy loading implementation
   ```

3. **Add Fallback Images**
   ```php
   function tmu_get_fallback_image($type = 'poster'): string {
       return get_template_directory_uri() . "/assets/images/no-{$type}.jpg";
   }
   ```

### Data Synchronization Problems

**Problem:** TMDB data not syncing properly with local content.

**Symptoms:**
- Outdated information
- Missing data fields
- Sync errors in logs

**Solutions:**

1. **Manual Sync**
   ```php
   // Force resync specific content
   $syncer = new TMU\API\TMDBSyncer();
   $syncer->sync_content($post_id, $content_type);
   ```

2. **Check Sync Schedule**
   ```php
   // Verify cron job
   $timestamp = wp_next_scheduled('tmu_sync_content');
   if (!$timestamp) {
       wp_schedule_event(time(), 'daily', 'tmu_sync_content');
   }
   ```

3. **Clear Sync Cache**
   ```php
   // Clear cached API responses
   wp_cache_flush();
   delete_transient('tmu_tmdb_cache');
   ```

## Performance Problems

### Slow Page Loading

**Problem:** Pages load slowly, especially content pages.

**Symptoms:**
- High Time to First Byte (TTFB)
- Slow database queries
- Large page sizes

**Solutions:**

1. **Enable Caching**
   ```php
   // Implement object caching
   function tmu_cache_movie_data($post_id): array {
       $cache_key = "tmu_movie_{$post_id}";
       $data = wp_cache_get($cache_key);
       
       if ($data === false) {
           $data = tmu_get_movie_data($post_id);
           wp_cache_set($cache_key, $data, '', 3600);
       }
       
       return $data;
   }
   ```

2. **Optimize Database Queries**
   ```sql
   -- Add missing indexes
   ALTER TABLE wp_tmu_movies ADD INDEX idx_tmdb_popularity (tmdb_popularity);
   ALTER TABLE wp_tmu_movies ADD INDEX idx_release_date (release_date);
   ```

3. **Optimize Images**
   ```php
   // Implement responsive images
   function tmu_get_responsive_image($path, $sizes): string {
       $srcset = [];
       foreach ($sizes as $size) {
           $url = tmu_get_tmdb_image_url($path, $size);
           $width = str_replace('w', '', $size);
           $srcset[] = "{$url} {$width}w";
       }
       return implode(', ', $srcset);
   }
   ```

### High Memory Usage

**Problem:** PHP memory limit exceeded.

**Symptoms:**
- Fatal memory errors
- Plugin conflicts
- Server crashes

**Solutions:**

1. **Increase Memory Limit**
   ```php
   // wp-config.php
   ini_set('memory_limit', '512M');
   define('WP_MEMORY_LIMIT', '512M');
   ```

2. **Optimize Queries**
   ```php
   // Use pagination for large datasets
   function tmu_get_movies_paginated($page = 1, $per_page = 20): array {
       $offset = ($page - 1) * $per_page;
       
       global $wpdb;
       return $wpdb->get_results($wpdb->prepare("
           SELECT * FROM {$wpdb->prefix}tmu_movies 
           ORDER BY tmdb_popularity DESC 
           LIMIT %d OFFSET %d
       ", $per_page, $offset));
   }
   ```

3. **Unload Unnecessary Data**
   ```php
   // Free memory after processing
   unset($large_array);
   gc_collect_cycles();
   ```

### Database Performance

**Problem:** Slow database queries affecting site performance.

**Symptoms:**
- Long query execution times
- Database timeouts
- High CPU usage

**Solutions:**

1. **Analyze Slow Queries**
   ```sql
   -- Enable slow query log
   SET GLOBAL slow_query_log = 'ON';
   SET GLOBAL long_query_time = 1;
   ```

2. **Add Database Indexes**
   ```sql
   -- Common indexes for better performance
   CREATE INDEX idx_movie_title ON wp_tmu_movies (title);
   CREATE INDEX idx_tv_first_air ON wp_tmu_tv_series (first_air_date);
   CREATE INDEX idx_person_name ON wp_tmu_people (name);
   ```

3. **Optimize Queries**
   ```php
   // Use proper JOIN instead of multiple queries
   function tmu_get_movie_with_genres($movie_id): array {
       global $wpdb;
       
       return $wpdb->get_row($wpdb->prepare("
           SELECT m.*, GROUP_CONCAT(g.name) as genre_names
           FROM {$wpdb->prefix}tmu_movies m
           LEFT JOIN {$wpdb->prefix}tmu_movie_genres mg ON m.id = mg.movie_id
           LEFT JOIN {$wpdb->prefix}tmu_genres g ON mg.genre_id = g.id
           WHERE m.id = %d
           GROUP BY m.id
       ", $movie_id), ARRAY_A);
   }
   ```

## Display Issues

### Layout Problems

**Problem:** Theme layout broken or not displaying correctly.

**Symptoms:**
- Overlapping elements
- Missing CSS styles
- Responsive issues

**Solutions:**

1. **Clear CSS Cache**
   ```bash
   # Rebuild CSS assets
   npm run build
   ```

2. **Check CSS Conflicts**
   ```javascript
   // Debug CSS conflicts in browser console
   console.log(getComputedStyle(element));
   ```

3. **Verify Tailwind Build**
   ```bash
   # Check if Tailwind CSS is properly compiled
   npx tailwindcss -i ./assets/src/css/main.css -o ./assets/build/css/main.css --watch
   ```

### Missing Images

**Problem:** Images not displaying or showing broken links.

**Symptoms:**
- 404 errors for image URLs
- Alt text displayed instead of images
- Slow image loading

**Solutions:**

1. **Check Image Paths**
   ```php
   // Verify image URL generation
   $image_url = tmu_get_tmdb_image_url($poster_path);
   if (!wp_http_validate_url($image_url)) {
       // Invalid URL
   }
   ```

2. **Implement Error Handling**
   ```javascript
   // Handle image load errors
   document.querySelectorAll('img').forEach(img => {
       img.onerror = function() {
           this.src = '/wp-content/themes/tmu/assets/images/no-poster.jpg';
       };
   });
   ```

3. **Add Image Optimization**
   ```php
   // Optimize image delivery
   function tmu_optimize_image_url($url, $width = null): string {
       if ($width) {
           return add_query_arg('w', $width, $url);
       }
       return $url;
   }
   ```

### Search Results Issues

**Problem:** Search not returning expected results or displaying incorrectly.

**Symptoms:**
- No search results
- Incorrect result formatting
- Search pagination not working

**Solutions:**

1. **Debug Search Query**
   ```php
   // Log search parameters
   add_action('pre_get_posts', function($query) {
       if ($query->is_search() && !is_admin()) {
           error_log('Search Query: ' . print_r($query->query_vars, true));
       }
   });
   ```

2. **Check Search Template**
   ```php
   // Verify search template hierarchy
   $templates = [
       'search-movie.php',
       'search.php',
       'index.php'
   ];
   ```

3. **Fix Search Parameters**
   ```php
   // Ensure proper search handling
   function tmu_modify_search_query($query): void {
       if (!is_admin() && $query->is_main_query() && $query->is_search()) {
           $query->set('post_type', ['movie', 'tv_series', 'drama', 'person']);
       }
   }
   add_action('pre_get_posts', 'tmu_modify_search_query');
   ```

## Database Issues

### Migration Failures

**Problem:** Database migration fails during theme activation or updates.

**Symptoms:**
- Missing database tables
- Migration error messages
- Incomplete data structure

**Solutions:**

1. **Run Manual Migration**
   ```php
   // Force migration run
   $migrator = new TMU\Migration\DatabaseMigrator();
   $migrator->run_migrations();
   ```

2. **Check Database Permissions**
   ```sql
   -- Verify user permissions
   SHOW GRANTS FOR CURRENT_USER();
   ```

3. **Restore from Backup**
   ```bash
   # Restore database backup
   mysql -u username -p database_name < backup.sql
   ```

### Data Corruption

**Problem:** Corrupted data in TMU tables.

**Symptoms:**
- Invalid JSON data
- Missing relationships
- Inconsistent data states

**Solutions:**

1. **Repair Tables**
   ```sql
   -- Repair corrupted tables
   REPAIR TABLE wp_tmu_movies;
   REPAIR TABLE wp_tmu_tv_series;
   ```

2. **Validate Data Integrity**
   ```php
   // Check for orphaned records
   function tmu_check_data_integrity(): array {
       global $wpdb;
       
       $orphaned = $wpdb->get_results("
           SELECT m.id, m.post_id 
           FROM {$wpdb->prefix}tmu_movies m
           LEFT JOIN {$wpdb->posts} p ON m.post_id = p.ID
           WHERE p.ID IS NULL
       ");
       
       return $orphaned;
   }
   ```

3. **Clean Invalid Data**
   ```php
   // Remove invalid JSON data
   function tmu_clean_invalid_json(): void {
       global $wpdb;
       
       $wpdb->query("
           UPDATE {$wpdb->prefix}tmu_movies 
           SET genres = NULL 
           WHERE genres != '' 
           AND genres IS NOT NULL 
           AND JSON_VALID(genres) = 0
       ");
   }
   ```

## Security Issues

### API Key Exposure

**Problem:** TMDB API key exposed in logs or client-side code.

**Symptoms:**
- API key visible in browser
- Key appearing in error logs
- Unauthorized API usage

**Solutions:**

1. **Secure API Key Storage**
   ```php
   // Store in wp-config.php
   define('TMU_TMDB_API_KEY', 'your-secure-api-key');
   
   // Never expose in frontend
   function tmu_get_secure_api_key(): string {
       return defined('TMU_TMDB_API_KEY') ? TMU_TMDB_API_KEY : get_option('tmu_tmdb_api_key');
   }
   ```

2. **Remove from Logs**
   ```php
   // Filter sensitive data from logs
   add_filter('wp_die_handler', function($handler) {
       if (strpos($message, 'api_key') !== false) {
           $message = preg_replace('/api_key=[^&\s]*/', 'api_key=***', $message);
       }
       return $handler;
   });
   ```

3. **Rotate API Key**
   ```php
   // Implement key rotation
   function tmu_rotate_api_key(): void {
       $new_key = 'new-api-key';
       update_option('tmu_tmdb_api_key', $new_key);
       wp_cache_delete('tmu_api_key');
   }
   ```

### SQL Injection Prevention

**Problem:** Potential SQL injection vulnerabilities.

**Symptoms:**
- Unescaped user input in queries
- Dynamic SQL construction
- Security scanner warnings

**Solutions:**

1. **Use Prepared Statements**
   ```php
   // Always use wpdb->prepare()
   function tmu_get_movies_by_genre($genre_id): array {
       global $wpdb;
       
       return $wpdb->get_results($wpdb->prepare("
           SELECT * FROM {$wpdb->prefix}tmu_movies 
           WHERE JSON_CONTAINS(genres, %s)
       ", json_encode(['id' => intval($genre_id)])));
   }
   ```

2. **Validate Input**
   ```php
   // Sanitize all inputs
   function tmu_sanitize_search_input($input): string {
       return sanitize_text_field(wp_unslash($input));
   }
   ```

3. **Escape Output**
   ```php
   // Escape output data
   function tmu_safe_output($data): string {
       return esc_html($data);
   }
   ```

## Migration Problems

### Plugin to Theme Migration Issues

**Problem:** Migration from TMU plugin to theme fails or is incomplete.

**Symptoms:**
- Missing content after migration
- Broken relationships
- Performance issues

**Solutions:**

1. **Verify Migration Status**
   ```php
   // Check migration completion
   $migration_status = get_option('tmu_migration_status');
   if ($migration_status !== 'completed') {
       // Re-run migration
   }
   ```

2. **Manual Data Verification**
   ```sql
   -- Compare record counts
   SELECT 
       (SELECT COUNT(*) FROM wp_posts WHERE post_type = 'movie') as post_count,
       (SELECT COUNT(*) FROM wp_tmu_movies) as tmu_count;
   ```

3. **Fix Missing Relationships**
   ```php
   // Restore missing post relationships
   function tmu_fix_post_relationships(): void {
       global $wpdb;
       
       $wpdb->query("
           UPDATE {$wpdb->prefix}tmu_movies m
           INNER JOIN {$wpdb->posts} p ON p.post_title = m.title
           SET m.post_id = p.ID
           WHERE m.post_id IS NULL
           AND p.post_type = 'movie'
       ");
   }
   ```

### Content Loss During Migration

**Problem:** Content lost or corrupted during migration process.

**Symptoms:**
- Missing posts
- Broken meta data
- Lost custom fields

**Solutions:**

1. **Restore from Backup**
   ```bash
   # Restore pre-migration backup
   mysql -u username -p database_name < pre_migration_backup.sql
   ```

2. **Incremental Migration**
   ```php
   // Migrate in smaller batches
   function tmu_migrate_batch($batch_size = 100, $offset = 0): void {
       $migrator = new TMU\Migration\ContentMigrator();
       $migrator->migrate_content($batch_size, $offset);
   }
   ```

3. **Verify Each Step**
   ```php
   // Add migration logging
   function tmu_log_migration_step($step, $data): void {
       error_log("Migration Step: {$step} - " . print_r($data, true));
   }
   ```

## Block Editor Issues

### Blocks Not Loading

**Problem:** Custom TMU blocks not appearing in block editor.

**Symptoms:**
- Missing blocks in inserter
- JavaScript errors in console
- Block registration failures

**Solutions:**

1. **Check Block Registration**
   ```php
   // Verify block assets are enqueued
   add_action('enqueue_block_editor_assets', function() {
       wp_enqueue_script(
           'tmu-blocks',
           get_template_directory_uri() . '/assets/build/js/blocks.js',
           ['wp-blocks', 'wp-element', 'wp-editor'],
           filemtime(get_template_directory() . '/assets/build/js/blocks.js')
       );
   });
   ```

2. **Debug JavaScript Errors**
   ```javascript
   // Check browser console for errors
   console.log('TMU Blocks Loading...');
   
   // Verify block registration
   wp.blocks.getBlockTypes().filter(block => 
       block.name.startsWith('tmu/')
   );
   ```

3. **Rebuild Block Assets**
   ```bash
   # Rebuild JavaScript assets
   npm run build:blocks
   ```

### Block Rendering Issues

**Problem:** Blocks not rendering correctly on frontend.

**Symptoms:**
- Empty block output
- PHP errors in block rendering
- Styling issues

**Solutions:**

1. **Check Render Callbacks**
   ```php
   // Verify render callback is working
   function tmu_debug_block_render($content, $block): string {
       error_log("Rendering block: " . $block['blockName']);
       return $content;
   }
   add_filter('render_block', 'tmu_debug_block_render', 10, 2);
   ```

2. **Validate Block Attributes**
   ```php
   // Ensure attributes are properly defined
   function tmu_validate_block_attributes($attributes, $block_type): array {
       // Validation logic
       return $attributes;
   }
   ```

## Development Problems

### Build Process Issues

**Problem:** Asset build process fails or produces incorrect output.

**Symptoms:**
- JavaScript/CSS not compiling
- Build errors in terminal
- Missing asset files

**Solutions:**

1. **Clear Build Cache**
   ```bash
   # Clear npm cache and rebuild
   npm cache clean --force
   rm -rf node_modules package-lock.json
   npm install
   npm run build
   ```

2. **Check Build Configuration**
   ```javascript
   // Verify webpack.config.js
   const config = require('./webpack.config.js');
   console.log(config);
   ```

3. **Update Dependencies**
   ```bash
   # Update to latest compatible versions
   npm update
   npm audit fix
   ```

### Code Quality Issues

**Problem:** Code quality tools reporting errors or warnings.

**Symptoms:**
- PHPCS violations
- ESLint errors
- PHPStan warnings

**Solutions:**

1. **Fix PHP Standards**
   ```bash
   # Auto-fix PHP code style
   vendor/bin/phpcbf --standard=WordPress includes/
   ```

2. **Fix JavaScript Issues**
   ```bash
   # Auto-fix JavaScript issues
   npm run lint:fix
   ```

3. **Address Static Analysis**
   ```bash
   # Run PHPStan analysis
   vendor/bin/phpstan analyse includes/ --level=5
   ```

### Testing Failures

**Problem:** Unit tests or integration tests failing.

**Symptoms:**
- Test suite failures
- Assertion errors
- Environment issues

**Solutions:**

1. **Check Test Environment**
   ```bash
   # Verify test database
   mysql -u root -p -e "CREATE DATABASE tmu_test;"
   ```

2. **Update Test Configuration**
   ```php
   // phpunit.xml
   <env name="WP_TESTS_DOMAIN" value="example.org"/>
   <env name="WP_TESTS_EMAIL" value="admin@example.org"/>
   ```

3. **Run Individual Tests**
   ```bash
   # Test specific components
   vendor/bin/phpunit tests/Unit/PostTypes/MoviePostTypeTest.php
   ```

---

## Emergency Procedures

### Site Down Recovery

**Problem:** Complete site failure.

**Immediate Actions:**
1. Switch to maintenance mode
2. Check error logs
3. Restore from backup if necessary
4. Contact hosting provider if server issues

### Data Recovery

**Problem:** Data loss or corruption.

**Immediate Actions:**
1. Stop all write operations
2. Identify scope of data loss
3. Restore from most recent backup
4. Verify data integrity after restoration

### Security Breach

**Problem:** Site compromised or suspicious activity.

**Immediate Actions:**
1. Change all passwords immediately
2. Update WordPress core and all plugins
3. Scan for malware
4. Review access logs
5. Implement additional security measures

---

## Getting Help

### Support Channels
- **Documentation:** Check this guide and other documentation files
- **Error Logs:** Review WordPress error logs and server logs
- **Community Forum:** Post questions to the community
- **GitHub Issues:** Report bugs and feature requests

### Diagnostic Information
When seeking help, provide:
- WordPress version
- PHP version
- Theme version
- Error messages (full text)
- Steps to reproduce issue
- Relevant log entries

### Log Locations
```bash
# Common log file locations
/var/log/apache2/error.log
/var/log/nginx/error.log
wp-content/debug.log
```

---

*This troubleshooting guide covers the most common issues encountered with the TMU theme. For additional support, refer to the complete documentation or contact the development team.*