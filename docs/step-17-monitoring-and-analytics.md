# Step 17: Monitoring and Analytics

## Overview
This step implements comprehensive monitoring, analytics, and observability for the TMU theme, providing real-time insights into performance, user behavior, errors, and system health.

## 1. Application Performance Monitoring (APM)

### 1.1 Performance Tracking
```php
// src/Monitoring/PerformanceTracker.php
<?php
namespace TMU\Monitoring;

class PerformanceTracker {
    private $metrics = [];
    private $start_time;
    private $checkpoints = [];
    
    public function __construct() {
        $this->start_time = microtime(true);
        add_action('init', [$this, 'init_tracking']);
        add_action('wp_footer', [$this, 'send_metrics']);
    }
    
    public function init_tracking(): void {
        // Track WordPress core performance
        add_action('wp_head', [$this, 'checkpoint'], 1);
        add_action('wp_footer', [$this, 'checkpoint'], 999);
        
        // Track custom post type queries
        add_filter('posts_pre_query', [$this, 'track_query_start'], 10, 2);
        add_filter('the_posts', [$this, 'track_query_end'], 10, 2);
        
        // Track TMDB API calls
        add_action('tmu_tmdb_api_call_start', [$this, 'track_api_start']);
        add_action('tmu_tmdb_api_call_end', [$this, 'track_api_end']);
    }
    
    public function checkpoint($name = null): void {
        $name = $name ?: current_action();
        $this->checkpoints[$name] = [
            'time' => microtime(true) - $this->start_time,
            'memory' => memory_get_usage(),
            'queries' => get_num_queries()
        ];
    }
    
    public function track_query_start($posts, $query): ?array {
        if ($query->get('post_type') && in_array($query->get('post_type'), ['movie', 'tv', 'drama', 'people'])) {
            $this->metrics['query_start'] = microtime(true);
        }
        return $posts;
    }
    
    public function track_query_end($posts, $query): array {
        if (isset($this->metrics['query_start']) && $query->get('post_type')) {
            $duration = microtime(true) - $this->metrics['query_start'];
            $this->metrics['custom_queries'][] = [
                'post_type' => $query->get('post_type'),
                'duration' => $duration,
                'count' => count($posts),
                'memory' => memory_get_usage()
            ];
            unset($this->metrics['query_start']);
        }
        return $posts;
    }
    
    public function track_api_start($endpoint): void {
        $this->metrics['api_calls'][$endpoint]['start'] = microtime(true);
    }
    
    public function track_api_end($endpoint, $response_code, $cache_hit = false): void {
        if (isset($this->metrics['api_calls'][$endpoint]['start'])) {
            $duration = microtime(true) - $this->metrics['api_calls'][$endpoint]['start'];
            $this->metrics['api_calls'][$endpoint] = [
                'duration' => $duration,
                'response_code' => $response_code,
                'cache_hit' => $cache_hit,
                'timestamp' => current_time('c')
            ];
        }
    }
    
    public function send_metrics(): void {
        $this->metrics['total_time'] = microtime(true) - $this->start_time;
        $this->metrics['peak_memory'] = memory_get_peak_usage();
        $this->metrics['total_queries'] = get_num_queries();
        $this->metrics['url'] = $_SERVER['REQUEST_URI'] ?? '';
        $this->metrics['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $this->metrics['checkpoints'] = $this->checkpoints;
        
        // Send to monitoring service
        $this->send_to_monitoring_service();
        
        // Log slow requests
        if ($this->metrics['total_time'] > 2) {
            error_log('TMU Slow Request: ' . json_encode($this->metrics));
        }
    }
    
    private function send_to_monitoring_service(): void {
        $monitoring_config = get_option('tmu_monitoring_config', []);
        
        if (!empty($monitoring_config['webhook_url'])) {
            wp_remote_post($monitoring_config['webhook_url'], [
                'body' => json_encode([
                    'service' => 'tmu-theme',
                    'environment' => wp_get_environment_type(),
                    'metrics' => $this->metrics,
                    'timestamp' => current_time('c')
                ]),
                'headers' => ['Content-Type' => 'application/json'],
                'timeout' => 5,
                'blocking' => false
            ]);
        }
    }
}
```

