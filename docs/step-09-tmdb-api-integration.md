# Step 09: TMDB API Integration - Complete Implementation

## Purpose
Implement comprehensive TMDB API integration with enhanced sync capabilities, caching, error handling, and automated data updates. The system preserves all existing functionality while adding modern API management features.

## Dependencies from Previous Steps
- **[REQUIRED]** Post types registration [FROM STEP 5] - API data maps to post types
- **[REQUIRED]** Taxonomies registration [FROM STEP 6] - Genre, country, language mapping
- **[REQUIRED]** Database migration [FROM STEP 3] - Custom tables for TMDB data
- **[REQUIRED]** Admin interface [FROM STEP 8] - TMDB sync meta boxes
- **[REQUIRED]** PSR-4 autoloading [FROM STEP 4] - API class loading
- **[REQUIRED]** Helper functions [FROM STEP 4] - Utility functions

## Files Created in This Step
- **[CREATE NEW]** `includes/classes/API/TMDB/Client.php` - Main TMDB API client
- **[CREATE NEW]** `includes/classes/API/TMDB/SyncService.php` - Sync service manager
- **[CREATE NEW]** `includes/classes/API/TMDB/DataMapper.php` - Data mapping system
- **[CREATE NEW]** `includes/classes/API/TMDB/ImageSyncService.php` - Image synchronization
- **[CREATE NEW]** `includes/classes/API/TMDB/SyncScheduler.php` - Automated sync scheduler
- **[CREATE NEW]** `includes/classes/API/TMDB/Exception.php` - TMDB exception handling
- **[CREATE NEW]** `includes/classes/API/TMDB/Cache.php` - API response caching
- **[CREATE NEW]** `includes/classes/API/TMDB/RateLimiter.php` - API rate limiting
- **[CREATE NEW]** `includes/classes/API/TMDB/SearchService.php` - TMDB search functionality
- **[CREATE NEW]** `includes/classes/API/TMDB/WebhookHandler.php` - TMDB webhook processing
- **[CREATE NEW]** `includes/classes/Admin/Settings/TMDBSettings.php` - TMDB settings page
- **[CREATE NEW]** `assets/src/js/tmdb-sync.js` - TMDB sync JavaScript
- **[CREATE NEW]** `tests/API/TMDBTest.php` - TMDB API testing

## Tailwind CSS Status
**USES** - TMDB sync interfaces use Tailwind utility classes for styling

## Architecture Implementation

### Directory Structure with File Status
```
includes/classes/API/TMDB/                       # [CREATE DIR - STEP 9] TMDB API integration
├── Client.php            # [CREATE NEW - STEP 9] Main TMDB API client - Core API communication
├── SyncService.php       # [CREATE NEW - STEP 9] Sync service manager - Content synchronization
├── DataMapper.php        # [CREATE NEW - STEP 9] Data mapping system - API to database mapping
├── ImageSyncService.php  # [CREATE NEW - STEP 9] Image synchronization - Media management
├── SyncScheduler.php     # [CREATE NEW - STEP 9] Automated sync scheduler - Background jobs
├── Exception.php         # [CREATE NEW - STEP 9] TMDB exception handling - Error management
├── Cache.php             # [CREATE NEW - STEP 9] API response caching - Performance optimization
├── RateLimiter.php       # [CREATE NEW - STEP 9] API rate limiting - Request management
├── SearchService.php     # [CREATE NEW - STEP 9] TMDB search functionality - Content discovery
└── WebhookHandler.php    # [CREATE NEW - STEP 9] TMDB webhook processing - Real-time updates

includes/classes/Admin/Settings/                 # [UPDATE DIR - STEP 9] Extend admin settings
└── TMDBSettings.php      # [CREATE NEW - STEP 9] TMDB settings page - Configuration interface

assets/src/js/                                  # [UPDATE DIR - STEP 9] Extend JavaScript directory
└── tmdb-sync.js          # [CREATE NEW - STEP 9] TMDB sync JavaScript - Frontend interactions

tests/API/                                      # [CREATE DIR - STEP 9] API testing
└── TMDBTest.php          # [CREATE NEW - STEP 9] TMDB API testing - Comprehensive API tests
```

### **Dependencies from Previous Steps:**
- **[REQUIRED]** Post types [FROM STEP 5] - movie, tv, drama, people
- **[REQUIRED]** Database tables [FROM STEP 3] - tmu_movies, tmu_tv_series, tmu_dramas, tmu_people
- **[REQUIRED]** Admin interface [FROM STEP 8] - TMDB sync meta boxes
- **[REQUIRED]** Asset compilation [FROM STEP 1] - JavaScript compilation
- **[REQUIRED]** Helper functions [FROM STEP 4] - Utility functions

### **Files Created in Future Steps:**
- **`includes/classes/API/REST/TMDBEndpoints.php`** - [CREATE NEW - STEP 9] REST API endpoints
- **`templates/tmdb/`** - [CREATE NEW - STEP 10] TMDB template files
- **`includes/classes/Cron/TMDBJobs.php`** - [CREATE NEW - STEP 9] Background sync jobs

