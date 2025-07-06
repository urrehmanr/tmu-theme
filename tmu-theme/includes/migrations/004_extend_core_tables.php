<?php
/**
 * Extend Core Tables Migration
 *
 * @package TMU\Database\Migrations
 * @version 1.0.0
 */

namespace TMU\Database\Migrations;

use TMU\Database\Schema;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Extend Core Tables
 */
class ExtendCoreTables {
    
    /**
     * Run the migration
     */
    public function up(): void {
        global $wpdb;
        
        // Get core table extensions
        $extensions = Schema::getCoreTableExtensions();
        
        foreach ($extensions as $sql) {
            // Check if this is an ALTER TABLE statement
            if (strpos($sql, 'ALTER TABLE') === 0) {
                // Execute the ALTER TABLE statement
                $result = $wpdb->query($sql);
                
                if ($result === false) {
                    // Log the error but don't stop migration
                    error_log("TMU Migration Warning: {$wpdb->last_error}");
                    error_log("TMU Migration Warning: Failed SQL: {$sql}");
                }
            }
        }
        
        // Create additional custom indexes for performance
        $this->createPerformanceIndexes();
        
        // Log migration
        error_log('TMU Migration: Core tables extended successfully');
    }
    
    /**
     * Create performance indexes
     */
    private function createPerformanceIndexes(): void {
        global $wpdb;
        
        $indexes = [
            // Index for post type queries
            "CREATE INDEX `idx_post_type_status` ON `{$wpdb->prefix}posts` (`post_type`, `post_status`)",
            
            // Index for date queries
            "CREATE INDEX `idx_post_date` ON `{$wpdb->prefix}posts` (`post_date`)",
            
            // Index for meta queries (if meta table exists)
            "CREATE INDEX `idx_meta_key_value` ON `{$wpdb->prefix}postmeta` (`meta_key`, `meta_value`(100))",
            
            // Index for comment queries
            "CREATE INDEX `idx_comment_post_approved` ON `{$wpdb->prefix}comments` (`comment_post_ID`, `comment_approved`)",
            
            // Index for comment rating queries
            "CREATE INDEX `idx_comment_rating_post` ON `{$wpdb->prefix}comments` (`comment_rating`, `comment_post_ID`)"
        ];
        
        foreach ($indexes as $index_sql) {
            $result = $wpdb->query($index_sql);
            
            if ($result === false) {
                // Log the error but continue with other indexes
                error_log("TMU Migration Warning: Index creation failed - {$wpdb->last_error}");
            }
        }
    }
    
    /**
     * Rollback the migration
     */
    public function down(): void {
        global $wpdb;
        
        // Remove added columns from wp_posts
        $columns_to_remove = [
            "ALTER TABLE `{$wpdb->prefix}posts` DROP COLUMN `seo_title`",
            "ALTER TABLE `{$wpdb->prefix}posts` DROP COLUMN `seo_description`",
            "ALTER TABLE `{$wpdb->prefix}posts` DROP COLUMN `meta_keywords`"
        ];
        
        foreach ($columns_to_remove as $sql) {
            $result = $wpdb->query($sql);
            
            if ($result === false) {
                error_log("TMU Migration Warning: Column removal failed - {$wpdb->last_error}");
            }
        }
        
        // Remove added columns from wp_comments
        $comment_columns_to_remove = [
            "ALTER TABLE `{$wpdb->prefix}comments` DROP COLUMN `comment_rating`",
            "ALTER TABLE `{$wpdb->prefix}comments` DROP COLUMN `parent_post_id`"
        ];
        
        foreach ($comment_columns_to_remove as $sql) {
            $result = $wpdb->query($sql);
            
            if ($result === false) {
                error_log("TMU Migration Warning: Comment column removal failed - {$wpdb->last_error}");
            }
        }
        
        // Remove custom indexes
        $indexes_to_remove = [
            "DROP INDEX `idx_post_type_status` ON `{$wpdb->prefix}posts`",
            "DROP INDEX `idx_post_date` ON `{$wpdb->prefix}posts`",
            "DROP INDEX `idx_meta_key_value` ON `{$wpdb->prefix}postmeta`",
            "DROP INDEX `idx_comment_post_approved` ON `{$wpdb->prefix}comments`",
            "DROP INDEX `idx_comment_rating_post` ON `{$wpdb->prefix}comments`",
            "DROP INDEX `seo_title` ON `{$wpdb->prefix}posts`",
            "DROP INDEX `comment_rating` ON `{$wpdb->prefix}comments`",
            "DROP INDEX `parent_post_id` ON `{$wpdb->prefix}comments`"
        ];
        
        foreach ($indexes_to_remove as $sql) {
            $result = $wpdb->query($sql);
            
            if ($result === false) {
                // Indexes may not exist, so just log as warning
                error_log("TMU Migration Warning: Index removal failed - {$wpdb->last_error}");
            }
        }
        
        error_log('TMU Migration: Core table extensions removed');
    }
}