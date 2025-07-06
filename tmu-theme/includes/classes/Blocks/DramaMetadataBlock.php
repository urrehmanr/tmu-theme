<?php
/**
 * Drama Metadata Block
 * 
 * Drama series metadata management with channel integration
 * and specialized drama-specific features.
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

namespace TMU\Blocks;

/**
 * DramaMetadataBlock class
 * 
 * Handles drama metadata with channel integration
 */
class DramaMetadataBlock extends BaseBlock {
    
    /**
     * Block properties
     */
    protected $name = 'drama-metadata';
    protected $title = 'Drama Metadata';
    protected $description = 'Drama series metadata management with channel integration';
    protected $icon = 'video-alt';
    protected $keywords = ['drama', 'series', 'channel', 'metadata'];
    protected $post_types = ['drama'];
    
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
            // Basic Information
            'title' => [
                'type' => 'string',
                'default' => '',
            ],
            'original_title' => [
                'type' => 'string',
                'default' => '',
            ],
            'tagline' => [
                'type' => 'string',
                'default' => '',
            ],
            'overview' => [
                'type' => 'string',
                'default' => '',
            ],
            
            // Air Date Information
            'first_air_date' => [
                'type' => 'string',
                'default' => '',
            ],
            'last_air_date' => [
                'type' => 'string',
                'default' => '',
            ],
            
            // Drama Information
            'status' => [
                'type' => 'string',
                'default' => 'Airing',
                'enum' => ['Airing', 'Completed', 'Upcoming', 'Canceled'],
            ],
            'total_episodes' => [
                'type' => 'number',
                'default' => null,
            ],
            'episode_duration' => [
                'type' => 'number',
                'default' => null,
            ],
            
            // Channel Information
            'channel' => [
                'type' => 'string',
                'default' => '',
            ],
            'original_network' => [
                'type' => 'string',
                'default' => '',
            ],
            
            // Country & Language
            'country' => [
                'type' => 'string',
                'default' => '',
            ],
            'original_language' => [
                'type' => 'string',
                'default' => '',
            ],
            
            // Media & Links
            'poster_path' => [
                'type' => 'string',
                'default' => '',
            ],
            'backdrop_path' => [
                'type' => 'string',
                'default' => '',
            ],
            
            // Ratings
            'local_rating' => [
                'type' => 'number',
                'default' => null,
            ],
            'local_rating_count' => [
                'type' => 'number',
                'default' => 0,
            ],
            'watch_count' => [
                'type' => 'number',
                'default' => 0,
            ],
            
            // Cast & Crew
            'main_cast' => [
                'type' => 'array',
                'default' => [],
            ],
            'director' => [
                'type' => 'string',
                'default' => '',
            ],
            'writer' => [
                'type' => 'string',
                'default' => '',
            ],
            
            // SEO & Display Options
            'featured' => [
                'type' => 'boolean',
                'default' => false,
            ],
            'trending' => [
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
        
        if (empty($attributes['title'])) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="tmu-drama-metadata" itemscope itemtype="https://schema.org/TVSeries">
            <div class="drama-info">
                <?php if ($attributes['poster_path']): ?>
                <div class="drama-poster">
                    <img src="<?php echo esc_url($attributes['poster_path']); ?>" 
                         alt="<?php echo esc_attr($attributes['title']); ?>" 
                         itemprop="image" />
                </div>
                <?php endif; ?>
                
                <div class="drama-details">
                    <h1 class="drama-title" itemprop="name">
                        <?php echo esc_html($attributes['title']); ?>
                    </h1>
                    
                    <?php if ($attributes['original_title'] && $attributes['original_title'] !== $attributes['title']): ?>
                    <p class="drama-original-title">
                        <em><?php echo esc_html($attributes['original_title']); ?></em>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($attributes['tagline']): ?>
                    <p class="drama-tagline">
                        <?php echo esc_html($attributes['tagline']); ?>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($attributes['overview']): ?>
                    <div class="drama-overview" itemprop="description">
                        <?php echo wp_kses_post(wpautop($attributes['overview'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="drama-meta">
                        <?php if ($attributes['first_air_date']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('First Air Date:', 'tmu-theme'); ?></span>
                            <span class="meta-value" itemprop="startDate">
                                <?php echo esc_html(self::format_date($attributes['first_air_date'], 'F j, Y')); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['channel']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Channel:', 'tmu-theme'); ?></span>
                            <span class="meta-value">
                                <?php echo esc_html($attributes['channel']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['status']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Status:', 'tmu-theme'); ?></span>
                            <span class="meta-value">
                                <?php echo esc_html($attributes['status']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['total_episodes']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Episodes:', 'tmu-theme'); ?></span>
                            <span class="meta-value" itemprop="numberOfEpisodes">
                                <?php echo esc_html($attributes['total_episodes']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['episode_duration']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Episode Duration:', 'tmu-theme'); ?></span>
                            <span class="meta-value">
                                <?php echo esc_html(self::format_runtime($attributes['episode_duration'])); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['country']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Country:', 'tmu-theme'); ?></span>
                            <span class="meta-value">
                                <?php echo esc_html($attributes['country']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['local_rating']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Rating:', 'tmu-theme'); ?></span>
                            <span class="meta-value" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                                <span itemprop="ratingValue"><?php echo esc_html(number_format($attributes['local_rating'], 1)); ?></span>/10
                                <?php if ($attributes['local_rating_count']): ?>
                                <span class="vote-count">
                                    (<span itemprop="ratingCount"><?php echo esc_html(number_format($attributes['local_rating_count'])); ?></span> votes)
                                </span>
                                <?php endif; ?>
                                <meta itemprop="bestRating" content="10">
                                <meta itemprop="worstRating" content="1">
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($attributes['main_cast'] && !empty($attributes['main_cast'])): ?>
                    <div class="main-cast">
                        <h3><?php _e('Main Cast', 'tmu-theme'); ?></h3>
                        <ul>
                            <?php foreach ($attributes['main_cast'] as $actor): ?>
                            <li itemprop="actor" itemscope itemtype="https://schema.org/Person">
                                <span itemprop="name"><?php echo esc_html($actor['name'] ?? ''); ?></span>
                                <?php if (isset($actor['character'])): ?>
                                <span class="character"> as <?php echo esc_html($actor['character']); ?></span>
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
        
        $table_name = $wpdb->prefix . 'tmu_dramas';
        $attributes = self::validate_attributes($attributes);
        
        $data = [
            'post_id' => $post_id,
            'title' => $attributes['title'],
            'original_title' => $attributes['original_title'],
            'tagline' => $attributes['tagline'],
            'overview' => $attributes['overview'],
            'first_air_date' => $attributes['first_air_date'],
            'last_air_date' => $attributes['last_air_date'],
            'status' => $attributes['status'],
            'total_episodes' => $attributes['total_episodes'],
            'episode_duration' => $attributes['episode_duration'],
            'channel' => $attributes['channel'],
            'original_network' => $attributes['original_network'],
            'country' => $attributes['country'],
            'original_language' => $attributes['original_language'],
            'poster_path' => $attributes['poster_path'],
            'backdrop_path' => $attributes['backdrop_path'],
            'local_rating' => $attributes['local_rating'],
            'local_rating_count' => $attributes['local_rating_count'],
            'watch_count' => $attributes['watch_count'],
            'main_cast' => !empty($attributes['main_cast']) ? json_encode($attributes['main_cast']) : null,
            'director' => $attributes['director'],
            'writer' => $attributes['writer'],
            'featured' => $attributes['featured'] ? 1 : 0,
            'trending' => $attributes['trending'] ? 1 : 0,
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