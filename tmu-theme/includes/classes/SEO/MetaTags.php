<?php
namespace TMU\SEO;

/**
 * SEO Meta Tags Manager
 */
class MetaTags {
    
    /**
     * Initialize meta tags manager
     */
    public function init(): void {
        add_action('wp_head', [$this, 'output_meta_tags'], 1);
        add_filter('document_title_parts', [$this, 'filter_title_parts']);
        add_filter('wp_title', [$this, 'filter_wp_title'], 10, 2);
        add_action('wp_head', [$this, 'output_canonical_url'], 2);
        add_action('wp_head', [$this, 'output_robots_meta'], 3);
    }
    
    /**
     * Output all meta tags
     */
    public function output_meta_tags(): void {
        if (is_singular(['movie', 'tv', 'drama', 'people'])) {
            $this->output_post_meta_tags();
        } elseif (is_tax(['genre', 'country', 'language', 'by-year', 'network', 'channel'])) {
            $this->output_taxonomy_meta_tags();
        } elseif (is_archive()) {
            $this->output_archive_meta_tags();
        } elseif (is_search()) {
            $this->output_search_meta_tags();
        } elseif (is_home() || is_front_page()) {
            $this->output_homepage_meta_tags();
        }
    }
    
    /**
     * Output meta tags for single posts
     */
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
            echo '<meta property="og:image" content="' . esc_url($image['url']) . '">' . "\n";
            echo '<meta name="twitter:image" content="' . esc_url($image['url']) . '">' . "\n";
            
            if (!empty($image['width']) && !empty($image['height'])) {
                echo '<meta property="og:image:width" content="' . esc_attr($image['width']) . '">' . "\n";
                echo '<meta property="og:image:height" content="' . esc_attr($image['height']) . '">' . "\n";
            }
            
            if (!empty($image['alt'])) {
                echo '<meta property="og:image:alt" content="' . esc_attr($image['alt']) . '">' . "\n";
                echo '<meta name="twitter:image:alt" content="' . esc_attr($image['alt']) . '">' . "\n";
            }
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
        
        // Twitter site
        $twitter_handle = get_theme_mod('tmu_twitter_handle');
        if ($twitter_handle) {
            echo '<meta name="twitter:site" content="' . esc_attr($twitter_handle) . '">' . "\n";
        }
        