### 1.2 Error Tracking
```php
// src/Monitoring/ErrorTracker.php
<?php
namespace TMU\Monitoring;

class ErrorTracker {
    private $errors = [];
    
    public function __construct() {
        add_action('init', [$this, 'init_error_tracking']);
        add_action('wp_footer', [$this, 'report_errors']);
    }
    
    public function init_error_tracking(): void {
        set_error_handler([$this, 'handle_error']);
        set_exception_handler([$this, 'handle_exception']);
        register_shutdown_function([$this, 'handle_fatal_error']);
        
        // Track WordPress errors
        add_action('wp_die_handler', [$this, 'track_wp_die']);
        add_filter('wp_die_ajax_handler', [$this, 'track_ajax_error']);
    }
    
    public function handle_error($severity, $message, $file, $line): bool {
        // Only track errors in TMU theme files
        if (strpos($file, get_template_directory()) !== false) {
            $this->errors[] = [
                'type' => 'error',
                'severity' => $severity,
                'message' => $message,
                'file' => $file,
                'line' => $line,
                'timestamp' => current_time('c'),
                'url' => $_SERVER['REQUEST_URI'] ?? '',
                'user_id' => get_current_user_id()
            ];
        }
        
        return false; // Don't prevent default error handling
    }
    
    public function handle_exception($exception): void {
        $this->errors[] = [
            'type' => 'exception',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => current_time('c'),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_id' => get_current_user_id()
        ];
    }
    
    public function handle_fatal_error(): void {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            if (strpos($error['file'], get_template_directory()) !== false) {
                $this->errors[] = [
                    'type' => 'fatal_error',
                    'message' => $error['message'],
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'timestamp' => current_time('c'),
                    'url' => $_SERVER['REQUEST_URI'] ?? '',
                    'user_id' => get_current_user_id()
                ];
                
                $this->report_errors();
            }
        }
    }
    
    public function report_errors(): void {
        if (!empty($this->errors)) {
            $error_config = get_option('tmu_error_tracking_config', []);
            
            if (!empty($error_config['webhook_url'])) {
                wp_remote_post($error_config['webhook_url'], [
                    'body' => json_encode([
                        'service' => 'tmu-theme',
                        'environment' => wp_get_environment_type(),
                        'errors' => $this->errors,
                        'timestamp' => current_time('c')
                    ]),
                    'headers' => ['Content-Type' => 'application/json'],
                    'timeout' => 10,
                    'blocking' => false
                ]);
            }
            
            // Log errors locally
            foreach ($this->errors as $error) {
                error_log('TMU Error: ' . json_encode($error));
            }
        }
    }
}
```

## 2. User Analytics and Behavior Tracking

