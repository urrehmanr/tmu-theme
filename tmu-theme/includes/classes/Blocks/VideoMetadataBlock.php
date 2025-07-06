<?php
/**
 * Video Metadata Block
 * 
 * Handles video metadata with database integration for tmu_videos table
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

namespace TMU\Blocks;

/**
 * VideoMetadataBlock class
 */
class VideoMetadataBlock extends BaseBlock {
    
    protected $name = 'video-metadata';
    protected $title = 'Video Metadata';
    protected $description = 'Video metadata management with database integration';
    protected $icon = 'video-alt';
    protected $keywords = ['video', 'metadata', 'media'];
    protected $post_types = ['video'];
    
    protected $supports = [
        'html' => false,
        'multiple' => false,
        'reusable' => false,
        'lock' => false,
    ];
    
    public static function get_attributes(): array {
        return [
            'video_data' => [
                'type' => 'string',
                'default' => '',
            ],
            'post_id' => [
                'type' => 'number',
                'default' => null,
            ],
            'video_type' => [
                'type' => 'string',
                'default' => 'youtube',
                'enum' => ['youtube', 'vimeo', 'mp4', 'embed'],
            ],
            'video_url' => [
                'type' => 'string',
                'default' => '',
            ],
            'video_id' => [
                'type' => 'string',
                'default' => '',
            ],
            'title' => [
                'type' => 'string',
                'default' => '',
            ],
            'description' => [
                'type' => 'string',
                'default' => '',
            ],
            'duration' => [
                'type' => 'number',
                'default' => null,
            ],
            'thumbnail' => [
                'type' => 'string',
                'default' => '',
            ],
        ];
    }
    
    public static function render($attributes, $content): string {
        $attributes = self::validate_attributes($attributes);
        
        if (empty($attributes['video_data'])) {
            return '';
        }
        
        $video_data = json_decode($attributes['video_data'], true);
        if (!$video_data) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="tmu-video-metadata" itemscope itemtype="https://schema.org/VideoObject">
            <div class="video-info">
                <?php if (!empty($video_data['title'])): ?>
                <h3 class="video-title" itemprop="name">
                    <?php echo esc_html($video_data['title']); ?>
                </h3>
                <?php endif; ?>
                
                <?php if (!empty($video_data['description'])): ?>
                <div class="video-description" itemprop="description">
                    <?php echo wp_kses_post(wpautop($video_data['description'])); ?>
                </div>
                <?php endif; ?>
                
                <div class="video-player">
                    <?php echo self::render_video_embed($video_data); ?>
                </div>
                
                <div class="video-meta">
                    <?php if (!empty($video_data['duration'])): ?>
                    <div class="meta-item">
                        <span class="meta-label"><?php _e('Duration:', 'tmu-theme'); ?></span>
                        <span class="meta-value" itemprop="duration">
                            <?php echo esc_html(self::format_runtime($video_data['duration'])); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="meta-item">
                        <span class="meta-label"><?php _e('Type:', 'tmu-theme'); ?></span>
                        <span class="meta-value">
                            <?php echo esc_html(ucfirst($video_data['type'] ?? 'video')); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private static function render_video_embed($video_data): string {
        $type = $video_data['type'] ?? 'youtube';
        $video_id = $video_data['video_id'] ?? '';
        $url = $video_data['url'] ?? '';
        
        switch ($type) {
            case 'youtube':
                if ($video_id) {
                    return sprintf(
                        '<iframe width="560" height="315" src="https://www.youtube.com/embed/%s" frameborder="0" allowfullscreen></iframe>',
                        esc_attr($video_id)
                    );
                }
                break;
                
            case 'vimeo':
                if ($video_id) {
                    return sprintf(
                        '<iframe src="https://player.vimeo.com/video/%s" width="560" height="315" frameborder="0" allowfullscreen></iframe>',
                        esc_attr($video_id)
                    );
                }
                break;
                
            case 'mp4':
                if ($url) {
                    return sprintf(
                        '<video controls width="560" height="315"><source src="%s" type="video/mp4">Your browser does not support the video tag.</video>',
                        esc_url($url)
                    );
                }
                break;
                
            case 'embed':
                if ($url) {
                    return wp_kses_post($url); // Allow safe HTML for embed codes
                }
                break;
        }
        
        return '<p>Video not available</p>';
    }
    
    public static function save_to_database($post_id, $attributes): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_videos';
        $attributes = self::validate_attributes($attributes);
        
        $data = [
            'ID' => $post_id,
            'video_data' => $attributes['video_data'],
            'post_id' => $attributes['post_id'] ?: $post_id,
            'updated_at' => current_time('mysql'),
        ];
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT ID FROM {$table_name} WHERE ID = %d",
            $post_id
        ));
        
        if ($existing) {
            $update_data = $data;
            unset($update_data['ID']);
            return $wpdb->update($table_name, $update_data, ['ID' => $post_id]) !== false;
        } else {
            $data['created_at'] = current_time('mysql');
            return $wpdb->insert($table_name, $data) !== false;
        }
    }
    
    public static function load_from_database($post_id): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_videos';
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE ID = %d",
            $post_id
        ), ARRAY_A);
        
        if (!$data) {
            return self::get_default_attributes();
        }
        
        // Parse video_data JSON
        $video_data = [];
        if (!empty($data['video_data'])) {
            $video_data = json_decode($data['video_data'], true) ?: [];
        }
        
        $mapped_data = [
            'video_data' => $data['video_data'],
            'post_id' => $data['post_id'],
            'video_type' => $video_data['type'] ?? 'youtube',
            'video_url' => $video_data['url'] ?? '',
            'video_id' => $video_data['video_id'] ?? '',
            'title' => $video_data['title'] ?? '',
            'description' => $video_data['description'] ?? '',
            'duration' => $video_data['duration'] ?? null,
            'thumbnail' => $video_data['thumbnail'] ?? '',
        ];
        
        return array_merge(self::get_default_attributes(), $mapped_data);
    }
}