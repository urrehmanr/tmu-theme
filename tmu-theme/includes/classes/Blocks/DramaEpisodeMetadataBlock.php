<?php
/**
 * Drama Episode Metadata Block
 * 
 * Handles drama episode metadata with database integration for tmu_dramas_episodes table
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

namespace TMU\Blocks;

/**
 * DramaEpisodeMetadataBlock class
 */
class DramaEpisodeMetadataBlock extends BaseBlock {
    
    protected $name = 'drama-episode-metadata';
    protected $title = 'Drama Episode Metadata';
    protected $description = 'Drama episode metadata management with database integration';
    protected $icon = 'video-alt2';
    protected $keywords = ['drama', 'episode', 'metadata'];
    protected $post_types = ['drama_episode'];
    
    protected $supports = [
        'html' => false,
        'multiple' => false,
        'reusable' => false,
        'lock' => false,
    ];
    
    public static function get_attributes(): array {
        return [
            'drama' => [
                'type' => 'number',
                'default' => null,
            ],
            'season_number' => [
                'type' => 'number',
                'default' => 1,
            ],
            'episode_number' => [
                'type' => 'number',
                'default' => 1,
            ],
            'title' => [
                'type' => 'string',
                'default' => '',
            ],
            'overview' => [
                'type' => 'string',
                'default' => '',
            ],
            'air_date' => [
                'type' => 'string',
                'default' => '',
            ],
            'runtime' => [
                'type' => 'number',
                'default' => null,
            ],
            'tmdb_id' => [
                'type' => 'number',
                'default' => null,
            ],
            'still_path' => [
                'type' => 'string',
                'default' => '',
            ],
            'vote_average' => [
                'type' => 'number',
                'default' => 0,
            ],
            'vote_count' => [
                'type' => 'number',
                'default' => 0,
            ],
        ];
    }
    
    public static function render($attributes, $content): string {
        $attributes = self::validate_attributes($attributes);
        
        if (empty($attributes['title'])) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="tmu-drama-episode-metadata" itemscope itemtype="https://schema.org/TVEpisode">
            <div class="episode-info">
                <h3 class="episode-title" itemprop="name">
                    <?php echo esc_html($attributes['title']); ?>
                </h3>
                
                <div class="episode-meta">
                    <div class="meta-item">
                        <span class="meta-label"><?php _e('Season:', 'tmu-theme'); ?></span>
                        <span class="meta-value" itemprop="partOfSeason">
                            <?php echo esc_html($attributes['season_number']); ?>
                        </span>
                    </div>
                    
                    <div class="meta-item">
                        <span class="meta-label"><?php _e('Episode:', 'tmu-theme'); ?></span>
                        <span class="meta-value" itemprop="episodeNumber">
                            <?php echo esc_html($attributes['episode_number']); ?>
                        </span>
                    </div>
                    
                    <?php if ($attributes['air_date']): ?>
                    <div class="meta-item">
                        <span class="meta-label"><?php _e('Air Date:', 'tmu-theme'); ?></span>
                        <span class="meta-value" itemprop="datePublished">
                            <?php echo esc_html(self::format_date($attributes['air_date'], 'F j, Y')); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($attributes['runtime']): ?>
                    <div class="meta-item">
                        <span class="meta-label"><?php _e('Runtime:', 'tmu-theme'); ?></span>
                        <span class="meta-value">
                            <?php echo esc_html(self::format_runtime($attributes['runtime'])); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($attributes['overview']): ?>
                <div class="episode-overview" itemprop="description">
                    <?php echo wp_kses_post(wpautop($attributes['overview'])); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public static function save_to_database($post_id, $attributes): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_dramas_episodes';
        $attributes = self::validate_attributes($attributes);
        
        // Convert air_date to timestamp
        $air_timestamp = null;
        if (!empty($attributes['air_date'])) {
            $air_timestamp = strtotime($attributes['air_date']);
        }
        
        $data = [
            'drama' => $attributes['drama'],
            'season_number' => $attributes['season_number'],
            'episode_number' => $attributes['episode_number'],
            'title' => $attributes['title'],
            'overview' => $attributes['overview'],
            'air_date' => $attributes['air_date'],
            'air_timestamp' => $air_timestamp,
            'runtime' => $attributes['runtime'],
            'tmdb_id' => $attributes['tmdb_id'],
            'still_path' => $attributes['still_path'],
            'vote_average' => $attributes['vote_average'],
            'vote_count' => $attributes['vote_count'],
            'updated_at' => current_time('mysql'),
        ];
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT ID FROM {$table_name} WHERE drama = %d AND season_number = %d AND episode_number = %d",
            $attributes['drama'], $attributes['season_number'], $attributes['episode_number']
        ));
        
        if ($existing) {
            return $wpdb->update($table_name, $data, [
                'drama' => $attributes['drama'],
                'season_number' => $attributes['season_number'],
                'episode_number' => $attributes['episode_number']
            ]) !== false;
        } else {
            $data['created_at'] = current_time('mysql');
            return $wpdb->insert($table_name, $data) !== false;
        }
    }
    
    public static function load_from_database($post_id): array {
        global $wpdb;
        
        // Get drama ID from post parent or meta
        $drama_id = wp_get_post_parent_id($post_id) ?: get_post_meta($post_id, 'drama_id', true);
        
        if (!$drama_id) {
            return self::get_default_attributes();
        }
        
        $table_name = $wpdb->prefix . 'tmu_dramas_episodes';
        $episode_meta = get_post_meta($post_id, 'episode_number', true);
        $season_meta = get_post_meta($post_id, 'season_number', true);
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE drama = %d AND season_number = %d AND episode_number = %d",
            $drama_id, $season_meta ?: 1, $episode_meta ?: 1
        ), ARRAY_A);
        
        if (!$data) {
            return self::get_default_attributes();
        }
        
        return array_merge(self::get_default_attributes(), $data);
    }
}