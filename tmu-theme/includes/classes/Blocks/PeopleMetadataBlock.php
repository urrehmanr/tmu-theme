<?php
/**
 * People Metadata Block
 * 
 * Cast/crew people metadata management with biography,
 * filmography, and person-specific features.
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

namespace TMU\Blocks;

/**
 * PeopleMetadataBlock class
 * 
 * Handles people metadata for cast and crew
 */
class PeopleMetadataBlock extends BaseBlock {
    
    /**
     * Block properties
     */
    protected $name = 'people-metadata';
    protected $title = 'People Metadata';
    protected $description = 'Cast/crew people metadata management';
    protected $icon = 'businessman';
    protected $keywords = ['people', 'actor', 'director', 'cast', 'crew'];
    protected $post_types = ['people'];
    
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
            'biography' => [
                'type' => 'string',
                'default' => '',
            ],
            
            // Personal Information
            'birthday' => [
                'type' => 'string',
                'default' => '',
            ],
            'deathday' => [
                'type' => 'string',
                'default' => '',
            ],
            'place_of_birth' => [
                'type' => 'string',
                'default' => '',
            ],
            'gender' => [
                'type' => 'string',
                'default' => '',
                'enum' => ['Male', 'Female', 'Non-binary', 'Other'],
            ],
            
            // Professional Information
            'known_for_department' => [
                'type' => 'string',
                'default' => '',
                'enum' => ['Acting', 'Directing', 'Writing', 'Producing', 'Cinematography', 'Editing', 'Sound', 'Art', 'Costume & Make-Up'],
            ],
            'also_known_as' => [
                'type' => 'array',
                'default' => [],
            ],
            
            // Career Information
            'profession' => [
                'type' => 'array',
                'default' => [],
            ],
            'active_years' => [
                'type' => 'string',
                'default' => '',
            ],
            
            // Media
            'profile_path' => [
                'type' => 'string',
                'default' => '',
            ],
            'homepage' => [
                'type' => 'string',
                'default' => '',
            ],
            
            // TMDB Data
            'tmdb_popularity' => [
                'type' => 'number',
                'default' => null,
            ],
            'adult' => [
                'type' => 'boolean',
                'default' => false,
            ],
            
            // Extended Data
            'external_ids' => [
                'type' => 'object',
                'default' => null,
            ],
            'images' => [
                'type' => 'object',
                'default' => null,
            ],
            'movie_credits' => [
                'type' => 'object',
                'default' => null,
            ],
            'tv_credits' => [
                'type' => 'object',
                'default' => null,
            ],
            'combined_credits' => [
                'type' => 'object',
                'default' => null,
            ],
            
            // Additional Database Fields  
            'marital_status' => [
                'type' => 'string',
                'default' => '',
            ],
            'net_worth' => [
                'type' => 'number',
                'default' => null,
            ],
            'no_movies' => [
                'type' => 'number',
                'default' => 0,
            ],
            'no_tv_series' => [
                'type' => 'number',
                'default' => 0,
            ],
            'no_dramas' => [
                'type' => 'number',
                'default' => 0,
            ],
            'videos' => [
                'type' => 'object',
                'default' => null,
            ],
            
