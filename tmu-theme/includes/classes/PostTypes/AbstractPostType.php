<?php
/**
 * Abstract Post Type Base Class
 *
 * @package TMU\PostTypes
 * @version 1.0.0
 */

namespace TMU\PostTypes;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract base class for all TMU post types
 */
abstract class AbstractPostType {
    
    /**
     * Post type slug
     *
     * @var string
     */
    protected $post_type;
    
    /**
     * Post type labels
     *
     * @var array
     */
    protected $labels = [];
    
    /**
     * Post type arguments
     *
     * @var array
     */
    protected $args = [];
    
    /**
     * Supported taxonomies
     *
     * @var array
     */
    protected $taxonomies = [];
    
    /**
     * Post type supports
     *
     * @var array
     */
    protected $supports = ['title', 'editor', 'thumbnail', 'comments', 'excerpt'];
    
    /**
     * Custom meta fields
     *
     * @var array
     */
    protected $meta_fields = [];
    
    /**
     * Get post type labels
     *
     * @return array
     */
    abstract protected function getLabels(): array;
    
    /**
     * Get post type arguments
     *
     * @return array
     */
    abstract protected function getArgs(): array;
    
    /**
     * Register the post type
     */
    public function register(): void {
        if ($this->shouldRegister()) {
            $this->labels = $this->getLabels();
            $this->args = $this->getArgs();
            
            $register_args = array_merge($this->args, [
                'labels' => $this->labels,
                'supports' => $this->supports,
                'taxonomies' => $this->taxonomies,
            ]);
            
            register_post_type($this->post_type, $register_args);
            
            // Register meta fields if any
            $this->registerMetaFields();
            
            // Add custom hooks
            $this->addHooks();
        }
    }
    
    /**
     * Check if post type should be registered
     *
     * @return bool
     */
    protected function shouldRegister(): bool {
        return true; // Override in child classes for conditional registration
    }
    
    /**
     * Get default post type arguments
     *
     * @return array
     */
    protected function getDefaultArgs(): array {
        return [
            'public' => true,
            'hierarchical' => false,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'can_export' => true,
            'delete_with_user' => true,
            'has_archive' => true,
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'rewrite' => [
                'slug' => $this->post_type,
                'with_front' => false,
                'feeds' => true,
                'pages' => true
            ],
        ];
    }
    
    /**
     * Register meta fields for this post type
     */
    protected function registerMetaFields(): void {
        foreach ($this->meta_fields as $meta_key => $meta_args) {
            register_meta('post', $meta_key, array_merge([
                'object_subtype' => $this->post_type,
                'type' => 'string',
                'single' => true,
                'show_in_rest' => false,
                'auth_callback' => function() {
                    return current_user_can('edit_posts');
                }
            ], $meta_args));
        }
    }
    
    /**
     * Add custom hooks for this post type
     */
    protected function addHooks(): void {
        // Override in child classes to add specific hooks
    }
    
    /**
     * Get post type slug
     *
     * @return string
     */
    public function getPostType(): string {
        return $this->post_type;
    }
    
    /**
     * Get post type object
     *
     * @return WP_Post_Type|null
     */
    public function getPostTypeObject(): ?WP_Post_Type {
        return get_post_type_object($this->post_type);
    }
    
    /**
     * Check if post type exists
     *
     * @return bool
     */
    public function exists(): bool {
        return post_type_exists($this->post_type);
    }
    
    /**
     * Get post type capabilities
     *
     * @return array
     */
    public function getCapabilities(): array {
        $post_type_object = $this->getPostTypeObject();
        return $post_type_object ? (array) $post_type_object->cap : [];
    }
    
    /**
     * Get archive URL
     *
     * @return string|false
     */
    public function getArchiveUrl() {
        return get_post_type_archive_link($this->post_type);
    }
    
    /**
     * Get posts count
     *
     * @param string $status Post status
     * @return int
     */
    public function getPostsCount(string $status = 'publish'): int {
        $counts = wp_count_posts($this->post_type);
        return isset($counts->$status) ? $counts->$status : 0;
    }
    
    /**
     * Add custom columns to admin list
     *
     * @param array $columns Existing columns
     * @return array
     */
    public function addAdminColumns(array $columns): array {
        // Override in child classes to add custom columns
        return $columns;
    }
    
    /**
     * Display custom column content
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public function displayAdminColumnContent(string $column, int $post_id): void {
        // Override in child classes to display custom column content
    }
    
    /**
     * Make columns sortable
     *
     * @param array $columns Sortable columns
     * @return array
     */
    public function makeSortableColumns(array $columns): array {
        // Override in child classes to make columns sortable
        return $columns;
    }
    
    /**
     * Handle sorting for custom columns
     *
     * @param WP_Query $query Query object
     */
    public function handleSorting(WP_Query $query): void {
        // Override in child classes to handle custom sorting
    }
    
    /**
     * Add row actions
     *
     * @param array $actions Existing actions
     * @param WP_Post $post Post object
     * @return array
     */
    public function addRowActions(array $actions, WP_Post $post): array {
        // Override in child classes to add custom row actions
        return $actions;
    }
    
    /**
     * Add bulk actions
     *
     * @param array $actions Existing actions
     * @return array
     */
    public function addBulkActions(array $actions): array {
        // Override in child classes to add custom bulk actions
        return $actions;
    }
    
    /**
     * Handle bulk actions
     *
     * @param string $redirect_to Redirect URL
     * @param string $doaction Action name
     * @param array $post_ids Post IDs
     * @return string
     */
    public function handleBulkActions(string $redirect_to, string $doaction, array $post_ids): string {
        // Override in child classes to handle custom bulk actions
        return $redirect_to;
    }
    
    /**
     * Get default meta fields configuration
     *
     * @return array
     */
    protected function getDefaultMetaFields(): array {
        return [
            'tmdb_id' => [
                'type' => 'integer',
                'description' => 'TMDB ID for this content',
                'single' => true,
            ],
            'release_date' => [
                'type' => 'string',
                'description' => 'Release date',
                'single' => true,
            ],
            'average_rating' => [
                'type' => 'number',
                'description' => 'Average rating',
                'single' => true,
            ],
            'vote_count' => [
                'type' => 'integer',
                'description' => 'Number of votes',
                'single' => true,
            ],
            'popularity' => [
                'type' => 'number',
                'description' => 'Popularity score',
                'single' => true,
            ],
        ];
    }
}