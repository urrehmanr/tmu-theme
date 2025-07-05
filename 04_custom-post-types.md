# Step 4: Custom Post Types

## 🎯 Goal
Implement a comprehensive custom post type system using WordPress core functions with modern OOP architecture, replicating all functionality from the original plugin.

## 📋 What We'll Accomplish
- Create PostTypeManager for centralized registration
- Implement individual post type classes
- Configure proper labels, supports, and capabilities
- Set up rewrite rules and permalinks
- Add custom columns to admin lists
- Integrate with existing database tables

---

## 🏗️ Post Type Manager

### Create `src/PostTypes/PostTypeManager.php`

```php
<?php
/**
 * Post Type Manager
 *
 * @package TMUTheme\PostTypes
 * @since 1.0.0
 */

namespace TMUTheme\PostTypes;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * PostTypeManager class
 * 
 * Manages registration of all custom post types
 */
class PostTypeManager {
    
    /**
     * Post type instances
     *
     * @var array
     */
    private $post_types = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'init', [ $this, 'register_post_types' ], 5 );
        add_action( 'admin_init', [ $this, 'setup_admin_features' ] );
    }
    
    /**
     * Register all custom post types
     *
     * @return void
     */
    public function register_post_types(): void {
        // Initialize post type classes
        if ( tmu_get_option( 'tmu_enable_movies', true ) ) {
            $this->post_types['movie'] = new Movie();
        }
        
        if ( tmu_get_option( 'tmu_enable_tv_series', true ) ) {
            $this->post_types['tv-series'] = new TVSeries();
        }
        
        if ( tmu_get_option( 'tmu_enable_dramas', true ) ) {
            $this->post_types['drama'] = new Drama();
        }
        
        if ( tmu_get_option( 'tmu_enable_people', true ) ) {
            $this->post_types['person'] = new Person();
        }
        
        // Always register episodes, seasons, and videos if parent types exist
        if ( ! empty( $this->post_types ) ) {
            $this->post_types['episode'] = new Episode();
            $this->post_types['season'] = new Season();
            $this->post_types['video'] = new Video();
        }
        
        // Register each post type
        foreach ( $this->post_types as $post_type ) {
            $post_type->register();
        }
    }
    
    /**
     * Setup admin features
     *
     * @return void
     */
    public function setup_admin_features(): void {
        // Add custom columns to admin lists
        foreach ( $this->post_types as $post_type ) {
            if ( method_exists( $post_type, 'setup_admin_columns' ) ) {
                $post_type->setup_admin_columns();
            }
        }
    }
    
    /**
     * Get post type instance
     *
     * @param string $post_type Post type name.
     * @return object|null
     */
    public function get_post_type( string $post_type ) {
        return $this->post_types[ $post_type ] ?? null;
    }
}
```

---

## 🎬 Movie Post Type

### Create `src/PostTypes/Movie.php`

