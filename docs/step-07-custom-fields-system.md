# Step 07: Custom Fields System - Complete Implementation

## Overview
This step implements a comprehensive custom fields system to replace the Meta Box plugin dependency while maintaining 100% functionality. The system includes post type fields, episode management, taxonomy fields, content blocks, and TMDB sync utilities.

## 1. Post Type Custom Fields

### 1.1 Main Content Type Fields
- **Movies** (`movie.php`) - Complex movie metadata with ratings, credits, images, TMDB sync
- **TV Series** (`tv-series.php`) - TV show metadata with seasons, networks, comprehensive credits
- **Dramas** (`drama.php`) - Drama series metadata with channels, episodes, special fields
- **People** (`people.php`) - Celebrity profiles with biography, filmography, social media

### 1.2 Episode Management Fields
- **TV Episodes** (`tv-episode.php`) - Individual episode metadata for TV series
- **Drama Episodes** (`drama-episode.php`) - Individual episode metadata for dramas
- **TV Seasons** (`tv-season.php`) - Season-level metadata for TV series

### 1.3 Media Content Fields
- **Videos** (`video.php`) - Video content management (trailers, clips, features, teasers, OSTs)

## 2. Taxonomy Custom Fields

### 2.1 Universal Taxonomy Fields
- **Channel/Network Images** (`channel-image.php`) - Logo attachments for all taxonomies
- **FAQs** (`faqs.php`) - Question/answer pairs for channels and genres

## 3. Content Block Fields (For Gutenberg Blocks)

### 3.1 Post Listing Blocks
- **Blog Posts List 1** (`blog-posts-list1.php`) - Content curation based on enabled features
- **Blog Posts List 2** (`blog-posts-list2.php`) - Alternative content listing format

### 3.2 Trending Content Blocks
- **Trending Dramas** (`trending-dramas.php`) - Curated trending content (YouTube, TV, Recommendations)

## 4. TMDB Sync Utilities

### 4.1 Data Fetching Controls
- **Fetch Data Movie** (`fetch-data-movie.php`) - TMDB sync controls for movies
- **Fetch Data TV Series** (`fetch-data-tv-series.php`) - TMDB sync controls for TV shows
- **Fetch Data Drama** (`fetch-data-drama.php`) - TMDB sync controls for dramas

## Implementation Strategy

### Phase 1: Core Field System Architecture

#### 1.1 Base Field Classes
```php
// src/CustomFields/BaseField.php
<?php
namespace TMU\CustomFields;

abstract class BaseField {
    protected $post_type;
    protected $table_name;
    protected $meta_box_id;
    protected $meta_box_title;
    
    abstract protected function define_fields(): array;
    abstract protected function get_storage_config(): array;
    
    public function register(): void {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta_box']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    public function add_meta_box(): void {
        add_meta_box(
            $this->meta_box_id,
            $this->meta_box_title,
            [$this, 'render_meta_box'],
            $this->post_type,
            'normal',
            'high'
        );
    }
    
    abstract public function render_meta_box($post): void;
    abstract public function save_meta_box($post_id): void;
}
```

#### 1.2 Complex Field Types
```php
// src/CustomFields/FieldTypes/GroupField.php
<?php
namespace TMU\CustomFields\FieldTypes;

class GroupField extends BaseFieldType {
    protected $fields = [];
    protected $collapsible = false;
    protected $clone = false;
    protected $sort_clone = false;
    
    public function render($value, $field_config): string {
        $html = '<div class="tmu-group-field">';
        
        if ($this->collapsible) {
            $html .= '<div class="tmu-group-toggle">';
            $html .= '<button type="button" class="tmu-toggle-button">' . $field_config['name'] . '</button>';
            $html .= '</div>';
            $html .= '<div class="tmu-group-content" style="display: none;">';
        }
        
        foreach ($this->fields as $field) {
            $html .= $this->render_field($field, $value);
        }
        
        if ($this->collapsible) {
            $html .= '</div>';
        }
        
        if ($this->clone) {
            $html .= '<div class="tmu-clone-controls">';
            $html .= '<button type="button" class="tmu-add-clone">Add</button>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
}
```

