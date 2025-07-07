<?php
namespace TMU\SEO;

/**
 * Schema Manager for comprehensive Schema.org markup
 */
class SchemaManager {
    private $schema_generators = [];
    
    public function __construct() {
        $this->register_schema_generators();
    }
    
    /**
     * Initialize schema manager
     */
    public function init(): void {
        add_action('wp_head', [$this, 'output_schema'], 999);
        add_filter('wp_head', [$this, 'add_json_ld_script']);
    }
    
    /**
     * Register schema generators for different content types
     */
    private function register_schema_generators(): void {
        $this->schema_generators = [
            'movie' => new Schema\MovieSchema(),
            'tv' => new Schema\TVShowSchema(),
            'drama' => new Schema\TVShowSchema(), // Drama uses TV show schema
            'people' => new Schema\PersonSchema(),
            'episode' => new Schema\EpisodeSchema(),
            'drama-episode' => new Schema\EpisodeSchema(),
            'season' => new Schema\SeasonSchema()
        ];
    }
    
    /**
     * Output schema markup
     */
    public function output_schema(): void {
        $schemas = [];
        
        if (is_singular()) {
            $post_type = get_post_type();
            if (isset($this->schema_generators[$post_type])) {
                $schema = $this->schema_generators[$post_type]->generate(get_the_ID());
                if ($schema) {
                    $schemas[] = $schema;
                }
            }
            
            // Add breadcrumb schema
            $breadcrumb_schema = $this->generate_breadcrumb_schema();
            if ($breadcrumb_schema) {
                $schemas[] = $breadcrumb_schema;
            }
            
        } elseif (is_archive() || is_tax() || is_home()) {
            // Add website schema for archive pages
            $schemas[] = $this->generate_website_schema();
            $schemas[] = $this->generate_breadcrumb_schema();
            
            // Add collection page schema for archives
            if (is_post_type_archive() || is_tax()) {
                $collection_schema = $this->generate_collection_page_schema();
                if ($collection_schema) {
                    $schemas[] = $collection_schema;
                }
            }
        }
        
        if (!empty($schemas)) {
            $this->output_json_ld($schemas);
        }
    }
    

    
    /**
     * Generate breadcrumb schema
     */
    public function generate_breadcrumb_schema(): ?array {
        $breadcrumbs = $this->get_breadcrumbs();
        
        if (empty($breadcrumbs)) return null;
        
        $breadcrumb_list = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];
        
        foreach ($breadcrumbs as $position => $breadcrumb) {
            $breadcrumb_list['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $breadcrumb['name'],
                'item' => $breadcrumb['url']
            ];
        }
        