## 1. TMDB API Service Architecture

### 1.1 Core API Client (`includes/classes/API/TMDB/Client.php`)
**File Status**: [CREATE NEW - STEP 9]
**File Path**: `tmu-theme/includes/classes/API/TMDB/Client.php`
**Purpose**: Main TMDB API client providing core API communication functionality
**Dependencies**: 
- [DEPENDS ON] WordPress HTTP API - wp_remote_get, wp_remote_post
- [DEPENDS ON] WordPress transients - get_transient, set_transient
- [DEPENDS ON] TMDB API credentials - API key from settings
- [DEPENDS ON] TMDBException class [CREATE NEW - STEP 9] - Error handling
**Integration**: Central API client for all TMDB operations
**Used By**: 
- Sync services [CREATE NEW - STEP 9] - Data synchronization
- Search service [CREATE NEW - STEP 9] - Content discovery
- Admin meta boxes [FROM STEP 8] - Manual sync operations
**Features**: 
- Rate limiting and caching
- Error handling and logging
- Movie, TV show, and person data retrieval
- Comprehensive data fetching with related content
**AI Action**: Create TMDB API client class with caching and error handling

```php
<?php
namespace TMU\TMDB;

class TMDBClient {
    private $api_key;
    private $base_url = 'https://api.themoviedb.org/3';
    private $cache_duration = 3600; // 1 hour
    
    public function __construct() {
        $this->api_key = get_option('tmu_tmdb_api_key');
    }
    
    public function get_movie_details($tmdb_id): array {
        $endpoint = "/movie/{$tmdb_id}";
        $params = ['append_to_response' => 'credits,images,videos,keywords'];
        return $this->make_request($endpoint, $params);
    }
    
    public function get_tv_details($tmdb_id): array {
        $endpoint = "/tv/{$tmdb_id}";
        $params = ['append_to_response' => 'credits,images,videos,keywords'];
        return $this->make_request($endpoint, $params);
    }
    
    public function get_person_details($tmdb_id): array {
        $endpoint = "/person/{$tmdb_id}";
        $params = ['append_to_response' => 'movie_credits,tv_credits,images'];
        return $this->make_request($endpoint, $params);
    }
    
    private function make_request($endpoint, $params = []): array {
        $cache_key = 'tmdb_' . md5($endpoint . serialize($params));
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $params['api_key'] = $this->api_key;
        $url = $this->base_url . $endpoint . '?' . http_build_query($params);
        
        $response = wp_remote_get($url, [
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'TMU-Theme/1.0'
            ]
        ]);
        
        if (is_wp_error($response)) {
            throw new TMDBException('API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new TMDBException('Invalid JSON response');
        }
        
        if (isset($data['status_code']) && $data['status_code'] !== 200) {
            throw new TMDBException($data['status_message'] ?? 'API error');
        }
        
        set_transient($cache_key, $data, $this->cache_duration);
        return $data;
    }
}
```

### 1.2 Sync Service Manager (`includes/classes/API/TMDB/SyncService.php`)
**File Status**: [CREATE NEW - STEP 9]
**File Path**: `tmu-theme/includes/classes/API/TMDB/SyncService.php`
**Purpose**: Sync service manager that coordinates content synchronization with TMDB
**Dependencies**: 
- [DEPENDS ON] TMDB Client [CREATE NEW - STEP 9] - API communication
- [DEPENDS ON] Data mapper [CREATE NEW - STEP 9] - Data transformation
- [DEPENDS ON] Post types [FROM STEP 5] - Content type integration
- [DEPENDS ON] Custom tables [FROM STEP 3] - Data storage
**Integration**: Orchestrates TMDB data synchronization for all content types
**Used By**: 
- Admin meta boxes [FROM STEP 8] - Manual sync operations
- Sync scheduler [CREATE NEW - STEP 9] - Automated sync jobs
- REST API endpoints [CREATE NEW - STEP 9] - API-triggered syncs
**Features**: 
- Content type-specific sync methods
- Flexible sync options
- Error handling and logging
- Progress tracking for bulk operations
**AI Action**: Create sync service manager with content type handlers

