<?php
/**
 * Block Data Migrator
 * 
 * Migrates existing meta box data to Gutenberg block-compatible format
 * 
 * @package TMU\Migration
 * @version 1.0.0
 */

namespace TMU\Migration;

use TMU\Database\Schema;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Block Data Migrator Class
 * 
 * Handles migration from meta box system to Gutenberg blocks
 */
class BlockDataMigrator {
    
    /**
     * Field mapping from meta box to block attributes
     *
     * @var array
     */
    private $field_mappings = [
        'movie' => [
            // Direct mappings
            'tmdb_id' => 'tmdb_id',
            'release_date' => 'release_date',
            'runtime' => 'runtime',
            'budget' => 'budget',
            'revenue' => 'revenue',
            'original_title' => 'original_title',
            'tagline' => 'tagline',
            'average_rating' => 'tmdb_vote_average',
            'vote_count' => 'tmdb_vote_count',
            'popularity' => 'tmdb_popularity',
            
            // Field name changes
            'poster_url' => 'poster_path',
            'backdrop_url' => 'backdrop_path',
            'trailer_url' => 'videos',
            'imdb_id' => 'imdb_id',
            
            // New fields (will be null initially)
            'status' => 'status',
            'homepage' => 'homepage',
            'adult' => 'adult',
            'video' => 'video',
        ],
        
        'tv' => [
            'tmdb_id' => 'tmdb_id',
            'first_air_date' => 'first_air_date',
            'last_air_date' => 'last_air_date',
            'number_of_seasons' => 'number_of_seasons',
            'number_of_episodes' => 'number_of_episodes',
            'episode_runtime' => 'episode_run_time',
            'status' => 'status',
            'poster_url' => 'poster_path',
            'backdrop_url' => 'backdrop_path',
            'imdb_id' => 'imdb_id',
        ],
        
        'drama' => [
            'tmdb_id' => 'tmdb_id',
            'first_air_date' => 'first_air_date',
            'last_air_date' => 'last_air_date',
            'number_of_episodes' => 'number_of_episodes',
            'episode_runtime' => 'episode_run_time',
            'network' => 'networks',
            'poster_url' => 'poster_path',
            'backdrop_url' => 'backdrop_path',
        ],
        
        'people' => [
            'tmdb_id' => 'tmdb_id',
            'date_of_birth' => 'birthday',
            'date_of_death' => 'deathday',
            'place_of_birth' => 'place_of_birth',
            'gender' => 'gender',
            'known_for_department' => 'known_for_department',
            'profile_path' => 'profile_path',
            'biography' => 'biography',
            'imdb_id' => 'imdb_id',
            'popularity' => 'popularity',
        ],
    ];
    