### Phase 2: Episode Management System

#### 2.1 TV Episode Fields
```php
// src/CustomFields/PostTypes/TvEpisodeFields.php
<?php
namespace TMU\CustomFields\PostTypes;

class TvEpisodeFields extends BaseField {
    protected $post_type = 'episode';
    protected $meta_box_id = 'tv-episode-fields';
    protected $meta_box_title = 'TV Series Episode';
    
    protected function define_fields(): array {
        return [
            'tv_series' => [
                'type' => 'post',
                'label' => 'TV Series',
                'post_type' => 'tv',
                'required' => true,
                'field_type' => 'select_advanced'
            ],
            'season_no' => [
                'type' => 'number',
                'label' => 'Season No',
                'required' => true
            ],
            'episode_no' => [
                'type' => 'number',
                'label' => 'Episode No',
                'required' => true
            ],
            'episode_title' => [
                'type' => 'text',
                'label' => 'Episode Title',
                'admin_column' => true
            ],
            'air_date' => [
                'type' => 'date',
                'label' => 'Air Date',
                'admin_column' => true
            ],
            'episode_type' => [
                'type' => 'select',
                'label' => 'Episode Type',
                'options' => [
                    'standard' => 'Standard',
                    'finale' => 'Finale',
                    'mid_season' => 'Mid Season',
                    'special' => 'Special'
                ]
            ],
            'runtime' => [
                'type' => 'text',
                'label' => 'Runtime'
            ],
            'overview' => [
                'type' => 'textarea',
                'label' => 'Overview'
            ],
            'credits' => [
                'type' => 'group',
                'label' => 'Credits',
                'fields' => [
                    'cast' => [
                        'type' => 'group',
                        'label' => 'Cast',
                        'clone' => true,
                        'collapsible' => true,
                        'fields' => [
                            'person' => [
                                'type' => 'post',
                                'label' => 'Person',
                                'post_type' => 'people',
                                'required' => true
                            ],
                            'department' => [
                                'type' => 'select',
                                'label' => 'Department',
                                'options' => ['Acting' => 'Acting'],
                                'default' => 'Acting'
                            ],
                            'acting_job' => [
                                'type' => 'text',
                                'label' => 'Character Role',
                                'placeholder' => 'Character (Role)'
                            ]
                        ]
                    ],
                    'crew' => [
                        'type' => 'group',
                        'label' => 'Crew',
                        'clone' => true,
                        'collapsible' => true,
                        'fields' => [
                            'person' => [
                                'type' => 'post',
                                'label' => 'Person',
                                'post_type' => 'people',
                                'required' => true
                            ],
                            'department' => [
                                'type' => 'select',
                                'label' => 'Department',
                                'options' => $this->get_crew_departments()
                            ],
                            'directing_job' => [
                                'type' => 'select',
                                'label' => 'Directing Job',
                                'options' => $this->get_directing_jobs(),
                                'conditional' => ['department' => 'Directing']
                            ],
                            'crew_job' => [
                                'type' => 'select',
                                'label' => 'Crew Job',
                                'options' => $this->get_crew_jobs(),
                                'conditional' => ['department' => 'Crew']
                            ],
                            'production_job' => [
                                'type' => 'select',
                                'label' => 'Production Job',
                                'options' => $this->get_production_jobs(),
                                'conditional' => ['department' => 'Production']
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
    
    protected function get_storage_config(): array {
        return [
            'type' => 'custom_table',
            'table' => 'tmu_tv_series_episodes'
        ];
    }
}
```