```php
<?php
namespace TMU\TMDB;

class SyncService {
    private $client;
    private $data_mapper;
    
    public function __construct() {
        $this->client = new TMDBClient();
        $this->data_mapper = new DataMapper();
    }
    
    public function sync_movie($post_id, $options = []): bool {
        $tmdb_id = get_post_meta($post_id, 'tmdb_id', true);
        if (!$tmdb_id) return false;
        
        try {
            $data = $this->client->get_movie_details($tmdb_id);
            return $this->data_mapper->map_movie_data($post_id, $data, $options);
        } catch (TMDBException $e) {
            error_log("TMDB Sync Error for movie {$post_id}: " . $e->getMessage());
            return false;
        }
    }
    
    public function sync_tv_show($post_id, $options = []): bool {
        $tmdb_id = get_post_meta($post_id, 'tmdb_id', true);
        if (!$tmdb_id) return false;
        
        try {
            $data = $this->client->get_tv_details($tmdb_id);
            return $this->data_mapper->map_tv_data($post_id, $data, $options);
        } catch (TMDBException $e) {
            error_log("TMDB Sync Error for TV show {$post_id}: " . $e->getMessage());
            return false;
        }
    }
    
    public function sync_person($post_id, $options = []): bool {
        $tmdb_id = get_post_meta($post_id, 'tmdb_id', true);
        if (!$tmdb_id) return false;
        
        try {
            $data = $this->client->get_person_details($tmdb_id);
            return $this->data_mapper->map_person_data($post_id, $data, $options);
        } catch (TMDBException $e) {
            error_log("TMDB Sync Error for person {$post_id}: " . $e->getMessage());
            return false;
        }
    }
}
```

## 2. Data Mapping System

### 2.1 Movie Data Mapper (`includes/classes/API/TMDB/DataMapper.php`)
**File Status**: [CREATE NEW - STEP 9]
**File Path**: `tmu-theme/includes/classes/API/TMDB/DataMapper.php`
**Purpose**: Data mapping system that transforms TMDB API data to WordPress database structure
**Dependencies**: 
- [DEPENDS ON] Custom database tables [FROM STEP 3] - Data storage structure
- [DEPENDS ON] Taxonomies [FROM STEP 6] - Genre, country, language mapping
- [DEPENDS ON] Image sync service [CREATE NEW - STEP 9] - Media management
- [DEPENDS ON] WordPress post functions - wp_insert_post, wp_update_post
**Integration**: Central data transformation layer between TMDB API and WordPress
**Used By**: 
- Sync service [CREATE NEW - STEP 9] - Data synchronization
- Import tools [FROM STEP 8] - Data import operations
- Webhook handler [CREATE NEW - STEP 9] - Real-time updates
**Features**: 
- Complete field mapping for all content types
- Taxonomy synchronization
- Image and video content handling
- Relationship management
- Data validation and sanitization
**AI Action**: Create data mapper with comprehensive field mapping and validation

