# TMU Theme Production Deployment Guide

## Overview
This guide provides step-by-step instructions for deploying the TMU theme to production, including pre-deployment validation, launch day procedures, and post-deployment monitoring.

## Table of Contents
1. [Pre-Deployment Preparation](#pre-deployment-preparation)
2. [Environment Setup](#environment-setup)
3. [Launch Day Procedures](#launch-day-procedures)
4. [Post-Deployment Validation](#post-deployment-validation)
5. [Monitoring and Maintenance](#monitoring-and-maintenance)
6. [Rollback Procedures](#rollback-procedures)
7. [Troubleshooting](#troubleshooting)

## Pre-Deployment Preparation

### 1. Final Validation Checklist
Before beginning deployment, ensure all requirements are met:

```bash
# Run all tests
npm run test
composer test

# Run security checks
npm run security:check
composer run analyze

# Build production assets
npm run build:production
```

#### Technical Validation
- [ ] All automated tests passing (unit, integration, browser)
- [ ] Performance benchmarks met (< 3s load time, < 50 queries, < 64MB memory)
- [ ] Security audit completed with no critical issues
- [ ] Accessibility compliance verified (WCAG 2.1 AA)
- [ ] Cross-browser testing completed
- [ ] Mobile responsiveness confirmed
- [ ] SEO optimization validated
- [ ] TMDB API integration tested
- [ ] Database migration scripts verified
- [ ] Backup procedures tested

#### Content Preparation
- [ ] Content migration plan finalized
- [ ] Media files optimized and ready
- [ ] User permissions and roles configured
- [ ] TMDB API key configured
- [ ] Admin users training completed

### 2. Environment Requirements

#### Server Specifications
```bash
# Minimum server requirements
PHP >= 7.4 (with extensions: json, curl, mbstring, xml, zip)
WordPress >= 6.0
MySQL >= 5.7 or MariaDB >= 10.3
Apache >= 2.4 or Nginx >= 1.18
Node.js >= 16 (for build tools)
Composer >= 2.0
```

#### Server Configuration
```apache
# .htaccess optimizations
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^index\.php$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.php [L]
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set X-Content-Type-Options "nosniff"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Gzip compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

#### Database Configuration
```sql
-- Optimize MySQL for TMU theme
SET GLOBAL innodb_buffer_pool_size = 256M;
SET GLOBAL max_connections = 100;
SET GLOBAL query_cache_size = 64M;
SET GLOBAL query_cache_type = 1;
```

## Environment Setup

### 1. Production Server Setup

#### File System Preparation
```bash
# Create necessary directories
sudo mkdir -p /var/backups/tmu-theme
sudo mkdir -p /var/log/tmu-theme
sudo mkdir -p /var/cache/tmu-theme

# Set proper permissions
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
sudo chmod -R 644 /var/www/html/wp-content/themes/tmu/*.php
```

#### SSL Certificate Setup
```bash
# Install SSL certificate (example with Let's Encrypt)
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Verify SSL configuration
sudo certbot certificates
```

### 2. Database Setup

#### Create Production Database
```sql
-- Create database and user
CREATE DATABASE tmu_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'tmu_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON tmu_production.* TO 'tmu_user'@'localhost';
FLUSH PRIVILEGES;
```

#### Database Optimization
```sql
-- Optimize database settings
SET GLOBAL innodb_file_per_table = 1;
SET GLOBAL innodb_flush_log_at_trx_commit = 2;
SET GLOBAL sync_binlog = 0;
```

### 3. WordPress Configuration

#### wp-config.php Production Settings
```php
<?php
// Production wp-config.php settings

// Database configuration
define('DB_NAME', 'tmu_production');
define('DB_USER', 'tmu_user');
define('DB_PASSWORD', 'secure_password');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// Security keys (generate new ones)
define('AUTH_KEY', 'your-unique-auth-key');
define('SECURE_AUTH_KEY', 'your-unique-secure-auth-key');
define('LOGGED_IN_KEY', 'your-unique-logged-in-key');
define('NONCE_KEY', 'your-unique-nonce-key');
define('AUTH_SALT', 'your-unique-auth-salt');
define('SECURE_AUTH_SALT', 'your-unique-secure-auth-salt');
define('LOGGED_IN_SALT', 'your-unique-logged-in-salt');
define('NONCE_SALT', 'your-unique-nonce-salt');

// Production optimizations
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', false);
define('WP_CACHE', true);
define('COMPRESS_CSS', true);
define('COMPRESS_SCRIPTS', true);
define('ENFORCE_GZIP', true);
define('AUTOMATIC_UPDATER_DISABLED', true);
define('DISALLOW_FILE_EDIT', true);
define('DISALLOW_FILE_MODS', true);

// Memory limits
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');

// Security
define('FORCE_SSL_ADMIN', true);
define('WP_POST_REVISIONS', 3);
define('EMPTY_TRASH_DAYS', 30);

// TMU Theme specific
define('TMU_ENVIRONMENT', 'production');
define('TMU_DEBUG', false);
define('TMU_CACHE_ENABLED', true);
define('TMU_TMDB_API_KEY', 'your-tmdb-api-key');
```

## Launch Day Procedures

### Phase 1: Pre-Launch Backup and Preparation (30 minutes)

#### 1. Create Complete Site Backup
```bash
#!/bin/bash
# backup-site.sh - Complete site backup script

# Set variables
BACKUP_DIR="/var/backups/tmu-theme"
DATE=$(date +%Y%m%d_%H%M%S)
SITE_DIR="/var/www/html"
DB_NAME="tmu_production"
DB_USER="tmu_user"
DB_PASS="secure_password"

# Create backup directory
mkdir -p "$BACKUP_DIR/$DATE"

# Backup database
echo "Creating database backup..."
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > "$BACKUP_DIR/$DATE/database.sql"

# Backup files
echo "Creating files backup..."
tar -czf "$BACKUP_DIR/$DATE/files.tar.gz" -C "$SITE_DIR" .

# Backup WordPress uploads
echo "Creating uploads backup..."
tar -czf "$BACKUP_DIR/$DATE/uploads.tar.gz" -C "$SITE_DIR/wp-content" uploads/

# Create backup verification
echo "Verifying backup integrity..."
if [ -f "$BACKUP_DIR/$DATE/database.sql" ] && [ -f "$BACKUP_DIR/$DATE/files.tar.gz" ]; then
    echo "Backup completed successfully: $BACKUP_DIR/$DATE"
    echo "Database size: $(du -h $BACKUP_DIR/$DATE/database.sql | cut -f1)"
    echo "Files size: $(du -h $BACKUP_DIR/$DATE/files.tar.gz | cut -f1)"
else
    echo "Backup failed! Aborting deployment."
    exit 1
fi
```

#### 2. Enable Maintenance Mode
```php
<?php
// Create maintenance.php in WordPress root
?>
<!DOCTYPE html>
<html>
<head>
    <title>Site Maintenance</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #333; }
        p { color: #666; }
    </style>
</head>
<body>
    <h1>Site Under Maintenance</h1>
    <p>We're updating our movie database with exciting new features!</p>
    <p>We'll be back online shortly. Thank you for your patience.</p>
</body>
</html>

<?php
// Add to wp-config.php to enable maintenance mode
define('WP_MAINTENANCE_MODE', true);

// Or create .maintenance file
$maintenance_file = ABSPATH . '.maintenance';
file_put_contents($maintenance_file, '<?php $upgrading = ' . time() . ';');
?>
```

### Phase 2: Theme Deployment (20 minutes)

#### 1. Upload Theme Files
```bash
#!/bin/bash
# deploy-theme.sh - Theme deployment script

THEME_DIR="/var/www/html/wp-content/themes/tmu"
BACKUP_THEME_DIR="/var/backups/tmu-theme/current-theme-$(date +%Y%m%d_%H%M%S)"

# Backup current theme
if [ -d "$THEME_DIR" ]; then
    echo "Backing up current theme..."
    cp -r "$THEME_DIR" "$BACKUP_THEME_DIR"
fi

# Upload new theme files
echo "Uploading new theme files..."
rsync -avz --delete ./tmu-theme/ "$THEME_DIR/"

# Set proper permissions
echo "Setting file permissions..."
find "$THEME_DIR" -type f -exec chmod 644 {} \;
find "$THEME_DIR" -type d -exec chmod 755 {} \;
chown -R www-data:www-data "$THEME_DIR"

# Install Composer dependencies
echo "Installing Composer dependencies..."
cd "$THEME_DIR" && composer install --no-dev --optimize-autoloader

# Build production assets
echo "Building production assets..."
npm ci --production
npm run build:production

echo "Theme deployment completed successfully!"
```

#### 2. Database Migration
```bash
#!/bin/bash
# migrate-database.sh - Database migration script

cd /var/www/html

# Run WordPress CLI migration commands
echo "Running database migrations..."

# Update database structure
wp core update-db --allow-root

# Run TMU theme migrations
wp eval-file wp-content/themes/tmu/migrations/migrate.php --allow-root

# Update permalink structure
wp rewrite structure '/%postname%/' --allow-root
wp rewrite flush --allow-root

# Clear all caches
wp cache flush --allow-root

echo "Database migration completed successfully!"
```

### Phase 3: Theme Activation (10 minutes)

#### 1. Activate Theme
```bash
#!/bin/bash
# activate-theme.sh - Theme activation script

# Activate TMU theme
echo "Activating TMU theme..."
wp theme activate tmu --allow-root

# Import theme settings
echo "Importing theme settings..."
wp option update tmu_movies 'on' --allow-root
wp option update tmu_tv_series 'on' --allow-root
wp option update tmu_dramas 'on' --allow-root
wp option update tmu_people 'on' --allow-root

# Configure TMDB API
echo "Configuring TMDB API..."
wp option update tmu_tmdb_api_key 'your-tmdb-api-key' --allow-root
wp option update tmu_tmdb_cache_duration '3600' --allow-root

# Update rewrite rules
echo "Updating rewrite rules..."
wp rewrite flush --allow-root

echo "Theme activation completed successfully!"
```

#### 2. Content Verification
```bash
#!/bin/bash
# verify-content.sh - Content verification script

echo "Verifying content migration..."

# Check post types
echo "Post type counts:"
wp post list --post_type=movie --format=count --allow-root
wp post list --post_type=tv --format=count --allow-root
wp post list --post_type=drama --format=count --allow-root
wp post list --post_type=people --format=count --allow-root

# Check taxonomies
echo "Taxonomy counts:"
wp term list movie_genre --format=count --allow-root
wp term list tv_genre --format=count --allow-root
wp term list drama_genre --format=count --allow-root

# Verify TMDB data
echo "Verifying TMDB data integrity..."
wp eval "
global \$wpdb;
\$movie_count = \$wpdb->get_var('SELECT COUNT(*) FROM {\$wpdb->prefix}tmu_movies');
\$tv_count = \$wpdb->get_var('SELECT COUNT(*) FROM {\$wpdb->prefix}tmu_tv_series');
echo \"Movies with TMDB data: \$movie_count\n\";
echo \"TV series with TMDB data: \$tv_count\n\";
" --allow-root

echo "Content verification completed!"
```

### Phase 4: Functional Testing (15 minutes)

#### 1. Automated Testing
```bash
#!/bin/bash
# test-deployment.sh - Deployment testing script

echo "Running deployment tests..."

# Test homepage
echo "Testing homepage..."
curl -s -o /dev/null -w "%{http_code}" http://localhost/ | grep -q "200" && echo "✓ Homepage OK" || echo "✗ Homepage failed"

# Test search functionality
echo "Testing search..."
curl -s -o /dev/null -w "%{http_code}" "http://localhost/?s=movie" | grep -q "200" && echo "✓ Search OK" || echo "✗ Search failed"

# Test admin access
echo "Testing admin access..."
curl -s -o /dev/null -w "%{http_code}" http://localhost/wp-admin/ | grep -q "200\|302" && echo "✓ Admin OK" || echo "✗ Admin failed"

# Test TMDB API
echo "Testing TMDB API..."
wp eval "
try {
    \$api = new TMU\API\TMDBClient();
    \$result = \$api->get_movie(550);
    echo \$result ? '✓ TMDB API OK' : '✗ TMDB API failed';
} catch (Exception \$e) {
    echo '✗ TMDB API failed: ' . \$e->getMessage();
}
echo \"\n\";
" --allow-root

echo "Deployment testing completed!"
```

#### 2. Manual Testing Checklist
- [ ] Homepage loads correctly
- [ ] Movie/TV/Drama pages display properly
- [ ] Search functionality works
- [ ] Admin interface accessible
- [ ] TMDB data syncing
- [ ] User registration/login
- [ ] Mobile responsiveness
- [ ] Cross-browser compatibility

### Phase 5: Go Live (5 minutes)

#### 1. Disable Maintenance Mode
```bash
#!/bin/bash
# go-live.sh - Final go-live script

echo "Preparing to go live..."

# Remove maintenance mode
rm -f /var/www/html/.maintenance

# Update wp-config.php to remove maintenance mode
sed -i '/WP_MAINTENANCE_MODE/d' /var/www/html/wp-config.php

# Clear all caches
wp cache flush --allow-root

# Warm up cache
echo "Warming up cache..."
curl -s http://localhost/ > /dev/null
curl -s http://localhost/movies/ > /dev/null
curl -s http://localhost/tv-series/ > /dev/null

# Verify site is live
echo "Verifying site is live..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/)
if [ "$HTTP_CODE" = "200" ]; then
    echo "🎉 Site is now LIVE!"
    echo "Deployment completed at: $(date)"
else
    echo "❌ Site verification failed (HTTP $HTTP_CODE)"
    exit 1
fi
```

## Post-Deployment Validation

### 1. Immediate Validation (First 30 minutes)

#### Performance Testing
```bash
#!/bin/bash
# performance-test.sh - Post-deployment performance validation

echo "Running performance tests..."

# Test page load times
echo "Testing page load times..."
for url in "/" "/movies/" "/tv-series/" "/search/?s=action"; do
    time=$(curl -w "%{time_total}" -s -o /dev/null "http://localhost$url")
    echo "URL: $url - Load time: ${time}s"
    
    # Check if load time is under 3 seconds
    if (( $(echo "$time < 3.0" | bc -l) )); then
        echo "✓ Performance OK"
    else
        echo "⚠ Performance warning: Load time exceeds 3 seconds"
    fi
done

# Test database query count
echo "Testing database performance..."
wp eval "
\$start_queries = get_num_queries();
get_header();
get_footer();
\$end_queries = get_num_queries();
\$query_count = \$end_queries - \$start_queries;
echo \"Database queries: \$query_count\n\";
if (\$query_count < 50) {
    echo \"✓ Database performance OK\n\";
} else {
    echo \"⚠ Database performance warning: Too many queries\n\";
}
" --allow-root

echo "Performance testing completed!"
```

#### Error Monitoring
```bash
#!/bin/bash
# monitor-errors.sh - Error monitoring script

echo "Starting error monitoring..."

# Monitor PHP error log
tail -f /var/log/apache2/error.log | grep -i "tmu\|fatal\|error" &

# Monitor WordPress debug log
tail -f /var/www/html/wp-content/debug.log | grep -i "tmu\|fatal\|error" &

# Monitor database errors
mysqladmin processlist | grep -i "error" &

echo "Error monitoring started. Press Ctrl+C to stop."
```

### 2. Extended Validation (First 24 hours)

#### Analytics Verification
```bash
#!/bin/bash
# analytics-verification.sh - Analytics and tracking verification

echo "Verifying analytics setup..."

# Check Google Analytics
echo "Checking Google Analytics integration..."
curl -s http://localhost/ | grep -q "gtag\|analytics" && echo "✓ Google Analytics found" || echo "⚠ Google Analytics not detected"

# Check search console
echo "Checking Search Console setup..."
curl -s http://localhost/ | grep -q "google-site-verification" && echo "✓ Search Console verified" || echo "⚠ Search Console verification not found"

# Test internal analytics
echo "Testing internal analytics..."
wp eval "
\$analytics = new TMU\Analytics\AnalyticsManager();
\$test_event = \$analytics->track_event('page_view', 1, ['page' => 'deployment_test']);
echo \$test_event ? '✓ Internal analytics OK' : '⚠ Internal analytics failed';
echo \"\n\";
" --allow-root

echo "Analytics verification completed!"
```

## Monitoring and Maintenance

### 1. Automated Monitoring Setup

#### Health Check Script
```bash
#!/bin/bash
# health-check.sh - Automated health monitoring

LOG_FILE="/var/log/tmu-theme/health-check.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

echo "[$DATE] Starting health check..." >> $LOG_FILE

# Check website availability
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/)
if [ "$HTTP_CODE" = "200" ]; then
    echo "[$DATE] ✓ Website available (HTTP $HTTP_CODE)" >> $LOG_FILE
else
    echo "[$DATE] ❌ Website unavailable (HTTP $HTTP_CODE)" >> $LOG_FILE
    # Send alert email
    echo "Website down - HTTP $HTTP_CODE" | mail -s "TMU Site Alert" admin@example.com
fi

# Check database connectivity
DB_CHECK=$(wp db check --allow-root 2>&1)
if [[ $DB_CHECK == *"Success"* ]]; then
    echo "[$DATE] ✓ Database connection OK" >> $LOG_FILE
else
    echo "[$DATE] ❌ Database connection failed" >> $LOG_FILE
    echo "Database connection failed" | mail -s "TMU DB Alert" admin@example.com
fi

# Check TMDB API
API_CHECK=$(wp eval "
try {
    \$api = new TMU\API\TMDBClient();
    \$result = \$api->get_movie(550);
    echo \$result ? 'OK' : 'FAILED';
} catch (Exception \$e) {
    echo 'FAILED';
}
" --allow-root)

if [ "$API_CHECK" = "OK" ]; then
    echo "[$DATE] ✓ TMDB API connection OK" >> $LOG_FILE
else
    echo "[$DATE] ❌ TMDB API connection failed" >> $LOG_FILE
fi

# Check disk space
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -lt 90 ]; then
    echo "[$DATE] ✓ Disk usage OK ($DISK_USAGE%)" >> $LOG_FILE
else
    echo "[$DATE] ⚠ High disk usage ($DISK_USAGE%)" >> $LOG_FILE
    echo "High disk usage: $DISK_USAGE%" | mail -s "TMU Disk Alert" admin@example.com
fi

echo "[$DATE] Health check completed" >> $LOG_FILE
```

#### Cron Job Setup
```bash
# Add to crontab (crontab -e)

# Health check every 5 minutes
*/5 * * * * /var/scripts/health-check.sh

# Daily backup at 2 AM
0 2 * * * /var/scripts/backup-site.sh

# Weekly performance report on Sundays at 3 AM
0 3 * * 0 /var/scripts/performance-report.sh

# Monthly security audit on 1st of month at 4 AM
0 4 1 * * /var/scripts/security-audit.sh
```

### 2. Performance Monitoring

#### Real-time Performance Dashboard
```php
<?php
// wp-content/themes/tmu/admin/performance-dashboard.php
// Performance monitoring dashboard

class TMU_Performance_Dashboard {
    public function display_dashboard() {
        global $wpdb;
        
        // Get performance metrics
        $metrics = $this->get_performance_metrics();
        
        echo '<div class="wrap">';
        echo '<h1>TMU Performance Dashboard</h1>';
        
        // Current status
        echo '<div class="tmu-metrics-grid">';
        echo '<div class="metric-card">';
        echo '<h3>Response Time</h3>';
        echo '<div class="metric-value">' . $metrics['avg_response_time'] . 's</div>';
        echo '</div>';
        
        echo '<div class="metric-card">';
        echo '<h3>Memory Usage</h3>';
        echo '<div class="metric-value">' . $metrics['avg_memory_usage'] . 'MB</div>';
        echo '</div>';
        
        echo '<div class="metric-card">';
        echo '<h3>Database Queries</h3>';
        echo '<div class="metric-value">' . $metrics['avg_queries'] . '</div>';
        echo '</div>';
        
        echo '<div class="metric-card">';
        echo '<h3>Error Rate</h3>';
        echo '<div class="metric-value">' . $metrics['error_rate'] . '%</div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
    }
    
    private function get_performance_metrics() {
        global $wpdb;
        
        return $wpdb->get_row("
            SELECT 
                AVG(response_time) as avg_response_time,
                AVG(memory_usage) as avg_memory_usage,
                AVG(query_count) as avg_queries,
                (COUNT(CASE WHEN status = 'error' THEN 1 END) / COUNT(*) * 100) as error_rate
            FROM {$wpdb->prefix}tmu_performance_logs 
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ", ARRAY_A);
    }
}
```

## Rollback Procedures

### 1. Emergency Rollback Script
```bash
#!/bin/bash
# rollback.sh - Emergency rollback script

echo "🚨 EMERGENCY ROLLBACK INITIATED"
echo "Timestamp: $(date)"

# Get latest backup
BACKUP_DIR="/var/backups/tmu-theme"
LATEST_BACKUP=$(ls -t $BACKUP_DIR | head -1)

if [ -z "$LATEST_BACKUP" ]; then
    echo "❌ No backup found! Manual intervention required."
    exit 1
fi

echo "Using backup: $LATEST_BACKUP"

# Enable maintenance mode
echo "Enabling maintenance mode..."
echo '<?php $upgrading = ' $(date +%s) ';' > /var/www/html/.maintenance

# Restore database
echo "Restoring database..."
mysql -u tmu_user -p$DB_PASS tmu_production < "$BACKUP_DIR/$LATEST_BACKUP/database.sql"

# Restore files
echo "Restoring files..."
cd /var/www/html
tar -xzf "$BACKUP_DIR/$LATEST_BACKUP/files.tar.gz"

# Clear caches
echo "Clearing caches..."
wp cache flush --allow-root

# Disable maintenance mode
echo "Disabling maintenance mode..."
rm -f /var/www/html/.maintenance

# Verify rollback
echo "Verifying rollback..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/)
if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ Rollback completed successfully!"
else
    echo "❌ Rollback verification failed (HTTP $HTTP_CODE)"
fi

echo "Rollback completed at: $(date)"
```

### 2. Selective Rollback Options

#### Theme-only Rollback
```bash
#!/bin/bash
# rollback-theme-only.sh - Rollback only theme files

THEME_BACKUP_DIR="/var/backups/tmu-theme"
LATEST_THEME_BACKUP=$(ls -t $THEME_BACKUP_DIR/current-theme-* | head -1)

echo "Rolling back theme to: $LATEST_THEME_BACKUP"

# Restore theme files
rm -rf /var/www/html/wp-content/themes/tmu
cp -r "$LATEST_THEME_BACKUP" /var/www/html/wp-content/themes/tmu

# Set permissions
chown -R www-data:www-data /var/www/html/wp-content/themes/tmu

echo "Theme rollback completed!"
```

#### Database-only Rollback
```bash
#!/bin/bash
# rollback-database-only.sh - Rollback only database

BACKUP_DIR="/var/backups/tmu-theme"
LATEST_BACKUP=$(ls -t $BACKUP_DIR | head -1)

echo "Rolling back database to: $LATEST_BACKUP"

# Restore database
mysql -u tmu_user -p$DB_PASS tmu_production < "$BACKUP_DIR/$LATEST_BACKUP/database.sql"

# Clear WordPress caches
wp cache flush --allow-root

echo "Database rollback completed!"
```

## Troubleshooting

### Common Issues and Solutions

#### 1. Site Not Loading (HTTP 500 Error)
```bash
# Check error logs
tail -50 /var/log/apache2/error.log

# Check WordPress debug log
tail -50 /var/www/html/wp-content/debug.log

# Verify file permissions
find /var/www/html -type f -not -perm 644
find /var/www/html -type d -not -perm 755

# Test with default theme
wp theme activate twentytwentythree --allow-root
```

#### 2. Database Connection Issues
```bash
# Test database connection
mysql -u tmu_user -p$DB_PASS -e "USE tmu_production; SELECT 1;"

# Check WordPress database configuration
wp config get DB_NAME --allow-root
wp config get DB_USER --allow-root
wp config get DB_HOST --allow-root

# Repair database if needed
wp db repair --allow-root
```

#### 3. TMDB API Issues
```bash
# Test API connectivity
curl -s "https://api.themoviedb.org/3/movie/550?api_key=YOUR_API_KEY"

# Check API key configuration
wp option get tmu_tmdb_api_key --allow-root

# Clear TMDB cache
wp eval "delete_transient('tmu_tmdb_cache');" --allow-root
```

#### 4. Performance Issues
```bash
# Enable WordPress debugging
wp config set WP_DEBUG true --allow-root
wp config set WP_DEBUG_LOG true --allow-root

# Check slow queries
mysql -u tmu_user -p$DB_PASS -e "
SELECT query_time, sql_text 
FROM mysql.slow_log 
WHERE sql_text LIKE '%tmu_%' 
ORDER BY query_time DESC 
LIMIT 10;"

# Clear all caches
wp cache flush --allow-root
wp rewrite flush --allow-root
```

### Emergency Contacts

- **Technical Lead:** [name@example.com]
- **Server Administrator:** [admin@example.com]
- **24/7 Emergency:** [+1-XXX-XXX-XXXX]
- **Hosting Provider Support:** [support@hosting.com]

---

## Deployment Checklist Summary

### Pre-Deployment ✅
- [ ] All tests passing
- [ ] Backups created
- [ ] Server configured
- [ ] Team notified

### Deployment ✅
- [ ] Maintenance mode enabled
- [ ] Files uploaded
- [ ] Database migrated
- [ ] Theme activated

### Post-Deployment ✅
- [ ] Functionality tested
- [ ] Performance verified
- [ ] Monitoring active
- [ ] Team notified

**Deployment Status:** Ready for Production

**Estimated Deployment Time:** 90 minutes

**Rollback Time:** 15 minutes

---

*This deployment guide ensures a smooth, monitored, and reversible deployment process for the TMU theme.*