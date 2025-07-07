<?php
/**
 * TV Series Metadata Block
 * 
 * Comprehensive TV series metadata management with hierarchical support
 * for seasons and episodes, network integration, and TMDB data.
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

namespace TMU\Blocks;

/**
 * TvSeriesMetadataBlock class
 * 
 * Handles TV series metadata with season/episode management
 */
class TvSeriesMetadataBlock extends BaseBlock {
    
    /**
     * Block properties
     */
    protected $name = 'tv-series-metadata';
    protected $title = 'TV Series Metadata';
    protected $description = 'Comprehensive TV series metadata with season/episode management';
    protected $icon = 'video-alt2';
    protected $keywords = ['tv', 'series', 'show', 'metadata', 'tmdb'];
    protected $post_types = ['tv'];
    
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
            // TMDB Integration
            'tmdb_id' => [
                'type' => 'number',
                'default' => null,
            ],
            'imdb_id' => [
                'type' => 'string',
                'default' => '',
            ],
            
            // Basic Information
            'name' => [
                'type' => 'string',
                'default' => '',
            ],
            'original_name' => [
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
            'next_episode_to_air' => [
                'type' => 'object',
                'default' => null,
            ],
            'last_episode_to_air' => [
                'type' => 'object',
                'default' => null,
            ],
            
            // Series Information
            'status' => [
                'type' => 'string',
                'default' => 'Returning Series',
                'enum' => ['Returning Series', 'Planned', 'In Production', 'Ended', 'Canceled', 'Pilot'],
            ],
            'type' => [
                'type' => 'string',
                'default' => 'Scripted',
                'enum' => ['Scripted', 'Reality', 'Documentary', 'News', 'Talk Show', 'Miniseries'],
            ],
            'in_production' => [
                'type' => 'boolean',
                'default' => false,
            ],
            
            // Episode Information
            'number_of_episodes' => [
                'type' => 'number',
                'default' => null,
            ],
            'number_of_seasons' => [
                'type' => 'number',
                'default' => null,
            ],
            'episode_run_time' => [
                'type' => 'array',
                'default' => [],
            ],
            
            // Language & Country
            'languages' => [
                'type' => 'array',
                'default' => [],
            ],
            'origin_country' => [
                'type' => 'array',
                'default' => [],
            ],
            'original_language' => [
                'type' => 'string',
                'default' => '',
            ],
            
            // Media & Links
            'homepage' => [
                'type' => 'string',
                'default' => '',
            ],
            'poster_path' => [
                'type' => 'string',
                'default' => '',
            ],
            'backdrop_path' => [
                'type' => 'string',
                'default' => '',
            ],
            
            // TMDB Ratings & Popularity
            'tmdb_vote_average' => [
                'type' => 'number',
                'default' => null,
            ],
            'tmdb_vote_count' => [
                'type' => 'number',
                'default' => null,
            ],
            'tmdb_popularity' => [
                'type' => 'number',
                'default' => null,
            ],
            
            // Content Flags
            'adult' => [
                'type' => 'boolean',
                'default' => false,
            ],
            
            // Creator Information
            'created_by' => [
                'type' => 'array',
                'default' => [],
            ],
            
            // Genre Information
            'genres' => [
                'type' => 'array',
                'default' => [],
            ],
            
            // Network Information
            'networks' => [
                'type' => 'array',
                'default' => [],
            ],
            
            // Production Information
            'production_companies' => [
                'type' => 'array',
                'default' => [],
            ],
            'production_countries' => [
                'type' => 'array',
                'default' => [],
            ],
            
            // Season Information
            'seasons' => [
                'type' => 'array',
                'default' => [],
            ],
            
            // Spoken Languages
            'spoken_languages' => [
                'type' => 'array',
                'default' => [],
            ],
            
            // Extended TMDB Data
            'credits' => [
                'type' => 'object',
                'default' => null,
            ],
            'external_ids' => [
                'type' => 'object',
                'default' => null,
            ],
            'images' => [
                'type' => 'object',
                'default' => null,
            ],
            'videos' => [
                'type' => 'object',
                'default' => null,
            ],
            'similar' => [
                'type' => 'array',
                'default' => [],
            ],
            'recommendations' => [
                'type' => 'array',
                'default' => [],
            ],
            
            // Local Data
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
            'last_tmdb_sync' => [
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
        
        if (empty($attributes['name'])) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="tmu-tv-series-metadata" itemscope itemtype="https://schema.org/TVSeries">
            <div class="tv-series-info">
                <?php if ($attributes['poster_path']): ?>
                <div class="tv-series-poster">
                    <img src="<?php echo esc_url(self::get_tmdb_image_url($attributes['poster_path'], 'w500')); ?>" 
                         alt="<?php echo esc_attr($attributes['name']); ?>" 
                         itemprop="image" />
                </div>
                <?php endif; ?>
                
                <div class="tv-series-details">
                    <h1 class="tv-series-title" itemprop="name">
                        <?php echo esc_html($attributes['name']); ?>
                    </h1>
                    
                    <?php if ($attributes['original_name'] && $attributes['original_name'] !== $attributes['name']): ?>
                    <p class="tv-series-original-name">
                        <em><?php echo esc_html($attributes['original_name']); ?></em>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($attributes['tagline']): ?>
                    <p class="tv-series-tagline">
                        <?php echo esc_html($attributes['tagline']); ?>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($attributes['overview']): ?>
                    <div class="tv-series-overview" itemprop="description">
                        <?php echo wp_kses_post(wpautop($attributes['overview'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="tv-series-meta">
                        <?php if ($attributes['first_air_date']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('First Air Date:', 'tmu-theme'); ?></span>
                            <span class="meta-value" itemprop="startDate">
                                <?php echo esc_html(self::format_date($attributes['first_air_date'], 'F j, Y')); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['last_air_date']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Last Air Date:', 'tmu-theme'); ?></span>
                            <span class="meta-value" itemprop="endDate">
                                <?php echo esc_html(self::format_date($attributes['last_air_date'], 'F j, Y')); ?>
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
                        
                        <?php if ($attributes['type']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Type:', 'tmu-theme'); ?></span>
                            <span class="meta-value">
                                <?php echo esc_html($attributes['type']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['number_of_seasons']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Seasons:', 'tmu-theme'); ?></span>
                            <span class="meta-value" itemprop="numberOfSeasons">
                                <?php echo esc_html($attributes['number_of_seasons']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['number_of_episodes']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Episodes:', 'tmu-theme'); ?></span>
                            <span class="meta-value" itemprop="numberOfEpisodes">
                                <?php echo esc_html($attributes['number_of_episodes']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['episode_run_time'] && !empty($attributes['episode_run_time'])): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Episode Runtime:', 'tmu-theme'); ?></span>
                            <span class="meta-value">
                                <?php echo esc_html(self::format_runtime($attributes['episode_run_time'][0])); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['tmdb_vote_average']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('TMDB Rating:', 'tmu-theme'); ?></span>
                            <span class="meta-value" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                                <span itemprop="ratingValue"><?php echo esc_html(number_format($attributes['tmdb_vote_average'], 1)); ?></span>/10
                                <?php if ($attributes['tmdb_vote_count']): ?>
                                <span class="vote-count">
                                    (<span itemprop="ratingCount"><?php echo esc_html(number_format($attributes['tmdb_vote_count'])); ?></span> votes)
                                </span>
                                <?php endif; ?>
                                <meta itemprop="bestRating" content="10">
                                <meta itemprop="worstRating" content="1">
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['homepage']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Official Website:', 'tmu-theme'); ?></span>
                            <span class="meta-value">
                                <a href="<?php echo esc_url($attributes['homepage']); ?>" 
                                   target="_blank" rel="noopener noreferrer" itemprop="url">
                                    <?php _e('Visit Website', 'tmu-theme'); ?>
                                </a>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['imdb_id']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('IMDB:', 'tmu-theme'); ?></span>
                            <span class="meta-value">
                                <a href="https://www.imdb.com/title/<?php echo esc_attr($attributes['imdb_id']); ?>" 
                                   target="_blank" rel="noopener noreferrer">
                                    <?php echo esc_html($attributes['imdb_id']); ?>
                                </a>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($attributes['created_by'] && !empty($attributes['created_by'])): ?>
                    <div class="created-by">
                        <h3><?php _e('Created By', 'tmu-theme'); ?></h3>
                        <ul>
                            <?php foreach ($attributes['created_by'] as $creator): ?>
                            <li itemprop="creator" itemscope itemtype="https://schema.org/Person">
                                <span itemprop="name"><?php echo esc_html($creator['name'] ?? ''); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($attributes['networks'] && !empty($attributes['networks'])): ?>
                    <div class="networks">
                        <h3><?php _e('Networks', 'tmu-theme'); ?></h3>
                        <ul>
                            <?php foreach ($attributes['networks'] as $network): ?>
                            <li itemprop="broadcastAffiliation" itemscope itemtype="https://schema.org/Organization">
                                <span itemprop="name"><?php echo esc_html($network['name'] ?? ''); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($attributes['production_companies'] && !empty($attributes['production_companies'])): ?>
                    <div class="production-companies">
                        <h3><?php _e('Production Companies', 'tmu-theme'); ?></h3>
                        <ul>
                            <?php foreach ($attributes['production_companies'] as $company): ?>
                            <li itemprop="productionCompany" itemscope itemtype="https://schema.org/Organization">
                                <span itemprop="name"><?php echo esc_html($company['name'] ?? ''); ?></span>
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
        
        $table_name = $wpdb->prefix . 'tmu_tv_series';
        $attributes = self::validate_attributes($attributes);
        
        // Convert dates to timestamps
        $release_timestamp = null;
        if (!empty($attributes['first_air_date'])) {
            $release_timestamp = strtotime($attributes['first_air_date']);
        }
        
        $data = [
            'ID' => $post_id,
            'tmdb_id' => $attributes['tmdb_id'],
            'release_date' => $attributes['first_air_date'],
            'release_timestamp' => $release_timestamp,
            'original_title' => $attributes['original_name'],
            'finished' => $attributes['status'] === 'Ended' ? 'Yes' : 'No',
            'tagline' => $attributes['tagline'],
            'production_house' => !empty($attributes['production_companies']) ? json_encode($attributes['production_companies']) : null,
            'streaming_platforms' => $attributes['streaming_platforms'] ?? null,
            'schedule_time' => $attributes['schedule_time'] ?? null,
            'runtime' => !empty($attributes['episode_run_time']) ? $attributes['episode_run_time'][0] : null,
            'certification' => $attributes['certification'] ?? null,
            'revenue' => $attributes['revenue'] ?? null,
            'budget' => $attributes['budget'] ?? null,
            'star_cast' => $attributes['star_cast'] ?? null,
            'credits' => !empty($attributes['credits']) ? json_encode($attributes['credits']) : null,
            'credits_temp' => null,
            'videos' => !empty($attributes['videos']) ? json_encode($attributes['videos']) : null,
            'images' => !empty($attributes['images']) ? json_encode($attributes['images']) : null,
            'seasons' => !empty($attributes['seasons']) ? json_encode($attributes['seasons']) : null,
            'last_season' => $attributes['number_of_seasons'] ?? null,
            'last_episode' => $attributes['number_of_episodes'] ?? null,
            'average_rating' => $attributes['tmdb_vote_average'] ?? 0,
            'vote_count' => $attributes['tmdb_vote_count'] ?? 0,
            'popularity' => $attributes['tmdb_popularity'] ?? 0,
            'where_to_watch' => $attributes['where_to_watch'] ?? null,
            'total_average_rating' => $attributes['local_rating'] ?? 0,
            'total_vote_count' => $attributes['local_rating_count'] ?? 0,
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
    
    /**
     * Load block data from database
     * 
     * @param int $post_id Post ID
     * @return array Block attributes
     */
    public static function load_from_database($post_id): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_tv_series';
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE ID = %d",
            $post_id
        ), ARRAY_A);
        
        if (!$data) {
            return self::get_default_attributes();
        }
        
        // Map database fields to block attributes
        $mapped_data = [
            'tmdb_id' => $data['tmdb_id'],
            'first_air_date' => $data['release_date'],
            'original_name' => $data['original_title'],
            'status' => $data['finished'] === 'Yes' ? 'Ended' : 'Returning Series',
            'tagline' => $data['tagline'],
            'production_companies' => !empty($data['production_house']) ? json_decode($data['production_house'], true) : [],
            'streaming_platforms' => $data['streaming_platforms'],
            'schedule_time' => $data['schedule_time'],
            'episode_run_time' => [$data['runtime']],
            'certification' => $data['certification'],
            'revenue' => $data['revenue'],
            'budget' => $data['budget'],
            'star_cast' => $data['star_cast'],
            'credits' => !empty($data['credits']) ? json_decode($data['credits'], true) : null,
            'videos' => !empty($data['videos']) ? json_decode($data['videos'], true) : null,
            'images' => !empty($data['images']) ? json_decode($data['images'], true) : null,
            'seasons' => !empty($data['seasons']) ? json_decode($data['seasons'], true) : [],
            'number_of_seasons' => $data['last_season'],
            'number_of_episodes' => $data['last_episode'],
            'tmdb_vote_average' => $data['average_rating'],
            'tmdb_vote_count' => $data['vote_count'],
            'tmdb_popularity' => $data['popularity'],
            'where_to_watch' => $data['where_to_watch'],
            'local_rating' => $data['total_average_rating'],
            'local_rating_count' => $data['total_vote_count'],
        ];
        
        return array_merge(self::get_default_attributes(), $mapped_data);
    }
}