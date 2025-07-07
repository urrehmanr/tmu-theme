<?php
namespace TMU\SEO;

class OpenGraph {
    public function init(): void {
        add_action('wp_head', [$this, 'output_og_tags'], 5);
    }
    
    public function output_og_tags(): void {
        if (is_singular(['movie', 'tv', 'drama', 'people'])) {
            $this->output_post_og_tags();
        } elseif (is_tax(['genre', 'country', 'language', 'by-year', 'network', 'channel'])) {
            $this->output_taxonomy_og_tags();
        } elseif (is_archive()) {
            $this->output_archive_og_tags();
        } elseif (is_home() || is_front_page()) {
            $this->output_homepage_og_tags();
        }
    }
    
    private function output_post_og_tags(): void {
        global $post;
        
        $post_type = get_post_type();
        $data = $this->get_post_data($post->ID, $post_type);
        
        // Basic OG tags
        $this->output_og_tag('og:type', $this->get_og_type($post_type));
        $this->output_og_tag('og:title', $this->get_og_title($post->ID, $post_type, $data));
        $this->output_og_tag('og:description', $this->get_og_description($post->ID, $post_type, $data));
        $this->output_og_tag('og:url', get_permalink($post->ID));
        $this->output_og_tag('og:site_name', get_bloginfo('name'));
        
        // Image
        $image = $this->get_og_image($post->ID);
        if ($image) {
            $this->output_og_tag('og:image', $image['url']);
            $this->output_og_tag('og:image:width', $image['width']);
            $this->output_og_tag('og:image:height', $image['height']);
            $this->output_og_tag('og:image:alt', $image['alt']);
        }
        
        // Post type specific tags
        $this->output_post_type_og_tags($post->ID, $post_type, $data);
        
        // Additional tags
        $this->output_og_tag('og:locale', get_locale());
        $this->output_og_tag('article:published_time', get_the_date('c', $post->ID));
        $this->output_og_tag('article:modified_time', get_the_modified_date('c', $post->ID));
        
        // Tags for genres
        $genres = get_the_terms($post->ID, 'genre');
        if ($genres && !is_wp_error($genres)) {
            foreach ($genres as $genre) {
                $this->output_og_tag('article:tag', $genre->name);
            }
        }
    }
    
    private function output_post_type_og_tags($post_id, $post_type, $data): void {
        switch ($post_type) {
            case 'movie':
                $this->output_og_tag('video:release_date', $data['release_date']);
                $this->output_og_tag('video:duration', $data['runtime'] ? $data['runtime'] * 60 : null);
                $this->output_og_tag('video:director', $this->get_director_name($post_id));
                
                // Cast members
                $cast = $this->get_cast_members($post_id, 5);
                foreach ($cast as $actor) {
                    $this->output_og_tag('video:actor', $actor['name']);
                }
                
                break;
                
            case 'tv':
            case 'drama':
                $this->output_og_tag('video:release_date', $data['first_air_date']);
                $this->output_og_tag('video:series', get_the_title($post_id));
                
                // Creators
                $creators = $this->get_creators($post_id);
                foreach ($creators as $creator) {
                    $this->output_og_tag('video:director', $creator['name']);
                }
                
                // Network
                $networks = get_the_terms($post_id, 'network');
                if ($networks && !is_wp_error($networks)) {
                    $this->output_og_tag('video:network', $networks[0]->name);
                }
                
                break;
                
            case 'people':
                $this->output_og_tag('profile:first_name', $this->get_first_name($data['name']));
                $this->output_og_tag('profile:last_name', $this->get_last_name($data['name']));
                $this->output_og_tag('profile:username', sanitize_title($data['name']));
                
                break;
        }
    }
    
    private function output_taxonomy_og_tags(): void {
        $term = get_queried_object();
        
        $this->output_og_tag('og:type', 'website');
        $this->output_og_tag('og:title', $this->get_taxonomy_title($term));
        $this->output_og_tag('og:description', $this->get_taxonomy_description($term));
        $this->output_og_tag('og:url', get_term_link($term));
        $this->output_og_tag('og:site_name', get_bloginfo('name'));
        
        // Image for taxonomy
        $image = $this->get_taxonomy_image($term);
        if ($image) {
            $this->output_og_tag('og:image', $image);
        }
    }
    
    private function output_archive_og_tags(): void {
        $post_type_obj = get_queried_object();
        
        $this->output_og_tag('og:type', 'website');
        $this->output_og_tag('og:title', $post_type_obj->labels->name);
        $this->output_og_tag('og:description', $this->get_archive_description($post_type_obj));
        $this->output_og_tag('og:url', get_post_type_archive_link($post_type_obj->name));
        $this->output_og_tag('og:site_name', get_bloginfo('name'));
    }
    