```php
<?php
/**
 * Movie Post Type
 *
 * @package TMUTheme\PostTypes
 * @since 1.0.0
 */

namespace TMUTheme\PostTypes;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Movie class
 * 
 * Handles movie post type registration and functionality
 */
class Movie {
    
    /**
     * Post type slug
     */
    const POST_TYPE = 'movie';
    
    /**
     * Register movie post type
     *
     * @return void
     */
    public function register(): void {
        $labels = [
            'name'                     => esc_html__( 'Movies', 'tmu-theme' ),
            'singular_name'            => esc_html__( 'Movie', 'tmu-theme' ),
            'menu_name'                => esc_html__( 'Movies', 'tmu-theme' ),
            'add_new'                  => esc_html__( 'Add New Movie', 'tmu-theme' ),
            'add_new_item'             => esc_html__( 'Add New Movie', 'tmu-theme' ),
            'edit_item'                => esc_html__( 'Edit Movie', 'tmu-theme' ),
            'new_item'                 => esc_html__( 'New Movie', 'tmu-theme' ),
            'view_item'                => esc_html__( 'View Movie', 'tmu-theme' ),
            'view_items'               => esc_html__( 'View Movies', 'tmu-theme' ),
            'search_items'             => esc_html__( 'Search Movies', 'tmu-theme' ),
            'not_found'                => esc_html__( 'No movies found.', 'tmu-theme' ),
            'not_found_in_trash'       => esc_html__( 'No movies found in Trash.', 'tmu-theme' ),
            'parent_item_colon'        => esc_html__( 'Parent Movie:', 'tmu-theme' ),
            'all_items'                => esc_html__( 'All Movies', 'tmu-theme' ),
            'archives'                 => esc_html__( 'Movie Archives', 'tmu-theme' ),
            'attributes'               => esc_html__( 'Movie Attributes', 'tmu-theme' ),
            'insert_into_item'         => esc_html__( 'Insert into movie', 'tmu-theme' ),
            'uploaded_to_this_item'    => esc_html__( 'Uploaded to this movie', 'tmu-theme' ),
            'featured_image'           => esc_html__( 'Movie Poster', 'tmu-theme' ),
            'set_featured_image'       => esc_html__( 'Set movie poster', 'tmu-theme' ),
            'remove_featured_image'    => esc_html__( 'Remove movie poster', 'tmu-theme' ),
            'use_featured_image'       => esc_html__( 'Use as movie poster', 'tmu-theme' ),
            'filter_items_list'        => esc_html__( 'Filter movies list', 'tmu-theme' ),
            'items_list_navigation'    => esc_html__( 'Movies list navigation', 'tmu-theme' ),
            'items_list'               => esc_html__( 'Movies list', 'tmu-theme' ),
            'item_published'           => esc_html__( 'Movie published.', 'tmu-theme' ),
            'item_published_privately' => esc_html__( 'Movie published privately.', 'tmu-theme' ),
            'item_reverted_to_draft'   => esc_html__( 'Movie reverted to draft.', 'tmu-theme' ),
            'item_scheduled'           => esc_html__( 'Movie scheduled.', 'tmu-theme' ),
            'item_updated'             => esc_html__( 'Movie updated.', 'tmu-theme' ),
        ];
        
        $args = [
            'label'               => esc_html__( 'Movies', 'tmu-theme' ),
            'labels'              => $labels,
            'description'         => esc_html__( 'Movie database with TMDB integration', 'tmu-theme' ),
            'public'              => true,
            'hierarchical'        => false,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'show_in_rest'        => true,
            'query_var'           => true,
            'can_export'          => true,
            'delete_with_user'    => false,
            'has_archive'         => true,
            'rest_base'           => 'movies',
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-video-alt3',
            'capability_type'     => 'post',
            'capabilities'        => [
                'edit_post'          => 'edit_movie',
                'read_post'          => 'read_movie', 
                'delete_post'        => 'delete_movie',
                'edit_posts'         => 'edit_movies',
                'edit_others_posts'  => 'edit_others_movies',
                'delete_posts'       => 'delete_movies',
                'publish_posts'      => 'publish_movies',
                'read_private_posts' => 'read_private_movies',
            ],
            'map_meta_cap'        => true,
            'supports'            => [
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'comments',
                'revisions',
                'custom-fields',
                'page-attributes'
            ],
            'taxonomies'          => [ 'genre', 'country', 'language', 'by-year', 'keyword' ],
            'rewrite'             => [
                'slug'       => 'movies',
                'with_front' => false,
                'feeds'      => true,
                'pages'      => true
            ],
        ];
        
        register_post_type( self::POST_TYPE, $args );
        
        // Add custom capabilities
        $this->add_capabilities();
    }
    
    /**
     * Add custom capabilities
     *
     * @return void
     */
    private function add_capabilities(): void {
        $capabilities = [
            'edit_movie',
            'read_movie',
            'delete_movie',
            'edit_movies',
            'edit_others_movies',
            'delete_movies',
            'publish_movies',
            'read_private_movies',
        ];
        
        $roles = [ 'administrator', 'editor' ];
        
        foreach ( $roles as $role_name ) {
            $role = get_role( $role_name );
            if ( $role ) {
                foreach ( $capabilities as $capability ) {
                    $role->add_cap( $capability );
                }
            }
        }
    }
    
    /**
     * Setup admin columns
     *
     * @return void
     */
    public function setup_admin_columns(): void {
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'add_admin_columns' ] );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'populate_admin_columns' ], 10, 2 );
        add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', [ $this, 'sortable_columns' ] );
    }
    
    /**
     * Add custom admin columns
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function add_admin_columns( array $columns ): array {
        $new_columns = [];
        
        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;
            
            // Add custom columns after title
            if ( $key === 'title' ) {
                $new_columns['movie_poster']     = esc_html__( 'Poster', 'tmu-theme' );
                $new_columns['tmdb_id']         = esc_html__( 'TMDB ID', 'tmu-theme' );
                $new_columns['release_date']    = esc_html__( 'Release Date', 'tmu-theme' );
                $new_columns['rating']          = esc_html__( 'Rating', 'tmu-theme' );
                $new_columns['genres']          = esc_html__( 'Genres', 'tmu-theme' );
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Populate custom admin columns
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     * @return void
     */
    public function populate_admin_columns( string $column, int $post_id ): void {
        global $wpdb;
        
        switch ( $column ) {
            case 'movie_poster':
                if ( has_post_thumbnail( $post_id ) ) {
                    echo get_the_post_thumbnail( $post_id, [ 50, 75 ] );
                } else {
                    echo '<span class="dashicons dashicons-format-image" style="color: #ccc;"></span>';
                }
                break;
                
            case 'tmdb_id':
                $table_name = $wpdb->prefix . 'tmu_movies';
                $tmdb_id = $wpdb->get_var( 
                    $wpdb->prepare( "SELECT tmdb_id FROM {$table_name} WHERE ID = %d", $post_id ) 
                );
                if ( $tmdb_id ) {
                    echo '<a href="https://www.themoviedb.org/movie/' . esc_attr( $tmdb_id ) . '" target="_blank">' . esc_html( $tmdb_id ) . '</a>';
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
                
            case 'release_date':
                $table_name = $wpdb->prefix . 'tmu_movies';
                $release_date = $wpdb->get_var( 
                    $wpdb->prepare( "SELECT release_date FROM {$table_name} WHERE ID = %d", $post_id ) 
                );
                if ( $release_date ) {
                    echo esc_html( date( 'M j, Y', strtotime( $release_date ) ) );
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
                
            case 'rating':
                $table_name = $wpdb->prefix . 'tmu_movies';
                $rating = $wpdb->get_var( 
                    $wpdb->prepare( "SELECT total_average_rating FROM {$table_name} WHERE ID = %d", $post_id ) 
                );
                if ( $rating ) {
                    echo '<span style="color: #f39c12;">★</span> ' . esc_html( $rating );
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
                
            case 'genres':
                $terms = get_the_terms( $post_id, 'genre' );
                if ( $terms && ! is_wp_error( $terms ) ) {
                    $genre_names = array_map( function( $term ) {
                        return $term->name;
                    }, $terms );
                    echo esc_html( implode( ', ', $genre_names ) );
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
        }
    }
    
    /**
     * Make columns sortable
     *
     * @param array $columns Sortable columns.
     * @return array
     */
    public function sortable_columns( array $columns ): array {
        $columns['release_date'] = 'release_date';
        $columns['rating'] = 'rating';
        $columns['tmdb_id'] = 'tmdb_id';
        
        return $columns;
    }
}
```

