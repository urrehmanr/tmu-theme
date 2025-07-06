# Step 09: TMDB API Integration - Complete Implementation

## Overview
This step implements comprehensive TMDB API integration with enhanced sync capabilities, caching, error handling, and automated data updates. The system preserves all existing functionality while adding modern API management features.

## 1. TMDB API Service Architecture

### 1.1 Core API Client
```php
// src/TMDB/TMDBClient.php
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

### 1.2 Sync Service Manager
```php
// src/TMDB/SyncService.php
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

### 2.1 Movie Data Mapper
```php
// src/TMDB/DataMapper.php
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

## 3. Image Synchronization Service

```php
// src/TMDB/ImageSyncService.php
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

## 4. Automated Sync Scheduler

```php
// src/TMDB/SyncScheduler.php
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

## Next Steps

After completing this step:
- Full TMDB integration with enhanced features
- Automated data synchronization
- Comprehensive error handling
- Performance optimization through caching
- User-friendly admin interface
- Scalable architecture for future enhancements