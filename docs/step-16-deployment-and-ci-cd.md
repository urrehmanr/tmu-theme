# Step 16: Deployment and CI/CD

## Purpose
Implement deployment strategies and CI/CD pipeline for automated testing, building, and deployment of the TMU theme.

## Dependencies from Previous Steps
- **[REQUIRED]** Complete theme system [FROM STEPS 1-15] - Deployment target
- **[REQUIRED]** Testing framework [FROM STEP 15] - CI/CD pipeline integration
- **[REQUIRED]** Build system [FROM STEP 1] - Asset compilation for deployment

## Files Created in This Step
- **[CREATE NEW]** `.github/workflows/deploy.yml` - Deployment workflow
- **[CREATE NEW]** `deploy/scripts/` - Deployment scripts directory
- **[CREATE NEW]** `docker/` - Docker configuration for development
- **[CREATE NEW]** `deploy.sh` - Main deployment script
- **[CREATE NEW]** `.env.example` - Environment configuration template

## Tailwind CSS Status
**BUILDS** - Production builds include Tailwind CSS optimization and purging

**Step 16 Status**: ✅ READY FOR AI IMPLEMENTATION
**Dependencies**: Steps 1-15 must be completed
**Next Step**: Step 17 - Monitoring and Analytics Pipeline

## Overview
This step establishes a comprehensive deployment strategy and CI/CD pipeline for the TMU theme, ensuring reliable, automated deployments with proper testing, staging, and production environments.

## 1. Development Workflow

### 1.1 Git Workflow Strategy
```bash
# Branch structure
main              # Production-ready code
├── develop       # Development integration branch
├── feature/*     # Feature development branches
├── hotfix/*      # Critical bug fixes
└── release/*     # Release preparation branches

# Example workflow
git checkout develop
git checkout -b feature/tmdb-integration-enhancement
# Development work
git commit -m "feat: enhance TMDB API integration with caching"
git push origin feature/tmdb-integration-enhancement
# Create pull request to develop
```

### 1.2 Semantic Versioning
```json
{
  "version": "1.0.0",
  "versioning": {
    "major": "Breaking changes",
    "minor": "New features, backward compatible",
    "patch": "Bug fixes, backward compatible"
  },
  "release_notes": {
    "1.0.0": "Initial release with complete plugin-to-theme migration",
    "1.1.0": "Added Gutenberg block system",
    "1.1.1": "Fixed performance issues with large datasets"
  }
}
```

## 2. CI/CD Pipeline Configuration