---

## 📺 TV Series Post Type

### Create `src/PostTypes/TVSeries.php`

```php
<?php
/**
 * TV Series Post Type
 *
 * @package TMUTheme\PostTypes
 * @since 1.0.0
 */

namespace TMUTheme\PostTypes;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * TVSeries class
 * 
 * Handles TV series post type registration and functionality
 */
class TVSeries {
    
    /**
     * Post type slug
     */
    const POST_TYPE = 'tv-series';
    
    /**
     * Register TV series post type
     *
     * @return void
     */
    public function register(): void {
        $labels = [
            'name'                     => esc_html__( 'TV Series', 'tmu-theme' ),
            'singular_name'            => esc_html__( 'TV Series', 'tmu-theme' ),
            'menu_name'                => esc_html__( 'TV Series', 'tmu-theme' ),
            'add_new'                  => esc_html__( 'Add New TV Series', 'tmu-theme' ),
            'add_new_item'             => esc_html__( 'Add New TV Series', 'tmu-theme' ),
            'edit_item'                => esc_html__( 'Edit TV Series', 'tmu-theme' ),
            'new_item'                 => esc_html__( 'New TV Series', 'tmu-theme' ),
            'view_item'                => esc_html__( 'View TV Series', 'tmu-theme' ),
            'view_items'               => esc_html__( 'View TV Series', 'tmu-theme' ),
            'search_items'             => esc_html__( 'Search TV Series', 'tmu-theme' ),
            'not_found'                => esc_html__( 'No TV series found.', 'tmu-theme' ),
            'not_found_in_trash'       => esc_html__( 'No TV series found in Trash.', 'tmu-theme' ),
            'all_items'                => esc_html__( 'All TV Series', 'tmu-theme' ),
            'archives'                 => esc_html__( 'TV Series Archives', 'tmu-theme' ),
            'featured_image'           => esc_html__( 'Series Poster', 'tmu-theme' ),
            'set_featured_image'       => esc_html__( 'Set series poster', 'tmu-theme' ),
            'remove_featured_image'    => esc_html__( 'Remove series poster', 'tmu-theme' ),
            'use_featured_image'       => esc_html__( 'Use as series poster', 'tmu-theme' ),
        ];
        
        $args = [
            'label'               => esc_html__( 'TV Series', 'tmu-theme' ),
            'labels'              => $labels,
            'description'         => esc_html__( 'TV Series database with TMDB integration', 'tmu-theme' ),
            'public'              => true,
            'hierarchical'        => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => true,
            'has_archive'         => true,
            'menu_position'       => 6,
            'menu_icon'           => 'dashicons-format-video',
            'supports'            => [
                'title',
                'editor', 
                'thumbnail',
                'excerpt',
                'comments',
                'revisions',
                'custom-fields'
            ],
            'taxonomies'          => [ 'genre', 'country', 'language', 'by-year', 'network', 'keyword' ],
            'rewrite'             => [
                'slug'       => 'tv-series',
                'with_front' => false,
                'feeds'      => true,
                'pages'      => true
            ],
        ];
        
        register_post_type( self::POST_TYPE, $args );
    }
    
    /**
     * Setup admin columns
     *
     * @return void
     */
    public function setup_admin_columns(): void {
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'add_admin_columns' ] );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'populate_admin_columns' ], 10, 2 );
    }
    
    /**
     * Add custom admin columns
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function add_admin_columns( array $columns ): array {
        $new_columns = [];
        
        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;
            
            if ( $key === 'title' ) {
                $new_columns['poster']       = esc_html__( 'Poster', 'tmu-theme' );
                $new_columns['tmdb_id']      = esc_html__( 'TMDB ID', 'tmu-theme' );
                $new_columns['status']       = esc_html__( 'Status', 'tmu-theme' );
                $new_columns['seasons']      = esc_html__( 'Seasons', 'tmu-theme' );
                $new_columns['rating']       = esc_html__( 'Rating', 'tmu-theme' );
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Populate custom admin columns
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     * @return void
     */
    public function populate_admin_columns( string $column, int $post_id ): void {
        global $wpdb;
        
        switch ( $column ) {
            case 'poster':
                if ( has_post_thumbnail( $post_id ) ) {
                    echo get_the_post_thumbnail( $post_id, [ 50, 75 ] );
                } else {
                    echo '<span class="dashicons dashicons-format-image" style="color: #ccc;"></span>';
                }
                break;
                
            case 'tmdb_id':
                $table_name = $wpdb->prefix . 'tmu_tv_series';
                $tmdb_id = $wpdb->get_var( 
                    $wpdb->prepare( "SELECT tmdb_id FROM {$table_name} WHERE ID = %d", $post_id ) 
                );
                echo $tmdb_id ? esc_html( $tmdb_id ) : '<span style="color: #999;">—</span>';
                break;
                
            case 'status':
                $table_name = $wpdb->prefix . 'tmu_tv_series';
                $status = $wpdb->get_var( 
                    $wpdb->prepare( "SELECT status FROM {$table_name} WHERE ID = %d", $post_id ) 
                );
                if ( $status ) {
                    $status_class = $status === 'Ended' ? 'error' : 'success';
                    echo '<span class="status-' . esc_attr( $status_class ) . '">' . esc_html( $status ) . '</span>';
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
                
            case 'seasons':
                $seasons_count = $wpdb->get_var( 
                    $wpdb->prepare( 
                        "SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type = 'season' AND post_parent = %d", 
                        $post_id 
                    ) 
                );
                echo $seasons_count ? esc_html( $seasons_count ) : '0';
                break;
                
            case 'rating':
                $table_name = $wpdb->prefix . 'tmu_tv_series';
                $rating = $wpdb->get_var( 
                    $wpdb->prepare( "SELECT total_average_rating FROM {$table_name} WHERE ID = %d", $post_id ) 
                );
                echo $rating ? '<span style="color: #f39c12;">★</span> ' . esc_html( $rating ) : '<span style="color: #999;">—</span>';
                break;
        }
    }
}
```