    private function output_homepage_og_tags(): void {
        $this->output_og_tag('og:type', 'website');
        $this->output_og_tag('og:title', get_bloginfo('name'));
        $this->output_og_tag('og:description', get_bloginfo('description'));
        $this->output_og_tag('og:url', home_url('/'));
        $this->output_og_tag('og:site_name', get_bloginfo('name'));
        
        // Site logo
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo = wp_get_attachment_image_url($custom_logo_id, 'full');
            $this->output_og_tag('og:image', $logo);
        }
    }
    
    private function output_og_tag($property, $content): void {
        if (empty($content)) return;
        
        echo '<meta property="' . esc_attr($property) . '" content="' . esc_attr($content) . '">' . "\n";
    }
    
    private function get_og_type($post_type): string {
        $types = [
            'movie' => 'video.movie',
            'tv' => 'video.tv_show',
            'drama' => 'video.tv_show',
            'people' => 'profile'
        ];
        
        return $types[$post_type] ?? 'article';
    }
    
    private function get_og_title($post_id, $post_type, $data): string {
        $title = get_the_title($post_id);
        
        switch ($post_type) {
            case 'movie':
                if (!empty($data['release_date'])) {
                    $year = date('Y', strtotime($data['release_date']));
                    $title .= " ({$year})";
                }
                break;
                
            case 'tv':
            case 'drama':
                if (!empty($data['first_air_date'])) {
                    $year = date('Y', strtotime($data['first_air_date']));
                    $title .= " ({$year})";
                }
                break;
                
            case 'people':
                if (!empty($data['known_for_department'])) {
                    $title .= " - {$data['known_for_department']}";
                }
                break;
        }
        
        return $title;
    }
    
    private function get_og_description($post_id, $post_type, $data): string {
        $description = '';
        
        switch ($post_type) {
            case 'movie':
                $description = $data['overview'] ?: $data['tagline'];
                if (!empty($data['runtime'])) {
                    $description .= " Runtime: {$data['runtime']} minutes.";
                }
                break;
                
            case 'tv':
            case 'drama':
                $description = $data['overview'];
                if (!empty($data['number_of_seasons'])) {
                    $description .= " {$data['number_of_seasons']} seasons";
                    if (!empty($data['number_of_episodes'])) {
                        $description .= ", {$data['number_of_episodes']} episodes";
                    }
                    $description .= ".";
                }
                break;
                
            case 'people':
                $description = $data['biography'];
                if (!empty($data['known_for_department'])) {
                    $description = "Known for {$data['known_for_department']}. " . $description;
                }
                break;
        }
        
        // Fallback to excerpt
        if (empty($description)) {
            $description = get_the_excerpt($post_id);
        }
        
        // Trim to appropriate length
        return wp_trim_words($description, 30, '...');
    }
    
    private function get_og_image($post_id): ?array {
        if (has_post_thumbnail($post_id)) {
            $image_id = get_post_thumbnail_id($post_id);
            $image_meta = wp_get_attachment_metadata($image_id);
            
            return [
                'url' => get_the_post_thumbnail_url($post_id, 'large'),
                'width' => $image_meta['width'] ?? 1200,
                'height' => $image_meta['height'] ?? 630,
                'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true)
            ];
        }
        
        return null;
    }
    
    private function get_post_data($post_id, $post_type): array {
        switch ($post_type) {
            case 'movie':
                return [
                    'overview' => get_post_meta($post_id, 'tmu_movie_overview', true),
                    'tagline' => get_post_meta($post_id, 'tmu_movie_tagline', true),
                    'release_date' => get_post_meta($post_id, 'tmu_movie_release_date', true),
                    'runtime' => get_post_meta($post_id, 'tmu_movie_runtime', true),
                ];
                
            case 'tv':
            case 'drama':
                return [
                    'overview' => get_post_meta($post_id, 'tmu_tv_overview', true),
                    'first_air_date' => get_post_meta($post_id, 'tmu_tv_first_air_date', true),
                    'last_air_date' => get_post_meta($post_id, 'tmu_tv_last_air_date', true),
                    'number_of_seasons' => get_post_meta($post_id, 'tmu_tv_number_of_seasons', true),
                    'number_of_episodes' => get_post_meta($post_id, 'tmu_tv_number_of_episodes', true),
                ];
                
            case 'people':
                return [
                    'name' => get_the_title($post_id),
                    'biography' => get_post_meta($post_id, 'tmu_person_biography', true),
                    'known_for_department' => get_post_meta($post_id, 'tmu_person_known_for_department', true),
                ];
                
            default:
                return [];
        }
    }
    
    private function get_director_name($post_id): ?string {
        // Get director from crew data
        $crew = get_post_meta($post_id, 'tmu_movie_crew', true);
        if ($crew) {
            $crew_data = json_decode($crew, true);
            foreach ($crew_data as $member) {
                if ($member['job'] === 'Director') {
                    return $member['name'];
                }
            }
        }
        
        return null;
    }
    
    private function get_cast_members($post_id, $limit = 5): array {
        $cast = get_post_meta($post_id, 'tmu_movie_cast', true);
        if ($cast) {
            $cast_data = json_decode($cast, true);
            return array_slice($cast_data, 0, $limit);
        }
        
        return [];
    }
    
    private function get_creators($post_id): array {
        $creators = get_post_meta($post_id, 'tmu_tv_creators', true);
        if ($creators) {
            return json_decode($creators, true);
        }
        
        return [];
    }
    
    private function get_first_name($full_name): string {
        $parts = explode(' ', $full_name);
        return $parts[0] ?? '';
    }
    
    private function get_last_name($full_name): string {
        $parts = explode(' ', $full_name);
        return end($parts) ?? '';
    }
    
    private function get_taxonomy_title($term): string {
        $taxonomy = get_taxonomy($term->taxonomy);
        return $term->name . ' - ' . $taxonomy->labels->name;
    }
    
    private function get_taxonomy_description($term): string {
        if ($term->description) {
            return $term->description;
        }
        
        $taxonomy = get_taxonomy($term->taxonomy);
        return "Browse {$taxonomy->labels->name} for {$term->name}";
    }
    
    private function get_taxonomy_image($term): ?string {
        // Check for term meta image
        $image = get_term_meta($term->term_id, 'tmu_term_image', true);
        if ($image) {
            return wp_get_attachment_url($image);
        }
        
        return null;
    }
    
    private function get_archive_description($post_type_obj): string {
        return "Browse our collection of {$post_type_obj->labels->name}";
    }
}