<?php
/**
 * Relationship Meta Box
 * 
 * Meta box for managing relationships between TMU content types including
 * cast/crew relationships, season/episode connections, and related content.
 * 
 * @package TMU\Admin\MetaBoxes
 * @since 1.0.0
 */

namespace TMU\Admin\MetaBoxes;

/**
 * RelationshipBox class
 * 
 * Manages content relationships in post editor
 */
class RelationshipBox {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('save_post', [$this, 'saveRelationships']);
        add_action('wp_ajax_tmu_search_content', [$this, 'searchContent']);
        add_action('wp_ajax_tmu_get_relationship_data', [$this, 'getRelationshipData']);
    }
    
    /**
     * Add relationship meta box
     */
    public function addMetaBox(): void {
        $post_types = ['movie', 'tv', 'drama', 'people'];
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'tmu-relationships',
                __('Content Relationships', 'tmu-theme'),
                [$this, 'renderMetaBox'],
                $post_type,
                'normal',
                'default'
            );
        }
    }
    
    /**
     * Render relationship meta box
     * 
     * @param \WP_Post $post Current post
     */
    public function renderMetaBox(\WP_Post $post): void {
        wp_nonce_field('tmu_relationships', 'tmu_relationships_nonce');
        
        $post_type = $post->post_type;
        $relationships = $this->getExistingRelationships($post->ID);
        ?>
        <div class="tmu-relationships-box">
            <div class="relationships-tabs">
                <?php if ($post_type !== 'people'): ?>
                    <button type="button" class="tab-button active" data-tab="cast">
                        <?php _e('Cast & Crew', 'tmu-theme'); ?>
                    </button>
                <?php endif; ?>
                
                <?php if (in_array($post_type, ['tv', 'drama'])): ?>
                    <button type="button" class="tab-button" data-tab="episodes">
                        <?php _e('Episodes', 'tmu-theme'); ?>
                    </button>
                <?php endif; ?>
                
                <button type="button" class="tab-button" data-tab="related">
                    <?php _e('Related Content', 'tmu-theme'); ?>
                </button>
                
                <?php if ($post_type === 'people'): ?>
                    <button type="button" class="tab-button active" data-tab="filmography">
                        <?php _e('Filmography', 'tmu-theme'); ?>
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="relationships-content">
                <?php if ($post_type !== 'people'): ?>
                    <div class="tab-panel active" id="cast-panel">
                        <?php $this->renderCastPanel($post->ID, $relationships); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (in_array($post_type, ['tv', 'drama'])): ?>
                    <div class="tab-panel" id="episodes-panel">
                        <?php $this->renderEpisodesPanel($post->ID, $relationships); ?>
                    </div>
                <?php endif; ?>
                
                <div class="tab-panel" id="related-panel">
                    <?php $this->renderRelatedPanel($post->ID, $relationships); ?>
                </div>
                
                <?php if ($post_type === 'people'): ?>
                    <div class="tab-panel active" id="filmography-panel">
                        <?php $this->renderFilmographyPanel($post->ID, $relationships); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.tab-button').on('click', function() {
                var tab = $(this).data('tab');
                $('.tab-button').removeClass('active');
                $('.tab-panel').removeClass('active');
                $(this).addClass('active');
                $('#' + tab + '-panel').addClass('active');
            });
            
            // Content search
            $('.content-search').on('input', function() {
                var input = $(this);
                var searchTerm = input.val();
                var contentType = input.data('content-type');
                
                if (searchTerm.length < 2) {
                    input.siblings('.search-results').hide();
                    return;
                }
                
                $.post(ajaxurl, {
                    action: 'tmu_search_content',
                    search: searchTerm,
                    content_type: contentType,
                    nonce: '<?php echo wp_create_nonce('tmu_search_content'); ?>'
                }, function(response) {
                    if (response.success) {
                        var results = input.siblings('.search-results');
                        results.empty();
                        
                        if (response.data.length > 0) {
                            response.data.forEach(function(item) {
                                var result = $('<div class="search-result" data-id="' + item.id + '">' +
                                    '<div class="result-thumb">' + (item.thumbnail || '') + '</div>' +
                                    '<div class="result-info">' +
                                        '<div class="result-title">' + item.title + '</div>' +
                                        '<div class="result-meta">' + item.meta + '</div>' +
                                    '</div>' +
                                '</div>');
                                results.append(result);
                            });
                            results.show();
                        } else {
                            results.html('<div class="no-results">No results found</div>').show();
                        }
                    }
                });
            });
            
            // Add relationship
            $(document).on('click', '.search-result', function() {
                var item = $(this);
                var panel = item.closest('.tab-panel');
                var relationshipType = panel.attr('id').replace('-panel', '');
                
                var relationship = $('<div class="relationship-item" data-id="' + item.data('id') + '">' +
                    '<div class="relationship-thumb">' + item.find('.result-thumb').html() + '</div>' +
                    '<div class="relationship-info">' +
                        '<div class="relationship-title">' + item.find('.result-title').text() + '</div>' +
                        '<div class="relationship-role">' +
                            '<input type="text" name="relationships[' + relationshipType + '][' + item.data('id') + '][role]" placeholder="Role/Character" class="small-text">' +
                        '</div>' +
                    '</div>' +
                    '<button type="button" class="remove-relationship button-small">×</button>' +
                    '<input type="hidden" name="relationships[' + relationshipType + '][' + item.data('id') + '][id]" value="' + item.data('id') + '">' +
                '</div>');
                
                panel.find('.relationships-list').append(relationship);
                item.closest('.search-results').hide().empty();
                item.closest('.content-search').val('');
            });
            
            // Remove relationship
            $(document).on('click', '.remove-relationship', function() {
                $(this).closest('.relationship-item').remove();
            });
        });
        </script>
        
        <style>
        .tmu-relationships-box {
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        .relationships-tabs {
            background: #f9f9f9;
            border-bottom: 1px solid #ddd;
            display: flex;
        }
        .tab-button {
            background: none;
            border: none;
            padding: 12px 16px;
            cursor: pointer;
            border-right: 1px solid #ddd;
        }
        .tab-button.active {
            background: #fff;
            border-bottom: 1px solid #fff;
            margin-bottom: -1px;
        }
        .relationships-content {
            padding: 20px;
        }
        .tab-panel {
            display: none;
        }
        .tab-panel.active {
            display: block;
        }
        .search-section {
            margin-bottom: 20px;
            position: relative;
        }
        .content-search {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 4px 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 10;
            display: none;
        }
        .search-result {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        .search-result:hover {
            background: #f5f5f5;
        }
        .result-thumb {
            width: 40px;
            height: 60px;
            margin-right: 10px;
            flex-shrink: 0;
        }
        .result-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 3px;
        }
        .result-info {
            flex: 1;
        }
        .result-title {
            font-weight: 500;
            margin-bottom: 2px;
        }
        .result-meta {
            font-size: 12px;
            color: #666;
        }
        .relationships-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }
        .relationship-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
            border: 1px solid #e5e5e5;
        }
        .relationship-thumb {
            width: 40px;
            height: 60px;
            margin-right: 10px;
            flex-shrink: 0;
        }
        .relationship-info {
            flex: 1;
            min-width: 0;
        }
        .relationship-title {
            font-weight: 500;
            margin-bottom: 5px;
        }
        .relationship-role input {
            width: 100%;
        }
        .remove-relationship {
            background: #dc3232;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            margin-left: 10px;
        }
        .no-results {
            padding: 12px;
            text-align: center;
            color: #666;
            font-style: italic;
        }
        </style>
        <?php
    }
    
    /**
     * Render cast panel
     * 
     * @param int $post_id Post ID
     * @param array $relationships Existing relationships
     */
    private function renderCastPanel(int $post_id, array $relationships): void {
        ?>
        <div class="search-section">
            <label for="cast-search"><?php _e('Add Cast Member:', 'tmu-theme'); ?></label>
            <input type="text" id="cast-search" class="content-search" data-content-type="people" placeholder="<?php esc_attr_e('Search for people...', 'tmu-theme'); ?>">
            <div class="search-results"></div>
        </div>
        
        <div class="relationships-list" id="cast-list">
            <?php if (isset($relationships['cast'])): ?>
                <?php foreach ($relationships['cast'] as $person): ?>
                    <div class="relationship-item" data-id="<?php echo esc_attr($person['id']); ?>">
                        <div class="relationship-thumb">
                            <?php echo get_the_post_thumbnail($person['id'], [40, 60]); ?>
                        </div>
                        <div class="relationship-info">
                            <div class="relationship-title"><?php echo esc_html($person['title']); ?></div>
                            <div class="relationship-role">
                                <input type="text" name="relationships[cast][<?php echo $person['id']; ?>][role]" value="<?php echo esc_attr($person['role']); ?>" placeholder="Role/Character" class="small-text">
                            </div>
                        </div>
                        <button type="button" class="remove-relationship button-small">×</button>
                        <input type="hidden" name="relationships[cast][<?php echo $person['id']; ?>][id]" value="<?php echo $person['id']; ?>">
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render episodes panel
     * 
     * @param int $post_id Post ID
     * @param array $relationships Existing relationships
     */
    private function renderEpisodesPanel(int $post_id, array $relationships): void {
        ?>
        <div class="episodes-management">
            <h4><?php _e('Episode Management', 'tmu-theme'); ?></h4>
            <p><?php _e('Episodes are managed automatically through TMDB sync or can be added manually.', 'tmu-theme'); ?></p>
            
            <div class="episode-actions">
                <button type="button" id="sync-episodes" class="button button-secondary">
                    <?php _e('Sync Episodes from TMDB', 'tmu-theme'); ?>
                </button>
                <button type="button" id="add-episode" class="button button-secondary">
                    <?php _e('Add Episode Manually', 'tmu-theme'); ?>
                </button>
            </div>
            
            <div class="episodes-list">
                <?php
                $episodes = get_posts([
                    'post_type' => 'episode',
                    'meta_query' => [
                        [
                            'key' => 'parent_series_id',
                            'value' => $post_id,
                            'compare' => '='
                        ]
                    ],
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                    'numberposts' => -1
                ]);
                
                if ($episodes): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Episode', 'tmu-theme'); ?></th>
                                <th><?php _e('Title', 'tmu-theme'); ?></th>
                                <th><?php _e('Air Date', 'tmu-theme'); ?></th>
                                <th><?php _e('Actions', 'tmu-theme'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($episodes as $episode): ?>
                                <tr>
                                    <td><?php echo esc_html(get_post_meta($episode->ID, 'episode_number', true)); ?></td>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($episode->ID); ?>">
                                            <?php echo esc_html($episode->post_title); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html(get_post_meta($episode->ID, 'air_date', true)); ?></td>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($episode->ID); ?>" class="button button-small">
                                            <?php _e('Edit', 'tmu-theme'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-episodes"><?php _e('No episodes found. Sync with TMDB or add manually.', 'tmu-theme'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render related content panel
     * 
     * @param int $post_id Post ID
     * @param array $relationships Existing relationships
     */
    private function renderRelatedPanel(int $post_id, array $relationships): void {
        ?>
        <div class="search-section">
            <label for="related-search"><?php _e('Add Related Content:', 'tmu-theme'); ?></label>
            <input type="text" id="related-search" class="content-search" data-content-type="all" placeholder="<?php esc_attr_e('Search for movies, TV shows, dramas...', 'tmu-theme'); ?>">
            <div class="search-results"></div>
        </div>
        
        <div class="relationships-list" id="related-list">
            <?php if (isset($relationships['related'])): ?>
                <?php foreach ($relationships['related'] as $content): ?>
                    <div class="relationship-item" data-id="<?php echo esc_attr($content['id']); ?>">
                        <div class="relationship-thumb">
                            <?php echo get_the_post_thumbnail($content['id'], [40, 60]); ?>
                        </div>
                        <div class="relationship-info">
                            <div class="relationship-title"><?php echo esc_html($content['title']); ?></div>
                            <div class="relationship-role">
                                <select name="relationships[related][<?php echo $content['id']; ?>][type]" class="small-text">
                                    <option value="similar" <?php selected($content['type'], 'similar'); ?>><?php _e('Similar', 'tmu-theme'); ?></option>
                                    <option value="sequel" <?php selected($content['type'], 'sequel'); ?>><?php _e('Sequel', 'tmu-theme'); ?></option>
                                    <option value="prequel" <?php selected($content['type'], 'prequel'); ?>><?php _e('Prequel', 'tmu-theme'); ?></option>
                                    <option value="remake" <?php selected($content['type'], 'remake'); ?>><?php _e('Remake', 'tmu-theme'); ?></option>
                                </select>
                            </div>
                        </div>
                        <button type="button" class="remove-relationship button-small">×</button>
                        <input type="hidden" name="relationships[related][<?php echo $content['id']; ?>][id]" value="<?php echo $content['id']; ?>">
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render filmography panel for people
     * 
     * @param int $post_id Post ID
     * @param array $relationships Existing relationships
     */
    private function renderFilmographyPanel(int $post_id, array $relationships): void {
        ?>
        <div class="search-section">
            <label for="filmography-search"><?php _e('Add to Filmography:', 'tmu-theme'); ?></label>
            <input type="text" id="filmography-search" class="content-search" data-content-type="content" placeholder="<?php esc_attr_e('Search for movies, TV shows, dramas...', 'tmu-theme'); ?>">
            <div class="search-results"></div>
        </div>
        
        <div class="relationships-list" id="filmography-list">
            <?php if (isset($relationships['filmography'])): ?>
                <?php foreach ($relationships['filmography'] as $content): ?>
                    <div class="relationship-item" data-id="<?php echo esc_attr($content['id']); ?>">
                        <div class="relationship-thumb">
                            <?php echo get_the_post_thumbnail($content['id'], [40, 60]); ?>
                        </div>
                        <div class="relationship-info">
                            <div class="relationship-title"><?php echo esc_html($content['title']); ?></div>
                            <div class="relationship-role">
                                <input type="text" name="relationships[filmography][<?php echo $content['id']; ?>][role]" value="<?php echo esc_attr($content['role']); ?>" placeholder="Role/Character" class="small-text">
                            </div>
                        </div>
                        <button type="button" class="remove-relationship button-small">×</button>
                        <input type="hidden" name="relationships[filmography][<?php echo $content['id']; ?>][id]" value="<?php echo $content['id']; ?>">
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Get existing relationships
     * 
     * @param int $post_id Post ID
     * @return array Existing relationships
     */
    private function getExistingRelationships(int $post_id): array {
        $relationships = get_post_meta($post_id, '_tmu_relationships', true);
        return is_array($relationships) ? $relationships : [];
    }
    
    /**
     * Save relationships
     * 
     * @param int $post_id Post ID
     */
    public function saveRelationships(int $post_id): void {
        if (!isset($_POST['tmu_relationships_nonce']) || 
            !wp_verify_nonce($_POST['tmu_relationships_nonce'], 'tmu_relationships')) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $relationships = $_POST['relationships'] ?? [];
        
        // Sanitize relationships data
        $sanitized_relationships = [];
        foreach ($relationships as $type => $items) {
            if (!is_array($items)) continue;
            
            foreach ($items as $item_id => $item_data) {
                $sanitized_relationships[$type][] = [
                    'id' => intval($item_data['id']),
                    'title' => get_the_title($item_data['id']),
                    'role' => sanitize_text_field($item_data['role'] ?? ''),
                    'type' => sanitize_text_field($item_data['type'] ?? '')
                ];
            }
        }
        
        update_post_meta($post_id, '_tmu_relationships', $sanitized_relationships);
    }
    
    /**
     * AJAX handler for content search
     */
    public function searchContent(): void {
        check_ajax_referer('tmu_search_content', 'nonce');
        
        $search_term = sanitize_text_field($_POST['search']);
        $content_type = sanitize_text_field($_POST['content_type']);
        
        $post_types = [];
        switch ($content_type) {
            case 'people':
                $post_types = ['people'];
                break;
            case 'content':
                $post_types = ['movie', 'tv', 'drama'];
                break;
            default:
                $post_types = ['movie', 'tv', 'drama', 'people'];
        }
        
        $query = new \WP_Query([
            'post_type' => $post_types,
            'post_status' => 'publish',
            's' => $search_term,
            'posts_per_page' => 10,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        ]);
        
        $results = [];
        foreach ($query->posts as $post) {
            $results[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'meta' => get_post_type_object($post->post_type)->labels->singular_name,
                'thumbnail' => get_the_post_thumbnail($post->ID, [40, 60])
            ];
        }
        
        wp_send_json_success($results);
    }
}