### 2.1 GitHub Actions Workflow
```yaml
# .github/workflows/ci-cd.yml
name: TMU Theme CI/CD

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]
  release:
    types: [ published ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: wordpress_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: mbstring, intl, gd, xml, zip, mysql
        tools: composer, phpunit
    
    - name: Setup Node.js
      uses: actions/setup-node@v3
      with:
        node-version: '18'
        cache: 'npm'
    
    - name: Install PHP dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Install Node dependencies
      run: npm ci
    
    - name: Run PHP Code Sniffer
      run: ./vendor/bin/phpcs
    
    - name: Run PHPStan
      run: ./vendor/bin/phpstan analyse
    
    - name: Build assets
      run: npm run build
    
    - name: Setup WordPress test environment
      run: |
        bash bin/install-wp-tests.sh wordpress_test root password 127.0.0.1 latest
    
    - name: Run PHPUnit tests
      run: ./vendor/bin/phpunit
    
    - name: Run JavaScript tests
      run: npm test
    
    - name: Run accessibility tests
      run: npm run test:a11y
    
    - name: Generate coverage report
      run: ./vendor/bin/phpunit --coverage-clover=coverage.xml
    
    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v3
      with:
        file: ./coverage.xml

  security-scan:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v3
    
    - name: Run security scan
      uses: securecodewarrior/github-action-add-sarif@v1
      with:
        sarif-file: security-scan-results.sarif
    
    - name: PHP Security Checker
      run: |
        curl -L https://github.com/fabpot/local-php-security-checker/releases/download/v1.0.0/local-php-security-checker_1.0.0_linux_amd64 -o local-php-security-checker
        chmod +x local-php-security-checker
        ./local-php-security-checker

  deploy-staging:
    needs: [test, security-scan]
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/develop'
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup Node.js
      uses: actions/setup-node@v3
      with:
        node-version: '18'
        cache: 'npm'
    
    - name: Install dependencies and build
      run: |
        npm ci
        npm run build:staging
    
    - name: Deploy to staging
      uses: easingthemes/ssh-deploy@main
      env:
        SSH_PRIVATE_KEY: ${{ secrets.STAGING_SSH_KEY }}
        ARGS: "-rlgoDzvc -i --delete"
        SOURCE: "./"
        REMOTE_HOST: ${{ secrets.STAGING_HOST }}
        REMOTE_USER: ${{ secrets.STAGING_USER }}
        TARGET: ${{ secrets.STAGING_PATH }}
        EXCLUDE: "/node_modules/, /.git/, /.github/, /tests/, /docs/"
    
    - name: Run staging tests
      run: |
        curl -f ${{ secrets.STAGING_URL }}/wp-json/tmu/v1/health || exit 1

  deploy-production:
    needs: [test, security-scan]
    runs-on: ubuntu-latest
    if: github.event_name == 'release'
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup Node.js
      uses: actions/setup-node@v3
      with:
        node-version: '18'
        cache: 'npm'
    
    - name: Install dependencies and build
      run: |
        npm ci
        npm run build:production
    
    - name: Create deployment package
      run: |
        zip -r tmu-theme-${{ github.event.release.tag_name }}.zip . \
          -x "node_modules/*" ".git/*" ".github/*" "tests/*" "docs/*" \
          "*.md" "package*.json" "webpack.config.js" "composer.json" "composer.lock"
    
    - name: Upload release asset
      uses: actions/upload-release-asset@v1
      env:
        GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
      with:
        upload_url: ${{ github.event.release.upload_url }}
        asset_path: ./tmu-theme-${{ github.event.release.tag_name }}.zip
        asset_name: tmu-theme-${{ github.event.release.tag_name }}.zip
        asset_content_type: application/zip
    
    - name: Deploy to production
      uses: easingthemes/ssh-deploy@main
      env:
        SSH_PRIVATE_KEY: ${{ secrets.PRODUCTION_SSH_KEY }}
        ARGS: "-rlgoDzvc -i --delete"
        SOURCE: "./"
        REMOTE_HOST: ${{ secrets.PRODUCTION_HOST }}
        REMOTE_USER: ${{ secrets.PRODUCTION_USER }}
        TARGET: ${{ secrets.PRODUCTION_PATH }}
        EXCLUDE: "/node_modules/, /.git/, /.github/, /tests/, /docs/"
    
    - name: Verify production deployment
      run: |
        curl -f ${{ secrets.PRODUCTION_URL }}/wp-json/tmu/v1/health || exit 1
    
    - name: Notify deployment success
      uses: 8398a7/action-slack@v3
      with:
        status: success
        text: "TMU Theme ${{ github.event.release.tag_name }} deployed successfully to production!"
      env:
        SLACK_WEBHOOK_URL: ${{ secrets.SLACK_WEBHOOK }}
```

### 2.2 Build Scripts
```json
{
  "scripts": {
    "dev": "webpack --mode=development --watch",
    "build": "webpack --mode=production",
    "build:staging": "NODE_ENV=staging webpack --mode=production",
    "build:production": "NODE_ENV=production webpack --mode=production",
    "test": "jest",
    "test:watch": "jest --watch",
    "test:coverage": "jest --coverage",
    "test:a11y": "pa11y-ci --sitemap http://localhost:8080/sitemap.xml",
    "lint": "eslint assets/src --ext .js,.jsx",
    "lint:fix": "eslint assets/src --ext .js,.jsx --fix",
    "lint:php": "phpcs --standard=WordPress src/",
    "lint:php:fix": "phpcbf --standard=WordPress src/",
    "analyze": "phpstan analyse src/",
    "security:check": "npm audit && ./local-php-security-checker",
    "deploy:staging": "npm run build:staging && rsync -avz --delete ./ user@staging-server:/path/to/theme/",
    "deploy:production": "npm run build:production && rsync -avz --delete ./ user@production-server:/path/to/theme/"
  }
}
```

