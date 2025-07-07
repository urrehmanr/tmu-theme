<?php
namespace TMU\SEO\Schema;

/**
 * Season Schema Generator
 * 
 * Generates Schema.org markup for TV seasons
 */
class SeasonSchema {
    
    /**
     * Generate season schema
     * 
     * @param int $post_id Season post ID
     * @return array Schema markup array
     */
    public function generate($post_id): array {
        $season_data = function_exists('tmu_get_season_data') ? tmu_get_season_data($post_id) : [];
        $post = get_post($post_id);
        
        if (!$post) return [];
        
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
            
            // Add genres from parent series
            $schema['genre'] = $this->get_genres($series_id);
        }
        
        // Add episodes
        $episodes = $this->get_episodes($post_id);
        if ($episodes) {
            $schema['episode'] = $episodes;
        }
        
        return $schema;
    }
    
    /**
     * Get season images
     */
    private function get_images($post_id): array {
        $images = [];
        
        // Featured image (season poster)
        if (has_post_thumbnail($post_id)) {
            $images[] = get_the_post_thumbnail_url($post_id, 'large');
        }
        
        // Fallback to series poster if no season poster
        if (empty($images)) {
            $series_id = get_post_meta($post_id, 'tv_series_id', true) ?: get_post_meta($post_id, 'drama_id', true);
            if ($series_id && has_post_thumbnail($series_id)) {
                $images[] = get_the_post_thumbnail_url($series_id, 'large');
            }
        }
        
        return $images;
    }
    
    /**
     * Get genres from parent series
     */
    private function get_genres($series_id): array {
        $genres = get_the_terms($series_id, 'genre');
        if (!$genres || is_wp_error($genres)) return [];
        
        return array_map(function($genre) {
            return $genre->name;
        }, $genres);
    }
    
    /**
     * Get episodes in this season
     */
    private function get_episodes($post_id): array {
        $episodes = get_posts([
            'post_type' => ['episode', 'drama-episode'],
            'meta_key' => 'season_id',
            'meta_value' => $post_id,
            'posts_per_page' => -1,
            'orderby' => 'meta_value_num',
            'meta_key' => 'episode_number',
            'order' => 'ASC'
        ]);
        
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
        
        return $episode_schemas;
    }
}