<?php
/**
 * Database Connectivity Checker
 * 
 * Database connection testing utility as specified in Step 19 documentation
 * 
 * @package TMU\Database
 * @since 1.0.0
 */

namespace TMU\Database;

/**
 * Database Connectivity Checker Class
 * 
 * Implements database connection check from Step 19 documentation
 * lines 540-549 for troubleshooting database connection errors
 */
class DatabaseConnectivityChecker {
    
    /**
     * Check database connectivity as specified in Step 19 documentation
     * 
     * This method implements the exact code from Step 19 documentation
     * for checking database connection issues
     * 
     * @return array Connection test results
     */
    public function check_database_connectivity(): array {
        $results = [
            'connected' => false,
            'error' => null,
            'connection_time' => 0,
            'server_info' => null,
            'database_name' => null,
            'charset' => null,
            'collation' => null
        ];
        
        $start_time = microtime(true);
        
        try {
            global $wpdb;
            
            // Test basic connectivity with exact code from Step 19
            $test_result = $wpdb->get_var("SELECT 1");
            
            if ($test_result === '1') {
                $results['connected'] = true;
                echo "Database connection: OK\n";
                
                // Get additional connection info
                $results['server_info'] = $wpdb->get_var("SELECT VERSION()");
                $results['database_name'] = DB_NAME;
                $results['charset'] = $wpdb->charset;
                $results['collation'] = $wpdb->collate;
                
            } else {
                $results['error'] = 'Query returned unexpected result: ' . $test_result;
                echo "Database connection test failed\n";
            }
            
        } catch (Exception $e) {
            $results['error'] = $e->getMessage();
            echo "Database connection failed: " . $e->getMessage() . "\n";
        }
        
        $results['connection_time'] = microtime(true) - $start_time;
        
        return $results;
    }
    
    /**
     * Test database write operations
     * 
     * @return array Write test results
     */
    public function test_write_operations(): array {
        global $wpdb;
        
        $results = [
            'can_write' => false,
            'can_create_table' => false,
            'can_insert' => false,
            'can_update' => false,
            'can_delete' => false,
            'errors' => []
        ];
        
        $test_table = $wpdb->prefix . 'tmu_connection_test';
        
        try {
            // Test table creation
            $create_sql = "CREATE TEMPORARY TABLE {$test_table} (
                id INT PRIMARY KEY AUTO_INCREMENT,
                test_value VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            $created = $wpdb->query($create_sql);
            if ($created !== false) {
                $results['can_create_table'] = true;
                
                // Test insert
                $inserted = $wpdb->insert(
                    $test_table,
                    ['test_value' => 'TMU Connection Test'],
                    ['%s']
                );
                
                if ($inserted !== false) {
                    $results['can_insert'] = true;
                    $insert_id = $wpdb->insert_id;
                    
                    // Test update
                    $updated = $wpdb->update(
                        $test_table,
                        ['test_value' => 'TMU Updated Test'],
                        ['id' => $insert_id],
                        ['%s'],
                        ['%d']
                    );
                    
                    if ($updated !== false) {
                        $results['can_update'] = true;
                        
                        // Test delete
                        $deleted = $wpdb->delete(
                            $test_table,
                            ['id' => $insert_id],
                            ['%d']
                        );
                        
                        if ($deleted !== false) {
                            $results['can_delete'] = true;
                        } else {
                            $results['errors'][] = 'Delete operation failed: ' . $wpdb->last_error;
                        }
                    } else {
                        $results['errors'][] = 'Update operation failed: ' . $wpdb->last_error;
                    }
                } else {
                    $results['errors'][] = 'Insert operation failed: ' . $wpdb->last_error;
                }
                
                // Clean up - drop temporary table
                $wpdb->query("DROP TEMPORARY TABLE IF EXISTS {$test_table}");
                
            } else {
                $results['errors'][] = 'Table creation failed: ' . $wpdb->last_error;
            }
            
        } catch (Exception $e) {
            $results['errors'][] = 'Write test exception: ' . $e->getMessage();
        }
        
        $results['can_write'] = $results['can_create_table'] && 
                               $results['can_insert'] && 
                               $results['can_update'] && 
                               $results['can_delete'];
        
        return $results;
    }
    
