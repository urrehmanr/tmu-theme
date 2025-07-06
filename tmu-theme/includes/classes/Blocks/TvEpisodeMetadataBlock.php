<?php
/**
 * TV Episode Metadata Block
 * 
 * Individual TV episode metadata management with series
 * and season relationships, cast/crew data, and air information.
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

namespace TMU\Blocks;

/**
 * TvEpisodeMetadataBlock class
 * 
 * Handles TV episode metadata with hierarchical relationships
 */
class TvEpisodeMetadataBlock extends BaseBlock {
    
    /**
     * Block properties
     */
    protected $name = 'tv-episode-metadata';
    protected $title = 'TV Episode Metadata';
    protected $description = 'Individual TV episode metadata management';
    protected $icon = 'video-alt';
    protected $keywords = ['episode', 'tv', 'series', 'season'];
    protected $post_types = ['episode'];
    
    /**
     * Block supports
     */
    protected $supports = [
        'html' => false,
        'multiple' => false,
        'reusable' => false,
        'lock' => false,
    ];
    
    /**
     * Get block attributes schema
     * 
     * @return array Attributes configuration
     */
    public static function get_attributes(): array {
        return [
            // Series Relationship
            'tv_series' => [
                'type' => 'number',
                'default' => null,
            ],
            'season_number' => [
                'type' => 'number',
                'default' => null,
            ],
            'episode_number' => [
                'type' => 'number',
                'default' => null,
            ],
            
            // TMDB Integration
            'tmdb_id' => [
                'type' => 'number',
                'default' => null,
            ],
            
            // Episode Information
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
            'episode_type' => [
                'type' => 'string',
                'default' => 'standard',
                'enum' => ['standard', 'finale', 'premiere', 'special'],
            ],
            'runtime' => [
                'type' => 'number',
                'default' => null,
            ],
            'production_code' => [
                'type' => 'string',
                'default' => '',
            ],
            
            // Media
            'still_path' => [
                'type' => 'string',
                'default' => '',
            ],
            
            // Ratings
            'vote_average' => [
                'type' => 'number',
                'default' => null,
            ],
            'vote_count' => [
                'type' => 'number',
                'default' => null,
            ],
            
            // Cast & Crew
            'crew' => [
                'type' => 'array',
                'default' => [],
            ],
            'guest_stars' => [
                'type' => 'array',
                'default' => [],
            ],
            
            // Local Data
            'watch_count' => [
                'type' => 'number',
                'default' => 0,
            ],
            'featured' => [
                'type' => 'boolean',
                'default' => false,
            ],
        ];
    }
    
