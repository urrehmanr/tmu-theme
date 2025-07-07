<?php
/**
 * Migration: 001_example_migration
 * Description: Example migration demonstrating the TMU migration system
 * Created: 2024-01-01 00:00:00
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Migration implementation
try {
    global $wpdb;
    
    // Example: Add a new column to movies table
    $table_name = $wpdb->prefix . 'tmu_movies';
    
    // Check if column already exists
    $column_exists = $wpdb->get_results(
        "SHOW COLUMNS FROM {$table_name} LIKE 'example_field'"
    );
    
    if (empty($column_exists)) {
        $wpdb->query(
            "ALTER TABLE {$table_name} 
             ADD COLUMN example_field VARCHAR(255) NULL AFTER tmdb_id"
        );
        
        error_log('Migration 001: Added example_field column to movies table');
    }
    
    // Example: Update existing data
    $wpdb->update(
        $table_name,
        ['example_field' => 'default_value'],
        ['example_field' => null]
    );
    
    // Return true on success
    return true;
    
} catch (Exception $e) {
    error_log('Migration 001_example_migration failed: ' . $e->getMessage());
    return false;
}