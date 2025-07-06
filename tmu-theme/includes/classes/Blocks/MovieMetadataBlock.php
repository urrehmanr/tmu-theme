<?php
/**
 * Movie Metadata Block
 * 
 * Comprehensive movie metadata management block with TMDB integration,
 * financial tracking, ratings, and complete movie information.
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

namespace TMU\Blocks;

/**
 * MovieMetadataBlock class
 * 
 * Handles movie metadata with comprehensive TMDB integration
 */
class MovieMetadataBlock extends BaseBlock {
    
    /**
     * Block properties
     */
    protected $name = 'movie-metadata';
    protected $title = 'Movie Metadata';
    protected $description = 'Comprehensive movie metadata management with TMDB integration';
    protected $icon = 'video-alt3';
    protected $keywords = ['movie', 'film', 'metadata', 'tmdb'];
    protected $post_types = ['movie'];
    
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
            
            // Release Information
            'release_date' => [
                'type' => 'string',
                'default' => '',
            ],
            'status' => [
                'type' => 'string',
                'default' => 'Released',
                'enum' => ['Released', 'In Production', 'Post Production', 'Planned', 'Canceled'],
            ],
            'runtime' => [
                'type' => 'number',
                'default' => null,
            ],
            
            // Financial Information
            'budget' => [
                'type' => 'number',
                'default' => null,
            ],
            'revenue' => [
                'type' => 'number',
                'default' => null,
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
            'video' => [
                'type' => 'boolean',
                'default' => false,
            ],
            
            // Collection Information
            'belongs_to_collection' => [
                'type' => 'object',
                'default' => null,
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
            'reviews' => [
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
        
        if (empty($attributes['title'])) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="tmu-movie-metadata" itemscope itemtype="https://schema.org/Movie">
            <div class="movie-info">
                <?php if ($attributes['poster_path']): ?>
                <div class="movie-poster">
                    <img src="<?php echo esc_url(self::get_tmdb_image_url($attributes['poster_path'], 'w500')); ?>" 
                         alt="<?php echo esc_attr($attributes['title']); ?>" 
                         itemprop="image" />
                </div>
                <?php endif; ?>
                
                <div class="movie-details">
                    <h1 class="movie-title" itemprop="name">
                        <?php echo esc_html($attributes['title']); ?>
                    </h1>
                    
                    <?php if ($attributes['original_title'] && $attributes['original_title'] !== $attributes['title']): ?>
                    <p class="movie-original-title">
                        <em><?php echo esc_html($attributes['original_title']); ?></em>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($attributes['tagline']): ?>
                    <p class="movie-tagline" itemprop="tagline">
                        <?php echo esc_html($attributes['tagline']); ?>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($attributes['overview']): ?>
                    <div class="movie-overview" itemprop="description">
                        <?php echo wp_kses_post(wpautop($attributes['overview'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="movie-meta">
                        <?php if ($attributes['release_date']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Release Date:', 'tmu-theme'); ?></span>
                            <span class="meta-value" itemprop="datePublished">
                                <?php echo esc_html(self::format_date($attributes['release_date'], 'F j, Y')); ?>
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
                        
                        <?php if ($attributes['status']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Status:', 'tmu-theme'); ?></span>
                            <span class="meta-value">
                                <?php echo esc_html($attributes['status']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['budget']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Budget:', 'tmu-theme'); ?></span>
                            <span class="meta-value">
                                <?php echo esc_html(self::format_currency($attributes['budget'])); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['revenue']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Revenue:', 'tmu-theme'); ?></span>
                            <span class="meta-value">
                                <?php echo esc_html(self::format_currency($attributes['revenue'])); ?>
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
        
        $table_name = $wpdb->prefix . 'tmu_movies';
        $attributes = self::validate_attributes($attributes);
        
        $data = [
            'post_id' => $post_id,
            'tmdb_id' => $attributes['tmdb_id'],
            'imdb_id' => $attributes['imdb_id'],
            'title' => $attributes['title'],
            'original_title' => $attributes['original_title'],
            'tagline' => $attributes['tagline'],
            'overview' => $attributes['overview'],
            'runtime' => $attributes['runtime'],
            'release_date' => $attributes['release_date'],
            'status' => $attributes['status'],
            'budget' => $attributes['budget'],
            'revenue' => $attributes['revenue'],
            'homepage' => $attributes['homepage'],
            'poster_path' => $attributes['poster_path'],
            'backdrop_path' => $attributes['backdrop_path'],
            'tmdb_vote_average' => $attributes['tmdb_vote_average'],
            'tmdb_vote_count' => $attributes['tmdb_vote_count'],
            'tmdb_popularity' => $attributes['tmdb_popularity'],
            'adult' => $attributes['adult'] ? 1 : 0,
            'video' => $attributes['video'] ? 1 : 0,
            'belongs_to_collection' => !empty($attributes['belongs_to_collection']) ? json_encode($attributes['belongs_to_collection']) : null,
            'production_companies' => !empty($attributes['production_companies']) ? json_encode($attributes['production_companies']) : null,
            'production_countries' => !empty($attributes['production_countries']) ? json_encode($attributes['production_countries']) : null,
            'spoken_languages' => !empty($attributes['spoken_languages']) ? json_encode($attributes['spoken_languages']) : null,
            'credits' => !empty($attributes['credits']) ? json_encode($attributes['credits']) : null,
            'external_ids' => !empty($attributes['external_ids']) ? json_encode($attributes['external_ids']) : null,
            'images' => !empty($attributes['images']) ? json_encode($attributes['images']) : null,
            'videos' => !empty($attributes['videos']) ? json_encode($attributes['videos']) : null,
            'reviews' => !empty($attributes['reviews']) ? json_encode($attributes['reviews']) : null,
            'similar' => !empty($attributes['similar']) ? json_encode($attributes['similar']) : null,
            'recommendations' => !empty($attributes['recommendations']) ? json_encode($attributes['recommendations']) : null,
            'local_rating' => $attributes['local_rating'],
            'local_rating_count' => $attributes['local_rating_count'],
            'watch_count' => $attributes['watch_count'],
            'last_tmdb_sync' => $attributes['last_tmdb_sync'],
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
    
    /**
     * Load block data from database
     * 
     * @param int $post_id Post ID
     * @return array Block attributes
     */
    public static function load_from_database($post_id): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_movies';
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE post_id = %d",
            $post_id
        ), ARRAY_A);
        
        if (!$data) {
            return self::get_default_attributes();
        }
        
        // Decode JSON fields
        $json_fields = ['belongs_to_collection', 'production_companies', 'production_countries', 
                       'spoken_languages', 'credits', 'external_ids', 'images', 'videos', 
                       'reviews', 'similar', 'recommendations'];
        
        foreach ($json_fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = json_decode($data[$field], true);
            }
        }
        
        // Convert boolean fields
        $boolean_fields = ['adult', 'video', 'featured', 'trending'];
        foreach ($boolean_fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = (bool) $data[$field];
            }
        }
        
        return $data;
    }
}