    /**
     * Migration progress tracking
     *
     * @var array
     */
    private $migration_log = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->migration_log = [
            'started_at' => current_time('mysql'),
            'posts_processed' => 0,
            'posts_migrated' => 0,
            'errors' => [],
            'warnings' => [],
        ];
    }
    
    /**
     * Run complete migration
     *
     * @param array $options Migration options
     * @return array Migration results
     */
    public function runMigration(array $options = []): array {
        $defaults = [
            'post_types' => ['movie', 'tv', 'drama', 'people'],
            'batch_size' => 50,
            'dry_run' => false,
            'backup_data' => true,
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        $this->log('Starting migration process...', 'info');
        
        // Step 1: Backup existing data
        if ($options['backup_data']) {
            $this->createDataBackup();
        }
        
        // Step 2: Update database schema
        $this->updateDatabaseSchema();
        
        // Step 3: Migrate post meta to custom tables
        foreach ($options['post_types'] as $post_type) {
            $this->migratePostType($post_type, $options);
        }
        
        // Step 4: Generate block content for posts
        $this->generateBlockContent($options['post_types'], $options);
        
        $this->migration_log['completed_at'] = current_time('mysql');
        $this->log('Migration completed!', 'success');
        
        return $this->migration_log;
    }
    
    /**
     * Create data backup
     */
    private function createDataBackup(): void {
        global $wpdb;
        
        $this->log('Creating data backup...', 'info');
        
        $backup_tables = [
            'postmeta' => "CREATE TABLE {$wpdb->prefix}postmeta_backup_" . date('Ymd_His') . " AS SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE 'tmdb_%' OR meta_key LIKE 'release_%' OR meta_key LIKE 'runtime%'",
        ];
        
        foreach ($backup_tables as $table => $sql) {
            $result = $wpdb->query($sql);
            if ($result === false) {
                $this->log("Failed to backup {$table}: " . $wpdb->last_error, 'error');
            } else {
                $this->log("Backed up {$table} successfully", 'info');
            }
        }
    }
    
    /**
     * Update database schema
     */
    private function updateDatabaseSchema(): void {
        global $wpdb;
        
        $this->log('Updating database schema...', 'info');
        
        // Check if columns already exist to avoid errors
        $schema_updates = $this->getSchemaUpdates();
        
        foreach ($schema_updates as $table => $columns) {
            foreach ($columns as $column => $definition) {
                // Check if column exists
                $column_exists = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                        DB_NAME,
                        $wpdb->prefix . $table,
                        $column
                    )
                );
                
                if (empty($column_exists)) {
                    $sql = "ALTER TABLE `{$wpdb->prefix}{$table}` ADD COLUMN `{$column}` {$definition}";
                    $result = $wpdb->query($sql);
                    
                    if ($result === false) {
                        $this->log("Failed to add column {$column} to {$table}: " . $wpdb->last_error, 'error');
                    } else {
                        $this->log("Added column {$column} to {$table}", 'info');
                    }
                }
            }
        }
    }
    
    /**
     * Get schema updates needed
     *
     * @return array
     */
    private function getSchemaUpdates(): array {
        return [
            'tmu_movies' => [
                'imdb_id' => 'varchar(20) DEFAULT NULL',
                'status' => 'varchar(50) DEFAULT NULL',
                'homepage' => 'text DEFAULT NULL',
                'poster_path' => 'text DEFAULT NULL',
                'backdrop_path' => 'text DEFAULT NULL',
                'adult' => 'tinyint(1) DEFAULT 0',
                'video' => 'tinyint(1) DEFAULT 0',
                'belongs_to_collection' => 'longtext DEFAULT NULL',
                'production_companies' => 'longtext DEFAULT NULL',
                'production_countries' => 'longtext DEFAULT NULL',
                'spoken_languages' => 'longtext DEFAULT NULL',
                'external_ids' => 'longtext DEFAULT NULL',
                'similar' => 'longtext DEFAULT NULL',
                'recommendations' => 'longtext DEFAULT NULL',
            ],
            
            'tmu_tv_series' => [
                'imdb_id' => 'varchar(20) DEFAULT NULL',
                'name' => 'text DEFAULT NULL',
                'original_name' => 'text DEFAULT NULL',
                'type' => 'varchar(50) DEFAULT NULL',
                'homepage' => 'text DEFAULT NULL',
                'in_production' => 'tinyint(1) DEFAULT 0',
                'number_of_episodes' => 'int(11) DEFAULT NULL',
                'number_of_seasons' => 'int(11) DEFAULT NULL',
                'episode_run_time' => 'longtext DEFAULT NULL',
                'languages' => 'longtext DEFAULT NULL',
                'origin_country' => 'longtext DEFAULT NULL',
                'original_language' => 'varchar(10) DEFAULT NULL',
                'poster_path' => 'text DEFAULT NULL',
                'backdrop_path' => 'text DEFAULT NULL',
                'created_by' => 'longtext DEFAULT NULL',
                'networks' => 'longtext DEFAULT NULL',
                'genres' => 'longtext DEFAULT NULL',
                'production_companies' => 'longtext DEFAULT NULL',
                'production_countries' => 'longtext DEFAULT NULL',
                'spoken_languages' => 'longtext DEFAULT NULL',
                'external_ids' => 'longtext DEFAULT NULL',
                'similar' => 'longtext DEFAULT NULL',
                'recommendations' => 'longtext DEFAULT NULL',
            ],
            
            'tmu_dramas' => [
                'imdb_id' => 'varchar(20) DEFAULT NULL',
                'name' => 'text DEFAULT NULL',
                'original_name' => 'text DEFAULT NULL',
                'type' => 'varchar(50) DEFAULT NULL',
                'homepage' => 'text DEFAULT NULL',
                'number_of_episodes' => 'int(11) DEFAULT NULL',
                'episode_run_time' => 'longtext DEFAULT NULL',
                'languages' => 'longtext DEFAULT NULL',
                'origin_country' => 'longtext DEFAULT NULL',
                'original_language' => 'varchar(10) DEFAULT NULL',
                'poster_path' => 'text DEFAULT NULL',
                'backdrop_path' => 'text DEFAULT NULL',
                'created_by' => 'longtext DEFAULT NULL',
                'networks' => 'longtext DEFAULT NULL',
                'genres' => 'longtext DEFAULT NULL',
                'production_companies' => 'longtext DEFAULT NULL',
                'production_countries' => 'longtext DEFAULT NULL',
                'spoken_languages' => 'longtext DEFAULT NULL',
                'external_ids' => 'longtext DEFAULT NULL',
                'similar' => 'longtext DEFAULT NULL',
                'recommendations' => 'longtext DEFAULT NULL',
            ],
            
            'tmu_people' => [
                'imdb_id' => 'varchar(20) DEFAULT NULL',
                'also_known_as' => 'longtext DEFAULT NULL',
                'biography' => 'longtext DEFAULT NULL',
                'birthday' => 'date DEFAULT NULL',
                'deathday' => 'date DEFAULT NULL',
                'external_ids' => 'longtext DEFAULT NULL',
                'images' => 'longtext DEFAULT NULL',
            ],
        ];
    }
    
    /**
     * Migrate specific post type
     *
     * @param string $post_type Post type to migrate
     * @param array $options Migration options
     */
    private function migratePostType(string $post_type, array $options): void {
        global $wpdb;
        
        $this->log("Migrating {$post_type} posts...", 'info');
        
        $posts = get_posts([
            'post_type' => $post_type,
            'post_status' => 'any',
            'numberposts' => -1,
            'fields' => 'ids',
        ]);
        
        $total_posts = count($posts);
        $this->log("Found {$total_posts} {$post_type} posts to migrate", 'info');
        
        $batch_size = $options['batch_size'];
        $batches = array_chunk($posts, $batch_size);
        
        foreach ($batches as $batch_index => $batch) {
            $this->log("Processing batch " . ($batch_index + 1) . " of " . count($batches), 'info');
            
            foreach ($batch as $post_id) {
                if ($this->migratePost($post_id, $post_type, $options)) {
                    $this->migration_log['posts_migrated']++;
                }
                $this->migration_log['posts_processed']++;
            }
            
            // Prevent memory issues
            if (!$options['dry_run']) {
                wp_cache_flush();
            }
        }
    }
    
    /**
     * Migrate individual post
     *
     * @param int $post_id Post ID
     * @param string $post_type Post type
     * @param array $options Migration options
     * @return bool Success status
     */
    private function migratePost(int $post_id, string $post_type, array $options): bool {
        global $wpdb;
        
        try {
            // Get existing meta data
            $meta_data = get_post_meta($post_id);
            
            if (empty($meta_data)) {
                return true; // Nothing to migrate
            }
            
            // Map meta fields to database columns
            $mapped_data = $this->mapMetaFields($meta_data, $post_type);
            
            if (empty($mapped_data)) {
                return true; // No relevant fields to migrate
            }
            
            // Update custom table
            $table_name = $this->getTableName($post_type);
            
            if (!$options['dry_run']) {
                $existing = $wpdb->get_row($wpdb->prepare(
                    "SELECT ID FROM {$table_name} WHERE ID = %d",
                    $post_id
                ));
                
                $mapped_data['ID'] = $post_id;
                
                if ($existing) {
                    // Update existing record
                    $result = $wpdb->update($table_name, $mapped_data, ['ID' => $post_id]);
                } else {
                    // Insert new record
                    $result = $wpdb->insert($table_name, $mapped_data);
                }
                
                if ($result === false) {
                    $this->log("Failed to migrate post {$post_id}: " . $wpdb->last_error, 'error');
                    return false;
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->log("Error migrating post {$post_id}: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Map meta fields to database columns
     *
     * @param array $meta_data Post meta data
     * @param string $post_type Post type
     * @return array Mapped data
     */
    private function mapMetaFields(array $meta_data, string $post_type): array {
        $mappings = $this->field_mappings[$post_type] ?? [];
        $mapped_data = [];
        
        foreach ($mappings as $meta_key => $db_column) {
            if (isset($meta_data[$meta_key])) {
                $value = $meta_data[$meta_key][0] ?? '';
                
                // Handle special cases
                $mapped_data[$db_column] = $this->transformFieldValue($value, $meta_key, $db_column);
            }
        }
        
        return $mapped_data;
    }
    
    /**
     * Transform field value for new format
     *
     * @param mixed $value Original value
     * @param string $old_key Original field key
     * @param string $new_key New field key
     * @return mixed Transformed value
     */
    private function transformFieldValue($value, string $old_key, string $new_key) {
        // Handle URL to path conversions
        if (in_array($new_key, ['poster_path', 'backdrop_path']) && filter_var($value, FILTER_VALIDATE_URL)) {
            // Extract path from TMDB URL
            if (strpos($value, 'image.tmdb.org') !== false) {
                $path = parse_url($value, PHP_URL_PATH);
                return str_replace('/t/p/original', '', $path);
            }
        }
        
        // Handle date conversions
        if (in_array($new_key, ['birthday', 'deathday']) && !empty($value)) {
            return date('Y-m-d', strtotime($value));
        }
        
        // Handle array fields
        if (in_array($new_key, ['episode_run_time', 'languages', 'origin_country'])) {
            if (is_string($value) && !empty($value)) {
                return json_encode(explode(',', $value));
            }
        }
        
        // Handle boolean conversions
        if (in_array($new_key, ['adult', 'video', 'in_production'])) {
            return (bool) $value;
        }
        
        return $value;
    }
    
    /**
     * Generate block content for posts
     *
     * @param array $post_types Post types to process
     * @param array $options Migration options
     */
    private function generateBlockContent(array $post_types, array $options): void {
        $this->log('Generating block content...', 'info');
        
        foreach ($post_types as $post_type) {
            $posts = get_posts([
                'post_type' => $post_type,
                'post_status' => 'any',
                'numberposts' => -1,
            ]);
            
            foreach ($posts as $post) {
                $this->generatePostBlockContent($post, $options);
            }
        }
    }
    
    /**
     * Generate block content for individual post
     *
     * @param WP_Post $post Post object
     * @param array $options Migration options
     */
    private function generatePostBlockContent(\WP_Post $post, array $options): void {
        // Get block type for post type
        $block_type = $this->getBlockType($post->post_type);
        
        if (!$block_type) {
            return;
        }
        
        // Create block content
        $block_content = "<!-- wp:{$block_type} -->\n";
        $block_content .= '<div class="wp-block-tmu-' . str_replace('tmu/', '', $block_type) . '"></div>';
        $block_content .= "\n<!-- /wp:{$block_type} -->";
        
        // Update post content if it doesn't already contain blocks
        if (!has_blocks($post->post_content) && !$options['dry_run']) {
            $updated_content = $post->post_content . "\n\n" . $block_content;
            
            wp_update_post([
                'ID' => $post->ID,
                'post_content' => $updated_content,
            ]);
        }
    }
    
    /**
     * Get table name for post type
     *
     * @param string $post_type Post type
     * @return string Table name
     */
    private function getTableName(string $post_type): string {
        global $wpdb;
        
        $table_map = [
            'movie' => 'tmu_movies',
            'tv' => 'tmu_tv_series',
            'drama' => 'tmu_dramas',
            'people' => 'tmu_people',
        ];
        
        return $wpdb->prefix . ($table_map[$post_type] ?? '');
    }
    
    /**
     * Get block type for post type
     *
     * @param string $post_type Post type
     * @return string|null Block type
     */
    private function getBlockType(string $post_type): ?string {
        $block_map = [
            'movie' => 'tmu/movie-metadata',
            'tv' => 'tmu/tv-series-metadata',
            'drama' => 'tmu/drama-metadata',
            'people' => 'tmu/people-metadata',
        ];
        
        return $block_map[$post_type] ?? null;
    }
    
    /**
     * Log migration progress
     *
     * @param string $message Log message
     * @param string $type Message type (info, warning, error, success)
     */
    private function log(string $message, string $type = 'info'): void {
        $timestamp = current_time('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] {$message}";
        
        switch ($type) {
            case 'error':
                $this->migration_log['errors'][] = $log_entry;
                error_log("TMU Migration ERROR: {$message}");
                break;
                
            case 'warning':
                $this->migration_log['warnings'][] = $log_entry;
                error_log("TMU Migration WARNING: {$message}");
                break;
                
            case 'success':
            case 'info':
            default:
                error_log("TMU Migration INFO: {$message}");
                break;
        }
    }
    
    /**
     * Get migration log
     *
     * @return array Migration log
     */
    public function getLog(): array {
        return $this->migration_log;
    }
    
    /**
     * Validate migration results
     *
     * @return array Validation results
     */
    public function validateMigration(): array {
        global $wpdb;
        
        $validation = [
            'data_integrity' => true,
            'missing_data' => [],
            'inconsistencies' => [],
        ];
        
        // Check each post type
        foreach (['movie', 'tv', 'drama', 'people'] as $post_type) {
            $table_name = $this->getTableName($post_type);
            
            // Get posts that should have data
            $posts = get_posts([
                'post_type' => $post_type,
                'numberposts' => -1,
                'fields' => 'ids',
            ]);
            
            foreach ($posts as $post_id) {
                // Check if data exists in custom table
                $data = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$table_name} WHERE ID = %d",
                    $post_id
                ));
                
                if (!$data) {
                    $validation['missing_data'][] = [
                        'post_id' => $post_id,
                        'post_type' => $post_type,
                        'issue' => 'No data in custom table',
                    ];
                    $validation['data_integrity'] = false;
                }
            }
        }
        
        return $validation;
    }
}