```php
<?php
namespace TMU\TMDB;

class DataMapper {
    public function map_movie_data($post_id, $tmdb_data, $options): bool {
        global $wpdb;
        
        $table = $wpdb->prefix . 'tmu_movies';
        $mapped_data = [
            'post_id' => $post_id,
            'tmdb_id' => $tmdb_data['id'],
            'imdb_id' => $tmdb_data['imdb_id'] ?? '',
            'title' => $tmdb_data['title'],
            'original_title' => $tmdb_data['original_title'],
            'overview' => $tmdb_data['overview'],
            'tagline' => $tmdb_data['tagline'] ?? '',
            'runtime' => $tmdb_data['runtime'] ?? 0,
            'release_date' => $tmdb_data['release_date'] ?? null,
            'status' => $tmdb_data['status'] ?? '',
            'vote_average' => $tmdb_data['vote_average'] ?? 0,
            'vote_count' => $tmdb_data['vote_count'] ?? 0,
            'popularity' => $tmdb_data['popularity'] ?? 0,
            'budget' => $tmdb_data['budget'] ?? 0,
            'revenue' => $tmdb_data['revenue'] ?? 0,
            'adult' => $tmdb_data['adult'] ? 1 : 0,
            'backdrop_path' => $tmdb_data['backdrop_path'] ?? '',
            'poster_path' => $tmdb_data['poster_path'] ?? '',
            'homepage' => $tmdb_data['homepage'] ?? '',
            'production_companies' => json_encode($tmdb_data['production_companies'] ?? []),
            'production_countries' => json_encode($tmdb_data['production_countries'] ?? []),
            'spoken_languages' => json_encode($tmdb_data['spoken_languages'] ?? []),
            'genres' => json_encode($tmdb_data['genres'] ?? []),
            'keywords' => json_encode($tmdb_data['keywords']['keywords'] ?? []),
            'updated_at' => current_time('mysql')
        ];
        
        // Handle credits
        if (isset($options['sync_credits']) && $options['sync_credits']) {
            $mapped_data['credits'] = json_encode([
                'cast' => $tmdb_data['credits']['cast'] ?? [],
                'crew' => $tmdb_data['credits']['crew'] ?? []
            ]);
        }
        
        // Save to custom table
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table} WHERE post_id = %d", $post_id
        ));
        
        if ($existing) {
            $result = $wpdb->update($table, $mapped_data, ['post_id' => $post_id]);
        } else {
            $mapped_data['created_at'] = current_time('mysql');
            $result = $wpdb->insert($table, $mapped_data);
        }
        
        // Handle images
        if (isset($options['sync_images']) && $options['sync_images']) {
            $this->sync_images($post_id, $tmdb_data['images'] ?? []);
        }
        
        // Handle videos
        if (isset($options['sync_videos']) && $options['sync_videos']) {
            $this->sync_videos($post_id, $tmdb_data['videos']['results'] ?? []);
        }
        
        // Update taxonomies
        $this->update_taxonomies($post_id, $tmdb_data);
        
        return $result !== false;
    }
    
    private function sync_images($post_id, $images): void {
        $image_service = new ImageSyncService();
        
        // Sync posters
        if (!empty($images['posters'])) {
            foreach (array_slice($images['posters'], 0, 5) as $poster) {
                $image_service->download_and_attach_image(
                    $post_id, 
                    $poster['file_path'], 
                    'poster'
                );
            }
        }
        
        // Sync backdrops
        if (!empty($images['backdrops'])) {
            foreach (array_slice($images['backdrops'], 0, 5) as $backdrop) {
                $image_service->download_and_attach_image(
                    $post_id, 
                    $backdrop['file_path'], 
                    'backdrop'
                );
            }
        }
    }
    
    private function sync_videos($post_id, $videos): void {
        foreach ($videos as $video) {
            $video_post_id = wp_insert_post([
                'post_type' => 'video',
                'post_title' => $video['name'],
                'post_status' => 'publish',
                'post_parent' => $post_id
            ]);
            
            if ($video_post_id) {
                global $wpdb;
                $wpdb->insert($wpdb->prefix . 'tmu_videos', [
                    'post_id' => $video_post_id,
                    'source' => $video['key'],
                    'content_type' => strtolower($video['type']),
                    'related_post_id' => $post_id,
                    'created_at' => current_time('mysql')
                ]);
            }
        }
    }
    
    private function update_taxonomies($post_id, $tmdb_data): void {
        // Update genres
        if (!empty($tmdb_data['genres'])) {
            $genre_ids = [];
            foreach ($tmdb_data['genres'] as $genre) {
                $term = get_term_by('slug', sanitize_title($genre['name']), 'genre');
                if (!$term) {
                    $term = wp_insert_term($genre['name'], 'genre', [
                        'slug' => sanitize_title($genre['name'])
                    ]);
                    if (!is_wp_error($term)) {
                        $term_id = $term['term_id'];
                        update_term_meta($term_id, 'tmdb_id', $genre['id']);
                    }
                } else {
                    $term_id = $term->term_id;
                }
                $genre_ids[] = $term_id;
            }
            wp_set_object_terms($post_id, $genre_ids, 'genre');
        }
        
        // Update countries
        if (!empty($tmdb_data['production_countries'])) {
            $country_ids = [];
            foreach ($tmdb_data['production_countries'] as $country) {
                $term = get_term_by('slug', sanitize_title($country['name']), 'country');
                if (!$term) {
                    $term = wp_insert_term($country['name'], 'country', [
                        'slug' => sanitize_title($country['name'])
                    ]);
                    if (!is_wp_error($term)) {
                        $term_id = $term['term_id'];
                        update_term_meta($term_id, 'iso_3166_1', $country['iso_3166_1']);
                    }
                } else {
                    $term_id = $term->term_id;
                }
                $country_ids[] = $term_id;
            }
            wp_set_object_terms($post_id, $country_ids, 'country');
        }
        
        // Update release year
        if (!empty($tmdb_data['release_date'])) {
            $year = date('Y', strtotime($tmdb_data['release_date']));
            $term = get_term_by('name', $year, 'by-year');
            if (!$term) {
                $term = wp_insert_term($year, 'by-year');
            }
            if (!is_wp_error($term)) {
                wp_set_object_terms($post_id, [$term['term_id']], 'by-year');
            }
        }
    }
}
```

## 3. Image Synchronization Service (`includes/classes/API/TMDB/ImageSyncService.php`)
**File Status**: [CREATE NEW - STEP 9]
**File Path**: `tmu-theme/includes/classes/API/TMDB/ImageSyncService.php`
**Purpose**: Image synchronization service for downloading and managing TMDB media assets
**Dependencies**: 
- [DEPENDS ON] WordPress HTTP API - wp_remote_get for image downloads
- [DEPENDS ON] WordPress media functions - wp_insert_attachment, wp_generate_attachment_metadata
- [DEPENDS ON] WordPress upload directory - wp_upload_dir
- [DEPENDS ON] TMDB image CDN - image.tmdb.org
**Integration**: Media management system for TMDB content
**Used By**: 
- Data mapper [CREATE NEW - STEP 9] - Image synchronization during data mapping
- Sync service [CREATE NEW - STEP 9] - Media updates
- Admin interface [FROM STEP 8] - Manual image updates
**Features**: 
- Multi-size image downloading
- Duplicate detection and management
- Automatic attachment creation
- Featured image assignment
- Metadata preservation
**AI Action**: Create image synchronization service with download and attachment management

