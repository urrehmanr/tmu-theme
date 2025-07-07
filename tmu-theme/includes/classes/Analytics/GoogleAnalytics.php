<?php
/**
 * Google Analytics Integration
 * 
 * GA4 integration for the TMU theme.
 * 
 * @package TMU\Analytics
 * @since 1.0.0
 */

namespace TMU\Analytics;

class GoogleAnalytics {
    
    /**
     * GA4 Measurement ID
     * @var string
     */
    private $measurement_id;
    
    /**
     * GA4 API Secret
     * @var string
     */
    private $api_secret;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->measurement_id = get_option('tmu_ga4_measurement_id', '');
        $this->api_secret = get_option('tmu_ga4_api_secret', '');
        
        add_action('wp_head', [$this, 'add_gtag_script']);
        add_action('wp_footer', [$this, 'add_custom_events']);
        add_action('wp_ajax_tmu_send_ga_event', [$this, 'send_server_side_event']);
        add_action('wp_ajax_nopriv_tmu_send_ga_event', [$this, 'send_server_side_event']);
    }
    
    /**
     * Add Google Analytics gtag script
     */
    public function add_gtag_script(): void {
        if (empty($this->measurement_id)) {
            return;
        }
        
        // Skip analytics for admin users if configured
        if (current_user_can('manage_options') && get_option('tmu_ga4_exclude_admin', false)) {
            return;
        }
        
        ?>
        <!-- Google Analytics GA4 -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($this->measurement_id); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            
            gtag('config', '<?php echo esc_js($this->measurement_id); ?>', {
                'anonymize_ip': <?php echo get_option('tmu_ga4_anonymize_ip', true) ? 'true' : 'false'; ?>,
                'allow_google_signals': <?php echo get_option('tmu_ga4_allow_signals', true) ? 'true' : 'false'; ?>,
                'allow_ad_personalization_signals': <?php echo get_option('tmu_ga4_allow_ads', false) ? 'true' : 'false'; ?>
            });
            
            // Custom dimensions
            <?php $this->output_custom_dimensions(); ?>
        </script>
        <?php
    }
    
    /**
     * Output custom dimensions
     */
    private function output_custom_dimensions(): void {
        $custom_dimensions = [
            'user_type' => is_user_logged_in() ? 'logged_in' : 'guest',
            'post_type' => get_post_type() ?: 'none',
            'content_group' => $this->get_content_group(),
            'user_role' => $this->get_user_role()
        ];
        
        foreach ($custom_dimensions as $key => $value) {
            if (!empty($value)) {
                echo "gtag('config', '" . esc_js($this->measurement_id) . "', {
                    'custom_map.{$key}': '" . esc_js($value) . "'
                });\n";
            }
        }
    }
    
    /**
     * Get content group for current page
     */
    private function get_content_group(): string {
        if (is_home() || is_front_page()) {
            return 'home';
        } elseif (is_single()) {
            $post_type = get_post_type();
            if (in_array($post_type, ['movie', 'tv', 'drama'])) {
                return $post_type . '_detail';
            }
            return 'content';
        } elseif (is_archive()) {
            return 'archive';
        } elseif (is_search()) {
            return 'search';
        }
        
        return 'other';
    }
    
    /**
     * Get user role
     */
    private function get_user_role(): string {
        if (!is_user_logged_in()) {
            return 'guest';
        }
        
        $user = wp_get_current_user();
        return !empty($user->roles) ? $user->roles[0] : 'user';
    }
    
    /**
     * Add custom events
     */
    public function add_custom_events(): void {
        if (empty($this->measurement_id)) {
            return;
        }
        
        ?>
        <script>
        // Track TMU-specific events
        document.addEventListener('DOMContentLoaded', function() {
            
            // Track movie/TV show interactions
            document.addEventListener('click', function(e) {
                const target = e.target.closest('[data-ga-event]');
                if (target) {
                    const eventName = target.dataset.gaEvent;
                    const eventParams = {
                        'event_category': target.dataset.gaCategory || 'interaction',
                        'event_label': target.dataset.gaLabel || '',
                        'value': target.dataset.gaValue || ''
                    };
                    
                    // Add content-specific parameters
                    if (target.dataset.contentType) {
                        eventParams.content_type = target.dataset.contentType;
                    }
                    if (target.dataset.contentId) {
                        eventParams.content_id = target.dataset.contentId;
                    }
                    
                    gtag('event', eventName, eventParams);
                }
            });
            
            // Track search events
            const searchForms = document.querySelectorAll('.tmu-search-form');
            searchForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const query = form.querySelector('input[type="search"]').value;
                    gtag('event', 'search', {
                        'search_term': query,
                        'content_type': 'movie_tv_search'
                    });
                });
            });
            
            // Track video engagement
            document.addEventListener('play', function(e) {
                if (e.target.tagName === 'VIDEO') {
                    gtag('event', 'video_start', {
                        'event_category': 'video',
                        'event_label': e.target.src || 'unknown',
                        'content_type': 'trailer'
                    });
                }
            }, true);
            
            // Track scroll depth
            let maxScroll = 0;
            const milestones = [25, 50, 75, 100];
            const trackedMilestones = new Set();
            
            window.addEventListener('scroll', function() {
                const scrollPercent = Math.round(
                    (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100
                );
                
                if (scrollPercent > maxScroll) {
                    maxScroll = scrollPercent;
                    
                    milestones.forEach(milestone => {
                        if (scrollPercent >= milestone && !trackedMilestones.has(milestone)) {
                            trackedMilestones.add(milestone);
                            gtag('event', 'scroll', {
                                'event_category': 'engagement',
                                'event_label': milestone + '%',
                                'value': milestone
                            });
                        }
                    });
                }
            });
            
            // Track page timing
            window.addEventListener('load', function() {
                setTimeout(() => {
                    const timing = performance.timing;
                    const loadTime = timing.loadEventEnd - timing.navigationStart;
                    
                    gtag('event', 'timing_complete', {
                        'name': 'page_load_time',
                        'value': Math.round(loadTime)
                    });
                }, 1000);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Send server-side event to GA4
     */
    public function send_server_side_event(): void {
        if (empty($this->measurement_id) || empty($this->api_secret)) {
            wp_send_json_error('GA4 not configured');
        }
        
        $client_id = sanitize_text_field($_POST['client_id'] ?? '');
        $event_name = sanitize_text_field($_POST['event_name'] ?? '');
        $event_parameters = array_map('sanitize_text_field', $_POST['event_parameters'] ?? []);
        
        if (empty($client_id) || empty($event_name)) {
            wp_send_json_error('Missing required parameters');
        }
        
        $payload = [
            'client_id' => $client_id,
            'events' => [
                [
                    'name' => $event_name,
                    'params' => $event_parameters
                ]
            ]
        ];
        
        $url = sprintf(
            'https://www.google-analytics.com/mp/collect?measurement_id=%s&api_secret=%s',
            $this->measurement_id,
            $this->api_secret
        );
        
        $response = wp_remote_post($url, [
            'body' => json_encode($payload),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 10
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        wp_send_json_success(['status' => 'Event sent']);
    }
    
    /**
     * Track custom conversion events
     */
    public function track_conversion($event_name, $parameters = []): void {
        if (empty($this->measurement_id)) {
            return;
        }
        
        $default_params = [
            'currency' => 'USD',
            'value' => 0
        ];
        
        $parameters = array_merge($default_params, $parameters);
        
        // Queue event for frontend tracking
        if (!isset($GLOBALS['tmu_ga_events'])) {
            $GLOBALS['tmu_ga_events'] = [];
            add_action('wp_footer', [$this, 'output_queued_events'], 999);
        }
        
        $GLOBALS['tmu_ga_events'][] = [
            'name' => $event_name,
            'parameters' => $parameters
        ];
    }
    
    /**
     * Output queued events
     */
    public function output_queued_events(): void {
        if (empty($GLOBALS['tmu_ga_events'])) {
            return;
        }
        
        echo '<script>';
        foreach ($GLOBALS['tmu_ga_events'] as $event) {
            echo sprintf(
                "gtag('event', '%s', %s);\n",
                esc_js($event['name']),
                json_encode($event['parameters'])
            );
        }
        echo '</script>';
    }
    
    /**
     * Track ecommerce events (for premium content)
     */
    public function track_purchase($transaction_id, $items, $value = 0): void {
        $this->track_conversion('purchase', [
            'transaction_id' => $transaction_id,
            'value' => $value,
            'currency' => 'USD',
            'items' => $items
        ]);
    }
    
    /**
     * Track content engagement
     */
    public function track_content_engagement($content_type, $content_id, $action): void {
        $this->track_conversion('content_engagement', [
            'content_type' => $content_type,
            'content_id' => $content_id,
            'action' => $action
        ]);
    }
    
    /**
     * Get GA4 reporting data
     */
    public function get_analytics_data($start_date, $end_date, $metrics = []): array {
        // This would require Google Analytics Reporting API v4
        // For now, return placeholder data
        return [
            'pageviews' => 0,
            'sessions' => 0,
            'users' => 0,
            'bounce_rate' => 0,
            'session_duration' => 0
        ];
    }
    
    /**
     * Setup enhanced ecommerce tracking
     */
    public function setup_enhanced_ecommerce(): void {
        if (empty($this->measurement_id)) {
            return;
        }
        
        add_action('wp_footer', function() {
            ?>
            <script>
            // Enhanced ecommerce tracking for TMU content
            window.tmuEcommerce = {
                trackPurchase: function(transactionId, items, value) {
                    gtag('event', 'purchase', {
                        'transaction_id': transactionId,
                        'value': value,
                        'currency': 'USD',
                        'items': items
                    });
                },
                
                trackAddToCart: function(item) {
                    gtag('event', 'add_to_cart', {
                        'currency': 'USD',
                        'value': item.value || 0,
                        'items': [item]
                    });
                },
                
                trackViewItem: function(item) {
                    gtag('event', 'view_item', {
                        'currency': 'USD',
                        'value': item.value || 0,
                        'items': [item]
                    });
                }
            };
            </script>
            <?php
        });
    }
    
    /**
     * Generate GA4 client ID
     */
    public function generate_client_id(): string {
        return sprintf(
            '%d.%d',
            rand(100000000, 999999999),
            time()
        );
    }
    
    /**
     * Validate GA4 configuration
     */
    public function validate_configuration(): array {
        $errors = [];
        
        if (empty($this->measurement_id)) {
            $errors[] = 'GA4 Measurement ID is not set';
        } elseif (!preg_match('/^G-[A-Z0-9]{10}$/', $this->measurement_id)) {
            $errors[] = 'GA4 Measurement ID format is invalid';
        }
        
        if (empty($this->api_secret)) {
            $errors[] = 'GA4 API Secret is not set';
        }
        
        return $errors;
    }
    
    /**
     * Get configuration status
     */
    public function get_status(): array {
        return [
            'configured' => !empty($this->measurement_id),
            'measurement_id' => $this->measurement_id,
            'api_secret_set' => !empty($this->api_secret),
            'errors' => $this->validate_configuration()
        ];
    }
}