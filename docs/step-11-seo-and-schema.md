# Step 11: SEO and Schema Markup - Complete Implementation

## Overview
This step implements comprehensive SEO optimization and Schema.org structured data markup for movies, TV shows, dramas, and people. The system enhances search engine visibility and provides rich snippets for better SERP appearance.

## 1. Schema Markup System

### 1.1 Movie Schema Implementation
```php
// src/SEO/Schema/MovieSchema.php
<?php
namespace TMU\SEO\Schema;

class MovieSchema {
    public function generate($post_id): array {
        $movie_data = tmu_get_movie_data($post_id);
        $post = get_post($post_id);
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Movie',
            'name' => get_the_title($post_id),
            'url' => get_permalink($post_id),
            'description' => $movie_data['overview'] ?: get_the_excerpt($post_id),
            'datePublished' => $movie_data['release_date'],
            'duration' => $this->format_duration($movie_data['runtime']),
            'genre' => $this->get_genres($post_id),
            'image' => $this->get_images($post_id),
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => $movie_data['vote_average'],
                'ratingCount' => $movie_data['vote_count'],
                'bestRating' => 10,
                'worstRating' => 1
            ],
            'productionCompany' => $this->get_production_companies($movie_data),
            'director' => $this->get_directors($post_id),
            'actor' => $this->get_actors($post_id),
            'countryOfOrigin' => $this->get_countries($post_id),
            'inLanguage' => $this->get_languages($post_id)
        ];
        
        // Add trailer if available
        $trailer = $this->get_trailer($post_id);
        if ($trailer) {
            $schema['trailer'] = $trailer;
        }
        
        // Add IMDB URL if available
        if (!empty($movie_data['imdb_id'])) {
            $schema['sameAs'] = ['https://www.imdb.com/title/' . $movie_data['imdb_id']];
        }
        
        return $schema;
    }
    
    private function format_duration($runtime): ?string {
        if (!$runtime) return null;
        
        $hours = floor($runtime / 60);
        $minutes = $runtime % 60;
        
        return "PT{$hours}H{$minutes}M";
    }
    
    private function get_genres($post_id): array {
        $genres = get_the_terms($post_id, 'genre');
        if (!$genres || is_wp_error($genres)) return [];
        
        return array_map(function($genre) {
            return $genre->name;
        }, $genres);
    }
    
    private function get_images($post_id): array {
        $images = [];
        
        // Featured image
        if (has_post_thumbnail($post_id)) {
            $images[] = get_the_post_thumbnail_url($post_id, 'large');
        }
        
        // Additional images from gallery
        $gallery = get_attached_media('image', $post_id);
        foreach (array_slice($gallery, 0, 5) as $image) {
            $images[] = wp_get_attachment_url($image->ID);
        }
        
        return $images;
    }
    
    private function get_production_companies($movie_data): array {
        $companies = json_decode($movie_data['production_companies'] ?? '[]', true);
        
        return array_map(function($company) {
            return [
                '@type' => 'Organization',
                'name' => $company['name']
            ];
        }, $companies);
    }
    
    private function get_directors($post_id): array {
        $credits = $this->get_credits($post_id);
        $directors = [];
        
        foreach ($credits['crew'] ?? [] as $crew_member) {
            if ($crew_member['department'] === 'Directing' && 
                in_array($crew_member['job'], ['Director', 'Co-Director'])) {
                $directors[] = [
                    '@type' => 'Person',
                    'name' => $crew_member['name'],
                    'url' => get_permalink($crew_member['person_id'])
                ];
            }
        }
        
        return $directors;
    }
    
    private function get_actors($post_id): array {
        $credits = $this->get_credits($post_id);
        $actors = [];
        
        foreach (array_slice($credits['cast'] ?? [], 0, 10) as $cast_member) {
            $actors[] = [
                '@type' => 'Person',
                'name' => $cast_member['name'],
                'url' => get_permalink($cast_member['person_id'])
            ];
        }
        
        return $actors;
    }
}
```

