<?php
/**
 * Block Registry
 * 
 * Central registration system for all TMU Gutenberg blocks.
 * Manages block registration, asset enqueueing, and editor integration.
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

namespace TMU\Blocks;

/**
 * BlockRegistry class
 * 
 * Handles registration and management of all TMU blocks
 */
class BlockRegistry {
    
    /**
     * Registered blocks
     * @var array
     */
    private $blocks = [];
    
    /**
     * Block instances
     * @var array
     */
    private $block_instances = [];
    
    /**
     * Assets version
     * @var string
     */
    private $version = '1.0.0';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->register_block_classes();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        add_action('init', [$this, 'register_blocks']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
        add_action('enqueue_block_assets', [$this, 'enqueue_block_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_filter('block_categories_all', [$this, 'register_block_category']);
        add_filter('allowed_block_types_all', [$this, 'filter_allowed_blocks'], 10, 2);
        
        // Critical: Add hooks for database integration
        add_action('save_post', [$this, 'save_block_data_to_database'], 10, 2);
        add_action('wp_ajax_tmu_load_block_data', [$this, 'ajax_load_block_data']);
        add_action('wp_ajax_nopriv_tmu_load_block_data', [$this, 'ajax_load_block_data']);
    }
    
    /**
     * Register block classes
     */
    private function register_block_classes(): void {
        $this->blocks = [
            'movie-metadata' => MovieMetadataBlock::class,
            'tv-series-metadata' => TvSeriesMetadataBlock::class,
            'drama-metadata' => DramaMetadataBlock::class,
            'people-metadata' => PeopleMetadataBlock::class,
            'tv-episode-metadata' => TvEpisodeMetadataBlock::class,
            'drama-episode-metadata' => DramaEpisodeMetadataBlock::class,
            'season-metadata' => SeasonMetadataBlock::class,
            'video-metadata' => VideoMetadataBlock::class,
            'taxonomy-image' => TaxonomyImageBlock::class,
            'taxonomy-faqs' => TaxonomyFaqsBlock::class,
            'blog-posts-list' => BlogPostsListBlock::class,
            'trending-content' => TrendingContentBlock::class,
            'tmdb-sync' => TmdbSyncBlock::class,
        ];
    }
    
    /**
     * Register all blocks with WordPress
     */
    public function register_blocks(): void {
        foreach ($this->blocks as $name => $class) {
            if (!class_exists($class)) {
                continue;
            }
            
            $instance = new $class();
            $this->block_instances[$name] = $instance;
            
            register_block_type("tmu/{$name}", [
                'editor_script' => 'tmu-blocks-editor',
                'editor_style' => 'tmu-blocks-editor',
                'style' => 'tmu-blocks',
                'render_callback' => [$class, 'render'],
                'attributes' => $class::get_attributes(),
                'supports' => $instance->get_block_config()['supports'] ?? [],
            ]);
        }
    }
    
    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets(): void {
        $editor_js = get_template_directory_uri() . '/assets/build/js/blocks-editor.js';
        $editor_css = get_template_directory_uri() . '/assets/build/css/blocks-editor.css';
        
        // Check if files exist
        $js_path = get_template_directory() . '/assets/build/js/blocks-editor.js';
        $css_path = get_template_directory() . '/assets/build/css/blocks-editor.css';
        
        if (file_exists($js_path)) {
            wp_enqueue_script(
                'tmu-blocks-editor',
                $editor_js,
                [
                    'wp-blocks',
                    'wp-element',
                    'wp-editor',
                    'wp-block-editor',
                    'wp-components',
                    'wp-data',
                    'wp-i18n',
                    'wp-api-fetch'
                ],
                $this->get_file_version($js_path),
                true
            );
            
            // Localize script with data
            wp_localize_script('tmu-blocks-editor', 'tmuBlocks', [
                'apiUrl' => rest_url('tmu/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'postTypes' => $this->get_post_types_config(),
                'taxonomies' => $this->get_taxonomies_config(),
                'tmdbApiKey' => get_option('tmu_tmdb_api_key', ''),
                'themeUrl' => get_template_directory_uri(),
            ]);
        }
        
        if (file_exists($css_path)) {
            wp_enqueue_style(
                'tmu-blocks-editor',
                $editor_css,
                ['wp-edit-blocks'],
                $this->get_file_version($css_path)
            );
        }
    }
    
    /**
     * Enqueue block assets (both editor and frontend)
     */
    public function enqueue_block_assets(): void {
        $blocks_css = get_template_directory_uri() . '/assets/build/css/blocks.css';
        $css_path = get_template_directory() . '/assets/build/css/blocks.css';
        
        if (file_exists($css_path)) {
            wp_enqueue_style(
                'tmu-blocks',
                $blocks_css,
                [],
                $this->get_file_version($css_path)
            );
        }
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets(): void {
        if (is_admin()) {
            return;
        }
        
        $blocks_js = get_template_directory_uri() . '/assets/build/js/blocks.js';
        $js_path = get_template_directory() . '/assets/build/js/blocks.js';
        
        if (file_exists($js_path)) {
            wp_enqueue_script(
                'tmu-blocks',
                $blocks_js,
                ['jquery'],
                $this->get_file_version($js_path),
                true
            );
        }
    }
    
    /**
     * Register TMU block category
     * 
     * @param array $categories Existing categories
     * @return array Modified categories
     */
    public function register_block_category($categories): array {
        return array_merge(
            [
                [
                    'slug' => 'tmu-blocks',
                    'title' => __('TMU Blocks', 'tmu-theme'),
                    'icon' => 'video-alt3',
                ]
            ],
            $categories
        );
    }
    
    /**
     * Filter allowed blocks based on post type
     * 
     * @param array $allowed_blocks Allowed blocks
     * @param object $block_editor_context Block editor context
     * @return array Filtered allowed blocks
     */
    public function filter_allowed_blocks($allowed_blocks, $block_editor_context): array {
        if (!isset($block_editor_context->post)) {
            return $allowed_blocks;
        }
        
        $post_type = $block_editor_context->post->post_type;
        $tmu_blocks = [];
        
        foreach ($this->block_instances as $name => $instance) {
            if ($instance->is_allowed_post_type($post_type)) {
                $tmu_blocks[] = "tmu/{$name}";
            }
        }
        
        // If no specific blocks are allowed, return all
        if (empty($tmu_blocks)) {
            return $allowed_blocks;
        }
        
        // Merge TMU blocks with core blocks
        $core_blocks = [
            'core/paragraph',
            'core/heading',
            'core/image',
            'core/list',
            'core/quote',
            'core/separator',
            'core/spacer',
        ];
        
        return array_merge($core_blocks, $tmu_blocks);
    }
    
    /**
     * Get post types configuration for JavaScript
     * 
     * @return array Post types config
     */
    private function get_post_types_config(): array {
        $post_types = ['movie', 'tv', 'drama', 'people', 'season', 'episode', 'drama_episode', 'video'];
        $config = [];
        
        foreach ($post_types as $post_type) {
            $post_type_obj = get_post_type_object($post_type);
            if ($post_type_obj) {
                $config[$post_type] = [
                    'label' => $post_type_obj->label,
                    'labels' => $post_type_obj->labels,
                    'public' => $post_type_obj->public,
                ];
            }
        }
        
        return $config;
    }
    
    /**
     * Get taxonomies configuration for JavaScript
     * 
     * @return array Taxonomies config
     */
    private function get_taxonomies_config(): array {
        $taxonomies = ['genre', 'country', 'language', 'by_year', 'network', 'channel', 'keyword', 'nationality'];
        $config = [];
        
        foreach ($taxonomies as $taxonomy) {
            $taxonomy_obj = get_taxonomy($taxonomy);
            if ($taxonomy_obj) {
                $config[$taxonomy] = [
                    'label' => $taxonomy_obj->label,
                    'labels' => $taxonomy_obj->labels,
                    'public' => $taxonomy_obj->public,
                    'hierarchical' => $taxonomy_obj->hierarchical,
                ];
            }
        }
        
        return $config;
    }
    
    /**
     * Get file version for cache busting
     * 
     * @param string $file_path File path
     * @return string Version string
     */
    private function get_file_version($file_path): string {
        if (file_exists($file_path)) {
            return filemtime($file_path);
        }
        return $this->version;
    }
    
    /**
     * Get registered blocks
     * 
     * @return array Registered blocks
     */
    public function get_blocks(): array {
        return $this->blocks;
    }
    
    /**
     * Get block instance
     * 
     * @param string $name Block name
     * @return BaseBlock|null Block instance
     */
    public function get_block_instance($name): ?BaseBlock {
        return $this->block_instances[$name] ?? null;
    }
    
    /**
     * Check if block is registered
     * 
     * @param string $name Block name
     * @return bool Whether block is registered
     */
    public function is_block_registered($name): bool {
        return isset($this->blocks[$name]);
    }
    
    /**
     * Unregister a block
     * 
     * @param string $name Block name
     * @return bool Success
     */
    public function unregister_block($name): bool {
        if (isset($this->blocks[$name])) {
            unset($this->blocks[$name]);
            unset($this->block_instances[$name]);
            return unregister_block_type("tmu/{$name}");
        }
        return false;
    }
    
    /**
     * Get blocks for specific post type
     * 
     * @param string $post_type Post type
     * @return array Allowed blocks for post type
     */
    public function get_blocks_for_post_type($post_type): array {
        $allowed_blocks = [];
        
        foreach ($this->block_instances as $name => $instance) {
            if ($instance->is_allowed_post_type($post_type)) {
                $allowed_blocks[$name] = $instance->get_block_config();
            }
        }
        
        return $allowed_blocks;
    }
    
    /**
     * Register additional block if needed dynamically
     * 
     * @param string $name Block name
     * @param string $class Block class
     * @return bool Success
     */
    public function register_additional_block($name, $class): bool {
        if (!class_exists($class) || isset($this->blocks[$name])) {
            return false;
        }
        
        $this->blocks[$name] = $class;
        
        if (did_action('init')) {
            // If init already ran, register immediately
            $instance = new $class();
            $this->block_instances[$name] = $instance;
            
            return register_block_type("tmu/{$name}", [
                'editor_script' => 'tmu-blocks-editor',
                'editor_style' => 'tmu-blocks-editor',
                'style' => 'tmu-blocks',
                'render_callback' => [$class, 'render'],
                'attributes' => $class::get_attributes(),
                'supports' => $instance->get_block_config()['supports'] ?? [],
            ]) !== false;
        }
        
        return true;
    }
    
    /**
     * Save block data to database when post is saved
     * 
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     */
    public function save_block_data_to_database($post_id, $post): void {
        // Skip autosaves and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Only process TMU post types
        $tmu_post_types = ['movie', 'tv', 'drama', 'people', 'season', 'episode', 'drama_episode', 'video'];
        if (!in_array($post->post_type, $tmu_post_types)) {
            return;
        }
        
        // Parse blocks from post content
        $blocks = parse_blocks($post->post_content);
        
        foreach ($blocks as $block) {
            if (strpos($block['blockName'], 'tmu/') === 0) {
                $block_type = str_replace('tmu/', '', $block['blockName']);
                $attributes = $block['attrs'] ?? [];
                
                // Call the appropriate block's save method
                if (isset($this->blocks[$block_type])) {
                    $block_class = $this->blocks[$block_type];
                    if (method_exists($block_class, 'save_to_database')) {
                        $block_class::save_to_database($post_id, $attributes);
                    }
                }
            }
        }
    }
    
    /**
     * AJAX handler to load block data from database
     */
    public function ajax_load_block_data(): void {
        check_ajax_referer('tmu_load_block_data', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $block_type = sanitize_text_field($_POST['block_type'] ?? '');
        
        if (!$post_id || !$block_type) {
            wp_die('Invalid parameters');
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_die('Insufficient permissions');
        }
        
        if (isset($this->blocks[$block_type])) {
            $block_class = $this->blocks[$block_type];
            if (method_exists($block_class, 'load_from_database')) {
                $data = $block_class::load_from_database($post_id);
                wp_send_json_success($data);
            }
        }
        
        wp_send_json_error('Block type not found');
    }
}