#### 2.2 Drama Episode Fields
```php
// src/CustomFields/PostTypes/DramaEpisodeFields.php
<?php
namespace TMU\CustomFields\PostTypes;

class DramaEpisodeFields extends BaseField {
    protected $post_type = 'drama-episode';
    protected $meta_box_id = 'drama-episode-fields';
    protected $meta_box_title = 'Drama Episode';
    
    protected function define_fields(): array {
        return [
            'dramas' => [
                'type' => 'post',
                'label' => 'Drama',
                'post_type' => 'drama',
                'required' => true,
                'field_type' => 'select_advanced'
            ],
            'episode_no' => [
                'type' => 'number',
                'label' => 'Episode No',
                'required' => true
            ],
            'episode_title' => [
                'type' => 'text',
                'label' => 'Episode Title',
                'admin_column' => true
            ],
            'air_date' => [
                'type' => 'date',
                'label' => 'Air Date',
                'admin_column' => true
            ],
            'episode_type' => [
                'type' => 'select',
                'label' => 'Episode Type',
                'options' => [
                    'standard' => 'Standard',
                    'finale' => 'Finale',
                    'mid_season' => 'Mid Season',
                    'special' => 'Special'
                ]
            ],
            'runtime' => [
                'type' => 'text',
                'label' => 'Runtime'
            ],
            'overview' => [
                'type' => 'wysiwyg',
                'label' => 'Overview'
            ],
            'credits' => [
                'type' => 'group',
                'label' => 'Credits',
                'fields' => [
                    // Same structure as TV episodes but for dramas
                ]
            ]
        ];
    }
    
    protected function get_storage_config(): array {
        return [
            'type' => 'custom_table',
            'table' => 'tmu_dramas_episodes'
        ];
    }
}
```

#### 2.3 TV Season Fields
```php
// src/CustomFields/PostTypes/TvSeasonFields.php
<?php
namespace TMU\CustomFields\PostTypes;

class TvSeasonFields extends BaseField {
    protected $post_type = 'season';
    protected $meta_box_id = 'tv-season-fields';
    protected $meta_box_title = 'TV Series Season';
    
    protected function define_fields(): array {
        return [
            'season_no' => [
                'type' => 'number',
                'label' => 'Season No',
                'required' => true
            ],
            'season_name' => [
                'type' => 'text',
                'label' => 'Season Name'
            ],
            'tv_series' => [
                'type' => 'post',
                'label' => 'TV Series',
                'post_type' => 'tv',
                'required' => true,
                'field_type' => 'select_advanced'
            ],
            'air_date' => [
                'type' => 'date',
                'label' => 'Air Date'
            ]
        ];
    }
    
    protected function get_storage_config(): array {
        return [
            'type' => 'custom_table',
            'table' => 'tmu_tv_series_seasons'
        ];
    }
}
```

### Phase 3: Video Content Management

#### 3.1 Video Fields
```php
// src/CustomFields/PostTypes/VideoFields.php
<?php
namespace TMU\CustomFields\PostTypes;

class VideoFields extends BaseField {
    protected $post_type = 'video';
    protected $meta_box_id = 'video-fields';
    protected $meta_box_title = 'Video';
    
    protected function define_fields(): array {
        $content_type_options = [
            'Trailer' => 'Trailer',
            'Clip' => 'Clip',
            'Feature' => 'Feature',
            'Teaser' => 'Teaser'
        ];
        
        // Add OST option if dramas are enabled
        if (get_option('tmu_dramas') === 'on') {
            $content_type_options['OST'] = 'OST';
        }
        
        return [
            'video_data' => [
                'type' => 'group',
                'label' => 'Video Data',
                'fields' => [
                    'source' => [
                        'type' => 'text',
                        'label' => 'Source'
                    ],
                    'content_type' => [
                        'type' => 'select',
                        'label' => 'Content Type',
                        'options' => $content_type_options
                    ]
                ]
            ],
            'post_id' => [
                'type' => 'post',
                'label' => 'Related Content',
                'post_type' => ['tv', 'movie', 'people', 'drama'],
                'field_type' => 'select_advanced',
                'admin_column' => true
            ]
        ];
    }
    
    protected function get_storage_config(): array {
        return [
            'type' => 'custom_table',
            'table' => 'tmu_videos'
        ];
    }
}
```

### Phase 4: Taxonomy Custom Fields

