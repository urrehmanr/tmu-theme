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