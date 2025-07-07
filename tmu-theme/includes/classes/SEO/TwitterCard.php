<?php
namespace TMU\SEO;

class TwitterCard {
    public function init(): void {
        add_action('wp_head', [$this, 'output_twitter_tags'], 6);
    }
    
    public function output_twitter_tags(): void {
        if (is_singular(['movie', 'tv', 'drama', 'people'])) {
            $this->output_post_twitter_tags();
        } elseif (is_tax(['genre', 'country', 'language', 'by-year', 'network', 'channel'])) {
            $this->output_taxonomy_twitter_tags();
        } elseif (is_archive()) {
            $this->output_archive_twitter_tags();
        } elseif (is_home() || is_front_page()) {
            $this->output_homepage_twitter_tags();
        }
    }
    
    private function output_post_twitter_tags(): void {
        global $post;
        
        $post_type = get_post_type();
        $data = $this->get_post_data($post->ID, $post_type);
        
        // Card type
        $card_type = $this->get_card_type($post_type);
        $this->output_twitter_tag('twitter:card', $card_type);
        
        // Basic Twitter tags
        $this->output_twitter_tag('twitter:title', $this->get_twitter_title($post->ID, $post_type, $data));
        $this->output_twitter_tag('twitter:description', $this->get_twitter_description($post->ID, $post_type, $data));
        $this->output_twitter_tag('twitter:url', get_permalink($post->ID));
        
        // Image
        $image = $this->get_twitter_image($post->ID);
        if ($image) {
            $this->output_twitter_tag('twitter:image', $image['url']);
            $this->output_twitter_tag('twitter:image:alt', $image['alt']);
        }
        
        // Site attribution
        $twitter_site = get_option('tmu_twitter_site');
        if ($twitter_site) {
            $this->output_twitter_tag('twitter:site', $twitter_site);
        }
        
        // Creator attribution
        $twitter_creator = get_option('tmu_twitter_creator');
        if ($twitter_creator) {
            $this->output_twitter_tag('twitter:creator', $twitter_creator);
        }
        
        // Post type specific tags
        $this->output_post_type_twitter_tags($post->ID, $post_type, $data);
    }
    
    private function output_post_type_twitter_tags($post_id, $post_type, $data): void {
        switch ($post_type) {
            case 'movie':
                // Movie specific Twitter tags
                $this->output_twitter_tag('twitter:label1', 'Release Date');
                $this->output_twitter_tag('twitter:data1', $this->format_date($data['release_date']));
                
                $this->output_twitter_tag('twitter:label2', 'Runtime');
                $this->output_twitter_tag('twitter:data2', $this->format_runtime($data['runtime']));
                
                // Rating
                $rating = get_post_meta($post_id, 'tmu_movie_vote_average', true);
                if ($rating) {
                    $this->output_twitter_tag('twitter:label3', 'Rating');
                    $this->output_twitter_tag('twitter:data3', $rating . '/10');
                }
                
                break;
                
            case 'tv':
            case 'drama':
                // TV show specific Twitter tags
                $this->output_twitter_tag('twitter:label1', 'First Air Date');
                $this->output_twitter_tag('twitter:data1', $this->format_date($data['first_air_date']));
                
                $this->output_twitter_tag('twitter:label2', 'Seasons');
                $this->output_twitter_tag('twitter:data2', $data['number_of_seasons'] ?: 'Unknown');
                
                // Network
                $networks = get_the_terms($post_id, 'network');
                if ($networks && !is_wp_error($networks)) {
                    $this->output_twitter_tag('twitter:label3', 'Network');
                    $this->output_twitter_tag('twitter:data3', $networks[0]->name);
                }
                
                break;
                
            case 'people':
                // Person specific Twitter tags
                $this->output_twitter_tag('twitter:label1', 'Known For');
                $this->output_twitter_tag('twitter:data1', $data['known_for_department'] ?: 'Acting');
                
                $birthday = get_post_meta($post_id, 'tmu_person_birthday', true);
                if ($birthday) {
                    $this->output_twitter_tag('twitter:label2', 'Birthday');
                    $this->output_twitter_tag('twitter:data2', $this->format_date($birthday));
                }
                
                break;
        }
    }
    