    /**
     * Render block content
     * 
     * @param array $attributes Block attributes
     * @param string $content Block content
     * @return string Rendered HTML
     */
    public static function render($attributes, $content): string {
        $attributes = self::validate_attributes($attributes);
        
        if (empty($attributes['name'])) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="tmu-tv-episode-metadata" itemscope itemtype="https://schema.org/TVEpisode">
            <div class="episode-info">
                <?php if ($attributes['still_path']): ?>
                <div class="episode-still">
                    <img src="<?php echo esc_url(self::get_tmdb_image_url($attributes['still_path'], 'w500')); ?>" 
                         alt="<?php echo esc_attr($attributes['name']); ?>" 
                         itemprop="image" />
                </div>
                <?php endif; ?>
                
                <div class="episode-details">
                    <div class="episode-header">
                        <?php if ($attributes['season_number'] && $attributes['episode_number']): ?>
                        <div class="episode-number">
                            <span class="season">S<?php echo esc_html(str_pad($attributes['season_number'], 2, '0', STR_PAD_LEFT)); ?></span>
                            <span class="episode">E<?php echo esc_html(str_pad($attributes['episode_number'], 2, '0', STR_PAD_LEFT)); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <h1 class="episode-title" itemprop="name">
                            <?php echo esc_html($attributes['name']); ?>
                        </h1>
                        
                        <?php if ($attributes['episode_type'] !== 'standard'): ?>
                        <span class="episode-type badge-<?php echo esc_attr($attributes['episode_type']); ?>">
                            <?php echo esc_html(ucfirst($attributes['episode_type'])); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($attributes['overview']): ?>
                    <div class="episode-overview" itemprop="description">
                        <?php echo wp_kses_post(wpautop($attributes['overview'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="episode-meta">
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
                            <span class="meta-value" itemprop="duration">
                                <?php echo esc_html(self::format_runtime($attributes['runtime'])); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['production_code']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Production Code:', 'tmu-theme'); ?></span>
                            <span class="meta-value">
                                <?php echo esc_html($attributes['production_code']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['vote_average']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Rating:', 'tmu-theme'); ?></span>
                            <span class="meta-value" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                                <span itemprop="ratingValue"><?php echo esc_html(number_format($attributes['vote_average'], 1)); ?></span>/10
                                <?php if ($attributes['vote_count']): ?>
                                <span class="vote-count">
                                    (<span itemprop="ratingCount"><?php echo esc_html(number_format($attributes['vote_count'])); ?></span> votes)
                                </span>
                                <?php endif; ?>
                                <meta itemprop="bestRating" content="10">
                                <meta itemprop="worstRating" content="1">
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($attributes['guest_stars'] && !empty($attributes['guest_stars'])): ?>
                    <div class="guest-stars">
                        <h3><?php _e('Guest Stars', 'tmu-theme'); ?></h3>
                        <ul>
                            <?php foreach ($attributes['guest_stars'] as $guest): ?>
                            <li itemprop="actor" itemscope itemtype="https://schema.org/Person">
                                <span itemprop="name"><?php echo esc_html($guest['name'] ?? ''); ?></span>
                                <?php if (isset($guest['character'])): ?>
                                <span class="character"> as <?php echo esc_html($guest['character']); ?></span>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($attributes['crew'] && !empty($attributes['crew'])): ?>
                    <div class="episode-crew">
                        <h3><?php _e('Crew', 'tmu-theme'); ?></h3>
                        <ul>
                            <?php foreach ($attributes['crew'] as $member): ?>
                            <li>
                                <span class="crew-name"><?php echo esc_html($member['name'] ?? ''); ?></span>
                                <?php if (isset($member['job'])): ?>
                                <span class="crew-job"> - <?php echo esc_html($member['job']); ?></span>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Save block data to database
     * 
     * @param int $post_id Post ID
     * @param array $attributes Block attributes
     * @return bool Success
     */
    public static function save_to_database($post_id, $attributes): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_episodes';
        $attributes = self::validate_attributes($attributes);
        
        $data = [
            'post_id' => $post_id,
            'tv_series_id' => $attributes['tv_series'],
            'season_number' => $attributes['season_number'],
            'episode_number' => $attributes['episode_number'],
            'tmdb_id' => $attributes['tmdb_id'],
            'name' => $attributes['name'],
            'overview' => $attributes['overview'],
            'air_date' => $attributes['air_date'],
            'episode_type' => $attributes['episode_type'],
            'runtime' => $attributes['runtime'],
            'production_code' => $attributes['production_code'],
            'still_path' => $attributes['still_path'],
            'vote_average' => $attributes['vote_average'],
            'vote_count' => $attributes['vote_count'],
            'crew' => !empty($attributes['crew']) ? json_encode($attributes['crew']) : null,
            'guest_stars' => !empty($attributes['guest_stars']) ? json_encode($attributes['guest_stars']) : null,
            'watch_count' => $attributes['watch_count'],
            'featured' => $attributes['featured'] ? 1 : 0,
            'updated_at' => current_time('mysql'),
        ];
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE post_id = %d",
            $post_id
        ));
        
        if ($existing) {
            return $wpdb->update($table_name, $data, ['post_id' => $post_id]) !== false;
        } else {
            $data['created_at'] = current_time('mysql');
            return $wpdb->insert($table_name, $data) !== false;
        }
    }
}