### 2.1 User Behavior Analytics
```php
// src/Analytics/UserAnalytics.php
<?php
namespace TMU\Analytics;

class UserAnalytics {
    private $events = [];
    
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_analytics_scripts']);
        add_action('wp_ajax_tmu_track_event', [$this, 'track_event']);
        add_action('wp_ajax_nopriv_tmu_track_event', [$this, 'track_event']);
    }
    
    public function enqueue_analytics_scripts(): void {
        wp_enqueue_script(
            'tmu-analytics',
            get_template_directory_uri() . '/assets/js/analytics.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('tmu-analytics', 'tmu_analytics', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmu_analytics_nonce'),
            'user_id' => get_current_user_id(),
            'page_type' => $this->get_page_type(),
            'content_id' => get_queried_object_id()
        ]);
    }
    
    public function track_event(): void {
        if (!wp_verify_nonce($_POST['nonce'], 'tmu_analytics_nonce')) {
            wp_die('Security check failed');
        }
        
        $event_data = [
            'event_type' => sanitize_text_field($_POST['event_type']),
            'event_data' => array_map('sanitize_text_field', $_POST['event_data']),
            'user_id' => get_current_user_id(),
            'session_id' => $this->get_session_id(),
            'timestamp' => current_time('c'),
            'url' => esc_url_raw($_POST['url']),
            'referrer' => esc_url_raw($_POST['referrer']),
            'user_agent' => sanitize_text_field($_POST['user_agent']),
            'screen_resolution' => sanitize_text_field($_POST['screen_resolution']),
            'viewport_size' => sanitize_text_field($_POST['viewport_size'])
        ];
        
        // Store in database
        $this->store_event($event_data);
        
        // Send to analytics service
        $this->send_to_analytics_service($event_data);
        
        wp_send_json_success();
    }
    
    private function store_event($event_data): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_analytics_events';
        
        $wpdb->insert($table_name, [
            'event_type' => $event_data['event_type'],
            'event_data' => json_encode($event_data['event_data']),
            'user_id' => $event_data['user_id'],
            'session_id' => $event_data['session_id'],
            'timestamp' => $event_data['timestamp'],
            'url' => $event_data['url'],
            'referrer' => $event_data['referrer'],
            'user_agent' => $event_data['user_agent'],
            'screen_resolution' => $event_data['screen_resolution'],
            'viewport_size' => $event_data['viewport_size']
        ]);
    }
    
    private function send_to_analytics_service($event_data): void {
        $analytics_config = get_option('tmu_analytics_config', []);
        
        if (!empty($analytics_config['webhook_url'])) {
            wp_remote_post($analytics_config['webhook_url'], [
                'body' => json_encode($event_data),
                'headers' => ['Content-Type' => 'application/json'],
                'timeout' => 5,
                'blocking' => false
            ]);
        }
    }
    
    private function get_page_type(): string {
        if (is_home() || is_front_page()) {
            return 'home';
        } elseif (is_single()) {
            return 'single_' . get_post_type();
        } elseif (is_archive()) {
            return 'archive_' . get_post_type();
        } elseif (is_search()) {
            return 'search';
        } else {
            return 'other';
        }
    }
    
    private function get_session_id(): string {
        if (!isset($_COOKIE['tmu_session_id'])) {
            $session_id = wp_generate_uuid4();
            setcookie('tmu_session_id', $session_id, time() + (30 * 24 * 60 * 60), '/');
            return $session_id;
        }
        
        return $_COOKIE['tmu_session_id'];
    }
}
```