## 3. Environment Configuration

### 3.1 Environment Variables
```php
// config/environments.php
<?php
return [
    'development' => [
        'debug' => true,
        'cache_enabled' => false,
        'tmdb_api_key' => getenv('TMDB_API_KEY_DEV'),
        'database_host' => 'localhost',
        'database_name' => 'tmu_dev',
        'cdn_url' => '',
        'redis_enabled' => false,
    ],
    'staging' => [
        'debug' => false,
        'cache_enabled' => true,
        'tmdb_api_key' => getenv('TMDB_API_KEY_STAGING'),
        'database_host' => getenv('DB_HOST_STAGING'),
        'database_name' => getenv('DB_NAME_STAGING'),
        'cdn_url' => getenv('CDN_URL_STAGING'),
        'redis_enabled' => true,
    ],
    'production' => [
        'debug' => false,
        'cache_enabled' => true,
        'tmdb_api_key' => getenv('TMDB_API_KEY_PROD'),
        'database_host' => getenv('DB_HOST_PROD'),
        'database_name' => getenv('DB_NAME_PROD'),
        'cdn_url' => getenv('CDN_URL_PROD'),
        'redis_enabled' => true,
    ]
];
```

### 3.2 Docker Configuration
```dockerfile
# Dockerfile
FROM php:8.1-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Set working directory
WORKDIR /var/www/html

# Copy theme files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader
RUN npm ci && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 80
```

```yaml
# docker-compose.yml
version: '3.8'

services:
  wordpress:
    build: .
    ports:
      - "8080:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_NAME: wordpress
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress
      WORDPRESS_DEBUG: 1
    volumes:
      - ./:/var/www/html/wp-content/themes/tmu
    depends_on:
      - db
      - redis

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress
      MYSQL_ROOT_PASSWORD: rootpassword
    volumes:
      - db_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    ports:
      - "8081:80"
    environment:
      PMA_HOST: db
      PMA_USER: root
      PMA_PASSWORD: rootpassword

volumes:
  db_data:
```

## 4. Monitoring and Health Checks

### 4.1 Health Check Endpoint
```php
// src/Health/HealthCheck.php
<?php
namespace TMU\Health;

class HealthCheck {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_health_endpoints']);
    }
    
    public function register_health_endpoints(): void {
        register_rest_route('tmu/v1', '/health', [
            'methods' => 'GET',
            'callback' => [$this, 'health_check'],
            'permission_callback' => '__return_true'
        ]);
        
        register_rest_route('tmu/v1', '/health/detailed', [
            'methods' => 'GET',
            'callback' => [$this, 'detailed_health_check'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
    }
    
    public function health_check(): array {
        $status = 'healthy';
        $checks = [
            'database' => $this->check_database(),
            'cache' => $this->check_cache(),
            'tmdb_api' => $this->check_tmdb_api(),
        ];
        
        foreach ($checks as $check) {
            if (!$check['status']) {
                $status = 'unhealthy';
                break;
            }
        }
        
        return [
            'status' => $status,
            'timestamp' => current_time('c'),
            'version' => wp_get_theme()->get('Version'),
            'checks' => $checks
        ];
    }
    
    public function detailed_health_check(): array {
        return [
            'status' => 'healthy',
            'timestamp' => current_time('c'),
            'version' => wp_get_theme()->get('Version'),
            'environment' => wp_get_environment_type(),
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => ini_get('memory_limit')
            ],
            'database' => $this->detailed_database_check(),
            'cache' => $this->detailed_cache_check(),
            'tmdb_api' => $this->detailed_tmdb_check(),
            'disk_space' => $this->check_disk_space(),
            'performance' => $this->check_performance_metrics()
        ];
    }
    
    private function check_database(): array {
        global $wpdb;
        
        try {
            $result = $wpdb->get_var("SELECT 1");
            return [
                'status' => $result === '1',
                'message' => $result === '1' ? 'Database connection OK' : 'Database connection failed'
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    private function check_cache(): array {
        $test_key = 'tmu_health_check_' . time();
        $test_value = 'test_value';
        
        wp_cache_set($test_key, $test_value, 'tmu_health', 60);
        $cached_value = wp_cache_get($test_key, 'tmu_health');
        
        return [
            'status' => $cached_value === $test_value,
            'message' => $cached_value === $test_value ? 'Cache working' : 'Cache not working'
        ];
    }
    
    private function check_tmdb_api(): array {
        $tmdb_key = get_option('tmu_tmdb_api_key');
        
        if (!$tmdb_key) {
            return [
                'status' => false,
                'message' => 'TMDB API key not configured'
            ];
        }
        
        $response = wp_remote_get("https://api.themoviedb.org/3/configuration?api_key={$tmdb_key}");
        
        if (is_wp_error($response)) {
            return [
                'status' => false,
                'message' => 'TMDB API error: ' . $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        return [
            'status' => $status_code === 200,
            'message' => $status_code === 200 ? 'TMDB API accessible' : "TMDB API returned status {$status_code}"
        ];
    }
}
```