        return $breadcrumb_list;
    }
    
    /**
     * Generate website schema
     */
    public function generate_website_schema(): array {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'url' => home_url('/'),
            'description' => get_bloginfo('description'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => home_url('/?s={search_term_string}')
                ],
                'query-input' => 'required name=search_term_string'
            ]
        ];
    }
    
    /**
     * Generate collection page schema for archives
     */
    public function generate_collection_page_schema(): ?array {
        if (is_post_type_archive()) {
            $post_type_object = get_queried_object();
            
            return [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => $post_type_object->labels->name,
                'url' => get_post_type_archive_link($post_type_object->name),
                'description' => $post_type_object->description ?: "Browse all {$post_type_object->labels->name}",
                'mainEntity' => [
                    '@type' => 'ItemList',
                    'name' => $post_type_object->labels->name,
                    'numberOfItems' => wp_count_posts($post_type_object->name)->publish
                ]
            ];
        }
        
        if (is_tax()) {
            $term = get_queried_object();
            $taxonomy = get_taxonomy($term->taxonomy);
            
            return [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => $term->name,
                'url' => get_term_link($term),
                'description' => $term->description ?: "Browse {$taxonomy->labels->name}: {$term->name}",
                'mainEntity' => [
                    '@type' => 'ItemList',
                    'name' => $term->name,
                    'numberOfItems' => $term->count
                ]
            ];
        }
        
        return null;
    }
    
    /**
     * Helper methods
     */
    
    private function format_duration(int $runtime): string {
        $hours = floor($runtime / 60);
        $minutes = $runtime % 60;
        return "PT{$hours}H{$minutes}M";
    }
    
    private function get_images(int $post_id): array {
        $images = [];
        
        // Featured image
        if (has_post_thumbnail($post_id)) {
            $images[] = get_the_post_thumbnail_url($post_id, 'large');
        }
        
        // Additional images from gallery
        $gallery = get_attached_media('image', $post_id);
        foreach (array_slice($gallery, 0, 5) as $image) {
            $url = wp_get_attachment_url($image->ID);
            if ($url && !in_array($url, $images)) {
                $images[] = $url;
            }
        }
        
        return $images;
    }
    
    private function get_genres(int $post_id): array {
        $genres = get_the_terms($post_id, 'genre');
        if (!$genres || is_wp_error($genres)) return [];
        
        return array_map(function($genre) {
            return $genre->name;
        }, $genres);
    }
    
    private function get_keywords(int $post_id): array {
        // Get keywords from custom meta or tags
        $keywords = get_post_meta($post_id, 'keywords', true);
        if ($keywords) {
            return is_array($keywords) ? $keywords : explode(',', $keywords);
        }
        
        // Fallback to tags
        $tags = get_the_tags($post_id);
        if ($tags && !is_wp_error($tags)) {
            return array_map(function($tag) {
                return $tag->name;
            }, $tags);
        }
        
        return [];
    }
    
    private function get_credits(int $post_id): array {
        // This would integrate with your TMDB credits data
        $credits = [];
        
        // Get credits from custom meta
        $credits_data = get_post_meta($post_id, 'credits', true);
        if ($credits_data) {
            $credits_array = is_string($credits_data) ? json_decode($credits_data, true) : $credits_data;
            
            if (isset($credits_array['crew'])) {
                $directors = array_filter($credits_array['crew'], function($crew) {
                    return $crew['job'] === 'Director';
                });
                
                $credits['directors'] = array_map(function($director) {
                    return [
                        '@type' => 'Person',
                        'name' => $director['name']
                    ];
                }, $directors);
            }
            
            if (isset($credits_array['cast'])) {
                $cast = array_slice($credits_array['cast'], 0, 10);
                $credits['cast'] = array_map(function($actor) {
                    return [
                        '@type' => 'Person',
                        'name' => $actor['name']
                    ];
                }, $cast);
            }
        }
        
        return $credits;
    }
    
    private function get_countries(int $post_id): array {
        $countries = get_the_terms($post_id, 'country');
        if (!$countries || is_wp_error($countries)) return [];
        
        return array_map(function($country) {
            return [
                '@type' => 'Country',
                'name' => $country->name
            ];
        }, $countries);
    }
    
    private function get_networks(int $post_id): array {
        $networks = get_the_terms($post_id, 'network');
        if (!$networks || is_wp_error($networks)) return [];
        
        return array_map(function($network) {
            return [
                '@type' => 'BroadcastChannel',
                'name' => $network->name,
                'url' => get_term_link($network)
            ];
        }, $networks);
    }
    
    private function get_seasons(int $post_id): array {
        // Get related seasons
        $seasons = get_posts([
            'post_type' => 'season',
            'meta_key' => 'tv_series',
            'meta_value' => $post_id,
            'posts_per_page' => -1,
            'orderby' => 'meta_value_num',
            'meta_key' => 'season_number',
            'order' => 'ASC'
        ]);
        
        $season_schemas = [];
        foreach ($seasons as $season) {
            $season_number = get_post_meta($season->ID, 'season_number', true);
            $episode_count = get_post_meta($season->ID, 'episode_count', true);
            
            $season_schemas[] = [
                '@type' => 'TVSeason',
                'name' => $season->post_title,
                'seasonNumber' => $season_number,
                'url' => get_permalink($season->ID),
                'numberOfEpisodes' => $episode_count ?: 0
            ];
        }
        
        return $season_schemas;
    }
    
    private function get_trailer(int $post_id): ?array {
        $videos = get_posts([
            'post_type' => 'video',
            'meta_key' => 'related_post_id',
            'meta_value' => $post_id,
            'meta_query' => [
                [
                    'key' => 'content_type',
                    'value' => 'trailer',
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1
        ]);
        
        if ($videos) {
            $video = $videos[0];
            $source = get_post_meta($video->ID, 'source', true);
            
            return [
                '@type' => 'VideoObject',
                'name' => $video->post_title,
                'embedUrl' => "https://www.youtube.com/embed/{$source}",
                'url' => get_permalink($video->ID)
            ];
        }
        
        return null;
    }
    
    private function get_social_profiles(array $person_data): array {
        $profiles = [];
        
        if (!empty($person_data['imdb_id'])) {
            $profiles[] = 'https://www.imdb.com/name/' . $person_data['imdb_id'];
        }
        
        if (!empty($person_data['instagram_id'])) {
            $profiles[] = 'https://www.instagram.com/' . $person_data['instagram_id'];
        }
        
        if (!empty($person_data['twitter_id'])) {
            $profiles[] = 'https://twitter.com/' . $person_data['twitter_id'];
        }
        
        return $profiles;
    }
    
    private function get_known_for_works(int $post_id): array {
        // Get the person's most popular works
        global $wpdb;
        
        $works = [];
        
        // This would query your cast/crew tables
        $movies = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->prefix}tmu_movies_cast 
            WHERE person_id = %d
            ORDER BY popularity DESC
            LIMIT 5
        ", $post_id));
        
        foreach ($movies as $movie) {
            $works[] = get_the_title($movie->post_id);
        }
        
        return $works;
    }
    
    private function get_breadcrumbs(): array {
        $breadcrumbs = [
            ['name' => 'Home', 'url' => home_url('/')]
        ];
        
        if (is_singular()) {
            $post_type = get_post_type();
            
            // Add post type archive
            $post_type_object = get_post_type_object($post_type);
            if ($post_type_object && $post_type_object->has_archive) {
                $breadcrumbs[] = [
                    'name' => $post_type_object->labels->name,
                    'url' => get_post_type_archive_link($post_type)
                ];
            }
            
            // Add current post
            $breadcrumbs[] = [
                'name' => get_the_title(),
                'url' => get_permalink()
            ];
            
        } elseif (is_post_type_archive()) {
            $post_type_object = get_queried_object();
            $breadcrumbs[] = [
                'name' => $post_type_object->labels->name,
                'url' => get_post_type_archive_link($post_type_object->name)
            ];
            
        } elseif (is_tax()) {
            $term = get_queried_object();
            $breadcrumbs[] = [
                'name' => $term->name,
                'url' => get_term_link($term)
            ];
        }
        
        return $breadcrumbs;
    }
    
    /**
     * Output JSON-LD script
     */
    private function output_json_ld(array $schemas): void {
        echo '<script type="application/ld+json">' . "\n";
        
        if (count($schemas) === 1) {
            echo wp_json_encode($schemas[0], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            // Multiple schemas - output as array
            echo wp_json_encode($schemas, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        
        echo "\n" . '</script>' . "\n";
    }
    

    
    /**
     * Add JSON-LD script type
     */
    public function add_json_ld_script($tag): string {
        return str_replace('<script type="application/ld+json"', '<script type="application/ld+json"', $tag);
    }
}