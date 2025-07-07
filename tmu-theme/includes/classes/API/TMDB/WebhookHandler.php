<?php
namespace TMU\API\TMDB;

use TMU\API\TMDB\SyncService;
use TMU\API\TMDB\Exception as TMDBException;

/**
 * TMDB webhook handler for real-time updates
 */
class WebhookHandler {
    private $sync_service;
    private $webhook_secret;
    
    public function __construct() {
        $this->sync_service = new SyncService();
        $this->webhook_secret = get_option('tmu_tmdb_webhook_secret');
    }
    
    /**
     * Initialize webhook handler
     */
    public function init(): void {
        add_action('init', [$this, 'add_webhook_endpoint']);
        add_action('template_redirect', [$this, 'handle_webhook']);
    }
    
    /**
     * Add webhook endpoint
     */
    public function add_webhook_endpoint(): void {
        add_rewrite_rule(
            '^tmdb-webhook/?$',
            'index.php?tmdb_webhook=1',
            'top'
        );
        
        add_filter('query_vars', [$this, 'add_query_vars']);
    }
    
    /**
     * Add query variables
     */
    public function add_query_vars($vars): array {
        $vars[] = 'tmdb_webhook';
        return $vars;
    }
    
    /**
     * Handle webhook requests
     */
    public function handle_webhook(): void {
        if (!get_query_var('tmdb_webhook')) {
            return;
        }
        
        // Verify webhook is enabled
        if (!get_option('tmu_tmdb_webhooks_enabled', 0)) {
            wp_die('Webhooks not enabled', 403);
        }
        
        // Get request data
        $input = file_get_contents('php://input');
        $headers = getallheaders();
        
        try {
            // Verify webhook signature
            if (!$this->verify_webhook_signature($input, $headers)) {
                wp_die('Invalid webhook signature', 401);
            }
            
            // Parse webhook data
            $data = json_decode($input, true);
            if (!$data) {
                wp_die('Invalid JSON data', 400);
            }
            
            // Process webhook
            $this->process_webhook($data);
            
            // Return success response
            wp_send_json_success(['message' => 'Webhook processed successfully']);
            
        } catch (TMDBException $e) {
            error_log("TMDB Webhook error: " . $e->getMessage());
            wp_send_json_error(['message' => 'Webhook processing failed']);
        }
    }
    
    /**
     * Verify webhook signature
     */
    private function verify_webhook_signature(string $payload, array $headers): bool {
        if (!$this->webhook_secret) {
            return true; // Skip verification if no secret configured
        }
        
        $signature = $headers['X-TMU-Signature'] ?? '';
        if (!$signature) {
            return false;
        }
        
        $expected_signature = hash_hmac('sha256', $payload, $this->webhook_secret);
        return hash_equals($signature, $expected_signature);
    }
    
    /**
     * Process webhook data
     */
    private function process_webhook(array $data): void {
        $action = $data['action'] ?? '';
        $object_type = $data['object_type'] ?? '';
        $object_id = $data['object_id'] ?? 0;
        
        switch ($action) {
            case 'created':
                $this->handle_content_created($object_type, $object_id, $data);
                break;
            case 'updated':
                $this->handle_content_updated($object_type, $object_id, $data);
                break;
            case 'deleted':
                $this->handle_content_deleted($object_type, $object_id, $data);
                break;
            default:
                error_log("Unknown webhook action: {$action}");
        }
    }
    
    /**
     * Handle content creation
     */
    private function handle_content_created(string $object_type, int $object_id, array $data): void {
        // Check if we already have this content
        $existing_post = $this->find_existing_post($object_type, $object_id);
        
        if ($existing_post) {
            // Update existing post instead
            $this->handle_content_updated($object_type, $object_id, $data);
            return;
        }
        
        // Create new post based on webhook data
        $post_data = $this->prepare_post_data($object_type, $data);
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            throw new TMDBException("Failed to create post: " . $post_id->get_error_message());
        }
        
        // Add TMDB ID meta
        update_post_meta($post_id, 'tmdb_id', $object_id);
        
        // Schedule full sync for this content
        wp_schedule_single_event(time() + 60, 'tmu_sync_single_content', [
            'post_id' => $post_id,
            'post_type' => $object_type
        ]);
        