### 1.2 TV Show Schema Implementation
```php
// src/SEO/Schema/TVShowSchema.php
<?php
namespace TMU\SEO\Schema;

class TVShowSchema {
    public function generate($post_id): array {
        $tv_data = tmu_get_tv_data($post_id);
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'TVSeries',
            'name' => get_the_title($post_id),
            'url' => get_permalink($post_id),
            'description' => $tv_data['overview'] ?: get_the_excerpt($post_id),
            'startDate' => $tv_data['first_air_date'],
            'endDate' => $tv_data['last_air_date'],
            'numberOfSeasons' => $tv_data['number_of_seasons'],
            'numberOfEpisodes' => $tv_data['number_of_episodes'],
            'genre' => $this->get_genres($post_id),
            'image' => $this->get_images($post_id),
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => $tv_data['vote_average'],
                'ratingCount' => $tv_data['vote_count'],
                'bestRating' => 10,
                'worstRating' => 1
            ],
            'productionCompany' => $this->get_production_companies($tv_data),
            'creator' => $this->get_creators($post_id),
            'actor' => $this->get_actors($post_id),
            'countryOfOrigin' => $this->get_countries($post_id)
        ];
        
        // Add network information
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
    
    private function get_networks($post_id): array {
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
    
    private function get_seasons($post_id): array {
        $seasons = get_posts([
            'post_type' => 'season',
            'meta_key' => 'tv_series',
            'meta_value' => $post_id,
            'posts_per_page' => -1,
            'orderby' => 'meta_value_num',
            'meta_key' => 'season_no',
            'order' => 'ASC'
        ]);
        
        $season_schemas = [];
        foreach ($seasons as $season) {
            $season_data = tmu_get_season_data($season->ID);
            $season_schemas[] = [
                '@type' => 'TVSeason',
                'name' => $season->post_title,
                'seasonNumber' => $season_data['season_no'],
                'url' => get_permalink($season->ID),
                'numberOfEpisodes' => $this->count_season_episodes($season->ID)
            ];
        }
        
        return $season_schemas;
    }
}
```

### 1.3 Person Schema Implementation
```php
// src/SEO/Schema/PersonSchema.php
<?php
namespace TMU\SEO\Schema;

class PersonSchema {
    public function generate($post_id): array {
        $person_data = tmu_get_person_data($post_id);
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => get_the_title($post_id),
            'url' => get_permalink($post_id),
            'description' => $person_data['biography'] ?: get_the_excerpt($post_id),
            'image' => get_the_post_thumbnail_url($post_id, 'large'),
            'jobTitle' => $person_data['known_for_department'],
            'birthDate' => $person_data['birthday'],
            'birthPlace' => $person_data['place_of_birth'],
            'nationality' => $this->get_nationality($post_id),
            'knowsAbout' => $this->get_known_for($post_id)
        ];
        
        // Add social media profiles
        $social_profiles = $this->get_social_profiles($person_data);
        if ($social_profiles) {
            $schema['sameAs'] = $social_profiles;
        }
        
        // Add filmography
        $filmography = $this->get_filmography($post_id);
        if ($filmography) {
            $schema['performerIn'] = $filmography;
        }
        
        return $schema;
    }
    
    private function get_social_profiles($person_data): array {
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
    
    private function get_filmography($post_id): array {
        // Get movies and TV shows this person appeared in
        global $wpdb;
        
        $filmography = [];
        
        // Query movies
        $movies = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->prefix}tmu_movies_cast 
            WHERE person_id = %d
            UNION
            SELECT DISTINCT post_id 
            FROM {$wpdb->prefix}tmu_movies_crew 
            WHERE person_id = %d
        ", $post_id, $post_id));
        
        foreach ($movies as $movie) {
            $filmography[] = [
                '@type' => 'Movie',
                'name' => get_the_title($movie->post_id),
                'url' => get_permalink($movie->post_id)
            ];
        }
        
        return array_slice($filmography, 0, 20); // Limit to 20 items
    }
}
```

## 2. Meta Tags System