#### 4.1 Taxonomy Image Fields
```php
// src/CustomFields/Taxonomies/TaxonomyImageFields.php
<?php
namespace TMU\CustomFields\Taxonomies;

class TaxonomyImageFields {
    protected $taxonomies = [
        'channel', 'network', 'nationality', 'language', 
        'keyword', 'by-year', 'genre', 'country'
    ];
    
    public function register(): void {
        foreach ($this->taxonomies as $taxonomy) {
            add_action("{$taxonomy}_add_form_fields", [$this, 'add_form_fields']);
            add_action("{$taxonomy}_edit_form_fields", [$this, 'edit_form_fields']);
            add_action("edited_{$taxonomy}", [$this, 'save_fields']);
            add_action("create_{$taxonomy}", [$this, 'save_fields']);
        }
    }
    
    public function add_form_fields($taxonomy): void {
        ?>
        <div class="form-field">
            <label for="logo"><?php _e('Logo', 'tmu'); ?></label>
            <div class="tmu-image-upload">
                <input type="hidden" name="logo" id="logo" value="">
                <button type="button" class="button tmu-upload-button">
                    <?php _e('Upload Logo', 'tmu'); ?>
                </button>
                <div class="tmu-image-preview"></div>
            </div>
            <p><?php _e('Upload a logo for this term', 'tmu'); ?></p>
        </div>
        <?php
    }
    
    public function edit_form_fields($term): void {
        $logo = get_term_meta($term->term_id, 'logo', true);
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="logo"><?php _e('Logo', 'tmu'); ?></label>
            </th>
            <td>
                <div class="tmu-image-upload">
                    <input type="hidden" name="logo" id="logo" value="<?php echo esc_attr($logo); ?>">
                    <button type="button" class="button tmu-upload-button">
                        <?php _e('Upload Logo', 'tmu'); ?>
                    </button>
                    <div class="tmu-image-preview">
                        <?php if ($logo): ?>
                            <img src="<?php echo wp_get_attachment_url($logo); ?>" alt="Logo" style="max-width: 100px;">
                        <?php endif; ?>
                    </div>
                </div>
            </td>
        </tr>
        <?php
    }
    
    public function save_fields($term_id): void {
        if (isset($_POST['logo'])) {
            update_term_meta($term_id, 'logo', sanitize_text_field($_POST['logo']));
        }
    }
}
```