### 2.2 Frontend Analytics Script
```javascript
// assets/js/analytics.js
class TMUAnalytics {
    constructor() {
        this.events = [];
        this.config = window.tmu_analytics || {};
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.trackPageView();
        this.startSessionTracking();
    }
    
    setupEventListeners() {
        // Track content interactions
        document.addEventListener('click', (e) => {
            const target = e.target.closest('[data-track]');
            if (target) {
                this.trackEvent('click', {
                    element: target.tagName.toLowerCase(),
                    content_type: target.dataset.contentType || '',
                    content_id: target.dataset.contentId || '',
                    position: this.getElementPosition(target),
                    text: target.textContent.trim().substring(0, 100)
                });
            }
        });
        
        // Track video plays
        document.addEventListener('play', (e) => {
            if (e.target.tagName === 'VIDEO') {
                this.trackEvent('video_play', {
                    src: e.target.src,
                    duration: e.target.duration,
                    current_time: e.target.currentTime
                });
            }
        }, true);
        
        // Track search usage
        const searchForms = document.querySelectorAll('.tmu-search-form');
        searchForms.forEach(form => {
            form.addEventListener('submit', (e) => {
                const query = form.querySelector('input[type="search"]').value;
                this.trackEvent('search', {
                    query: query,
                    results_count: this.getSearchResultsCount()
                });
            });
        });
        
        // Track scroll depth
        this.trackScrollDepth();
        
        // Track time on page
        this.trackTimeOnPage();
    }
    
    trackEvent(eventType, eventData = {}) {
        const event = {
            event_type: eventType,
            event_data: eventData,
            url: window.location.href,
            referrer: document.referrer,
            user_agent: navigator.userAgent,
            screen_resolution: `${screen.width}x${screen.height}`,
            viewport_size: `${window.innerWidth}x${window.innerHeight}`,
            timestamp: new Date().toISOString()
        };
        
        this.events.push(event);
        this.sendEvent(event);
    }
    
    trackPageView() {
        this.trackEvent('page_view', {
            page_type: this.config.page_type,
            content_id: this.config.content_id,
            load_time: performance.timing.loadEventEnd - performance.timing.navigationStart
        });
    }
    
    trackScrollDepth() {
        let maxScroll = 0;
        const milestones = [25, 50, 75, 100];
        const trackedMilestones = new Set();
        
        window.addEventListener('scroll', () => {
            const scrollPercent = Math.round(
                (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100
            );
            
            if (scrollPercent > maxScroll) {
                maxScroll = scrollPercent;
                
                milestones.forEach(milestone => {
                    if (scrollPercent >= milestone && !trackedMilestones.has(milestone)) {
                        trackedMilestones.add(milestone);
                        this.trackEvent('scroll_depth', {
                            percentage: milestone,
                            max_scroll: maxScroll
                        });
                    }
                });
            }
        });
    }
    
    trackTimeOnPage() {
        const startTime = Date.now();
        
        const trackTime = () => {
            const timeOnPage = Math.round((Date.now() - startTime) / 1000);
            this.trackEvent('time_on_page', {
                seconds: timeOnPage,
                minutes: Math.round(timeOnPage / 60)
            });
        };
        
        // Track time intervals
        setTimeout(() => trackTime(), 30000); // 30 seconds
        setTimeout(() => trackTime(), 60000); // 1 minute
        setTimeout(() => trackTime(), 300000); // 5 minutes
        
        // Track on page unload
        window.addEventListener('beforeunload', trackTime);
    }
    
    startSessionTracking() {
        // Track session start
        if (!sessionStorage.getItem('tmu_session_started')) {
            this.trackEvent('session_start', {
                landing_page: window.location.href,
                referrer: document.referrer
            });
            sessionStorage.setItem('tmu_session_started', 'true');
        }
        
        // Track session end
        window.addEventListener('beforeunload', () => {
            this.trackEvent('session_end', {
                session_duration: Date.now() - parseInt(sessionStorage.getItem('tmu_session_start') || Date.now()),
                pages_viewed: parseInt(sessionStorage.getItem('tmu_pages_viewed') || 0) + 1
            });
        });
    }
    
    sendEvent(event) {
        fetch(this.config.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tmu_track_event',
                nonce: this.config.nonce,
                ...event
            })
        }).catch(error => {
            console.error('Analytics tracking error:', error);
        });
    }
    
    getElementPosition(element) {
        const rect = element.getBoundingClientRect();
        return {
            x: rect.left + window.scrollX,
            y: rect.top + window.scrollY,
            viewport_x: rect.left,
            viewport_y: rect.top
        };
    }
    
    getSearchResultsCount() {
        const resultsContainer = document.querySelector('.tmu-search-results');
        if (resultsContainer) {
            const results = resultsContainer.querySelectorAll('.tmu-search-result');
            return results.length;
        }
        return 0;
    }
}

// Initialize analytics when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.tmuAnalytics = new TMUAnalytics();
});
```

## 3. System Health Monitoring

### 3.1 Health Dashboard
```php
// src/Admin/HealthDashboard.php
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
```

## 4. Real-time Monitoring Dashboard

