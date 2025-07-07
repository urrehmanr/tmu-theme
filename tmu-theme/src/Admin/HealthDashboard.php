<?php
namespace TMU\Admin;

class HealthDashboard {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_tmu_health_check', [$this, 'ajax_health_check']);
        add_action('wp_ajax_tmu_performance_data', [$this, 'ajax_performance_data']);
    }
    
    public function add_admin_menu(): void {
        add_submenu_page(
            'tmu-settings',
            'System Health',
            'System Health',
            'manage_options',
            'tmu-health',
            [$this, 'render_health_page']
        );
    }
    
    public function render_health_page(): void {
        ?>
        <div class="wrap">
            <h1>TMU System Health</h1>
            
            <div id="tmu-health-dashboard">
                <div class="health-cards">
                    <div class="health-card" id="database-health">
                        <h3>Database Health</h3>
                        <div class="health-status loading">Checking...</div>
                    </div>
                    
                    <div class="health-card" id="cache-health">
                        <h3>Cache Performance</h3>
                        <div class="health-status loading">Checking...</div>
                    </div>
                    
                    <div class="health-card" id="api-health">
                        <h3>TMDB API Status</h3>
                        <div class="health-status loading">Checking...</div>
                    </div>
                    
                    <div class="health-card" id="performance-health">
                        <h3>Performance Metrics</h3>
                        <div class="health-status loading">Checking...</div>
                    </div>
                </div>
                
                <div class="health-charts">
                    <div class="chart-container">
                        <h3>Performance Over Time</h3>
                        <canvas id="performance-chart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h3>Error Rate</h3>
                        <canvas id="error-chart"></canvas>
                    </div>
                </div>
                
                <div class="health-logs">
                    <h3>Recent Events</h3>
                    <div id="recent-events"></div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize health dashboard
            TMUHealthDashboard.init();
        });
        </script>
        <?php
    }
    
    public function ajax_health_check(): void {
        check_ajax_referer('tmu_health_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $health_data = [
            'database' => $this->check_database_health(),
            'cache' => $this->check_cache_health(),
            'api' => $this->check_api_health(),
            'performance' => $this->check_performance_health(),
            'errors' => $this->get_recent_errors()
        ];
        
        wp_send_json_success($health_data);
    }
    
    private function check_database_health(): array {
        global $wpdb;
        
        $start_time = microtime(true);
        $tables = [
            $wpdb->prefix . 'tmu_movies',
            $wpdb->prefix . 'tmu_tv_series',
            $wpdb->prefix . 'tmu_dramas',
            $wpdb->prefix . 'tmu_people'
        ];
        
        $health = ['status' => 'healthy', 'details' => []];
        
        foreach ($tables as $table) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
            $health['details'][$table] = [
                'count' => $count,
                'status' => $count !== null ? 'ok' : 'error'
            ];
            
            if ($count === null) {
                $health['status'] = 'unhealthy';
            }
        }
        
        $health['response_time'] = microtime(true) - $start_time;
        
        return $health;
    }
    
    private function check_cache_health(): array {
        $test_key = 'tmu_cache_test_' . time();
        $test_value = 'test_value_' . rand(1000, 9999);
        
        $start_time = microtime(true);
        
        // Test cache set
        wp_cache_set($test_key, $test_value, 'tmu_health', 60);
        
        // Test cache get
        $cached_value = wp_cache_get($test_key, 'tmu_health');
        
        $response_time = microtime(true) - $start_time;
        
        return [
            'status' => $cached_value === $test_value ? 'healthy' : 'unhealthy',
            'response_time' => $response_time,
            'cache_hit' => $cached_value === $test_value
        ];
    }
    
    private function check_api_health(): array {
        $tmdb_key = get_option('tmu_tmdb_api_key');
        
        if (!$tmdb_key) {
            return ['status' => 'unconfigured', 'message' => 'TMDB API key not set'];
        }
        
        $start_time = microtime(true);
        $response = wp_remote_get("https://api.themoviedb.org/3/configuration?api_key={$tmdb_key}");
        $response_time = microtime(true) - $start_time;
        
        if (is_wp_error($response)) {
            return [
                'status' => 'unhealthy',
                'message' => $response->get_error_message(),
                'response_time' => $response_time
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        return [
            'status' => $status_code === 200 ? 'healthy' : 'unhealthy',
            'status_code' => $status_code,
            'response_time' => $response_time
        ];
    }
    
    private function check_performance_health(): array {
        global $wpdb;
        
        // Get average response times from last 24 hours
        $performance_data = $wpdb->get_results(
            "SELECT 
                AVG(response_time) as avg_response_time,
                MAX(response_time) as max_response_time,
                COUNT(*) as request_count
             FROM {$wpdb->prefix}tmu_performance_logs 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        
        if (empty($performance_data)) {
            return ['status' => 'no_data', 'message' => 'No performance data available'];
        }
        
        $data = $performance_data[0];
        
        return [
            'status' => $data->avg_response_time < 2 ? 'healthy' : 'warning',
            'avg_response_time' => $data->avg_response_time,
            'max_response_time' => $data->max_response_time,
            'request_count' => $data->request_count
        ];
    }
    
    private function get_recent_errors(): array {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}tmu_error_logs 
             ORDER BY timestamp DESC 
             LIMIT 10"
        );
    }
}