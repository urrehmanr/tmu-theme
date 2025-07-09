<?php
/**
 * Data Migrator Class
 *
 * @package TMU\Migration
 * @version 1.0.0
 */

namespace TMU\Migration;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Data Migrator Class
 * Handles migration of existing data to the new TMU schema
 */
class DataMigrator {
    
    /**
     * Database instance
     *
     * @var \wpdb
     */
    private $db;
    
    /**
     * Migration batch size
     *
     * @var int
     */
    private $batch_size = 100;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }
    
    /**
     * Migrate all data
     *
     * @return array Migration result
     */
    public function migrateAllData(): array {
        $results = [];
        
        try {
            // Migrate post types
            $results['posts'] = $this->migratePosts();
            
            // Migrate taxonomies
            $results['taxonomies'] = $this->migrateTaxonomies();
            
            // Migrate meta data
            $results['meta'] = $this->migrateMetaData();
            
            // Migrate user data
            $results['users'] = $this->migrateUserData();
            
            return [
                'success' => true,
                'message' => __('Data migration completed successfully.', 'tmu'),
                'results' => $results
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => sprintf(__('Data migration failed: %s', 'tmu'), $e->getMessage()),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Migrate posts to new structure
     *
     * @return array Migration result
     */
    public function migratePosts(): array {
        try {
            $migrated = 0;
            $skipped = 0;
            $errors = 0;
            
            // Get posts that need migration
            $posts = $this->getPostsForMigration();
            
            foreach ($posts as $post) {
                try {
                    if ($this->migratePost($post)) {
                        $migrated++;
                    } else {
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    tmu_log(sprintf('Post migration error for ID %d: %s', $post->ID, $e->getMessage()), 'error');
                }
            }
            
            return [
                'success' => true,
                'migrated' => $migrated,
                'skipped' => $skipped,
                'errors' => $errors,
                'total' => count($posts)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Migrate taxonomies to new structure
     *
     * @return array Migration result
     */
    public function migrateTaxonomies(): array {
        try {
            $migrated = 0;
            $skipped = 0;
            $errors = 0;
            
            // Define taxonomy mappings
            $taxonomy_mappings = [
                'category' => 'genre',
                'post_tag' => 'genre',
                // Add more mappings as needed
            ];
            
            foreach ($taxonomy_mappings as $old_taxonomy => $new_taxonomy) {
                $terms = get_terms([
                    'taxonomy' => $old_taxonomy,
                    'hide_empty' => false
                ]);
                
                if (!is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        try {
                            if ($this->migrateTerm($term, $new_taxonomy)) {
                                $migrated++;
                            } else {
                                $skipped++;
                            }
                        } catch (\Exception $e) {
                            $errors++;
                            tmu_log(sprintf('Term migration error for ID %d: %s', $term->term_id, $e->getMessage()), 'error');
                        }
                    }
                }
            }
            
            return [
                'success' => true,
                'migrated' => $migrated,
                'skipped' => $skipped,
                'errors' => $errors
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Migrate meta data to new structure
     *
     * @return array Migration result
     */
    public function migrateMetaData(): array {
        try {
            $migrated = 0;
            $skipped = 0;
            $errors = 0;
            
            // Define meta field mappings
            $meta_mappings = [
                'movie_rating' => 'average_rating',
                'movie_year' => 'release_date',
                'movie_duration' => 'runtime',
                // Add more mappings as needed
            ];
            
            foreach ($meta_mappings as $old_key => $new_key) {
                $meta_entries = $this->db->get_results($this->db->prepare(
                    "SELECT post_id, meta_value FROM {$this->db->postmeta} WHERE meta_key = %s",
                    $old_key
                ));
                
                foreach ($meta_entries as $entry) {
                    try {
                        if ($this->migrateMetaField($entry->post_id, $old_key, $new_key, $entry->meta_value)) {
                            $migrated++;
                        } else {
                            $skipped++;
                        }
                    } catch (\Exception $e) {
                        $errors++;
                        tmu_log(sprintf('Meta migration error for post %d: %s', $entry->post_id, $e->getMessage()), 'error');
                    }
                }
            }
            
            return [
                'success' => true,
                'migrated' => $migrated,
                'skipped' => $skipped,
                'errors' => $errors
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Migrate user data to new structure
     *
     * @return array Migration result
     */
    public function migrateUserData(): array {
        try {
            $migrated = 0;
            $skipped = 0;
            $errors = 0;
            
            // Get users that need migration
            $users = get_users(['fields' => 'all']);
            
            foreach ($users as $user) {
                try {
                    if ($this->migrateUser($user)) {
                        $migrated++;
                    } else {
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    tmu_log(sprintf('User migration error for ID %d: %s', $user->ID, $e->getMessage()), 'error');
                }
            }
            
            return [
                'success' => true,
                'migrated' => $migrated,
                'skipped' => $skipped,
                'errors' => $errors,
                'total' => count($users)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get posts that need migration
     *
     * @return array Posts to migrate
     */
    private function getPostsForMigration(): array {
        $args = [
            'post_type' => ['post', 'page'],
            'post_status' => 'any',
            'posts_per_page' => $this->batch_size,
            'meta_query' => [
                [
                    'key' => '_tmu_migrated',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ];
        
        return get_posts($args);
    }
    
    /**
     * Migrate a single post
     *
     * @param \WP_Post $post Post to migrate
     * @return bool Success status
     */
    private function migratePost(\WP_Post $post): bool {
        // Check if already migrated
        if (get_post_meta($post->ID, '_tmu_migrated', true)) {
            return false;
        }
        
        // Perform migration logic here
        // This is a placeholder - implement specific migration logic as needed
        
        // Mark as migrated
        update_post_meta($post->ID, '_tmu_migrated', time());
        
        return true;
    }
    
    /**
     * Migrate a single term
     *
     * @param \WP_Term $term Term to migrate
     * @param string $new_taxonomy New taxonomy name
     * @return bool Success status
     */
    private function migrateTerm(\WP_Term $term, string $new_taxonomy): bool {
        // Check if term already exists in new taxonomy
        $existing_term = get_term_by('slug', $term->slug, $new_taxonomy);
        
        if ($existing_term) {
            return false; // Already exists
        }
        
        // Create term in new taxonomy
        $result = wp_insert_term($term->name, $new_taxonomy, [
            'description' => $term->description,
            'slug' => $term->slug
        ]);
        
        return !is_wp_error($result);
    }
    
    /**
     * Migrate a single meta field
     *
     * @param int $post_id Post ID
     * @param string $old_key Old meta key
     * @param string $new_key New meta key
     * @param mixed $value Meta value
     * @return bool Success status
     */
    private function migrateMetaField(int $post_id, string $old_key, string $new_key, $value): bool {
        // Check if new meta already exists
        if (get_post_meta($post_id, $new_key, true)) {
            return false; // Already exists
        }
        
        // Add new meta
        return update_post_meta($post_id, $new_key, $value);
    }
    
    /**
     * Migrate a single user
     *
     * @param \WP_User $user User to migrate
     * @return bool Success status
     */
    private function migrateUser(\WP_User $user): bool {
        // Check if already migrated
        if (get_user_meta($user->ID, '_tmu_migrated', true)) {
            return false;
        }
        
        // Perform user migration logic here
        // This is a placeholder - implement specific migration logic as needed
        
        // Mark as migrated
        update_user_meta($user->ID, '_tmu_migrated', time());
        
        return true;
    }
    
    /**
     * Set batch size for migration
     *
     * @param int $size Batch size
     */
    public function setBatchSize(int $size): void {
        $this->batch_size = max(1, $size);
    }
    
    /**
     * Get migration progress
     *
     * @return array Progress information
     */
    public function getMigrationProgress(): array {
        try {
            $total_posts = wp_count_posts()->publish;
            $migrated_posts = $this->db->get_var(
                "SELECT COUNT(*) FROM {$this->db->postmeta} WHERE meta_key = '_tmu_migrated'"
            );
            
            $progress = $total_posts > 0 ? ($migrated_posts / $total_posts) * 100 : 100;
            
            return [
                'success' => true,
                'total_posts' => $total_posts,
                'migrated_posts' => $migrated_posts,
                'progress_percentage' => round($progress, 2)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}