### 2.1 SEO Meta Tags Manager
```php
// src/SEO/MetaTags.php
<?php
namespace TMU\SEO;

class MetaTags {
    public function init(): void {
        add_action('wp_head', [$this, 'output_meta_tags'], 1);
        add_filter('document_title_parts', [$this, 'filter_title_parts']);
        add_filter('wp_title', [$this, 'filter_wp_title'], 10, 2);
    }
    
    public function output_meta_tags(): void {
        if (is_singular(['movie', 'tv', 'drama', 'people'])) {
            $this->output_post_meta_tags();
        } elseif (is_tax(['genre', 'country', 'language', 'by-year', 'network', 'channel'])) {
            $this->output_taxonomy_meta_tags();
        } elseif (is_archive()) {
            $this->output_archive_meta_tags();
        }
    }
    
    private function output_post_meta_tags(): void {
        global $post;
        
        $post_type = get_post_type();
        $data = $this->get_post_data($post->ID, $post_type);
        
        // Title
        $title = $this->generate_title($post->ID, $post_type);
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        
        // Description
        $description = $this->generate_description($post->ID, $post_type, $data);
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
        
        // Image
        $image = $this->get_featured_image($post->ID);
        if ($image) {
            echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
            echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n";
        }
        
        // Type
        $og_type = in_array($post_type, ['movie', 'tv', 'drama']) ? 'video.movie' : 'article';
        echo '<meta property="og:type" content="' . esc_attr($og_type) . '">' . "\n";
        
        // URL
        echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '">' . "\n";
        
        // Site name
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
        
        // Twitter card
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        
        // Additional meta tags based on post type
        $this->output_post_type_specific_meta($post->ID, $post_type, $data);
    }
    
    private function generate_title($post_id, $post_type): string {
        $title = get_the_title($post_id);
        
        switch ($post_type) {
            case 'movie':
                $movie_data = tmu_get_movie_data($post_id);
                if ($movie_data['release_date']) {
                    $year = date('Y', strtotime($movie_data['release_date']));
                    $title .= " ({$year})";
                }
                break;
                
            case 'tv':
            case 'drama':
                $tv_data = tmu_get_tv_data($post_id);
                if ($tv_data['first_air_date']) {
                    $year = date('Y', strtotime($tv_data['first_air_date']));
                    $title .= " ({$year})";
                }
                break;
                
            case 'people':
                $person_data = tmu_get_person_data($post_id);
                if ($person_data['known_for_department']) {
                    $title .= " - {$person_data['known_for_department']}";
                }
                break;
        }
        
        return $title;
    }
    
    private function generate_description($post_id, $post_type, $data): string {
        $description = '';
        
        switch ($post_type) {
            case 'movie':
                $description = $data['overview'] ?: $data['tagline'];
                if ($data['runtime']) {
                    $description .= " Runtime: {$data['runtime']} minutes.";
                }
                break;
                
            case 'tv':
            case 'drama':
                $description = $data['overview'];
                if ($data['number_of_seasons']) {
                    $description .= " {$data['number_of_seasons']} seasons";
                    if ($data['number_of_episodes']) {
                        $description .= ", {$data['number_of_episodes']} episodes";
                    }
                    $description .= ".";
                }
                break;
                
            case 'people':
                $description = $data['biography'];
                if ($data['known_for_department']) {
                    $description = "Known for {$data['known_for_department']}. " . $description;
                }
                break;
        }
        
        // Fallback to excerpt
        if (!$description) {
            $description = get_the_excerpt($post_id);
        }
        
        // Trim to appropriate length
        return wp_trim_words($description, 30, '...');
    }
    
    private function output_post_type_specific_meta($post_id, $post_type, $data): void {
        switch ($post_type) {
            case 'movie':
                if ($data['release_date']) {
                    echo '<meta property="video:release_date" content="' . esc_attr($data['release_date']) . '">' . "\n";
                }
                if ($data['runtime']) {
                    echo '<meta property="video:duration" content="' . esc_attr($data['runtime'] * 60) . '">' . "\n";
                }
                break;
                
            case 'tv':
            case 'drama':
                if ($data['first_air_date']) {
                    echo '<meta property="video:release_date" content="' . esc_attr($data['first_air_date']) . '">' . "\n";
                }
                break;
        }
        
        // Genre tags
        $genres = get_the_terms($post_id, 'genre');
        if ($genres && !is_wp_error($genres)) {
            foreach ($genres as $genre) {
                echo '<meta property="video:tag" content="' . esc_attr($genre->name) . '">' . "\n";
            }
        }
    }
}
```