```php
<?php
namespace TMU\TMDB;

class ImageSyncService {
    private $base_url = 'https://image.tmdb.org/t/p/';
    
    public function download_and_attach_image($post_id, $file_path, $type = 'poster'): ?int {
        if (empty($file_path)) return null;
        
        $sizes = [
            'poster' => 'w500',
            'backdrop' => 'w1280',
            'profile' => 'w276'
        ];
        
        $size = $sizes[$type] ?? 'original';
        $image_url = $this->base_url . $size . $file_path;
        
        // Check if image already exists
        $existing = $this->get_existing_attachment($post_id, $file_path, $type);
        if ($existing) return $existing;
        
        // Download image
        $response = wp_remote_get($image_url, [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'TMU-Theme/1.0'
            ]
        ]);
        
        if (is_wp_error($response)) {
            error_log("Failed to download image: " . $response->get_error_message());
            return null;
        }
        
        $image_data = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        
        // Determine file extension
        $ext = $this->get_file_extension($content_type);
        if (!$ext) return null;
        
        // Generate filename
        $filename = $this->generate_filename($post_id, $type, $ext);
        
        // Save file
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        if (file_put_contents($file_path, $image_data) === false) {
            error_log("Failed to save image file: " . $file_path);
            return null;
        }
        
        // Create attachment
        $attachment_id = wp_insert_attachment([
            'post_mime_type' => $content_type,
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_parent' => $post_id
        ], $file_path);
        
        if (!$attachment_id) {
            unlink($file_path);
            return null;
        }
        
        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $metadata);
        
        // Store TMDB metadata
        update_post_meta($attachment_id, 'tmdb_file_path', $file_path);
        update_post_meta($attachment_id, 'tmdb_image_type', $type);
        
        // Set as featured image if poster
        if ($type === 'poster') {
            set_post_thumbnail($post_id, $attachment_id);
        }
        
        return $attachment_id;
    }
    
    private function get_existing_attachment($post_id, $file_path, $type): ?int {
        $attachments = get_attached_media('image', $post_id);
        foreach ($attachments as $attachment) {
            $stored_path = get_post_meta($attachment->ID, 'tmdb_file_path', true);
            $stored_type = get_post_meta($attachment->ID, 'tmdb_image_type', true);
            
            if ($stored_path === $file_path && $stored_type === $type) {
                return $attachment->ID;
            }
        }
        return null;
    }
    
    private function get_file_extension($content_type): ?string {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        
        return $extensions[$content_type] ?? null;
    }
    
    private function generate_filename($post_id, $type, $ext): string {
        $post_slug = get_post_field('post_name', $post_id);
        $timestamp = time();
        return "{$post_slug}-{$type}-{$timestamp}.{$ext}";
    }
}
```

## 4. Automated Sync Scheduler (`includes/classes/API/TMDB/SyncScheduler.php`)
**File Status**: [CREATE NEW - STEP 9]
**File Path**: `tmu-theme/includes/classes/API/TMDB/SyncScheduler.php`
**Purpose**: Automated sync scheduler for background TMDB data synchronization
**Dependencies**: 
- [DEPENDS ON] WordPress cron system - wp_schedule_event, wp_next_scheduled
- [DEPENDS ON] Sync service [CREATE NEW - STEP 9] - Content synchronization
- [DEPENDS ON] WordPress post queries - get_posts with custom parameters
- [DEPENDS ON] Cache management - WordPress transients
**Integration**: Background job system for automated TMDB updates
**Used By**: 
- WordPress cron system - Scheduled task execution
- Admin settings [CREATE NEW - STEP 9] - Sync configuration
- System monitoring - Performance tracking
**Features**: 
- Daily and weekly sync schedules
- Incremental sync for recent updates
- Full sync for complete data refresh
- Cache management and cleanup
- Error handling and logging
**AI Action**: Create automated sync scheduler with configurable intervals

```php
<?php
namespace TMU\TMDB;

class SyncScheduler {
    public function init(): void {
        add_action('init', [$this, 'schedule_sync_events']);
        add_action('tmu_daily_sync', [$this, 'run_daily_sync']);
        add_action('tmu_weekly_sync', [$this, 'run_weekly_sync']);
    }
    
    public function schedule_sync_events(): void {
        if (!wp_next_scheduled('tmu_daily_sync')) {
            wp_schedule_event(time(), 'daily', 'tmu_daily_sync');
        }
        
        if (!wp_next_scheduled('tmu_weekly_sync')) {
            wp_schedule_event(time(), 'weekly', 'tmu_weekly_sync');
        }
    }
    
    public function run_daily_sync(): void {
        $this->sync_recent_updates();
        $this->clean_expired_cache();
    }
    
    public function run_weekly_sync(): void {
        $this->sync_all_content();
        $this->update_popularity_scores();
    }
    
    private function sync_recent_updates(): void {
        $recent_posts = get_posts([
            'post_type' => ['movie', 'tv', 'drama'],
            'posts_per_page' => 50,
            'meta_key' => 'tmdb_id',
            'meta_value' => '',
            'meta_compare' => '!=',
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);
        
        $sync_service = new SyncService();
        
        foreach ($recent_posts as $post) {
            switch ($post->post_type) {
                case 'movie':
                    $sync_service->sync_movie($post->ID, ['sync_images' => false]);
                    break;
                case 'tv':
                    $sync_service->sync_tv_show($post->ID, ['sync_images' => false]);
                    break;
                case 'drama':
                    // Drama sync logic would go here
                    break;
            }
            
            // Prevent timeout
            sleep(1);
        }
    }
    
    private function clean_expired_cache(): void {
        global $wpdb;
        
        $expired_transients = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_timeout_tmdb_%' 
             AND option_value < UNIX_TIMESTAMP()"
        );
        
        foreach ($expired_transients as $transient) {
            $transient_name = str_replace('_transient_timeout_', '', $transient->option_name);
            delete_transient($transient_name);
        }
    }
}
```

