<?php
/**
 * Season Metadata Block
 * 
 * Handles season metadata for both TV series and dramas with database integration
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

namespace TMU\Blocks;

/**
 * SeasonMetadataBlock class
 */
class SeasonMetadataBlock extends BaseBlock {
    
    protected $name = 'season-metadata';
    protected $title = 'Season Metadata';
    protected $description = 'Season metadata management for TV series and dramas';
    protected $icon = 'playlist-video';
    protected $keywords = ['season', 'tv', 'drama', 'metadata'];
    protected $post_types = ['season'];
    
    protected $supports = [
        'html' => false,
        'multiple' => false,
        'reusable' => false,
        'lock' => false,
    ];
    
    public static function get_attributes(): array {
        return [
            'parent_type' => [
                'type' => 'string',
                'default' => 'tv_series',
                'enum' => ['tv_series', 'drama'],
            ],
            'parent_id' => [
                'type' => 'number',
                'default' => null,
            ],
            'season_number' => [
                'type' => 'number',
                'default' => 1,
            ],
            'name' => [
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
            'episode_count' => [
                'type' => 'number',
                'default' => 0,
            ],
            'tmdb_id' => [
                'type' => 'number',
                'default' => null,
            ],
            'poster_path' => [
                'type' => 'string',
                'default' => '',
            ],
        ];
    }
    
    public static function render($attributes, $content): string {
        $attributes = self::validate_attributes($attributes);
        
        if (empty($attributes['name']) && empty($attributes['season_number'])) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="tmu-season-metadata" itemscope itemtype="https://schema.org/TVSeason">
            <div class="season-info">
                <h3 class="season-title" itemprop="name">
                    <?php echo esc_html($attributes['name'] ?: 'Season ' . $attributes['season_number']); ?>
                </h3>
                
                <div class="season-meta">
                    <div class="meta-item">
                        <span class="meta-label"><?php _e('Season Number:', 'tmu-theme'); ?></span>
                        <span class="meta-value" itemprop="seasonNumber">
                            <?php echo esc_html($attributes['season_number']); ?>
                        </span>
                    </div>
                    
                    <?php if ($attributes['episode_count']): ?>
                    <div class="meta-item">
                        <span class="meta-label"><?php _e('Episodes:', 'tmu-theme'); ?></span>
                        <span class="meta-value" itemprop="numberOfEpisodes">
                            <?php echo esc_html($attributes['episode_count']); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($attributes['air_date']): ?>
                    <div class="meta-item">
                        <span class="meta-label"><?php _e('Air Date:', 'tmu-theme'); ?></span>
                        <span class="meta-value" itemprop="datePublished">
                            <?php echo esc_html(self::format_date($attributes['air_date'], 'F j, Y')); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($attributes['overview']): ?>
                <div class="season-overview" itemprop="description">
                    <?php echo wp_kses_post(wpautop($attributes['overview'])); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($attributes['poster_path']): ?>
                <div class="season-poster">
                    <img src="<?php echo esc_url(self::get_tmdb_image_url($attributes['poster_path'], 'w300')); ?>" 
                         alt="<?php echo esc_attr($attributes['name'] ?: 'Season ' . $attributes['season_number']); ?>" 
                         itemprop="image" />
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public static function save_to_database($post_id, $attributes): bool {
        global $wpdb;
        
        $attributes = self::validate_attributes($attributes);
        $parent_type = $attributes['parent_type'];
        
        // Determine table based on parent type
        $table_name = $wpdb->prefix . 'tmu_' . ($parent_type === 'drama' ? 'dramas' : 'tv_series') . '_seasons';
        
        // Convert air_date to timestamp
        $air_timestamp = null;
        if (!empty($attributes['air_date'])) {
            $air_timestamp = strtotime($attributes['air_date']);
        }
        
        $data = [
            $parent_type => $attributes['parent_id'],
            'season_number' => $attributes['season_number'],
            'name' => $attributes['name'],
            'overview' => $attributes['overview'],
            'air_date' => $attributes['air_date'],
            'air_timestamp' => $air_timestamp,
            'episode_count' => $attributes['episode_count'],
            'tmdb_id' => $attributes['tmdb_id'],
            'poster_path' => $attributes['poster_path'],
            'updated_at' => current_time('mysql'),
        ];
        
        $parent_field = $parent_type === 'drama' ? 'drama' : 'tv_series';
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT ID FROM {$table_name} WHERE {$parent_field} = %d AND season_number = %d",
            $attributes['parent_id'], $attributes['season_number']
        ));
        
        if ($existing) {
            return $wpdb->update($table_name, $data, [
                $parent_field => $attributes['parent_id'],
                'season_number' => $attributes['season_number']
            ]) !== false;
        } else {
            $data['created_at'] = current_time('mysql');
            return $wpdb->insert($table_name, $data) !== false;
        }
    }
    
    public static function load_from_database($post_id): array {
        global $wpdb;
        
        // Get parent info from post meta or parent
        $parent_id = wp_get_post_parent_id($post_id) ?: get_post_meta($post_id, 'parent_id', true);
        $parent_type = get_post_meta($post_id, 'parent_type', true) ?: 'tv_series';
        
        if (!$parent_id) {
            return self::get_default_attributes();
        }
        
        $table_name = $wpdb->prefix . 'tmu_' . ($parent_type === 'drama' ? 'dramas' : 'tv_series') . '_seasons';
        $parent_field = $parent_type === 'drama' ? 'drama' : 'tv_series';
        $season_number = get_post_meta($post_id, 'season_number', true) ?: 1;
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE {$parent_field} = %d AND season_number = %d",
            $parent_id, $season_number
        ), ARRAY_A);
        
        if (!$data) {
            return self::get_default_attributes();
        }
        
        $mapped_data = [
            'parent_type' => $parent_type,
            'parent_id' => $parent_id,
            'season_number' => $data['season_number'],
            'name' => $data['name'],
            'overview' => $data['overview'],
            'air_date' => $data['air_date'],
            'episode_count' => $data['episode_count'],
            'tmdb_id' => $data['tmdb_id'],
            'poster_path' => $data['poster_path'],
        ];
        
        return array_merge(self::get_default_attributes(), $mapped_data);
    }
}