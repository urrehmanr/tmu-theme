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
            'movie' => [$this, 'generate_movie_schema'],
            'tv' => [$this, 'generate_tv_schema'],
            'drama' => [$this, 'generate_tv_schema'], // Drama uses TV show schema
            'people' => [$this, 'generate_person_schema'],
            'episode' => [$this, 'generate_episode_schema'],
            'drama-episode' => [$this, 'generate_episode_schema'],
            'season' => [$this, 'generate_season_schema']
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
                $schema = call_user_func($this->schema_generators[$post_type], get_the_ID());
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
     * Generate movie schema
     */
    public function generate_movie_schema(int $post_id): ?array {
        $movie_data = function_exists('tmu_get_movie_data') ? tmu_get_movie_data($post_id) : [];
        $post = get_post($post_id);
        
        if (!$post) return null;
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Movie',
            'name' => get_the_title($post_id),
            'url' => get_permalink($post_id),
            'description' => $movie_data['overview'] ?: get_the_excerpt($post_id),
            'datePublished' => $movie_data['release_date'] ?: get_the_date('Y-m-d', $post_id),
            'image' => $this->get_images($post_id),
            'genre' => $this->get_genres($post_id),
            'keywords' => $this->get_keywords($post_id)
        ];
        
        // Add duration if available
        if (!empty($movie_data['runtime'])) {
            $schema['duration'] = $this->format_duration($movie_data['runtime']);
        }
        
        // Add rating if available
        if (!empty($movie_data['vote_average']) && !empty($movie_data['vote_count'])) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $movie_data['vote_average'],
                'ratingCount' => $movie_data['vote_count'],
                'bestRating' => 10,
                'worstRating' => 1
            ];
        }
        
        // Add production details
        if (!empty($movie_data['production_companies'])) {
            $companies = is_string($movie_data['production_companies']) 
                ? json_decode($movie_data['production_companies'], true) 
                : $movie_data['production_companies'];
            
            if (is_array($companies)) {
                $schema['productionCompany'] = array_map(function($company) {
                    return [
                        '@type' => 'Organization',
                        'name' => $company['name'] ?? $company
                    ];
                }, $companies);
            }
        }
        
        // Add director and cast
        $credits = $this->get_credits($post_id);
        if (!empty($credits['directors'])) {
            $schema['director'] = $credits['directors'];
        }
        if (!empty($credits['cast'])) {
            $schema['actor'] = $credits['cast'];
        }
        
        // Add countries
        $countries = $this->get_countries($post_id);
        if ($countries) {
            $schema['countryOfOrigin'] = $countries;
        }
        
        // Add trailer if available
        $trailer = $this->get_trailer($post_id);
        if ($trailer) {
            $schema['trailer'] = $trailer;
        }
        
        // Add IMDB link
        if (!empty($movie_data['imdb_id'])) {
            $schema['sameAs'] = ['https://www.imdb.com/title/' . $movie_data['imdb_id']];
        }
        
        return $schema;
    }
    
    /**
     * Generate TV show schema
     */
    public function generate_tv_schema(int $post_id): ?array {
        $tv_data = function_exists('tmu_get_tv_data') ? tmu_get_tv_data($post_id) : [];
        $post = get_post($post_id);
        
        if (!$post) return null;
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'TVSeries',
            'name' => get_the_title($post_id),
            'url' => get_permalink($post_id),
            'description' => $tv_data['overview'] ?: get_the_excerpt($post_id),
            'image' => $this->get_images($post_id),
            'genre' => $this->get_genres($post_id),
            'keywords' => $this->get_keywords($post_id)
        ];
        
        // Add air dates
        if (!empty($tv_data['first_air_date'])) {
            $schema['startDate'] = $tv_data['first_air_date'];
        }
        if (!empty($tv_data['last_air_date'])) {
            $schema['endDate'] = $tv_data['last_air_date'];
        }
        
        // Add episode counts
        if (!empty($tv_data['number_of_seasons'])) {
            $schema['numberOfSeasons'] = $tv_data['number_of_seasons'];
        }
        if (!empty($tv_data['number_of_episodes'])) {
            $schema['numberOfEpisodes'] = $tv_data['number_of_episodes'];
        }
        
        // Add rating
        if (!empty($tv_data['vote_average']) && !empty($tv_data['vote_count'])) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $tv_data['vote_average'],
                'ratingCount' => $tv_data['vote_count'],
                'bestRating' => 10,
                'worstRating' => 1
            ];
        }
        
        // Add creator and cast
        $credits = $this->get_credits($post_id);
        if (!empty($credits['creators'])) {
            $schema['creator'] = $credits['creators'];
        }
        if (!empty($credits['cast'])) {
            $schema['actor'] = $credits['cast'];
        }
        
        // Add networks
        $networks = $this->get_networks($post_id);
        if ($networks) {
            $schema['broadcastChannel'] = $networks;
        }
        
        // Add seasons
        $seasons = $this->get_seasons($post_id);
        if ($seasons) {
            $schema['containsSeason'] = $seasons;
        }
        
        return $schema;
    }
    
    /**
     * Generate person schema
     */
    public function generate_person_schema(int $post_id): ?array {
        $person_data = function_exists('tmu_get_person_data') ? tmu_get_person_data($post_id) : [];
        $post = get_post($post_id);
        
        if (!$post) return null;
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => get_the_title($post_id),
            'url' => get_permalink($post_id),
            'description' => $person_data['biography'] ?: get_the_excerpt($post_id),
            'image' => get_the_post_thumbnail_url($post_id, 'large')
        ];
        
        // Add job title
        if (!empty($person_data['known_for_department'])) {
            $schema['jobTitle'] = $person_data['known_for_department'];
        }
        
        // Add birth information
        if (!empty($person_data['birthday'])) {
            $schema['birthDate'] = $person_data['birthday'];
        }
        if (!empty($person_data['place_of_birth'])) {
            $schema['birthPlace'] = $person_data['place_of_birth'];
        }
        
        // Add death date if applicable
        if (!empty($person_data['deathday'])) {
            $schema['deathDate'] = $person_data['deathday'];
        }
        
        // Add social profiles
        $social_profiles = $this->get_social_profiles($person_data);
        if ($social_profiles) {
            $schema['sameAs'] = $social_profiles;
        }
        
        // Add known for works
        $known_for = $this->get_known_for_works($post_id);
        if ($known_for) {
            $schema['knowsAbout'] = $known_for;
        }
        
        return $schema;
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
     * Generate episode schema
     */
    public function generate_episode_schema(int $post_id): ?array {
        $episode_data = function_exists('tmu_get_episode_data') ? tmu_get_episode_data($post_id) : [];
        $post = get_post($post_id);
        
        if (!$post) return null;
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'TVEpisode',
            'name' => get_the_title($post_id),
            'url' => get_permalink($post_id),
            'description' => $episode_data['overview'] ?: get_the_excerpt($post_id),
            'image' => $this->get_images($post_id)
        ];
        
        // Add episode number
        if (!empty($episode_data['episode_number'])) {
            $schema['episodeNumber'] = $episode_data['episode_number'];
        }
        
        // Add season number
        if (!empty($episode_data['season_number'])) {
            $schema['seasonNumber'] = $episode_data['season_number'];
        }
        
        // Add air date
        if (!empty($episode_data['air_date'])) {
            $schema['datePublished'] = $episode_data['air_date'];
        }
        
        // Add runtime
        if (!empty($episode_data['runtime'])) {
            $schema['duration'] = $this->format_duration($episode_data['runtime']);
        }
        
        // Add parent series
        $series_id = get_post_meta($post_id, 'tv_series_id', true) ?: get_post_meta($post_id, 'drama_id', true);
        if ($series_id) {
            $schema['partOfSeries'] = [
                '@type' => 'TVSeries',
                'name' => get_the_title($series_id),
                'url' => get_permalink($series_id)
            ];
        }
        
        // Add parent season
        $season_id = get_post_meta($post_id, 'season_id', true);
        if ($season_id) {
            $season_number = get_post_meta($season_id, 'season_number', true);
            $schema['partOfSeason'] = [
                '@type' => 'TVSeason',
                'name' => get_the_title($season_id),
                'seasonNumber' => $season_number,
                'url' => get_permalink($season_id)
            ];
        }
        
        // Add rating if available
        if (!empty($episode_data['vote_average']) && !empty($episode_data['vote_count'])) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $episode_data['vote_average'],
                'ratingCount' => $episode_data['vote_count'],
                'bestRating' => 10,
                'worstRating' => 1
            ];
        }
        
        return $schema;
    }
    
    /**
     * Generate season schema
     */
    public function generate_season_schema(int $post_id): ?array {
        $season_data = function_exists('tmu_get_season_data') ? tmu_get_season_data($post_id) : [];
        $post = get_post($post_id);
        
        if (!$post) return null;
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'TVSeason',
            'name' => get_the_title($post_id),
            'url' => get_permalink($post_id),
            'description' => $season_data['overview'] ?: get_the_excerpt($post_id),
            'image' => $this->get_images($post_id)
        ];
        
        // Add season number
        $season_number = get_post_meta($post_id, 'season_number', true);
        if ($season_number) {
            $schema['seasonNumber'] = $season_number;
        }
        
        // Add air date
        if (!empty($season_data['air_date'])) {
            $schema['startDate'] = $season_data['air_date'];
        }
        
        // Add episode count
        $episode_count = get_post_meta($post_id, 'episode_count', true);
        if ($episode_count) {
            $schema['numberOfEpisodes'] = $episode_count;
        }
        
        // Add parent series
        $series_id = get_post_meta($post_id, 'tv_series_id', true) ?: get_post_meta($post_id, 'drama_id', true);
        if ($series_id) {
            $schema['partOfSeries'] = [
                '@type' => 'TVSeries',
                'name' => get_the_title($series_id),
                'url' => get_permalink($series_id)
            ];
        }
        
        // Add episodes
        $episodes = get_posts([
            'post_type' => ['episode', 'drama-episode'],
            'meta_key' => 'season_id',
            'meta_value' => $post_id,
            'posts_per_page' => -1,
            'orderby' => 'meta_value_num',
            'meta_key' => 'episode_number',
            'order' => 'ASC'
        ]);
        
        if ($episodes) {
            $episode_schemas = [];
            foreach ($episodes as $episode) {
                $episode_number = get_post_meta($episode->ID, 'episode_number', true);
                $episode_schemas[] = [
                    '@type' => 'TVEpisode',
                    'name' => $episode->post_title,
                    'episodeNumber' => $episode_number,
                    'url' => get_permalink($episode->ID)
                ];
            }
            $schema['episode'] = $episode_schemas;
        }
        
        return $schema;
    }
    
    /**
     * Add JSON-LD script type
     */
    public function add_json_ld_script($tag): string {
        return str_replace('<script type="application/ld+json"', '<script type="application/ld+json"', $tag);
    }
}