### 4.2 Performance Monitoring
```php
// src/Monitoring/PerformanceMonitor.php
<?php
namespace TMU\Monitoring;

class PerformanceMonitor {
    private $metrics = [];
    
    public function __construct() {
        add_action('init', [$this, 'start_monitoring']);
        add_action('wp_footer', [$this, 'log_metrics']);
    }
    
    public function start_monitoring(): void {
        $this->metrics['start_time'] = microtime(true);
        $this->metrics['start_memory'] = memory_get_usage();
        
        // Hook into various WordPress actions to track performance
        add_action('wp_head', [$this, 'track_head_performance']);
        add_action('wp_footer', [$this, 'track_footer_performance']);
    }
    
    public function track_head_performance(): void {
        $this->metrics['head_time'] = microtime(true) - $this->metrics['start_time'];
        $this->metrics['head_memory'] = memory_get_usage() - $this->metrics['start_memory'];
    }
    
    public function track_footer_performance(): void {
        $this->metrics['total_time'] = microtime(true) - $this->metrics['start_time'];
        $this->metrics['total_memory'] = memory_get_usage() - $this->metrics['start_memory'];
        $this->metrics['peak_memory'] = memory_get_peak_usage();
        $this->metrics['database_queries'] = get_num_queries();
    }
    
    public function log_metrics(): void {
        if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
            error_log('TMU Performance Metrics: ' . json_encode($this->metrics));
        }
        
        // Send to external monitoring service
        $this->send_to_monitoring_service();
    }
    
    private function send_to_monitoring_service(): void {
        $monitoring_url = get_option('tmu_monitoring_webhook');
        
        if ($monitoring_url && $this->metrics['total_time'] > 2) { // Only log slow requests
            wp_remote_post($monitoring_url, [
                'body' => json_encode([
                    'site' => get_site_url(),
                    'timestamp' => current_time('c'),
                    'metrics' => $this->metrics,
                    'url' => $_SERVER['REQUEST_URI'] ?? '',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]),
                'headers' => ['Content-Type' => 'application/json']
            ]);
        }
    }
}
```

## 5. Deployment Scripts

