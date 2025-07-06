<?php
/**
 * TMU Settings Migrator
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
 * Settings Migrator Class
 */
class SettingsMigrator {
    
    /**
     * Plugin option mappings
     */
    private $option_mappings = [
        'tmu_movies' => 'tmu_movies',
        'tmu_tv_series' => 'tmu_tv_series',
        'tmu_dramas' => 'tmu_dramas',
        'tmu_email' => 'tmu_email',
    ];
    
    /**
     * Migrate plugin settings to theme
     */
    public function migrate(): bool {
        $migrated_any = false;
        
        foreach ($this->option_mappings as $plugin_option => $theme_option) {
            $plugin_value = get_option($plugin_option);
            
            if ($plugin_value !== false) {
                // Option exists from plugin
                $theme_value = get_option($theme_option);
                
                if ($theme_value === false) {
                    // Theme option doesn't exist, migrate it
                    add_option($theme_option, $plugin_value);
                    $migrated_any = true;
                    
                    error_log("TMU: Migrated {$plugin_option} to {$theme_option} with value: {$plugin_value}");
                }
            }
        }
        
        if ($migrated_any) {
            // Mark migration as completed
            update_option('tmu_settings_migrated', true);
            update_option('tmu_migration_date', current_time('mysql'));
        }
        
        return $migrated_any;
    }
    
    /**
     * Check if settings were migrated
     */
    public function isMigrated(): bool {
        return (bool) get_option('tmu_settings_migrated', false);
    }
    
    /**
     * Get migration status
     */
    public function getMigrationStatus(): array {
        $status = [
            'migrated' => $this->isMigrated(),
            'migration_date' => get_option('tmu_migration_date'),
            'migrated_options' => []
        ];
        
        foreach ($this->option_mappings as $plugin_option => $theme_option) {
            $plugin_value = get_option($plugin_option);
            $theme_value = get_option($theme_option);
            
            $status['migrated_options'][$plugin_option] = [
                'plugin_value' => $plugin_value,
                'theme_value' => $theme_value,
                'migrated' => $plugin_value !== false && $theme_value !== false
            ];
        }
        
        return $status;
    }
    
    /**
     * Clean up plugin data (optional)
     */
    public function cleanupPluginData(): void {
        // This method can be used to remove plugin data after successful migration
        // Only use if you want to clean up old plugin options
        foreach ($this->option_mappings as $plugin_option => $theme_option) {
            // Uncomment the line below if you want to remove plugin options after migration
            // delete_option($plugin_option);
        }
        
        error_log('TMU: Plugin data cleanup completed');
    }
    
    /**
     * Rollback migration (restore plugin settings)
     */
    public function rollback(): bool {
        $rollback_any = false;
        
        foreach ($this->option_mappings as $plugin_option => $theme_option) {
            $theme_value = get_option($theme_option);
            
            if ($theme_value !== false) {
                // Restore plugin option
                update_option($plugin_option, $theme_value);
                $rollback_any = true;
                
                error_log("TMU: Rolled back {$theme_option} to {$plugin_option} with value: {$theme_value}");
            }
        }
        
        if ($rollback_any) {
            // Mark rollback as completed
            delete_option('tmu_settings_migrated');
            update_option('tmu_rollback_date', current_time('mysql'));
        }
        
        return $rollback_any;
    }
}