## 5. Admin Interface Integration

```php
// src/TMDB/AdminInterface.php
<?php
namespace TMU\TMDB;

class AdminInterface {
    public function init(): void {
        add_action('admin_menu', [$this, 'add_admin_pages']);
        add_action('wp_ajax_tmu_bulk_sync', [$this, 'handle_bulk_sync']);
        add_action('wp_ajax_tmu_test_api', [$this, 'test_api_connection']);
    }
    
    public function add_admin_pages(): void {
        add_submenu_page(
            'edit.php?post_type=movie',
            'TMDB Settings',
            'TMDB Settings',
            'manage_options',
            'tmu-tmdb-settings',
            [$this, 'render_settings_page']
        );
    }
    
    public function render_settings_page(): void {
        if (isset($_POST['submit'])) {
            update_option('tmu_tmdb_api_key', sanitize_text_field($_POST['tmdb_api_key']));
            update_option('tmu_tmdb_auto_sync', isset($_POST['auto_sync']) ? 1 : 0);
            update_option('tmu_tmdb_sync_images', isset($_POST['sync_images']) ? 1 : 0);
            update_option('tmu_tmdb_sync_videos', isset($_POST['sync_videos']) ? 1 : 0);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $api_key = get_option('tmu_tmdb_api_key', '');
        $auto_sync = get_option('tmu_tmdb_auto_sync', 0);
        $sync_images = get_option('tmu_tmdb_sync_images', 1);
        $sync_videos = get_option('tmu_tmdb_sync_videos', 1);
        
        ?>
        <div class="wrap">
            <h1>TMDB Settings</h1>
            
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="text" name="tmdb_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                            <p class="description">Enter your TMDB API key</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Auto Sync</th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_sync" <?php checked($auto_sync, 1); ?> />
                                Enable automatic daily sync
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Sync Images</th>
                        <td>
                            <label>
                                <input type="checkbox" name="sync_images" <?php checked($sync_images, 1); ?> />
                                Download and sync images from TMDB
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Sync Videos</th>
                        <td>
                            <label>
                                <input type="checkbox" name="sync_videos" <?php checked($sync_videos, 1); ?> />
                                Sync video information from TMDB
                            </label>
                        </td>
                    </tr>
                </table>
                
                <div class="tmu-tmdb-actions">
                    <button type="button" id="test-api" class="button">Test API Connection</button>
                    <button type="button" id="bulk-sync" class="button button-primary">Bulk Sync All Content</button>
                </div>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="Save Settings" />
                </p>
            </form>
            
            <div id="sync-progress" style="display: none;">
                <div class="tmu-progress-bar">
                    <div class="tmu-progress-fill"></div>
                </div>
                <div class="tmu-sync-log"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-api').on('click', function() {
                $.post(ajaxurl, {
                    action: 'tmu_test_api',
                    _wpnonce: '<?php echo wp_create_nonce('tmu_test_api'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('API connection successful!');
                    } else {
                        alert('API connection failed: ' + response.data);
                    }
                });
            });
            
            $('#bulk-sync').on('click', function() {
                if (!confirm('This will sync all content with TMDB. This may take a while. Continue?')) {
                    return;
                }
                
                var $progress = $('#sync-progress');
                var $log = $('.tmu-sync-log');
                
                $progress.show();
                $log.html('Starting bulk sync...<br>');
                
                $.post(ajaxurl, {
                    action: 'tmu_bulk_sync',
                    _wpnonce: '<?php echo wp_create_nonce('tmu_bulk_sync'); ?>'
                }, function(response) {
                    if (response.success) {
                        $log.append('Bulk sync completed successfully!<br>');
                    } else {
                        $log.append('Bulk sync failed: ' + response.data + '<br>');
                    }
                }).fail(function() {
                    $log.append('Bulk sync failed due to network error<br>');
                });
            });
        });
        </script>
        <?php
    }
    
    public function handle_bulk_sync(): void {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'tmu_bulk_sync')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $sync_service = new SyncService();
        $processed = 0;
        $errors = 0;
        
        // Get all posts with TMDB IDs
        $posts = get_posts([
            'post_type' => ['movie', 'tv', 'drama'],
            'posts_per_page' => -1,
            'meta_key' => 'tmdb_id',
            'meta_value' => '',
            'meta_compare' => '!='
        ]);
        
        foreach ($posts as $post) {
            try {
                $options = [
                    'sync_images' => get_option('tmu_tmdb_sync_images', 1),
                    'sync_videos' => get_option('tmu_tmdb_sync_videos', 1),
                    'sync_credits' => true
                ];
                
                switch ($post->post_type) {
                    case 'movie':
                        $result = $sync_service->sync_movie($post->ID, $options);
                        break;
                    case 'tv':
                        $result = $sync_service->sync_tv_show($post->ID, $options);
                        break;
                    default:
                        $result = false;
                }
                
                if ($result) {
                    $processed++;
                } else {
                    $errors++;
                }
                
                // Prevent timeout
                if ($processed % 10 === 0) {
                    sleep(1);
                }
                
            } catch (Exception $e) {
                $errors++;
                error_log("Bulk sync error for post {$post->ID}: " . $e->getMessage());
            }
        }
        
        wp_send_json_success([
            'processed' => $processed,
            'errors' => $errors,
            'total' => count($posts)
        ]);
    }
    
    public function test_api_connection(): void {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'tmu_test_api')) {
            wp_die('Security check failed');
        }
        
        try {
            $client = new TMDBClient();
            $result = $client->get_movie_details(550); // Fight Club test
            
            if (!empty($result['title'])) {
                wp_send_json_success('API connection successful');
            } else {
                wp_send_json_error('Invalid API response');
            }
        } catch (TMDBException $e) {
            wp_send_json_error($e->getMessage());
        }
    }
}
```

