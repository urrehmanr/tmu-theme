<?php
namespace TMU\SEO\Schema;

/**
 * Episode Schema Generator
 * 
 * Generates Schema.org markup for TV episodes and drama episodes
 */
class EpisodeSchema {
    
    /**
     * Generate episode schema
     * 
     * @param int $post_id Episode post ID
     * @return array Schema markup array
     */
    public function generate($post_id): array {
        $episode_data = function_exists('tmu_get_episode_data') ? tmu_get_episode_data($post_id) : [];
        $post = get_post($post_id);
        
        if (!$post) return [];
        
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
        
        // Add genres from parent series
        if ($series_id) {
            $schema['genre'] = $this->get_genres($series_id);
        }
        
        return $schema;
    }
    
    /**
     * Format runtime to ISO 8601 duration
     */
    private function format_duration($runtime): ?string {
        if (!$runtime) return null;
        
        $hours = floor($runtime / 60);
        $minutes = $runtime % 60;
        
        return "PT{$hours}H{$minutes}M";
    }
    
    /**
     * Get episode images
     */
    private function get_images($post_id): array {
        $images = [];
        
        // Featured image (episode still)
        if (has_post_thumbnail($post_id)) {
            $images[] = get_the_post_thumbnail_url($post_id, 'large');
        }
        
        // Fallback to series poster if no episode still
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
}