            // Local Data
            'featured' => [
                'type' => 'boolean',
                'default' => false,
            ],
            'last_tmdb_sync' => [
                'type' => 'string',
                'default' => '',
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
        <div class="tmu-people-metadata" itemscope itemtype="https://schema.org/Person">
            <div class="people-info">
                <?php if ($attributes['profile_path']): ?>
                <div class="people-profile">
                    <img src="<?php echo esc_url(self::get_tmdb_image_url($attributes['profile_path'], 'w500')); ?>" 
                         alt="<?php echo esc_attr($attributes['name']); ?>" 
                         itemprop="image" />
                </div>
                <?php endif; ?>
                
                <div class="people-details">
                    <h1 class="people-name" itemprop="name">
                        <?php echo esc_html($attributes['name']); ?>
                    </h1>
                    
                    <?php if ($attributes['original_name'] && $attributes['original_name'] !== $attributes['name']): ?>
                    <p class="people-original-name">
                        <em><?php echo esc_html($attributes['original_name']); ?></em>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($attributes['biography']): ?>
                    <div class="people-biography" itemprop="description">
                        <?php echo wp_kses_post(wpautop($attributes['biography'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="people-meta">
                        <?php if ($attributes['birthday']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Born:', 'tmu-theme'); ?></span>
                            <span class="meta-value" itemprop="birthDate">
                                <?php echo esc_html(self::format_date($attributes['birthday'], 'F j, Y')); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['deathday']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Died:', 'tmu-theme'); ?></span>
                            <span class="meta-value" itemprop="deathDate">
                                <?php echo esc_html(self::format_date($attributes['deathday'], 'F j, Y')); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['place_of_birth']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Place of Birth:', 'tmu-theme'); ?></span>
                            <span class="meta-value" itemprop="birthPlace">
                                <?php echo esc_html($attributes['place_of_birth']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['known_for_department']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Known For:', 'tmu-theme'); ?></span>
                            <span class="meta-value" itemprop="jobTitle">
                                <?php echo esc_html($attributes['known_for_department']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['active_years']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Active Years:', 'tmu-theme'); ?></span>
                            <span class="meta-value">
                                <?php echo esc_html($attributes['active_years']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['homepage']): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php _e('Website:', 'tmu-theme'); ?></span>
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
                                <a href="https://www.imdb.com/name/<?php echo esc_attr($attributes['imdb_id']); ?>" 
                                   target="_blank" rel="noopener noreferrer">
                                    <?php echo esc_html($attributes['imdb_id']); ?>
                                </a>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($attributes['also_known_as'] && !empty($attributes['also_known_as'])): ?>
                    <div class="also-known-as">
                        <h3><?php _e('Also Known As', 'tmu-theme'); ?></h3>
                        <ul>
                            <?php foreach ($attributes['also_known_as'] as $alias): ?>
                            <li itemprop="alternateName">
                                <?php echo esc_html($alias); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($attributes['profession'] && !empty($attributes['profession'])): ?>
                    <div class="profession">
                        <h3><?php _e('Profession', 'tmu-theme'); ?></h3>
                        <ul>
                            <?php foreach ($attributes['profession'] as $prof): ?>
                            <li>
                                <?php echo esc_html($prof); ?>
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
        
        $table_name = $wpdb->prefix . 'tmu_people';
        $attributes = self::validate_attributes($attributes);
        
        $data = [
            'ID' => $post_id,
            'name' => $attributes['name'],
            'date_of_birth' => $attributes['birthday'],
            'gender' => $attributes['gender'],
            'nick_name' => !empty($attributes['also_known_as']) ? implode(', ', $attributes['also_known_as']) : null,
            'marital_status' => $attributes['marital_status'] ?? null,
            'basic' => $attributes['biography'],
            'videos' => !empty($attributes['videos']) ? json_encode($attributes['videos']) : null,
            'photos' => !empty($attributes['images']) ? json_encode($attributes['images']) : null,
            'profession' => !empty($attributes['profession']) ? json_encode($attributes['profession']) : null,
            'net_worth' => $attributes['net_worth'] ?? null,
            'tmdb_id' => $attributes['tmdb_id'],
            'birthplace' => $attributes['place_of_birth'],
            'dead_on' => $attributes['deathday'],
            'social_media_account' => !empty($attributes['external_ids']) ? json_encode($attributes['external_ids']) : null,
            'no_movies' => $attributes['no_movies'] ?? 0,
            'no_tv_series' => $attributes['no_tv_series'] ?? 0,
            'no_dramas' => $attributes['no_dramas'] ?? 0,
            'known_for' => $attributes['known_for_department'],
            'popularity' => $attributes['tmdb_popularity'] ?? 0,
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
        
        $table_name = $wpdb->prefix . 'tmu_people';
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE ID = %d",
            $post_id
        ), ARRAY_A);
        
        if (!$data) {
            return self::get_default_attributes();
        }
        
        // Map database fields to block attributes
        $mapped_data = [
            'name' => $data['name'],
            'birthday' => $data['date_of_birth'],
            'gender' => $data['gender'],
            'also_known_as' => !empty($data['nick_name']) ? explode(', ', $data['nick_name']) : [],
            'marital_status' => $data['marital_status'],
            'biography' => $data['basic'],
            'videos' => !empty($data['videos']) ? json_decode($data['videos'], true) : null,
            'images' => !empty($data['photos']) ? json_decode($data['photos'], true) : null,
            'profession' => !empty($data['profession']) ? json_decode($data['profession'], true) : [],
            'net_worth' => $data['net_worth'],
            'tmdb_id' => $data['tmdb_id'],
            'place_of_birth' => $data['birthplace'],
            'deathday' => $data['dead_on'],
            'external_ids' => !empty($data['social_media_account']) ? json_decode($data['social_media_account'], true) : null,
            'no_movies' => $data['no_movies'],
            'no_tv_series' => $data['no_tv_series'],
            'no_dramas' => $data['no_dramas'],
            'known_for_department' => $data['known_for'],
            'tmdb_popularity' => $data['popularity'],
        ];
        
        return array_merge(self::get_default_attributes(), $mapped_data);
    }
}