## 6. Success Metrics

- [ ] All TMDB API endpoints integrated and functional
- [ ] Automatic sync scheduling working correctly
- [ ] Image download and attachment system operational
- [ ] Video sync functionality implemented
- [ ] Bulk sync interface completed
- [ ] Error handling and logging in place
- [ ] Cache management system active
- [ ] Admin interface fully functional

## AI Implementation Instructions for Step 9

### **Prerequisites Check**
Before implementing Step 9, verify these files exist from previous steps:
- **[REQUIRED]** Post types registration [FROM STEP 5] - API data maps to post types
- **[REQUIRED]** Taxonomies registration [FROM STEP 6] - Genre, country, language mapping
- **[REQUIRED]** Database migration [FROM STEP 3] - Custom tables for TMDB data
- **[REQUIRED]** Admin interface [FROM STEP 8] - TMDB sync meta boxes
- **[REQUIRED]** PSR-4 autoloading [FROM STEP 4] - API class loading
- **[REQUIRED]** Helper functions [FROM STEP 4] - Utility functions

### **Implementation Order for AI Models**

#### **Phase 1: Create Directories** (Required First)
```bash
mkdir -p tmu-theme/includes/classes/API/TMDB
mkdir -p tmu-theme/includes/classes/Admin/Settings
mkdir -p tmu-theme/tests/API
```

#### **Phase 2: Core API Infrastructure** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/API/TMDB/Exception.php` - TMDB exception handling
2. **[CREATE SECOND]** `includes/classes/API/TMDB/Cache.php` - API response caching
3. **[CREATE THIRD]** `includes/classes/API/TMDB/RateLimiter.php` - API rate limiting
4. **[CREATE FOURTH]** `includes/classes/API/TMDB/Client.php` - Main TMDB API client

#### **Phase 3: Data Management** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/API/TMDB/DataMapper.php` - Data mapping system
2. **[CREATE SECOND]** `includes/classes/API/TMDB/ImageSyncService.php` - Image synchronization
3. **[CREATE THIRD]** `includes/classes/API/TMDB/SyncService.php` - Sync service manager

#### **Phase 4: Automation and Scheduling** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/API/TMDB/SyncScheduler.php` - Automated sync scheduler
2. **[CREATE SECOND]** `includes/classes/API/TMDB/WebhookHandler.php` - TMDB webhook processing

#### **Phase 5: Search and Discovery** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/API/TMDB/SearchService.php` - TMDB search functionality

#### **Phase 6: Admin Interface** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/Admin/Settings/TMDBSettings.php` - TMDB settings page
2. **[CREATE SECOND]** `assets/src/js/tmdb-sync.js` - TMDB sync JavaScript
3. **[UPDATE THIRD]** `webpack.config.js` - Include TMDB sync JS compilation

#### **Phase 7: Testing** (Exact Order)
1. **[CREATE FIRST]** `tests/API/TMDBTest.php` - TMDB API testing

#### **Phase 8: Integration** (Final)
1. **[UPDATE]** `includes/classes/ThemeCore.php` - Include TMDB services
2. **[UPDATE]** `includes/classes/Admin/AdminManager.php` - Include TMDB settings

### **Key Implementation Notes**
- **API Rate Limiting**: TMDB API has rate limits - implement proper throttling
- **Image Management**: Large images consume server resources - implement size limits
- **Caching Strategy**: Cache API responses for 1 hour to reduce API calls
- **Error Handling**: Comprehensive error handling for network failures
- **Background Processing**: Use WordPress cron for automated sync jobs
- **Data Validation**: Validate all API data before storing in database