#### 4.2 FAQ Fields for Taxonomies
```php
// src/CustomFields/Taxonomies/FaqFields.php
<?php
namespace TMU\CustomFields\Taxonomies;

class FaqFields {
    protected $taxonomies = ['channel', 'genre'];
    
    public function register(): void {
        foreach ($this->taxonomies as $taxonomy) {
            add_action("{$taxonomy}_add_form_fields", [$this, 'add_form_fields']);
            add_action("{$taxonomy}_edit_form_fields", [$this, 'edit_form_fields']);
            add_action("edited_{$taxonomy}", [$this, 'save_fields']);
            add_action("create_{$taxonomy}", [$this, 'save_fields']);
        }
    }
    
    public function add_form_fields($taxonomy): void {
        ?>
        <div class="form-field">
            <label><?php _e('FAQs', 'tmu'); ?></label>
            <div class="tmu-faq-group">
                <div class="tmu-faq-item">
                    <input type="text" name="faqs[0][question]" placeholder="<?php _e('Question', 'tmu'); ?>">
                    <textarea name="faqs[0][answer]" placeholder="<?php _e('Answer', 'tmu'); ?>"></textarea>
                    <button type="button" class="button tmu-remove-faq"><?php _e('Remove', 'tmu'); ?></button>
                </div>
            </div>
            <button type="button" class="button tmu-add-faq"><?php _e('Add FAQ', 'tmu'); ?></button>
        </div>
        <?php
    }
    
    public function edit_form_fields($term): void {
        $faqs = get_term_meta($term->term_id, 'faqs', true);
        if (!is_array($faqs)) $faqs = [];
        ?>
        <tr class="form-field">
            <th scope="row">
                <label><?php _e('FAQs', 'tmu'); ?></label>
            </th>
            <td>
                <div class="tmu-faq-group">
                    <?php foreach ($faqs as $index => $faq): ?>
                        <div class="tmu-faq-item">
                            <input type="text" name="faqs[<?php echo $index; ?>][question]" 
                                   value="<?php echo esc_attr($faq['question']); ?>" 
                                   placeholder="<?php _e('Question', 'tmu'); ?>">
                            <textarea name="faqs[<?php echo $index; ?>][answer]" 
                                      placeholder="<?php _e('Answer', 'tmu'); ?>"><?php echo esc_textarea($faq['answer']); ?></textarea>
                            <button type="button" class="button tmu-remove-faq"><?php _e('Remove', 'tmu'); ?></button>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($faqs)): ?>
                        <div class="tmu-faq-item">
                            <input type="text" name="faqs[0][question]" placeholder="<?php _e('Question', 'tmu'); ?>">
                            <textarea name="faqs[0][answer]" placeholder="<?php _e('Answer', 'tmu'); ?>"></textarea>
                            <button type="button" class="button tmu-remove-faq"><?php _e('Remove', 'tmu'); ?></button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="button tmu-add-faq"><?php _e('Add FAQ', 'tmu'); ?></button>
            </td>
        </tr>
        <?php
    }
    
    public function save_fields($term_id): void {
        if (isset($_POST['faqs']) && is_array($_POST['faqs'])) {
            $faqs = [];
            foreach ($_POST['faqs'] as $faq) {
                if (!empty($faq['question']) && !empty($faq['answer'])) {
                    $faqs[] = [
                        'question' => sanitize_text_field($faq['question']),
                        'answer' => wp_kses_post($faq['answer'])
                    ];
                }
            }
            update_term_meta($term_id, 'faqs', $faqs);
        }
    }
}
```

### Phase 5: Gutenberg Block Fields

#### 5.1 Content Block System
```php
// src/CustomFields/Blocks/ContentBlockFields.php
<?php
namespace TMU\CustomFields\Blocks;

class ContentBlockFields {
    public function register(): void {
        add_action('init', [$this, 'register_block_fields']);
    }
    
    public function register_block_fields(): void {
        // Register post listing blocks
        $this->register_post_listing_block();
        $this->register_trending_block();
    }
    
    private function register_post_listing_block(): void {
        // This will be converted to Gutenberg blocks
        // but for now we maintain the meta box approach
        add_action('add_meta_boxes', function() {
            add_meta_box(
                'post-listing-fields',
                __('Post Listing Configuration', 'tmu'),
                [$this, 'render_post_listing_fields'],
                ['post', 'page'],
                'normal',
                'high'
            );
        });
    }
    
    public function render_post_listing_fields($post): void {
        $fields = [];
        
        if (get_option('tmu_dramas') === 'on') {
            $fields['dramas'] = [
                'type' => 'post',
                'label' => 'Dramas',
                'post_type' => 'drama',
                'multiple' => true
            ];
        } else {
            $fields['movies'] = [
                'type' => 'post',
                'label' => 'Movies',
                'post_type' => 'movie',
                'multiple' => true
            ];
            $fields['tv_show'] = [
                'type' => 'post',
                'label' => 'TV Shows',
                'post_type' => 'tv',
                'multiple' => true
            ];
        }
        
        foreach ($fields as $field_id => $field_config) {
            $this->render_field($field_id, $field_config, $post);
        }
    }
    
    private function register_trending_block(): void {
        if (get_option('tmu_dramas') === 'on') {
            add_action('admin_menu', function() {
                add_submenu_page(
                    'edit.php?post_type=drama',
                    __('Trending Configuration', 'tmu'),
                    __('Trending', 'tmu'),
                    'manage_options',
                    'trending-dramas',
                    [$this, 'render_trending_page']
                );
            });
        }
    }
    
    public function render_trending_page(): void {
        ?>
        <div class="wrap">
            <h1><?php _e('Trending Dramas Configuration', 'tmu'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('trending_dramas'); ?>
                <?php do_settings_sections('trending_dramas'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Trending YouTube', 'tmu'); ?></th>
                        <td>
                            <select name="trending_youtube[]" multiple class="tmu-post-selector">
                                <?php $this->render_drama_options('trending_youtube'); ?>
                            </select>
                            <p class="description"><?php _e('Select top 10 trending dramas on YouTube', 'tmu'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Trending TV', 'tmu'); ?></th>
                        <td>
                            <select name="trending_tv[]" multiple class="tmu-post-selector">
                                <?php $this->render_drama_options('trending_tv'); ?>
                            </select>
                            <p class="description"><?php _e('Select top 10 trending dramas on TV', 'tmu'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Our Recommendations', 'tmu'); ?></th>
                        <td>
                            <select name="trending_our_recommendation[]" multiple class="tmu-post-selector">
                                <?php $this->render_drama_options('trending_our_recommendation'); ?>
                            </select>
                            <p class="description"><?php _e('Select top 10 recommended dramas', 'tmu'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
```

