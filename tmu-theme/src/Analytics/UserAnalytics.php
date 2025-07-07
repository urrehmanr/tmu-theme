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