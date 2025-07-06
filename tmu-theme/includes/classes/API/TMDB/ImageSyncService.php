<?php
/**
 * TMDB Image Sync Service
 * 
 * Handles downloading and managing TMDB media assets
 * 
 * @package TMU\API\TMDB
 * @since 1.0.0
 */

namespace TMU\API\TMDB;

/**
 * ImageSyncService class
 * 
 * Downloads and manages TMDB images including posters, backdrops, and profiles
 */
class ImageSyncService {
    
    /**
     * TMDB image base URL
     * @var string
     */
    private $base_url = 'https://image.tmdb.org/t/p/';
    
    /**
     * Image size configurations
     * @var array
     */
    private $sizes = [
        'poster' => ['w300', 'w500', 'w780', 'original'],
        'backdrop' => ['w780', 'w1280', 'original'],
        'profile' => ['w185', 'w276', 'w400', 'original']
    ];
    
    /**
     * Default sizes for each image type
     * @var array
     */
    private $default_sizes = [
        'poster' => 'w500',
        'backdrop' => 'w1280',
        'profile' => 'w276'
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        // Ensure WordPress media functions are available
        if (!function_exists('wp_insert_attachment')) {
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }
    }
    
    /**
     * Download and attach image to a post
     * 
     * @param int $post_id Post ID to attach image to
     * @param string $file_path TMDB file path
     * @param string $type Image type (poster, backdrop, profile)
     * @param string $size Image size (optional)
     * @return int|null Attachment ID on success, null on failure
     */
    public function downloadAndAttachImage(int $post_id, string $file_path, string $type = 'poster', string $size = null): ?int {
        if (empty($file_path)) {
            error_log("ImageSyncService: Empty file path provided");
            return null;
        }
        
        // Use default size if not specified
        if (!$size) {
            $size = $this->default_sizes[$type] ?? 'original';
        }
        
        // Check if image already exists
        $existing_id = $this->getExistingAttachment($post_id, $file_path, $type);
        if ($existing_id) {
            return $existing_id;
        }
        
        $image_url = $this->base_url . $size . $file_path;
        
        try {
            // Download image
            $response = wp_remote_get($image_url, [
                'timeout' => 30,
                'headers' => [
                    'User-Agent' => 'TMU-Theme/1.0'
                ]
            ]);
            
            if (is_wp_error($response)) {
                error_log("Failed to download image {$image_url}: " . $response->get_error_message());
                return null;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                error_log("Failed to download image {$image_url}: HTTP {$response_code}");
                return null;
            }
            
            $image_data = wp_remote_retrieve_body($response);
            $content_type = wp_remote_retrieve_header($response, 'content-type');
            
            if (empty($image_data)) {
                error_log("Empty image data for {$image_url}");
                return null;
            }
            
            // Determine file extension
            $ext = $this->getFileExtension($content_type);
            if (!$ext) {
                error_log("Unsupported content type: {$content_type}");
                return null;
            }
            
            // Generate filename
            $filename = $this->generateFilename($post_id, $type, $size, $ext);
            
            // Save file
            $upload_dir = wp_upload_dir();
            if (!wp_mkdir_p($upload_dir['path'])) {
                error_log("Failed to create upload directory: " . $upload_dir['path']);
                return null;
            }
            
            $file_path_local = $upload_dir['path'] . '/' . $filename;
            
            if (file_put_contents($file_path_local, $image_data) === false) {
                error_log("Failed to save image file: " . $file_path_local);
                return null;
            }
            
            // Create attachment
            $attachment_id = wp_insert_attachment([
                'post_mime_type' => $content_type,
                'post_title' => $this->generateTitle($post_id, $type),
                'post_content' => '',
                'post_status' => 'inherit',
                'post_parent' => $post_id
            ], $file_path_local);
            
            if (!$attachment_id) {
                unlink($file_path_local);
                error_log("Failed to create attachment for: " . $file_path_local);
                return null;
            }
            
            // Generate metadata
            $metadata = wp_generate_attachment_metadata($attachment_id, $file_path_local);
            wp_update_attachment_metadata($attachment_id, $metadata);
            
            // Store TMDB metadata
            update_post_meta($attachment_id, 'tmdb_file_path', $file_path);
            update_post_meta($attachment_id, 'tmdb_image_type', $type);
            update_post_meta($attachment_id, 'tmdb_image_size', $size);
            update_post_meta($attachment_id, 'tmdb_sync_date', current_time('mysql'));
            
            // Set as featured image if poster
            if ($type === 'poster') {
                set_post_thumbnail($post_id, $attachment_id);
            }
            
            do_action('tmu_image_synced', $attachment_id, $post_id, $type, $file_path);
            
            return $attachment_id;
            
        } catch (\Exception $e) {
            error_log("ImageSyncService Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Bulk download multiple images
     * 
     * @param int $post_id Post ID
     * @param array $images Array of image data from TMDB
     * @param array $options Download options
     * @return array Results with success/failure counts
     */
    public function bulkDownloadImages(int $post_id, array $images, array $options = []): array {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        $max_images = $options['max_images'] ?? 5;
        
        // Download posters
        if (!empty($images['posters'])) {
            $posters = array_slice($images['posters'], 0, $max_images);
            foreach ($posters as $index => $poster) {
                $attachment_id = $this->downloadAndAttachImage(
                    $post_id, 
                    $poster['file_path'], 
                    'poster'
                );
                
                if ($attachment_id) {
                    $results['success']++;
                    // Set first poster as featured image
                    if ($index === 0) {
                        set_post_thumbnail($post_id, $attachment_id);
                    }
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to download poster: " . $poster['file_path'];
                }
            }
        }
        
        // Download backdrops
        if (!empty($images['backdrops'])) {
            $backdrops = array_slice($images['backdrops'], 0, $max_images);
            foreach ($backdrops as $backdrop) {
                $attachment_id = $this->downloadAndAttachImage(
                    $post_id, 
                    $backdrop['file_path'], 
                    'backdrop'
                );
                
                if ($attachment_id) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to download backdrop: " . $backdrop['file_path'];
                }
            }
        }
        
        // Download profiles (for people)
        if (!empty($images['profiles'])) {
            $profiles = array_slice($images['profiles'], 0, $max_images);
            foreach ($profiles as $index => $profile) {
                $attachment_id = $this->downloadAndAttachImage(
                    $post_id, 
                    $profile['file_path'], 
                    'profile'
                );
                
                if ($attachment_id) {
                    $results['success']++;
                    // Set first profile as featured image
                    if ($index === 0) {
                        set_post_thumbnail($post_id, $attachment_id);
                    }
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to download profile: " . $profile['file_path'];
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Get existing attachment for a TMDB image
     * 
     * @param int $post_id Post ID
     * @param string $file_path TMDB file path
     * @param string $type Image type
     * @return int|null Attachment ID if exists, null otherwise
     */
    private function getExistingAttachment(int $post_id, string $file_path, string $type): ?int {
        $attachments = get_attached_media('image', $post_id);
        
        foreach ($attachments as $attachment) {
            $stored_path = get_post_meta($attachment->ID, 'tmdb_file_path', true);
            $stored_type = get_post_meta($attachment->ID, 'tmdb_image_type', true);
            
            if ($stored_path === $file_path && $stored_type === $type) {
                return $attachment->ID;
            }
        }
        
        return null;
    }
    
    /**
     * Get file extension from content type
     * 
     * @param string $content_type HTTP content type
     * @return string|null File extension
     */
    private function getFileExtension(string $content_type): ?string {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg'
        ];
        
        return $extensions[$content_type] ?? null;
    }
    
    /**
     * Generate filename for downloaded image
     * 
     * @param int $post_id Post ID
     * @param string $type Image type
     * @param string $size Image size
     * @param string $ext File extension
     * @return string Generated filename
     */
    private function generateFilename(int $post_id, string $type, string $size, string $ext): string {
        $post_slug = get_post_field('post_name', $post_id);
        if (empty($post_slug)) {
            $post_slug = sanitize_title(get_the_title($post_id));
        }
        
        $timestamp = time();
        return "{$post_slug}-{$type}-{$size}-{$timestamp}.{$ext}";
    }
    
    /**
     * Generate title for attachment
     * 
     * @param int $post_id Post ID
     * @param string $type Image type
     * @return string Generated title
     */
    private function generateTitle(int $post_id, string $type): string {
        $post_title = get_the_title($post_id);
        $type_label = ucfirst($type);
        
        return "{$post_title} - {$type_label}";
    }
    
    /**
     * Get TMDB image URL
     * 
     * @param string $file_path TMDB file path
     * @param string $size Image size
     * @return string Full image URL
     */
    public function getImageUrl(string $file_path, string $size = 'original'): string {
        if (empty($file_path)) {
            return '';
        }
        
        return $this->base_url . $size . $file_path;
    }
    
    /**
     * Get available sizes for image type
     * 
     * @param string $type Image type
     * @return array Available sizes
     */
    public function getAvailableSizes(string $type): array {
        return $this->sizes[$type] ?? ['original'];
    }
    
    /**
     * Clean up orphaned TMDB images
     * 
     * @param int $days_old Delete images older than this many days
     * @return array Cleanup results
     */
    public function cleanupOrphanedImages(int $days_old = 30): array {
        global $wpdb;
        
        $results = [
            'deleted' => 0,
            'errors' => []
        ];
        
        // Find attachments with TMDB metadata older than specified days
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));
        
        $query = $wpdb->prepare("
            SELECT p.ID, p.post_parent
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
            WHERE p.post_type = 'attachment'
            AND pm1.meta_key = 'tmdb_file_path'
            AND pm2.meta_key = 'tmdb_sync_date'
            AND pm2.meta_value < %s
        ", $cutoff_date);
        
        $attachments = $wpdb->get_results($query);
        
        foreach ($attachments as $attachment) {
            // Check if parent post still exists
            if ($attachment->post_parent && !get_post($attachment->post_parent)) {
                if (wp_delete_attachment($attachment->ID, true)) {
                    $results['deleted']++;
                } else {
                    $results['errors'][] = "Failed to delete attachment {$attachment->ID}";
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Get image statistics
     * 
     * @return array Image statistics
     */
    public function getImageStatistics(): array {
        global $wpdb;
        
        $stats = [
            'total_images' => 0,
            'posters' => 0,
            'backdrops' => 0,
            'profiles' => 0,
            'total_size' => 0
        ];
        
        // Count TMDB images by type
        $query = "
            SELECT pm.meta_value as image_type, COUNT(*) as count
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = 'tmdb_image_type'
            AND p.post_type = 'attachment'
            GROUP BY pm.meta_value
        ";
        
        $results = $wpdb->get_results($query);
        
        foreach ($results as $result) {
            $stats[$result->image_type] = (int) $result->count;
            $stats['total_images'] += (int) $result->count;
        }
        
        // Calculate total size (approximate)
        $size_query = "
            SELECT SUM(pm.meta_value) as total_size
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
            WHERE pm.meta_key = '_wp_attachment_file'
            AND pm2.meta_key = 'tmdb_file_path'
            AND p.post_type = 'attachment'
        ";
        
        $size_result = $wpdb->get_var($size_query);
        $stats['total_size'] = (int) $size_result;
        
        return $stats;
    }
    
    /**
     * Update image from TMDB (re-download)
     * 
     * @param int $attachment_id Attachment ID
     * @return bool Success status
     */
    public function updateImage(int $attachment_id): bool {
        $file_path = get_post_meta($attachment_id, 'tmdb_file_path', true);
        $type = get_post_meta($attachment_id, 'tmdb_image_type', true);
        $size = get_post_meta($attachment_id, 'tmdb_image_size', true);
        $post_id = wp_get_post_parent_id($attachment_id);
        
        if (!$file_path || !$type || !$post_id) {
            return false;
        }
        
        // Delete old attachment
        wp_delete_attachment($attachment_id, true);
        
        // Download new version
        $new_attachment_id = $this->downloadAndAttachImage($post_id, $file_path, $type, $size);
        
        return $new_attachment_id !== null;
    }
    
    /**
     * Check if image needs update
     * 
     * @param int $attachment_id Attachment ID
     * @param int $max_age_days Maximum age in days
     * @return bool True if image needs update
     */
    public function needsUpdate(int $attachment_id, int $max_age_days = 30): bool {
        $sync_date = get_post_meta($attachment_id, 'tmdb_sync_date', true);
        
        if (!$sync_date) {
            return true;
        }
        
        $cutoff = strtotime("-{$max_age_days} days");
        $sync_timestamp = strtotime($sync_date);
        
        return $sync_timestamp < $cutoff;
    }
}