### Phase 6: TMDB Sync Utility Fields

#### 6.1 TMDB Sync Controls
```php
// src/CustomFields/TMDB/SyncControlFields.php
<?php
namespace TMU\CustomFields\TMDB;

class SyncControlFields {
    public function register(): void {
        add_action('add_meta_boxes', [$this, 'add_sync_meta_boxes']);
        add_action('wp_ajax_tmu_sync_tmdb_data', [$this, 'handle_sync_request']);
    }
    
    public function add_sync_meta_boxes(): void {
        $post_types = ['movie', 'tv', 'drama'];
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'tmu-tmdb-sync-' . $post_type,
                __('TMDB Sync Controls', 'tmu'),
                [$this, 'render_sync_controls'],
                $post_type,
                'side',
                'high'
            );
        }
    }
    
    public function render_sync_controls($post): void {
        wp_nonce_field('tmu_sync_tmdb', 'tmu_sync_nonce');
        
        ?>
        <div class="tmu-sync-controls">
            <h4><?php _e('Fetch TMDB Data', 'tmu'); ?></h4>
            
            <div class="tmu-sync-options">
                <label>
                    <input type="checkbox" name="sync_images" value="1">
                    <?php _e('Sync Images', 'tmu'); ?>
                </label>
                <br>
                <label>
                    <input type="checkbox" name="sync_videos" value="1">
                    <?php _e('Sync Videos', 'tmu'); ?>
                </label>
                <br>
                <label>
                    <input type="checkbox" name="sync_credits" value="1">
                    <?php _e('Sync Credits', 'tmu'); ?>
                </label>
            </div>
            
            <div class="tmu-sync-actions">
                <button type="button" class="button button-primary" id="tmu-sync-button">
                    <?php _e('Sync from TMDB', 'tmu'); ?>
                </button>
                <div class="tmu-sync-progress" style="display: none;">
                    <div class="tmu-progress-bar">
                        <div class="tmu-progress-fill"></div>
                    </div>
                    <div class="tmu-sync-status"></div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#tmu-sync-button').on('click', function() {
                var $button = $(this);
                var $progress = $('.tmu-sync-progress');
                var $status = $('.tmu-sync-status');
                
                var syncOptions = {
                    post_id: <?php echo $post->ID; ?>,
                    images: $('input[name="sync_images"]').is(':checked'),
                    videos: $('input[name="sync_videos"]').is(':checked'),
                    credits: $('input[name="sync_credits"]').is(':checked'),
                    _wpnonce: '<?php echo wp_create_nonce('tmu_sync_tmdb'); ?>'
                };
                
                $button.prop('disabled', true);
                $progress.show();
                $status.text('<?php _e('Initializing sync...', 'tmu'); ?>');
                
                $.post(ajaxurl, {
                    action: 'tmu_sync_tmdb_data',
                    ...syncOptions
                }, function(response) {
                    if (response.success) {
                        $status.text('<?php _e('Sync completed successfully!', 'tmu'); ?>');
                        location.reload();
                    } else {
                        $status.text('<?php _e('Sync failed: ', 'tmu'); ?>' + response.data);
                    }
                }).fail(function() {
                    $status.text('<?php _e('Sync failed: Network error', 'tmu'); ?>');
                }).always(function() {
                    $button.prop('disabled', false);
                    setTimeout(function() {
                        $progress.hide();
                    }, 2000);
                });
            });
        });
        </script>
        <?php
    }
    
    public function handle_sync_request(): void {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'tmu_sync_tmdb')) {
            wp_die('Security check failed');
        }
        
        $post_id = intval($_POST['post_id']);
        $sync_images = isset($_POST['images']) && $_POST['images'];
        $sync_videos = isset($_POST['videos']) && $_POST['videos'];
        $sync_credits = isset($_POST['credits']) && $_POST['credits'];
        
        // Implement TMDB sync logic here
        $tmdb_sync = new TMDBSyncService();
        $result = $tmdb_sync->sync_post_data($post_id, [
            'images' => $sync_images,
            'videos' => $sync_videos,
            'credits' => $sync_credits
        ]);
        
        if ($result) {
            wp_send_json_success('Data synchronized successfully');
        } else {
            wp_send_json_error('Failed to sync data');
        }
    }
}
```