### 4.1 Dashboard JavaScript
```javascript
// assets/js/health-dashboard.js
class TMUHealthDashboard {
    constructor() {
        this.charts = {};
        this.updateInterval = 30000; // 30 seconds
        this.init();
    }
    
    init() {
        this.setupCharts();
        this.loadInitialData();
        this.startAutoRefresh();
    }
    
    setupCharts() {
        // Performance chart
        const performanceCtx = document.getElementById('performance-chart').getContext('2d');
        this.charts.performance = new Chart(performanceCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Response Time (ms)',
                    data: [],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Response Time (ms)'
                        }
                    }
                }
            }
        });
        
        // Error rate chart
        const errorCtx = document.getElementById('error-chart').getContext('2d');
        this.charts.errors = new Chart(errorCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Errors per Hour',
                    data: [],
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgb(255, 99, 132)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Error Count'
                        }
                    }
                }
            }
        });
    }
    
    loadInitialData() {
        this.updateHealthCards();
        this.updateCharts();
        this.updateRecentEvents();
    }
    
    updateHealthCards() {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tmu_health_check',
                nonce: tmu_admin.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.renderHealthCards(response.data);
                }
            },
            error: (xhr, status, error) => {
                console.error('Health check failed:', error);
            }
        });
    }
    
    renderHealthCards(data) {
        // Database health
        const dbCard = document.getElementById('database-health');
        const dbStatus = dbCard.querySelector('.health-status');
        dbStatus.className = `health-status ${data.database.status}`;
        dbStatus.innerHTML = `
            <span class="status-indicator"></span>
            <span class="status-text">${data.database.status}</span>
            <span class="status-details">${data.database.response_time.toFixed(3)}s</span>
        `;
        
        // Cache health
        const cacheCard = document.getElementById('cache-health');
        const cacheStatus = cacheCard.querySelector('.health-status');
        cacheStatus.className = `health-status ${data.cache.status}`;
        cacheStatus.innerHTML = `
            <span class="status-indicator"></span>
            <span class="status-text">${data.cache.status}</span>
            <span class="status-details">${data.cache.response_time.toFixed(3)}s</span>
        `;
        
        // API health
        const apiCard = document.getElementById('api-health');
        const apiStatus = apiCard.querySelector('.health-status');
        apiStatus.className = `health-status ${data.api.status}`;
        apiStatus.innerHTML = `
            <span class="status-indicator"></span>
            <span class="status-text">${data.api.status}</span>
            <span class="status-details">${data.api.response_time ? data.api.response_time.toFixed(3) + 's' : ''}</span>
        `;
        
        // Performance health
        const perfCard = document.getElementById('performance-health');
        const perfStatus = perfCard.querySelector('.health-status');
        perfStatus.className = `health-status ${data.performance.status}`;
        perfStatus.innerHTML = `
            <span class="status-indicator"></span>
            <span class="status-text">${data.performance.status}</span>
            <span class="status-details">Avg: ${data.performance.avg_response_time ? data.performance.avg_response_time.toFixed(3) + 's' : 'N/A'}</span>
        `;
    }
    
    updateCharts() {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tmu_performance_data',
                nonce: tmu_admin.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.renderCharts(response.data);
                }
            }
        });
    }
    
    renderCharts(data) {
        // Update performance chart
        if (data.performance) {
            this.charts.performance.data.labels = data.performance.labels;
            this.charts.performance.data.datasets[0].data = data.performance.data;
            this.charts.performance.update();
        }
        
        // Update error chart
        if (data.errors) {
            this.charts.errors.data.labels = data.errors.labels;
            this.charts.errors.data.datasets[0].data = data.errors.data;
            this.charts.errors.update();
        }
    }
    
    updateRecentEvents() {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tmu_recent_events',
                nonce: tmu_admin.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.renderRecentEvents(response.data);
                }
            }
        });
    }
    
    renderRecentEvents(events) {
        const container = document.getElementById('recent-events');
        container.innerHTML = events.map(event => `
            <div class="event-item ${event.type}">
                <span class="event-time">${new Date(event.timestamp).toLocaleString()}</span>
                <span class="event-type">${event.type}</span>
                <span class="event-message">${event.message}</span>
            </div>
        `).join('');
    }
    
    startAutoRefresh() {
        setInterval(() => {
            this.updateHealthCards();
            this.updateCharts();
            this.updateRecentEvents();
        }, this.updateInterval);
    }
}

// Initialize dashboard
window.TMUHealthDashboard = new TMUHealthDashboard();
```

## Success Metrics

- **System Uptime**: > 99.9%
- **Average Response Time**: < 1 second
- **Error Rate**: < 0.1%
- **Cache Hit Rate**: > 80%
- **TMDB API Success Rate**: > 99%
- **User Engagement**: Tracked and analyzed
- **Performance Regression Detection**: Automated alerts
- **Health Check Success Rate**: > 99%

This comprehensive monitoring and analytics system provides complete visibility into the TMU theme's performance, user behavior, and system health.