### 5.1 Automated Deployment Script
```bash
#!/bin/bash
# deploy.sh

set -e

ENVIRONMENT=${1:-staging}
VERSION=${2:-latest}

echo "Deploying TMU Theme to $ENVIRONMENT environment..."

# Configuration
case $ENVIRONMENT in
    staging)
        SERVER="staging.example.com"
        PATH="/var/www/staging/wp-content/themes/tmu"
        ;;
    production)
        SERVER="production.example.com"
        PATH="/var/www/production/wp-content/themes/tmu"
        ;;
    *)
        echo "Invalid environment: $ENVIRONMENT"
        exit 1
        ;;
esac

# Pre-deployment checks
echo "Running pre-deployment checks..."
npm run lint
npm run test
composer install --no-dev --optimize-autoloader

# Build assets
echo "Building assets for $ENVIRONMENT..."
npm run build:$ENVIRONMENT

# Create backup
echo "Creating backup..."
ssh user@$SERVER "cp -r $PATH ${PATH}_backup_$(date +%Y%m%d_%H%M%S)"

# Deploy files
echo "Deploying files..."
rsync -avz --delete \
    --exclude 'node_modules' \
    --exclude '.git' \
    --exclude '.github' \
    --exclude 'tests' \
    --exclude 'docs' \
    --exclude '*.md' \
    --exclude 'package*.json' \
    --exclude 'webpack.config.js' \
    ./ user@$SERVER:$PATH/

# Run post-deployment tasks
echo "Running post-deployment tasks..."
ssh user@$SERVER "cd $PATH && php wp-cli.phar theme activate tmu"
ssh user@$SERVER "cd $PATH && php wp-cli.phar cache flush"

# Health check
echo "Performing health check..."
HEALTH_URL="https://$SERVER/wp-json/tmu/v1/health"
HEALTH_STATUS=$(curl -s -o /dev/null -w "%{http_code}" $HEALTH_URL)

if [ $HEALTH_STATUS -eq 200 ]; then
    echo "✅ Deployment successful! Health check passed."
else
    echo "❌ Deployment failed! Health check returned status $HEALTH_STATUS"
    echo "Rolling back..."
    ssh user@$SERVER "rm -rf $PATH && mv ${PATH}_backup_* $PATH"
    exit 1
fi

echo "🎉 Deployment completed successfully!"
```

### 5.2 Database Migration Script
```bash
#!/bin/bash
# migrate.sh

set -e

ENVIRONMENT=${1:-staging}
BACKUP_DIR="/var/backups/tmu-theme"

echo "Running database migrations for $ENVIRONMENT environment..."

# Create backup directory
mkdir -p $BACKUP_DIR

# Create database backup
echo "Creating database backup..."
wp db export "$BACKUP_DIR/backup_$(date +%Y%m%d_%H%M%S).sql"

# Run migrations
echo "Running database migrations..."
wp eval-file migrations/migrate.php

# Verify migrations
echo "Verifying migrations..."
wp eval-file migrations/verify.php

echo "✅ Database migrations completed successfully!"
```

## 6. Rollback Strategy

### 6.1 Automated Rollback
```bash
#!/bin/bash
# rollback.sh

set -e

ENVIRONMENT=${1:-staging}
BACKUP_VERSION=${2}

echo "Rolling back TMU Theme in $ENVIRONMENT environment..."

case $ENVIRONMENT in
    staging)
        SERVER="staging.example.com"
        PATH="/var/www/staging/wp-content/themes/tmu"
        ;;
    production)
        SERVER="production.example.com"
        PATH="/var/www/production/wp-content/themes/tmu"
        ;;
    *)
        echo "Invalid environment: $ENVIRONMENT"
        exit 1
        ;;
esac

if [ -z "$BACKUP_VERSION" ]; then
    # Use latest backup
    BACKUP_VERSION=$(ssh user@$SERVER "ls -t ${PATH}_backup_* | head -1")
fi

echo "Rolling back to: $BACKUP_VERSION"

# Rollback files
ssh user@$SERVER "rm -rf $PATH && mv $BACKUP_VERSION $PATH"

# Rollback database if needed
if [ -f "database_backup.sql" ]; then
    echo "Rolling back database..."
    ssh user@$SERVER "cd $PATH && wp db import database_backup.sql"
fi

# Clear cache
ssh user@$SERVER "cd $PATH && wp cache flush"

echo "✅ Rollback completed successfully!"
```

## Success Metrics

- **Deployment Success Rate**: > 95%
- **Deployment Time**: < 5 minutes
- **Rollback Time**: < 2 minutes
- **Zero-Downtime Deployments**: 100%
- **Automated Testing Coverage**: > 90%
- **Security Scan Pass Rate**: 100%
- **Health Check Success Rate**: > 99%
- **Performance Regression Detection**: Automated alerts for >10% performance degradation

This comprehensive deployment and CI/CD pipeline ensures reliable, automated, and secure deployments of the TMU theme across all environments.