## 3. Structured Data Integration

### 3.1 Schema Output Manager
```php
// src/SEO/SchemaManager.php
<?php
namespace TMU\SEO;

class SchemaManager {
    private $schema_generators = [];
    
    public function init(): void {
        $this->register_schema_generators();
        add_action('wp_head', [$this, 'output_schema'], 999);
    }
    
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
    
    public function output_schema(): void {
        $schemas = [];
        
        if (is_singular()) {
            $post_type = get_post_type();
            if (isset($this->schema_generators[$post_type])) {
                $schemas[] = $this->schema_generators[$post_type]->generate(get_the_ID());
            }
            
            // Add breadcrumb schema
            $schemas[] = $this->generate_breadcrumb_schema();
            
        } elseif (is_archive() || is_tax()) {
            // Add website schema for archive pages
            $schemas[] = $this->generate_website_schema();
            $schemas[] = $this->generate_breadcrumb_schema();
        }
        
        if (!empty($schemas)) {
            echo '<script type="application/ld+json">' . "\n";
            echo json_encode($schemas, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            echo "\n" . '</script>' . "\n";
        }
    }
    
    private function generate_breadcrumb_schema(): array {
        $breadcrumbs = $this->get_breadcrumbs();
        
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
            $taxonomy = get_taxonomy($term->taxonomy);
            
            $breadcrumbs[] = [
                'name' => $taxonomy->labels->name,
                'url' => get_term_link($term)
            ];
        }
        
        return $breadcrumbs;
    }
    
    private function generate_website_schema(): array {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'url' => home_url('/'),
            'description' => get_bloginfo('description'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => home_url('/?s={search_term_string}'),
                'query-input' => 'required name=search_term_string'
            ]
        ];
    }
}
```

## 4. XML Sitemap Generation

### 4.1 Custom Sitemap Generator
```php
// src/SEO/SitemapGenerator.php
<?php
namespace TMU\SEO;

class SitemapGenerator {
    public function init(): void {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_action('template_redirect', [$this, 'handle_sitemap_request']);
        add_action('wp_loaded', [$this, 'schedule_sitemap_generation']);
    }
    
    public function add_rewrite_rules(): void {
        add_rewrite_rule('^sitemap\.xml$', 'index.php?tmu_sitemap=index', 'top');
        add_rewrite_rule('^sitemap-([^/]+)\.xml$', 'index.php?tmu_sitemap=$matches[1]', 'top');
    }
    
    public function handle_sitemap_request(): void {
        $sitemap_type = get_query_var('tmu_sitemap');
        
        if (!$sitemap_type) return;
        
        header('Content-Type: application/xml; charset=UTF-8');
        
        switch ($sitemap_type) {
            case 'index':
                echo $this->generate_sitemap_index();
                break;
            case 'movies':
                echo $this->generate_post_type_sitemap('movie');
                break;
            case 'tv':
                echo $this->generate_post_type_sitemap('tv');
                break;
            case 'dramas':
                if (get_option('tmu_dramas') === 'on') {
                    echo $this->generate_post_type_sitemap('drama');
                }
                break;
            case 'people':
                echo $this->generate_post_type_sitemap('people');
                break;
            case 'taxonomies':
                echo $this->generate_taxonomy_sitemap();
                break;
            default:
                wp_die('Sitemap not found', 404);
        }
        
        exit;
    }
    
    private function generate_sitemap_index(): string {
        $sitemaps = [
            'movies' => home_url('/sitemap-movies.xml'),
            'tv' => home_url('/sitemap-tv.xml'),
            'people' => home_url('/sitemap-people.xml'),
            'taxonomies' => home_url('/sitemap-taxonomies.xml')
        ];
        
        if (get_option('tmu_dramas') === 'on') {
            $sitemaps['dramas'] = home_url('/sitemap-dramas.xml');
        }
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($sitemaps as $type => $url) {
            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>" . esc_url($url) . "</loc>\n";
            $xml .= "    <lastmod>" . date('Y-m-d\TH:i:s+00:00') . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }
        
        $xml .= '</sitemapindex>';
        
        return $xml;
    }
    
    private function generate_post_type_sitemap($post_type): string {
        $posts = get_posts([
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => 50000,
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
        $xml .= 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        
        foreach ($posts as $post) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . esc_url(get_permalink($post)) . "</loc>\n";
            $xml .= "    <lastmod>" . date('Y-m-d\TH:i:s+00:00', strtotime($post->post_modified)) . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            
            // Add image sitemap data
            if (has_post_thumbnail($post->ID)) {
                $image_url = get_the_post_thumbnail_url($post->ID, 'large');
                $xml .= "    <image:image>\n";
                $xml .= "      <image:loc>" . esc_url($image_url) . "</image:loc>\n";
                $xml .= "      <image:title>" . esc_html($post->post_title) . "</image:title>\n";
                $xml .= "    </image:image>\n";
            }
            
            $xml .= "  </url>\n";
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
}
```