    private function output_taxonomy_twitter_tags(): void {
        $term = get_queried_object();
        
        $this->output_twitter_tag('twitter:card', 'summary');
        $this->output_twitter_tag('twitter:title', $this->get_taxonomy_title($term));
        $this->output_twitter_tag('twitter:description', $this->get_taxonomy_description($term));
        $this->output_twitter_tag('twitter:url', get_term_link($term));
        
        // Site attribution
        $twitter_site = get_option('tmu_twitter_site');
        if ($twitter_site) {
            $this->output_twitter_tag('twitter:site', $twitter_site);
        }
        
        // Image for taxonomy
        $image = $this->get_taxonomy_image($term);
        if ($image) {
            $this->output_twitter_tag('twitter:image', $image);
        }
    }
    
    private function output_archive_twitter_tags(): void {
        $post_type_obj = get_queried_object();
        
        $this->output_twitter_tag('twitter:card', 'summary');
        $this->output_twitter_tag('twitter:title', $post_type_obj->labels->name);
        $this->output_twitter_tag('twitter:description', $this->get_archive_description($post_type_obj));
        $this->output_twitter_tag('twitter:url', get_post_type_archive_link($post_type_obj->name));
        
        // Site attribution
        $twitter_site = get_option('tmu_twitter_site');
        if ($twitter_site) {
            $this->output_twitter_tag('twitter:site', $twitter_site);
        }
    }
    
    private function output_homepage_twitter_tags(): void {
        $this->output_twitter_tag('twitter:card', 'summary');
        $this->output_twitter_tag('twitter:title', get_bloginfo('name'));
        $this->output_twitter_tag('twitter:description', get_bloginfo('description'));
        $this->output_twitter_tag('twitter:url', home_url('/'));
        
        // Site attribution
        $twitter_site = get_option('tmu_twitter_site');
        if ($twitter_site) {
            $this->output_twitter_tag('twitter:site', $twitter_site);
        }
        
        // Site logo
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo = wp_get_attachment_image_url($custom_logo_id, 'full');
            $this->output_twitter_tag('twitter:image', $logo);
        }
    }
    
    private function output_twitter_tag($name, $content): void {
        if (empty($content)) return;
        
        echo '<meta name="' . esc_attr($name) . '" content="' . esc_attr($content) . '">' . "\n";
    }
    
    private function get_card_type($post_type): string {
        // Use summary_large_image for visual content
        if (in_array($post_type, ['movie', 'tv', 'drama', 'people'])) {
            return 'summary_large_image';
        }
        
        return 'summary';
    }
    
    private function get_twitter_title($post_id, $post_type, $data): string {
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
    
    private function get_twitter_description($post_id, $post_type, $data): string {
        $description = '';
        
        switch ($post_type) {
            case 'movie':
                $description = $data['overview'] ?: $data['tagline'];
                break;
                
            case 'tv':
            case 'drama':
                $description = $data['overview'];
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
        
        // Trim to Twitter's preferred length (200 characters)
        return wp_trim_words($description, 25, '...');
    }
    
    private function get_twitter_image($post_id): ?array {
        if (has_post_thumbnail($post_id)) {
            $image_id = get_post_thumbnail_id($post_id);
            
            return [
                'url' => get_the_post_thumbnail_url($post_id, 'large'),
                'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true) ?: get_the_title($post_id)
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
                    'number_of_seasons' => get_post_meta($post_id, 'tmu_tv_number_of_seasons', true),
                    'number_of_episodes' => get_post_meta($post_id, 'tmu_tv_number_of_episodes', true),
                ];
                
            case 'people':
                return [
                    'biography' => get_post_meta($post_id, 'tmu_person_biography', true),
                    'known_for_department' => get_post_meta($post_id, 'tmu_person_known_for_department', true),
                ];
                
            default:
                return [];
        }
    }
    
    private function format_date($date): string {
        if (!$date) return 'Unknown';
        
        return date('M j, Y', strtotime($date));
    }
    
    private function format_runtime($runtime): string {
        if (!$runtime) return 'Unknown';
        
        $hours = floor($runtime / 60);
        $minutes = $runtime % 60;
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } else {
            return "{$minutes}m";
        }
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