        // Article specific meta
        if (in_array($post_type, ['movie', 'tv', 'drama'])) {
            echo '<meta property="article:published_time" content="' . esc_attr(get_the_date('c', $post->ID)) . '">' . "\n";
            echo '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c', $post->ID)) . '">' . "\n";
            
            // Add genre tags
            $genres = get_the_terms($post->ID, 'genre');
            if ($genres && !is_wp_error($genres)) {
                foreach ($genres as $genre) {
                    echo '<meta property="article:tag" content="' . esc_attr($genre->name) . '">' . "\n";
                }
            }
        }
        
        // Additional meta tags based on post type
        $this->output_post_type_specific_meta($post->ID, $post_type, $data);
    }
    
    /**
     * Output taxonomy meta tags
     */
    private function output_taxonomy_meta_tags(): void {
        $term = get_queried_object();
        $taxonomy = get_taxonomy($term->taxonomy);
        
        $title = $this->generate_taxonomy_title($term, $taxonomy);
        $description = $this->generate_taxonomy_description($term, $taxonomy);
        
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_term_link($term)) . '">' . "\n";
        echo '<meta name="twitter:card" content="summary">' . "\n";
    }
    
    /**
     * Output archive meta tags
     */
    private function output_archive_meta_tags(): void {
        $post_type_object = get_queried_object();
        
        $title = $this->generate_archive_title($post_type_object);
        $description = $this->generate_archive_description($post_type_object);
        
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_post_type_archive_link($post_type_object->name)) . '">' . "\n";
        echo '<meta name="twitter:card" content="summary">' . "\n";
    }
    
    /**
     * Output search meta tags
     */
    private function output_search_meta_tags(): void {
        $search_query = get_search_query();
        
        $title = sprintf('Search Results for "%s"', $search_query);
        $description = sprintf('Search results for "%s" on %s', $search_query, get_bloginfo('name'));
        
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta name="robots" content="noindex, follow">' . "\n";
        echo '<meta name="twitter:card" content="summary">' . "\n";
    }
    
    /**
     * Output homepage meta tags
     */
    private function output_homepage_meta_tags(): void {
        $title = get_bloginfo('name');
        $description = get_bloginfo('description') ?: 'Discover movies, TV shows, and entertainment content';
        
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(home_url('/')) . '">' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        
        // Homepage image
        $logo_url = $this->get_site_logo();
        if ($logo_url) {
            echo '<meta property="og:image" content="' . esc_url($logo_url) . '">' . "\n";
            echo '<meta name="twitter:image" content="' . esc_url($logo_url) . '">' . "\n";
        }
    }
    
    /**
     * Generate optimized title for different content types
     */
    private function generate_title(int $post_id, string $post_type): string {
        $title = get_the_title($post_id);
        
        switch ($post_type) {
            case 'movie':
                $movie_data = function_exists('tmu_get_movie_data') ? tmu_get_movie_data($post_id) : [];
                if (!empty($movie_data['release_date'])) {
                    $year = date('Y', strtotime($movie_data['release_date']));
                    $title .= " ({$year})";
                }
                break;
                
            case 'tv':
            case 'drama':
                $tv_data = function_exists('tmu_get_tv_data') ? tmu_get_tv_data($post_id) : [];
                if (!empty($tv_data['first_air_date'])) {
                    $year = date('Y', strtotime($tv_data['first_air_date']));
                    $title .= " ({$year})";
                }
                break;
                
            case 'people':
                $person_data = function_exists('tmu_get_person_data') ? tmu_get_person_data($post_id) : [];
                if (!empty($person_data['known_for_department'])) {
                    $title .= " - {$person_data['known_for_department']}";
                }
                break;
        }
        
        return $title;
    }
    
    /**
     * Generate description for different content types
     */
    private function generate_description(int $post_id, string $post_type, array $data): string {
        $description = '';
        
        switch ($post_type) {
            case 'movie':
                $description = $data['overview'] ?: $data['tagline'] ?: get_the_excerpt($post_id);
                if (!empty($data['runtime'])) {
                    $description .= " Runtime: {$data['runtime']} minutes.";
                }
                if (!empty($data['vote_average'])) {
                    $description .= " Rating: {$data['vote_average']}/10.";
                }
                break;
                
            case 'tv':
            case 'drama':
                $description = $data['overview'] ?: get_the_excerpt($post_id);
                if (!empty($data['number_of_seasons'])) {
                    $description .= " {$data['number_of_seasons']} seasons";
                    if (!empty($data['number_of_episodes'])) {
                        $description .= ", {$data['number_of_episodes']} episodes";
                    }
                    $description .= ".";
                }
                break;
                
            case 'people':
                $description = $data['biography'] ?: get_the_excerpt($post_id);
                if (!empty($data['known_for_department'])) {
                    $description = "Known for {$data['known_for_department']}. " . $description;
                }
                break;
        }
        
        // Fallback to excerpt
        if (!$description) {
            $description = get_the_excerpt($post_id);
        }
        
        // Trim to appropriate length (155 characters for meta description)
        return wp_trim_words($description, 25, '...');
    }
    
    /**
     * Generate taxonomy title
     */
    private function generate_taxonomy_title($term, $taxonomy): string {
        $title = $term->name;
        
        if ($taxonomy->name === 'genre') {
            $title .= ' Movies & TV Shows';
        } elseif ($taxonomy->name === 'country') {
            $title .= ' Films & Television';
        } elseif ($taxonomy->name === 'by-year') {
            $title .= ' Movies & TV Shows';
        }
        
        return $title;
    }
    
    /**
     * Generate taxonomy description
     */
    private function generate_taxonomy_description($term, $taxonomy): string {
        if ($term->description) {
            return $term->description;
        }
        
        $count = $term->count;
        $content_type = 'content';
        
        switch ($taxonomy->name) {
            case 'genre':
                $content_type = 'movies and TV shows';
                break;
            case 'country':
                $content_type = 'films and television series';
                break;
            case 'by-year':
                $content_type = 'movies and TV shows';
                break;
        }
        
        return sprintf('Browse %d %s in the %s category.', $count, $content_type, $term->name);
    }
    
    /**
     * Generate archive title
     */
    private function generate_archive_title($post_type_object): string {
        return $post_type_object->labels->name . ' Archive';
    }
    
    /**
     * Generate archive description
     */
    private function generate_archive_description($post_type_object): string {
        if ($post_type_object->description) {
            return $post_type_object->description;
        }
        
        $count = wp_count_posts($post_type_object->name)->publish;
        return sprintf('Browse our collection of %d %s.', $count, strtolower($post_type_object->labels->name));
    }
    
    /**
     * Get post data for meta generation
     */
    private function get_post_data(int $post_id, string $post_type): array {
        switch ($post_type) {
            case 'movie':
                return function_exists('tmu_get_movie_data') ? tmu_get_movie_data($post_id) : [];
            case 'tv':
            case 'drama':
                return function_exists('tmu_get_tv_data') ? tmu_get_tv_data($post_id) : [];
            case 'people':
                return function_exists('tmu_get_person_data') ? tmu_get_person_data($post_id) : [];
            default:
                return [];
        }
    }
    
    /**
     * Get featured image with metadata
     */
    private function get_featured_image(int $post_id): ?array {
        if (!has_post_thumbnail($post_id)) {
            return null;
        }
        
        $attachment_id = get_post_thumbnail_id($post_id);
        $image_url = get_the_post_thumbnail_url($post_id, 'large');
        $image_meta = wp_get_attachment_metadata($attachment_id);
        $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        
        return [
            'url' => $image_url,
            'width' => $image_meta['width'] ?? null,
            'height' => $image_meta['height'] ?? null,
            'alt' => $alt_text ?: get_the_title($post_id)
        ];
    }
    
    /**
     * Get site logo URL
     */
    private function get_site_logo(): ?string {
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            return wp_get_attachment_image_url($custom_logo_id, 'full');
        }
        
        return null;
    }
    
    /**
     * Output post-type specific meta tags
     */
    private function output_post_type_specific_meta(int $post_id, string $post_type, array $data): void {
        switch ($post_type) {
            case 'movie':
                if (!empty($data['release_date'])) {
                    echo '<meta property="video:release_date" content="' . esc_attr($data['release_date']) . '">' . "\n";
                }
                if (!empty($data['runtime'])) {
                    echo '<meta property="video:duration" content="' . esc_attr($data['runtime'] * 60) . '">' . "\n";
                }
                if (!empty($data['imdb_id'])) {
                    echo '<meta property="video:imdb_id" content="' . esc_attr($data['imdb_id']) . '">' . "\n";
                }
                break;
                
            case 'tv':
            case 'drama':
                if (!empty($data['first_air_date'])) {
                    echo '<meta property="video:release_date" content="' . esc_attr($data['first_air_date']) . '">' . "\n";
                }
                if (!empty($data['number_of_seasons'])) {
                    echo '<meta property="video:series:seasons" content="' . esc_attr($data['number_of_seasons']) . '">' . "\n";
                }
                if (!empty($data['number_of_episodes'])) {
                    echo '<meta property="video:series:episodes" content="' . esc_attr($data['number_of_episodes']) . '">' . "\n";
                }
                break;
                
            case 'people':
                if (!empty($data['birthday'])) {
                    echo '<meta property="profile:birth_date" content="' . esc_attr($data['birthday']) . '">' . "\n";
                }
                break;
        }
        
        // Genre tags for all content types
        $genres = get_the_terms($post_id, 'genre');
        if ($genres && !is_wp_error($genres)) {
            foreach ($genres as $genre) {
                echo '<meta property="video:tag" content="' . esc_attr($genre->name) . '">' . "\n";
            }
        }
    }
    
    /**
     * Output canonical URL
     */
    public function output_canonical_url(): void {
        $canonical_url = '';
        
        if (is_singular()) {
            $canonical_url = get_permalink();
        } elseif (is_post_type_archive()) {
            $post_type_object = get_queried_object();
            $canonical_url = get_post_type_archive_link($post_type_object->name);
        } elseif (is_tax()) {
            $term = get_queried_object();
            $canonical_url = get_term_link($term);
        } elseif (is_home()) {
            $canonical_url = home_url('/');
        }
        
        if ($canonical_url) {
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . "\n";
        }
    }
    
    /**
     * Output robots meta tag
     */
    public function output_robots_meta(): void {
        $robots = [];
        
        if (is_search() || is_404()) {
            $robots[] = 'noindex';
            $robots[] = 'nofollow';
        } elseif (is_paged() && get_query_var('paged') > 1) {
            $robots[] = 'noindex';
            $robots[] = 'follow';
        } else {
            $robots[] = 'index';
            $robots[] = 'follow';
        }
        
        // Add additional directives
        $robots[] = 'max-snippet:-1';
        $robots[] = 'max-image-preview:large';
        $robots[] = 'max-video-preview:-1';
        
        echo '<meta name="robots" content="' . esc_attr(implode(', ', $robots)) . '">' . "\n";
    }
    
    /**
     * Filter document title parts
     */
    public function filter_title_parts(array $title_parts): array {
        if (is_singular(['movie', 'tv', 'drama', 'people'])) {
            $post_type = get_post_type();
            $title_parts['title'] = $this->generate_title(get_the_ID(), $post_type);
        }
        
        return $title_parts;
    }
    
    /**
     * Filter wp_title for older themes
     */
    public function filter_wp_title(string $title, string $sep): string {
        if (is_singular(['movie', 'tv', 'drama', 'people'])) {
            $post_type = get_post_type();
            $custom_title = $this->generate_title(get_the_ID(), $post_type);
            return $custom_title . " {$sep} " . get_bloginfo('name');
        }
        
        return $title;
    }
}