    /**
     * Test database read operations
     * 
     * @return array Read test results
     */
    public function test_read_operations(): array {
        global $wpdb;
        
        $results = [
            'can_read' => false,
            'can_query_posts' => false,
            'can_query_options' => false,
            'can_query_custom_tables' => false,
            'errors' => []
        ];
        
        try {
            // Test basic read operation
            $basic_test = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}");
            if ($basic_test !== null) {
                $results['can_read'] = true;
                
                // Test posts table
                $posts_test = $wpdb->get_results(
                    "SELECT ID, post_title FROM {$wpdb->posts} LIMIT 1"
                );
                $results['can_query_posts'] = !empty($posts_test);
                
                // Test options table
                $options_test = $wpdb->get_var(
                    "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'blogname'"
                );
                $results['can_query_options'] = ($options_test !== null);
                
                // Test custom TMU tables
                $custom_tables = [
                    $wpdb->prefix . 'tmu_movies',
                    $wpdb->prefix . 'tmu_tv_series',
                    $wpdb->prefix . 'tmu_dramas',
                    $wpdb->prefix . 'tmu_people'
                ];
                
                $custom_table_results = [];
                foreach ($custom_tables as $table) {
                    $table_exists = $wpdb->get_var(
                        $wpdb->prepare("SHOW TABLES LIKE %s", $table)
                    );
                    $custom_table_results[] = ($table_exists === $table);
                }
                
                $results['can_query_custom_tables'] = !in_array(false, $custom_table_results, true);
                
            } else {
                $results['errors'][] = 'Basic read operation failed';
            }
            
        } catch (Exception $e) {
            $results['errors'][] = 'Read test exception: ' . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Comprehensive database health check
     * 
     * @return array Complete health check results
     */
    public function comprehensive_health_check(): array {
        $results = [
            'overall_health' => 'unknown',
            'connectivity' => $this->check_database_connectivity(),
            'read_operations' => $this->test_read_operations(),
            'write_operations' => $this->test_write_operations(),
            'recommendations' => []
        ];
        
        // Determine overall health
        $issues = 0;
        
        if (!$results['connectivity']['connected']) {
            $issues++;
            $results['recommendations'][] = 'Fix database connection issues';
        }
        
        if (!$results['read_operations']['can_read']) {
            $issues++;
            $results['recommendations'][] = 'Resolve database read permission issues';
        }
        
        if (!$results['write_operations']['can_write']) {
            $issues++;
            $results['recommendations'][] = 'Resolve database write permission issues';
        }
        
        if (!$results['read_operations']['can_query_custom_tables']) {
            $results['recommendations'][] = 'Run database migration to create missing TMU tables';
        }
        
        // Set overall health status
        if ($issues === 0) {
            $results['overall_health'] = 'excellent';
        } elseif ($issues === 1) {
            $results['overall_health'] = 'good';
        } elseif ($issues === 2) {
            $results['overall_health'] = 'fair';
        } else {
            $results['overall_health'] = 'poor';
        }
        
        return $results;
    }
    
    /**
     * Get database performance metrics
     * 
     * @return array Performance metrics
     */
    public function get_performance_metrics(): array {
        global $wpdb;
        
        $metrics = [
            'query_count' => get_num_queries(),
            'slow_queries' => [],
            'connection_time' => 0,
            'avg_query_time' => 0
        ];
        
        // Test connection time
        $start_time = microtime(true);
        $wpdb->get_var("SELECT 1");
        $metrics['connection_time'] = microtime(true) - $start_time;
        
        // Test query performance
        $test_queries = [
            'posts_count' => "SELECT COUNT(*) FROM {$wpdb->posts}",
            'options_query' => "SELECT option_name FROM {$wpdb->options} LIMIT 10",
            'meta_query' => "SELECT meta_key FROM {$wpdb->postmeta} LIMIT 10"
        ];
        
        $total_time = 0;
        foreach ($test_queries as $name => $query) {
            $start = microtime(true);
            $wpdb->get_results($query);
            $duration = microtime(true) - $start;
            $total_time += $duration;
            
            if ($duration > 1.0) { // Queries taking more than 1 second
                $metrics['slow_queries'][] = [
                    'name' => $name,
                    'query' => $query,
                    'duration' => $duration
                ];
            }
        }
        
        $metrics['avg_query_time'] = $total_time / count($test_queries);
        
        return $metrics;
    }
}