## 5. Performance and Analytics

### 5.1 SEO Analytics Tracker
```php
// src/SEO/Analytics.php
<?php
namespace TMU\SEO;

class Analytics {
    public function init(): void {
        add_action('wp_footer', [$this, 'output_analytics']);
        add_action('wp_head', [$this, 'output_gtag']);
    }
    
    public function output_gtag(): void {
        $ga_id = get_option('tmu_google_analytics_id');
        if (!$ga_id) return;
        
        ?>
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($ga_id); ?>"></script>
        <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', '<?php echo esc_js($ga_id); ?>', {
            custom_map: {'custom_parameter_1': 'content_type'}
          });
          
          <?php if (is_singular(['movie', 'tv', 'drama'])): ?>
          gtag('event', 'page_view', {
            'custom_parameter_1': '<?php echo esc_js(get_post_type()); ?>',
            'content_id': '<?php echo esc_js(get_the_ID()); ?>',
            'content_name': '<?php echo esc_js(get_the_title()); ?>'
          });
          <?php endif; ?>
        </script>
        <?php
    }
    
    public function output_analytics(): void {
        ?>
        <script>
        // Track content interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Track trailer views
            document.querySelectorAll('.tmu-trailer-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    gtag('event', 'video_play', {
                        'event_category': 'engagement',
                        'event_label': 'trailer',
                        'content_id': this.dataset.postId
                    });
                });
            });
            
            // Track watchlist additions
            document.querySelectorAll('.tmu-add-watchlist').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    gtag('event', 'add_to_wishlist', {
                        'event_category': 'engagement',
                        'event_label': 'watchlist',
                        'content_id': this.dataset.postId
                    });
                });
            });
            
            // Track search usage
            const searchForm = document.querySelector('.tmu-search-form');
            if (searchForm) {
                searchForm.addEventListener('submit', function() {
                    const query = this.querySelector('input[type="search"]').value;
                    gtag('event', 'search', {
                        'search_term': query
                    });
                });
            }
        });
        </script>
        <?php
    }
}
```

## 6. Success Metrics

- [ ] Schema markup implemented for all post types
- [ ] Meta tags system generating appropriate tags
- [ ] XML sitemaps automatically generated
- [ ] Breadcrumb navigation with schema
- [ ] Analytics tracking configured
- [ ] Performance optimization in place
- [ ] Rich snippets appearing in search results
- [ ] SEO score improvements verified

## Next Steps

After completing this step, the theme will have:
- Comprehensive Schema.org markup
- Optimized meta tags for social sharing
- Automated XML sitemap generation
- Enhanced search engine visibility
- Structured data for rich snippets
- Analytics integration for tracking
- Improved SERP appearance
- Better content discoverability