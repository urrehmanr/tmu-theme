<?php
/**
 * CDN Manager - Content Delivery Network Integration
 *
 * @package TMU\Performance
 * @version 1.0.0
 */

namespace TMU\Performance;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CDN Integration and Management
 */
class CDNManager {
    
    /**
     * CDN URL
     */
    private string $cdn_url;
    
    /**
     * CDN enabled status
     */
    private bool $cdn_enabled;
    
    /**
     * Supported CDN providers
     */
    private array $cdn_providers = [
        'cloudflare',
        'aws_cloudfront',
        'maxcdn',
        'custom'
    ];
    
    /**
     * Current CDN provider
     */
    private string $cdn_provider;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->cdn_url = get_option('tmu_cdn_url', '');
        $this->cdn_enabled = get_option('tmu_cdn_enabled', false);
        $this->cdn_provider = get_option('tmu_cdn_provider', 'custom');
        
        $this->init();
    }
    
    /**
     * Initialize CDN manager
     */
    public function init(): void {
        if (!$this->cdn_enabled || empty($this->cdn_url)) {
            return;
        }
        
        // Hook into WordPress attachment URLs
        add_filter('wp_get_attachment_url', [$this, 'use_cdn_for_attachments'], 10, 2);
        add_filter('wp_get_attachment_image_src', [$this, 'use_cdn_for_attachment_images'], 10, 4);
        
        // Hook into TMDB image URLs
        add_filter('tmu_tmdb_image_url', [$this, 'use_cdn_for_tmdb_images'], 10, 2);
        add_filter('tmu_poster_image_url', [$this, 'use_cdn_for_tmdb_images'], 10, 2);
        add_filter('tmu_backdrop_image_url', [$this, 'use_cdn_for_tmdb_images'], 10, 2);
        
        // Hook into content URLs
        add_filter('the_content', [$this, 'replace_content_urls']);
        
        // Admin hooks
        add_action('admin_init', [$this, 'register_cdn_settings']);
        add_action('admin_post_tmu_purge_cdn_cache', [$this, 'purge_cdn_cache']);
        
        // Automatic cache purging
        add_action('save_post', [$this, 'auto_purge_post_cache']);
        add_action('wp_update_nav_menu', [$this, 'auto_purge_menu_cache']);
    }
    
    /**
     * Use CDN for WordPress attachments
     */
    public function use_cdn_for_attachments($url, $attachment_id = null): string {
        if (!$this->should_use_cdn($url)) {
            return $url;
        }
        
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'];
        
        // Replace local upload URL with CDN URL
        if (strpos($url, $base_url) === 0) {
            $cdn_url = str_replace($base_url, $this->cdn_url, $url);
            
            // Add optimization parameters for supported CDN providers
            return $this->add_cdn_optimization_params($cdn_url, $attachment_id);
        }
        
        return $url;
    }
    
    /**
     * Use CDN for attachment image sources
     */
    public function use_cdn_for_attachment_images($image, $attachment_id, $size, $icon): array {
        if (!$image || !$this->should_use_cdn($image[0])) {
            return $image;
        }
        
        $image[0] = $this->use_cdn_for_attachments($image[0], $attachment_id);
        
        return $image;
    }
    
    /**
     * Use CDN for TMDB images
     */
    public function use_cdn_for_tmdb_images($url, $size = 'medium'): string {
        if (!$this->should_use_cdn_for_external($url)) {
            return $url;
        }
        
        // Create a proxy URL for TMDB images through CDN
        $encoded_url = base64_encode($url);
        $proxy_url = $this->cdn_url . '/tmdb-proxy/' . $encoded_url;
        
        // Add TMDB-specific optimization parameters
        return $this->add_tmdb_optimization_params($proxy_url, $size);
    }
    
    /**
     * Replace content URLs with CDN URLs
     */
    public function replace_content_urls($content): string {
        if (!$this->cdn_enabled) {
            return $content;
        }
        
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'];
        
        // Replace image URLs in content
        $content = str_replace($base_url, $this->cdn_url, $content);
        
        // Replace srcset URLs
        $content = preg_replace_callback(
            '/srcset=["\']([^"\']+)["\']/i',
            [$this, 'replace_srcset_urls'],
            $content
        );
        
        return $content;
    }
    
    /**
     * Replace srcset URLs callback
     */
    private function replace_srcset_urls($matches): string {
        $srcset = $matches[1];
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'];
        
        $updated_srcset = str_replace($base_url, $this->cdn_url, $srcset);
        
        return 'srcset="' . $updated_srcset . '"';
    }
    
    /**
     * Should use CDN for this URL
     */
    private function should_use_cdn($url): bool {
        // Don't use CDN in admin area
        if (is_admin()) {
            return false;
        }
        
        // Don't use CDN for external URLs (unless specifically configured)
        if (strpos($url, home_url()) !== 0) {
            return false;
        }
        
        // Don't use CDN for non-static files
        $file_extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        $static_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'css', 'js', 'svg', 'ico'];
        
        if (!in_array(strtolower($file_extension), $static_extensions)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Should use CDN for external images
     */
    private function should_use_cdn_for_external($url): bool {
        // Only proxy external images if specifically enabled
        $proxy_external = get_option('tmu_cdn_proxy_external', false);
        
        if (!$proxy_external) {
            return false;
        }
        
        // Check if it's a TMDB URL
        return strpos($url, 'image.tmdb.org') !== false;
    }
    
    /**
     * Add CDN optimization parameters
     */
    private function add_cdn_optimization_params($url, $attachment_id = null): string {
        switch ($this->cdn_provider) {
            case 'cloudflare':
                return $this->add_cloudflare_params($url, $attachment_id);
            case 'aws_cloudfront':
                return $this->add_cloudfront_params($url, $attachment_id);
            default:
                return $url;
        }
    }
    
    /**
     * Add Cloudflare optimization parameters
     */
    private function add_cloudflare_params($url, $attachment_id = null): string {
        $params = [];
        
        // Auto-optimize images
        $params['cf'] = 'auto';
        
        // WebP conversion if supported
        if ($this->browser_supports_webp()) {
            $params['format'] = 'webp';
        }
        
        // Quality optimization
        $params['quality'] = '85';
        
        return add_query_arg($params, $url);
    }
    
    /**
     * Add CloudFront optimization parameters
     */
    private function add_cloudfront_params($url, $attachment_id = null): string {
        // CloudFront optimization would depend on specific configuration
        // This is a placeholder for custom CloudFront parameters
        return $url;
    }
    
    /**
     * Add TMDB optimization parameters
     */
    private function add_tmdb_optimization_params($url, $size): string {
        $params = [
            'size' => $size,
            'quality' => '80',
            'cache' => '86400' // 24 hours
        ];
        
        if ($this->browser_supports_webp()) {
            $params['format'] = 'webp';
        }
        
        return add_query_arg($params, $url);
    }
    
    /**
     * Check if browser supports WebP
     */
    private function browser_supports_webp(): bool {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strpos($accept, 'image/webp') !== false;
    }
    
    /**
     * Register CDN settings
     */
    public function register_cdn_settings(): void {
        // CDN URL setting
        register_setting('tmu_performance', 'tmu_cdn_url', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw'
        ]);
        
        // CDN enabled setting
        register_setting('tmu_performance', 'tmu_cdn_enabled', [
            'type' => 'boolean',
            'default' => false
        ]);
        
        // CDN provider setting
        register_setting('tmu_performance', 'tmu_cdn_provider', [
            'type' => 'string',
            'default' => 'custom'
        ]);
        
        // Proxy external images setting
        register_setting('tmu_performance', 'tmu_cdn_proxy_external', [
            'type' => 'boolean',
            'default' => false
        ]);
    }
    
    /**
     * Purge CDN cache
     */
    public function purge_cdn_cache(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access', 'tmu'));
        }
        
        check_admin_referer('tmu_purge_cdn_cache');
        
        $purged = false;
        
        switch ($this->cdn_provider) {
            case 'cloudflare':
                $purged = $this->purge_cloudflare_cache();
                break;
            case 'aws_cloudfront':
                $purged = $this->purge_cloudfront_cache();
                break;
            default:
                // Custom CDN purging would be implemented here
                break;
        }
        
        $message = $purged ? 'cdn_cache_purged' : 'cdn_cache_purge_failed';
        wp_redirect(add_query_arg($message, '1', wp_get_referer()));
        exit;
    }
    
    /**
     * Purge Cloudflare cache
     */
    private function purge_cloudflare_cache(): bool {
        $zone_id = get_option('tmu_cloudflare_zone_id', '');
        $api_key = get_option('tmu_cloudflare_api_key', '');
        $email = get_option('tmu_cloudflare_email', '');
        
        if (empty($zone_id) || empty($api_key) || empty($email)) {
            return false;
        }
        
        $response = wp_remote_post("https://api.cloudflare.com/client/v4/zones/{$zone_id}/purge_cache", [
            'headers' => [
                'X-Auth-Email' => $email,
                'X-Auth-Key' => $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode(['purge_everything' => true])
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['success']) && $body['success'];
    }
    
    /**
     * Purge CloudFront cache
     */
    private function purge_cloudfront_cache(): bool {
        // CloudFront cache purging would require AWS SDK
        // This is a placeholder for CloudFront integration
        return false;
    }
    
    /**
     * Auto-purge post cache on save
     */
    public function auto_purge_post_cache($post_id): void {
        if (!$this->cdn_enabled) {
            return;
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return;
        }
        
        // Purge specific post-related cache
        $this->purge_post_specific_cache($post_id);
    }
    
    /**
     * Auto-purge menu cache
     */
    public function auto_purge_menu_cache(): void {
        if (!$this->cdn_enabled) {
            return;
        }
        
        // Purge navigation-related cache
        $this->purge_navigation_cache();
    }
    
    /**
     * Purge post-specific cache
     */
    private function purge_post_specific_cache($post_id): void {
        $urls_to_purge = [
            get_permalink($post_id),
            get_post_type_archive_link(get_post_type($post_id)),
            home_url()
        ];
        
        // Add image URLs associated with the post
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $urls_to_purge[] = wp_get_attachment_url($thumbnail_id);
        }
        
        $this->purge_specific_urls($urls_to_purge);
    }
    
    /**
     * Purge navigation cache
     */
    private function purge_navigation_cache(): void {
        $urls_to_purge = [
            home_url(),
            home_url('/sitemap.xml'),
            home_url('/robots.txt')
        ];
        
        $this->purge_specific_urls($urls_to_purge);
    }
    
    /**
     * Purge specific URLs
     */
    private function purge_specific_urls($urls): void {
        switch ($this->cdn_provider) {
            case 'cloudflare':
                $this->purge_cloudflare_urls($urls);
                break;
            case 'aws_cloudfront':
                $this->purge_cloudfront_urls($urls);
                break;
        }
    }
    
    /**
     * Purge specific Cloudflare URLs
     */
    private function purge_cloudflare_urls($urls): bool {
        $zone_id = get_option('tmu_cloudflare_zone_id', '');
        $api_key = get_option('tmu_cloudflare_api_key', '');
        $email = get_option('tmu_cloudflare_email', '');
        
        if (empty($zone_id) || empty($api_key) || empty($email)) {
            return false;
        }
        
        $response = wp_remote_post("https://api.cloudflare.com/client/v4/zones/{$zone_id}/purge_cache", [
            'headers' => [
                'X-Auth-Email' => $email,
                'X-Auth-Key' => $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode(['files' => array_values($urls)])
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['success']) && $body['success'];
    }
    
    /**
     * Purge specific CloudFront URLs
     */
    private function purge_cloudfront_urls($urls): bool {
        // CloudFront invalidation would require AWS SDK
        return false;
    }
    
    /**
     * Get CDN statistics
     */
    public function get_cdn_stats(): array {
        return [
            'cdn_enabled' => $this->cdn_enabled,
            'cdn_url' => $this->cdn_url,
            'cdn_provider' => $this->cdn_provider,
            'bandwidth_saved' => $this->calculate_bandwidth_saved(),
            'cache_hit_ratio' => $this->get_cache_hit_ratio(),
            'total_requests' => $this->get_total_cdn_requests()
        ];
    }
    
    /**
     * Calculate bandwidth saved
     */
    private function calculate_bandwidth_saved(): float {
        // This would require CDN provider API integration
        // Placeholder for bandwidth calculation
        return 0.0;
    }
    
    /**
     * Get cache hit ratio
     */
    private function get_cache_hit_ratio(): float {
        // This would require CDN provider API integration
        // Placeholder for cache hit ratio
        return 0.0;
    }
    
    /**
     * Get total CDN requests
     */
    private function get_total_cdn_requests(): int {
        // This would require CDN provider API integration
        // Placeholder for request count
        return 0;
    }
    
    /**
     * Get CDN URL
     */
    public function get_cdn_url(): string {
        return $this->cdn_url;
    }
    
    /**
     * Is CDN enabled
     */
    public function is_cdn_enabled(): bool {
        return $this->cdn_enabled;
    }
    
    /**
     * Get CDN provider
     */
    public function get_cdn_provider(): string {
        return $this->cdn_provider;
    }
}