## JavaScript and CSS Assets

### Field Interactions
```javascript
// assets/js/custom-fields.js
(function($) {
    'use strict';
    
    // Group field toggle functionality
    $('.tmu-group-toggle button').on('click', function() {
        var $content = $(this).closest('.tmu-group-field').find('.tmu-group-content');
        $content.slideToggle();
        $(this).toggleClass('expanded');
    });
    
    // Clone field functionality
    $('.tmu-add-clone').on('click', function() {
        var $group = $(this).closest('.tmu-group-field');
        var $template = $group.find('.tmu-clone-template').first();
        var $clone = $template.clone();
        
        // Update field names and IDs
        var index = $group.find('.tmu-clone-item').length;
        $clone.find('[name]').each(function() {
            var name = $(this).attr('name');
            $(this).attr('name', name.replace(/\[0\]/, '[' + index + ']'));
        });
        
        $clone.removeClass('tmu-clone-template').addClass('tmu-clone-item');
        $group.find('.tmu-clone-container').append($clone);
    });
    
    // Remove clone functionality
    $(document).on('click', '.tmu-remove-clone', function() {
        $(this).closest('.tmu-clone-item').remove();
    });
    
    // Media upload functionality
    $('.tmu-upload-button').on('click', function() {
        var $button = $(this);
        var $input = $button.siblings('input[type="hidden"]');
        var $preview = $button.siblings('.tmu-image-preview');
        
        var frame = wp.media({
            title: 'Select Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });
        
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $input.val(attachment.id);
            $preview.html('<img src="' + attachment.url + '" alt="" style="max-width: 100px;">');
        });
        
        frame.open();
    });
    
    // Conditional field display
    $('[data-conditional]').each(function() {
        var $field = $(this);
        var conditions = JSON.parse($field.attr('data-conditional'));
        
        function checkConditions() {
            var show = false;
            
            $.each(conditions, function(fieldName, expectedValue) {
                var $conditionField = $('[name="' + fieldName + '"]');
                var currentValue = $conditionField.val();
                
                if (currentValue === expectedValue) {
                    show = true;
                    return false; // break
                }
            });
            
            $field.toggle(show);
        }
        
        checkConditions();
        
        $.each(conditions, function(fieldName) {
            $('[name="' + fieldName + '"]').on('change', checkConditions);
        });
    });
    
})(jQuery);
```

## Database Integration