### **TMDB API Integration Features**
```
Core API Client:
├── Movie details retrieval
├── TV show details retrieval
├── Person details retrieval
├── Search functionality
├── Image URL generation
├── Rate limiting
├── Caching layer
└── Error handling

Sync Service:
├── Manual sync operations
├── Bulk sync capabilities
├── Scheduled sync jobs
├── Progress tracking
├── Error logging
└── Data validation

Image Sync:
├── Multi-size downloads
├── Duplicate detection
├── Automatic attachment creation
├── Featured image assignment
└── Metadata preservation
```

### **Database Integration**
- **Movies**: Data stored in `tmu_movies` table
- **TV Shows**: Data stored in `tmu_tv_series` table  
- **Dramas**: Data stored in `tmu_dramas` table
- **People**: Data stored in `tmu_people` table
- **Videos**: Data stored in `tmu_videos` table
- **Images**: WordPress attachments with TMDB metadata

### **Admin Interface Features**
1. **Settings Page**:
   - API key configuration
   - Auto-sync settings
   - Image sync options
   - Video sync options

2. **Bulk Operations**:
   - Bulk sync all content
   - Progress tracking
   - Error reporting
   - API connection testing

3. **Meta Boxes**:
   - Manual sync buttons
   - TMDB search interface
   - Sync status display
   - Last sync timestamp

### **Critical Dependencies**
- **TMDB API Account**: Required for API key access
- **WordPress HTTP API**: wp_remote_get, wp_remote_post
- **WordPress Cron**: wp_schedule_event, wp_next_scheduled
- **WordPress Media**: wp_insert_attachment, wp_generate_attachment_metadata
- **Custom Database Tables**: TMU database schema from Step 3

### **Testing Requirements**
1. **API Connection Test** - Verify API key and connectivity
2. **Data Sync Test** - Verify movie/TV show data synchronization
3. **Image Sync Test** - Verify image download and attachment
4. **Bulk Sync Test** - Verify bulk operations work correctly
5. **Cache Test** - Verify caching reduces API calls
6. **Error Handling Test** - Verify proper error responses

### **Development Workflow**
```bash
# Get TMDB API key
# Sign up at https://www.themoviedb.org/settings/api

# Install dependencies
composer install

# Build JavaScript assets
npm run build

# Run TMDB tests
composer test tests/API/TMDBTest.php

# Test API connection in WordPress admin
# Go to TMU Content > TMDB Settings
# Enter API key and test connection
```

### **Common Issues and Solutions**
1. **API Rate Limiting**: Implement proper delays between requests
2. **Image Download Failures**: Check server permissions and disk space
3. **Cache Not Working**: Verify WordPress transients are enabled
4. **Sync Failures**: Check API key validity and network connectivity
5. **Database Errors**: Verify custom tables exist and are properly structured

### **Verification Commands**
```bash
# Check TMDB classes created
ls -la includes/classes/API/TMDB/

# Check JavaScript compiled
ls -la assets/js/tmdb-sync.js

# Test API connection
# In WordPress admin:
# - Go to TMU Content > TMDB Settings
# - Enter valid API key
# - Click "Test API Connection"
# - Should show success message

# Test manual sync
# In WordPress admin:
# - Edit a movie/TV show with TMDB ID
# - Click "Sync from TMDB" in meta box
# - Verify data updates correctly
```

### **Performance Optimization**
- **Caching**: 1-hour cache for API responses
- **Rate Limiting**: 40 requests per 10 seconds (TMDB limit)
- **Image Optimization**: Download only necessary sizes
- **Background Processing**: Use WordPress cron for bulk operations
- **Database Indexes**: Ensure TMDB ID fields are indexed

### **Security Considerations**
- **API Key Storage**: Store API key in WordPress options (encrypted)
- **User Permissions**: Verify user capabilities for admin operations
- **Input Validation**: Sanitize all API data before storage
- **Rate Limiting**: Prevent abuse of sync operations
- **Error Logging**: Log errors without exposing sensitive data

### **Post-Implementation Checklist**
- [ ] All TMDB API classes created and functional
- [ ] API client with caching and rate limiting implemented
- [ ] Data mapper with comprehensive field mapping
- [ ] Image sync service operational
- [ ] Sync scheduler for automated updates
- [ ] Admin settings page functional
- [ ] Bulk sync operations working
- [ ] Error handling comprehensive
- [ ] JavaScript interactions working
- [ ] Tests passing
- [ ] ThemeCore integration complete
- [ ] Admin interface integration complete

## Next Steps

After completing this step:
- Full TMDB integration with enhanced features
- Automated data synchronization
- Comprehensive error handling
- Performance optimization through caching
- User-friendly admin interface
- Scalable architecture for future enhancements

**Step 9 Status**: ✅ READY FOR AI IMPLEMENTATION
**Dependencies**: Steps 1, 3, 4, 5, 6, 8 must be completed
**Next Step**: Step 10 - Frontend Templates