---

## 👥 Person Post Type

### Create `src/PostTypes/Person.php`

```php
<?php
/**
 * Person Post Type
 *
 * @package TMUTheme\PostTypes
 * @since 1.0.0
 */

namespace TMUTheme\PostTypes;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Person class
 * 
 * Handles person/celebrity post type registration
 */
class Person {
    
    /**
     * Post type slug
     */
    const POST_TYPE = 'person';
    
    /**
     * Register person post type
     *
     * @return void
     */
    public function register(): void {
        $labels = [
            'name'                     => esc_html__( 'People', 'tmu-theme' ),
            'singular_name'            => esc_html__( 'Person', 'tmu-theme' ),
            'menu_name'                => esc_html__( 'People', 'tmu-theme' ),
            'add_new'                  => esc_html__( 'Add New Person', 'tmu-theme' ),
            'add_new_item'             => esc_html__( 'Add New Person', 'tmu-theme' ),
            'edit_item'                => esc_html__( 'Edit Person', 'tmu-theme' ),
            'new_item'                 => esc_html__( 'New Person', 'tmu-theme' ),
            'view_item'                => esc_html__( 'View Person', 'tmu-theme' ),
            'search_items'             => esc_html__( 'Search People', 'tmu-theme' ),
            'not_found'                => esc_html__( 'No people found.', 'tmu-theme' ),
            'not_found_in_trash'       => esc_html__( 'No people found in Trash.', 'tmu-theme' ),
            'all_items'                => esc_html__( 'All People', 'tmu-theme' ),
            'featured_image'           => esc_html__( 'Profile Photo', 'tmu-theme' ),
            'set_featured_image'       => esc_html__( 'Set profile photo', 'tmu-theme' ),
            'remove_featured_image'    => esc_html__( 'Remove profile photo', 'tmu-theme' ),
            'use_featured_image'       => esc_html__( 'Use as profile photo', 'tmu-theme' ),
        ];
        
        $args = [
            'label'               => esc_html__( 'People', 'tmu-theme' ),
            'labels'              => $labels,
            'description'         => esc_html__( 'Actors, directors, and other entertainment personalities', 'tmu-theme' ),
            'public'              => true,
            'hierarchical'        => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => true,
            'has_archive'         => true,
            'menu_position'       => 7,
            'menu_icon'           => 'dashicons-groups',
            'supports'            => [
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'comments',
                'revisions'
            ],
            'taxonomies'          => [ 'nationality', 'keyword' ],
            'rewrite'             => [
                'slug'       => 'people',
                'with_front' => false,
                'feeds'      => true
            ],
        ];
        
        register_post_type( self::POST_TYPE, $args );
    }
    
    /**
     * Setup admin columns
     *
     * @return void
     */
    public function setup_admin_columns(): void {
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'add_admin_columns' ] );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'populate_admin_columns' ], 10, 2 );
    }
    
    /**
     * Add custom admin columns
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function add_admin_columns( array $columns ): array {
        $new_columns = [];
        
        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;
            
            if ( $key === 'title' ) {
                $new_columns['photo']       = esc_html__( 'Photo', 'tmu-theme' );
                $new_columns['profession']  = esc_html__( 'Profession', 'tmu-theme' );
                $new_columns['birth_date']  = esc_html__( 'Birth Date', 'tmu-theme' );
                $new_columns['popularity']  = esc_html__( 'Popularity', 'tmu-theme' );
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Populate custom admin columns
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     * @return void
     */
    public function populate_admin_columns( string $column, int $post_id ): void {
        global $wpdb;
        
        switch ( $column ) {
            case 'photo':
                if ( has_post_thumbnail( $post_id ) ) {
                    echo get_the_post_thumbnail( $post_id, [ 40, 60 ] );
                } else {
                    echo '<span class="dashicons dashicons-admin-users" style="color: #ccc;"></span>';
                }
                break;
                
            case 'profession':
                $table_name = $wpdb->prefix . 'tmu_people';
                $profession = $wpdb->get_var( 
                    $wpdb->prepare( "SELECT profession FROM {$table_name} WHERE ID = %d", $post_id ) 
                );
                echo $profession ? esc_html( $profession ) : '<span style="color: #999;">—</span>';
                break;
                
            case 'birth_date':
                $table_name = $wpdb->prefix . 'tmu_people';
                $birth_date = $wpdb->get_var( 
                    $wpdb->prepare( "SELECT date_of_birth FROM {$table_name} WHERE ID = %d", $post_id ) 
                );
                if ( $birth_date ) {
                    echo esc_html( date( 'M j, Y', strtotime( $birth_date ) ) );
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
                
            case 'popularity':
                $table_name = $wpdb->prefix . 'tmu_people';
                $popularity = $wpdb->get_var( 
                    $wpdb->prepare( "SELECT popularity FROM {$table_name} WHERE ID = %d", $post_id ) 
                );
                echo $popularity ? esc_html( number_format( $popularity, 1 ) ) : '<span style="color: #999;">—</span>';
                break;
        }
    }
}
```

---

## ✅ Verification Checklist

After completing this step, verify:

- [ ] PostTypeManager class is created and functional
- [ ] All post types are registered successfully
- [ ] Custom admin columns are working
- [ ] Capabilities are set correctly
- [ ] Rewrite rules are flushed
- [ ] Post types appear in admin menu
- [ ] Archives and single pages are accessible

---

## 🔍 Testing Post Types

```php
// Test post type registration
$post_types = get_post_types( [], 'objects' );
var_dump( isset( $post_types['movie'] ) ); // Should return true

// Test capabilities
$user = wp_get_current_user();
var_dump( $user->has_cap( 'edit_movies' ) ); // Should return true for admin

// Test archive URLs
echo get_post_type_archive_link( 'movie' ); // Should output movie archive URL
```

---

## 🎯 Next Step

Once custom post types are implemented, proceed to **[Step 5: Taxonomies](05_taxonomies.md)** to set up the taxonomy system.

---

*Estimated time for this step: 60-75 minutes*