        error_log("Created new {$object_type} post {$post_id} from webhook");
    }
    
    /**
     * Handle content updates
     */
    private function handle_content_updated(string $object_type, int $object_id, array $data): void {
        $existing_post = $this->find_existing_post($object_type, $object_id);
        
        if (!$existing_post) {
            // Create new post if it doesn't exist
            $this->handle_content_created($object_type, $object_id, $data);
            return;
        }
        
        // Schedule sync for this content
        wp_schedule_single_event(time() + 60, 'tmu_sync_single_content', [
            'post_id' => $existing_post,
            'post_type' => $object_type
        ]);
        
        error_log("Scheduled sync for {$object_type} post {$existing_post} from webhook");
    }
    
    /**
     * Handle content deletion
     */
    private function handle_content_deleted(string $object_type, int $object_id, array $data): void {
        $existing_post = $this->find_existing_post($object_type, $object_id);
        
        if (!$existing_post) {
            return; // Nothing to delete
        }
        
        // Check if we should delete or just mark as deleted
        $delete_behavior = get_option('tmu_tmdb_delete_behavior', 'mark_deleted');
        
        if ($delete_behavior === 'delete_post') {
            // Permanently delete the post
            wp_delete_post($existing_post, true);
            error_log("Deleted {$object_type} post {$existing_post} from webhook");
        } else {
            // Mark as deleted/unavailable
            update_post_meta($existing_post, 'tmdb_deleted', 1);
            update_post_meta($existing_post, 'tmdb_deleted_date', current_time('mysql'));
            
            // Optionally change post status
            if ($delete_behavior === 'set_draft') {
                wp_update_post([
                    'ID' => $existing_post,
                    'post_status' => 'draft'
                ]);
            }
            
            error_log("Marked {$object_type} post {$existing_post} as deleted from webhook");
        }
    }
    
    /**
     * Find existing post by TMDB ID
     */
    private function find_existing_post(string $object_type, int $object_id): ?int {
        $post_type = $this->get_post_type_from_object_type($object_type);
        
        $posts = get_posts([
            'post_type' => $post_type,
            'meta_key' => 'tmdb_id',
            'meta_value' => $object_id,
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);
        
        return $posts ? $posts[0] : null;
    }
    
    /**
     * Get WordPress post type from TMDB object type
     */
    private function get_post_type_from_object_type(string $object_type): string {
        return match($object_type) {
            'movie' => 'movie',
            'tv' => 'tv',
            'person' => 'people',
            'drama' => 'drama',
            default => 'movie'
        };
    }
    
    /**
     * Prepare post data from webhook
     */
    private function prepare_post_data(string $object_type, array $data): array {
        $post_type = $this->get_post_type_from_object_type($object_type);
        
        $post_data = [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'post_title' => $data['title'] ?? $data['name'] ?? 'Unknown Title',
            'post_content' => $data['overview'] ?? $data['biography'] ?? '',
            'post_excerpt' => $data['tagline'] ?? '',
            'post_date' => current_time('mysql'),
            'post_modified' => current_time('mysql')
        ];
        
        return $post_data;
    }
    
    /**
     * Register webhook actions
     */
    public function register_webhook_actions(): void {
        add_action('tmu_sync_single_content', [$this, 'sync_single_content'], 10, 2);
    }
    
    /**
     * Sync single content item
     */
    public function sync_single_content(int $post_id, string $post_type): void {
        try {
            $options = [
                'sync_images' => get_option('tmu_tmdb_sync_images', 1),
                'sync_credits' => true,
                'sync_videos' => get_option('tmu_tmdb_sync_videos', 1)
            ];
            
            switch ($post_type) {
                case 'movie':
                    $this->sync_service->sync_movie($post_id, $options);
                    break;
                case 'tv':
                    $this->sync_service->sync_tv_show($post_id, $options);
                    break;
                case 'drama':
                    $this->sync_service->sync_drama($post_id, $options);
                    break;
                case 'person':
                    $this->sync_service->sync_person($post_id, $options);
                    break;
            }
            
            error_log("Webhook sync completed for {$post_type} post {$post_id}");
            
        } catch (TMDBException $e) {
            error_log("Webhook sync failed for {$post_type} post {$post_id}: " . $e->getMessage());
        }
    }
    
    /**
     * Get webhook URL
     */
    public function get_webhook_url(): string {
        return home_url('/tmdb-webhook/');
    }
    
    /**
     * Test webhook endpoint
     */
    public function test_webhook(): array {
        $webhook_url = $this->get_webhook_url();
        
        $test_data = [
            'action' => 'test',
            'object_type' => 'movie',
            'object_id' => 123,
            'test' => true
        ];
        
        $response = wp_remote_post($webhook_url, [
            'body' => json_encode($test_data),
            'headers' => [
                'Content-Type' => 'application/json',
                'X-TMU-Signature' => hash_hmac('sha256', json_encode($test_data), $this->webhook_secret)
            ]
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        return [
            'success' => $response_code === 200,
            'code' => $response_code,
            'body' => $response_body
        ];
    }
    
    /**
     * Get webhook configuration
     */
    public function get_webhook_config(): array {
        return [
            'enabled' => get_option('tmu_tmdb_webhooks_enabled', 0),
            'secret' => $this->webhook_secret,
            'url' => $this->get_webhook_url(),
            'delete_behavior' => get_option('tmu_tmdb_delete_behavior', 'mark_deleted')
        ];
    }
    
    /**
     * Update webhook configuration
     */
    public function update_webhook_config(array $config): void {
        update_option('tmu_tmdb_webhooks_enabled', $config['enabled'] ?? 0);
        update_option('tmu_tmdb_webhook_secret', $config['secret'] ?? '');
        update_option('tmu_tmdb_delete_behavior', $config['delete_behavior'] ?? 'mark_deleted');
        
        // Flush rewrite rules if webhooks are enabled
        if ($config['enabled']) {
            flush_rewrite_rules();
        }
    }
}