### Custom Table Management
```php
// src/Database/CustomFieldsTable.php
<?php
namespace TMU\Database;

class CustomFieldsTable {
    public function create_tables(): void {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Episodes tables
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}tmu_tv_series_episodes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            tv_series bigint(20) unsigned NOT NULL,
            season_no int(11) NOT NULL,
            episode_no int(11) NOT NULL,
            episode_title varchar(255) NOT NULL,
            air_date date DEFAULT NULL,
            episode_type varchar(50) DEFAULT NULL,
            runtime varchar(50) DEFAULT NULL,
            overview text DEFAULT NULL,
            credits longtext DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY tv_series (tv_series),
            KEY season_no (season_no)
        ) $charset_collate;";
        
        $wpdb->query($sql);
        
        // Similar tables for drama episodes, seasons, etc.
    }
    
    public function save_custom_table_data($post_id, $table_name, $data): bool {
        global $wpdb;
        
        $table = $wpdb->prefix . $table_name;
        
        // Check if record exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table} WHERE post_id = %d",
            $post_id
        ));
        
        $data['post_id'] = $post_id;
        $data['updated_at'] = current_time('mysql');
        
        if ($existing) {
            return $wpdb->update($table, $data, ['post_id' => $post_id]);
        } else {
            $data['created_at'] = current_time('mysql');
            return $wpdb->insert($table, $data);
        }
    }
    
    public function get_custom_table_data($post_id, $table_name): array {
        global $wpdb;
        
        $table = $wpdb->prefix . $table_name;
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE post_id = %d",
            $post_id
        ), ARRAY_A);
        
        return $data ?: [];
    }
}
```

## Testing and Validation

### Field Validation
```php
// src/CustomFields/Validation/FieldValidator.php
<?php
namespace TMU\CustomFields\Validation;

class FieldValidator {
    public function validate_field($value, $field_config): array {
        $errors = [];
        
        // Required field validation
        if (isset($field_config['required']) && $field_config['required'] && empty($value)) {
            $errors[] = sprintf(__('Field "%s" is required', 'tmu'), $field_config['label']);
        }
        
        // Type-specific validation
        switch ($field_config['type']) {
            case 'number':
                if (!is_numeric($value)) {
                    $errors[] = sprintf(__('Field "%s" must be a number', 'tmu'), $field_config['label']);
                }
                break;
                
            case 'email':
                if (!is_email($value)) {
                    $errors[] = sprintf(__('Field "%s" must be a valid email', 'tmu'), $field_config['label']);
                }
                break;
                
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors[] = sprintf(__('Field "%s" must be a valid URL', 'tmu'), $field_config['label']);
                }
                break;
                
            case 'date':
                if (!$this->validate_date($value)) {
                    $errors[] = sprintf(__('Field "%s" must be a valid date', 'tmu'), $field_config['label']);
                }
                break;
        }
        
        return $errors;
    }
    
    private function validate_date($date): bool {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
```

## Migration Notes

### From Meta Box Plugin
1. **Field Mapping**: All Meta Box field types are mapped to native WordPress equivalents
2. **Data Preservation**: Custom table data is preserved through migration
3. **Conditional Logic**: Meta Box conditional fields are replicated using JavaScript
4. **Group Fields**: Complex group structures are maintained with enhanced UX
5. **Clone Fields**: Repeatable field functionality is preserved and improved

### Performance Considerations
1. **Database Optimization**: Custom tables maintain existing indexes
2. **Asset Loading**: JavaScript and CSS loaded only on relevant admin pages
3. **AJAX Integration**: Smooth user experience with async operations
4. **Caching**: Field configurations cached for better performance

## Success Metrics

- [ ] All episode management fields functional
- [ ] Taxonomy fields working with image uploads
- [ ] Gutenberg block preparation completed
- [ ] TMDB sync controls operational
- [ ] Zero data loss during migration
- [ ] Performance maintained or improved
- [ ] Admin UI responsive and intuitive

## Next Steps

After completing this step, the theme will have a complete custom fields system that:
- Replaces Meta Box plugin dependency
- Maintains all existing functionality
- Provides enhanced user experience
- Supports future Gutenberg block development
- Integrates seamlessly with TMDB API
- Preserves all existing data structures