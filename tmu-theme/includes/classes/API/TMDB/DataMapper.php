<?php
/**
 * TMDB Data Mapper
 * 
 * Transforms TMDB API data to WordPress database structure
 * 
 * @package TMU\API\TMDB
 * @since 1.0.0
 */

namespace TMU\API\TMDB;

use TMU\Fields\Storage\CustomTableStorage;

/**
 * DataMapper class
 * 
 * Maps TMDB API data to WordPress posts and custom database tables
 */
class DataMapper {
    
    /**
     * Custom table storage
     * @var CustomTableStorage
     */
    private $storage;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->storage = new CustomTableStorage();
    }
    
    /**
     * Map movie data from TMDB to WordPress
     * 
     * @param int $post_id WordPress post ID
     * @param array $tmdb_data TMDB API data
     * @param array $options Mapping options
     * @return bool Success status
     */
    public function mapMovieData(int $post_id, array $tmdb_data, array $options = []): bool {
        global $wpdb;
        
        try {
            // Update post content if empty or requested
            if (empty(get_post_field('post_content', $post_id)) || !empty($options['update_content'])) {
                if (!empty($tmdb_data['overview'])) {
                    wp_update_post([
                        'ID' => $post_id,
                        'post_content' => wp_kses_post($tmdb_data['overview'])
                    ]);
                }
            }
            
            // Map data to custom table fields
            $mapped_data = [
                'tmdb_id' => $tmdb_data['id'] ?? 0,
                'imdb_id' => $tmdb_data['imdb_id'] ?? '',
                'title' => $tmdb_data['title'] ?? '',
                'original_title' => $tmdb_data['original_title'] ?? '',
                'overview' => $tmdb_data['overview'] ?? '',
                'tagline' => $tmdb_data['tagline'] ?? '',
                'runtime' => $tmdb_data['runtime'] ?? 0,
                'release_date' => $tmdb_data['release_date'] ?? null,
                'status' => $tmdb_data['status'] ?? '',
                'vote_average' => $tmdb_data['vote_average'] ?? 0,
                'vote_count' => $tmdb_data['vote_count'] ?? 0,
                'popularity' => $tmdb_data['popularity'] ?? 0,
                'budget' => $tmdb_data['budget'] ?? 0,
                'revenue' => $tmdb_data['revenue'] ?? 0,
                'adult' => !empty($tmdb_data['adult']) ? 1 : 0,
                'backdrop_path' => $tmdb_data['backdrop_path'] ?? '',
                'poster_path' => $tmdb_data['poster_path'] ?? '',
                'homepage' => $tmdb_data['homepage'] ?? '',
                'original_language' => $tmdb_data['original_language'] ?? '',
                'production_companies' => json_encode($tmdb_data['production_companies'] ?? []),
                'production_countries' => json_encode($tmdb_data['production_countries'] ?? []),
                'spoken_languages' => json_encode($tmdb_data['spoken_languages'] ?? []),
                'genres' => json_encode($tmdb_data['genres'] ?? []),
                'keywords' => json_encode($tmdb_data['keywords']['keywords'] ?? [])
            ];
            
            // Handle credits if available
            if (!empty($tmdb_data['credits'])) {
                $mapped_data['credits'] = json_encode([
                    'cast' => $tmdb_data['credits']['cast'] ?? [],
                    'crew' => $tmdb_data['credits']['crew'] ?? []
                ]);
            }
            
            // Store data using CustomTableStorage
            foreach ($mapped_data as $field => $value) {
                $this->storage->save($post_id, $field, $value);
            }
            
            // Update taxonomies
            $this->updateMovieTaxonomies($post_id, $tmdb_data);
            
            return true;
            
        } catch (\Exception $e) {
            error_log("DataMapper Error for movie {$post_id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Map TV show data from TMDB to WordPress
     * 
     * @param int $post_id WordPress post ID
     * @param array $tmdb_data TMDB API data
     * @param array $options Mapping options
     * @return bool Success status
     */
    public function mapTvData(int $post_id, array $tmdb_data, array $options = []): bool {
        try {
            // Update post content if empty or requested
            if (empty(get_post_field('post_content', $post_id)) || !empty($options['update_content'])) {
                if (!empty($tmdb_data['overview'])) {
                    wp_update_post([
                        'ID' => $post_id,
                        'post_content' => wp_kses_post($tmdb_data['overview'])
                    ]);
                }
            }
            
            // Map data to custom table fields
            $mapped_data = [
                'tmdb_id' => $tmdb_data['id'] ?? 0,
                'imdb_id' => $tmdb_data['external_ids']['imdb_id'] ?? '',
                'title' => $tmdb_data['name'] ?? '',
                'original_title' => $tmdb_data['original_name'] ?? '',
                'overview' => $tmdb_data['overview'] ?? '',
                'tagline' => $tmdb_data['tagline'] ?? '',
                'first_air_date' => $tmdb_data['first_air_date'] ?? null,
                'last_air_date' => $tmdb_data['last_air_date'] ?? null,
                'status' => $tmdb_data['status'] ?? '',
                'vote_average' => $tmdb_data['vote_average'] ?? 0,
                'vote_count' => $tmdb_data['vote_count'] ?? 0,
                'popularity' => $tmdb_data['popularity'] ?? 0,
                'number_of_episodes' => $tmdb_data['number_of_episodes'] ?? 0,
                'number_of_seasons' => $tmdb_data['number_of_seasons'] ?? 0,
                'type' => $tmdb_data['type'] ?? '',
                'backdrop_path' => $tmdb_data['backdrop_path'] ?? '',
                'poster_path' => $tmdb_data['poster_path'] ?? '',
                'homepage' => $tmdb_data['homepage'] ?? '',
                'original_language' => $tmdb_data['original_language'] ?? '',
                'origin_country' => json_encode($tmdb_data['origin_country'] ?? []),
                'production_companies' => json_encode($tmdb_data['production_companies'] ?? []),
                'production_countries' => json_encode($tmdb_data['production_countries'] ?? []),
                'spoken_languages' => json_encode($tmdb_data['spoken_languages'] ?? []),
                'genres' => json_encode($tmdb_data['genres'] ?? []),
                'networks' => json_encode($tmdb_data['networks'] ?? []),
                'created_by' => json_encode($tmdb_data['created_by'] ?? []),
                'seasons' => json_encode($tmdb_data['seasons'] ?? [])
            ];
            
            // Handle credits if available
            if (!empty($tmdb_data['credits'])) {
                $mapped_data['credits'] = json_encode([
                    'cast' => $tmdb_data['credits']['cast'] ?? [],
                    'crew' => $tmdb_data['credits']['crew'] ?? []
                ]);
            }
            
            // Store data using CustomTableStorage
            foreach ($mapped_data as $field => $value) {
                $this->storage->save($post_id, $field, $value);
            }
            
            // Update taxonomies
            $this->updateTvTaxonomies($post_id, $tmdb_data);
            
            return true;
            
        } catch (\Exception $e) {
            error_log("DataMapper Error for TV show {$post_id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Map person data from TMDB to WordPress
     * 
     * @param int $post_id WordPress post ID
     * @param array $tmdb_data TMDB API data
     * @param array $options Mapping options
     * @return bool Success status
     */
    public function mapPersonData(int $post_id, array $tmdb_data, array $options = []): bool {
        try {
            // Update post content if empty or requested
            if (empty(get_post_field('post_content', $post_id)) || !empty($options['update_content'])) {
                if (!empty($tmdb_data['biography'])) {
                    wp_update_post([
                        'ID' => $post_id,
                        'post_content' => wp_kses_post($tmdb_data['biography'])
                    ]);
                }
            }
            
            // Map data to custom table fields
            $mapped_data = [
                'tmdb_id' => $tmdb_data['id'] ?? 0,
                'imdb_id' => $tmdb_data['external_ids']['imdb_id'] ?? '',
                'name' => $tmdb_data['name'] ?? '',
                'biography' => $tmdb_data['biography'] ?? '',
                'birthday' => $tmdb_data['birthday'] ?? null,
                'deathday' => $tmdb_data['deathday'] ?? null,
                'place_of_birth' => $tmdb_data['place_of_birth'] ?? '',
                'known_for_department' => $tmdb_data['known_for_department'] ?? '',
                'gender' => $tmdb_data['gender'] ?? 0,
                'popularity' => $tmdb_data['popularity'] ?? 0,
                'adult' => !empty($tmdb_data['adult']) ? 1 : 0,
                'profile_path' => $tmdb_data['profile_path'] ?? '',
                'homepage' => $tmdb_data['homepage'] ?? '',
                'also_known_as' => json_encode($tmdb_data['also_known_as'] ?? [])
            ];
            
            // Handle movie credits if available
            if (!empty($tmdb_data['movie_credits'])) {
                $mapped_data['movie_credits'] = json_encode([
                    'cast' => $tmdb_data['movie_credits']['cast'] ?? [],
                    'crew' => $tmdb_data['movie_credits']['crew'] ?? []
                ]);
            }
            
            // Handle TV credits if available
            if (!empty($tmdb_data['tv_credits'])) {
                $mapped_data['tv_credits'] = json_encode([
                    'cast' => $tmdb_data['tv_credits']['cast'] ?? [],
                    'crew' => $tmdb_data['tv_credits']['crew'] ?? []
                ]);
            }
            
            // Store data using CustomTableStorage
            foreach ($mapped_data as $field => $value) {
                $this->storage->save($post_id, $field, $value);
            }
            
            return true;
            
        } catch (\Exception $e) {
            error_log("DataMapper Error for person {$post_id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update movie taxonomies
     * 
     * @param int $post_id Post ID
     * @param array $tmdb_data TMDB data
     */
    private function updateMovieTaxonomies(int $post_id, array $tmdb_data): void {
        // Update genres
        if (!empty($tmdb_data['genres'])) {
            $this->updateTaxonomyTerms($post_id, 'genre', $tmdb_data['genres']);
        }
        
        // Update countries
        if (!empty($tmdb_data['production_countries'])) {
            $this->updateTaxonomyTerms($post_id, 'country', $tmdb_data['production_countries']);
        }
        
        // Update languages
        if (!empty($tmdb_data['spoken_languages'])) {
            $this->updateTaxonomyTerms($post_id, 'language', $tmdb_data['spoken_languages']);
        }
        
        // Update release year
        if (!empty($tmdb_data['release_date'])) {
            $year = date('Y', strtotime($tmdb_data['release_date']));
            $this->updateYearTaxonomy($post_id, $year);
        }
    }
    
    /**
     * Update TV show taxonomies
     * 
     * @param int $post_id Post ID
     * @param array $tmdb_data TMDB data
     */
    private function updateTvTaxonomies(int $post_id, array $tmdb_data): void {
        // Update genres
        if (!empty($tmdb_data['genres'])) {
            $this->updateTaxonomyTerms($post_id, 'genre', $tmdb_data['genres']);
        }
        
        // Update countries
        if (!empty($tmdb_data['production_countries'])) {
            $this->updateTaxonomyTerms($post_id, 'country', $tmdb_data['production_countries']);
        }
        
        // Update languages
        if (!empty($tmdb_data['spoken_languages'])) {
            $this->updateTaxonomyTerms($post_id, 'language', $tmdb_data['spoken_languages']);
        }
        
        // Update networks
        if (!empty($tmdb_data['networks'])) {
            $this->updateTaxonomyTerms($post_id, 'network', $tmdb_data['networks']);
        }
        
        // Update first air year
        if (!empty($tmdb_data['first_air_date'])) {
            $year = date('Y', strtotime($tmdb_data['first_air_date']));
            $this->updateYearTaxonomy($post_id, $year);
        }
    }
    
    /**
     * Update taxonomy terms for a post
     * 
     * @param int $post_id Post ID
     * @param string $taxonomy Taxonomy name
     * @param array $terms_data TMDB terms data
     */
    private function updateTaxonomyTerms(int $post_id, string $taxonomy, array $terms_data): void {
        $term_ids = [];
        
        foreach ($terms_data as $term_data) {
            $term_name = $term_data['name'] ?? '';
            $tmdb_id = $term_data['id'] ?? 0;
            
            if (empty($term_name)) {
                continue;
            }
            
            // Check if term exists
            $term = get_term_by('name', $term_name, $taxonomy);
            
            if (!$term) {
                // Create new term
                $result = wp_insert_term($term_name, $taxonomy, [
                    'slug' => sanitize_title($term_name)
                ]);
                
                if (!is_wp_error($result)) {
                    $term_id = $result['term_id'];
                    
                    // Store TMDB ID as term meta
                    if ($tmdb_id) {
                        update_term_meta($term_id, 'tmdb_id', $tmdb_id);
                    }
                    
                    // Store additional data based on taxonomy
                    $this->storeTermMetadata($term_id, $taxonomy, $term_data);
                    
                    $term_ids[] = $term_id;
                }
            } else {
                $term_ids[] = $term->term_id;
                
                // Update TMDB ID if missing
                if ($tmdb_id && !get_term_meta($term->term_id, 'tmdb_id', true)) {
                    update_term_meta($term->term_id, 'tmdb_id', $tmdb_id);
                }
            }
        }
        
        if (!empty($term_ids)) {
            wp_set_object_terms($post_id, $term_ids, $taxonomy);
        }
    }
    
    /**
     * Update year taxonomy
     * 
     * @param int $post_id Post ID
     * @param string $year Year
     */
    private function updateYearTaxonomy(int $post_id, string $year): void {
        $term = get_term_by('name', $year, 'by-year');
        
        if (!$term) {
            $result = wp_insert_term($year, 'by-year');
            if (!is_wp_error($result)) {
                $term_id = $result['term_id'];
            }
        } else {
            $term_id = $term->term_id;
        }
        
        if (isset($term_id)) {
            wp_set_object_terms($post_id, [$term_id], 'by-year');
        }
    }
    
    /**
     * Store taxonomy-specific metadata
     * 
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @param array $term_data TMDB term data
     */
    private function storeTermMetadata(int $term_id, string $taxonomy, array $term_data): void {
        switch ($taxonomy) {
            case 'country':
                if (!empty($term_data['iso_3166_1'])) {
                    update_term_meta($term_id, 'iso_3166_1', $term_data['iso_3166_1']);
                }
                break;
                
            case 'language':
                if (!empty($term_data['iso_639_1'])) {
                    update_term_meta($term_id, 'iso_639_1', $term_data['iso_639_1']);
                }
                break;
                
            case 'network':
                if (!empty($term_data['logo_path'])) {
                    update_term_meta($term_id, 'logo_path', $term_data['logo_path']);
                }
                if (!empty($term_data['origin_country'])) {
                    update_term_meta($term_id, 'origin_country', $term_data['origin_country']);
                }
                break;
        }
    }
    
    /**
     * Get mapped data for display
     * 
     * @param int $post_id Post ID
     * @param string $post_type Post type
     * @return array Mapped data
     */
    public function getMappedData(int $post_id, string $post_type): array {
        $data = [];
        
        switch ($post_type) {
            case 'movie':
                $data = $this->getMovieData($post_id);
                break;
            case 'tv':
            case 'drama':
                $data = $this->getTvData($post_id);
                break;
            case 'people':
                $data = $this->getPersonData($post_id);
                break;
        }
        
        return $data;
    }
    
    /**
     * Get movie data
     * 
     * @param int $post_id Post ID
     * @return array Movie data
     */
    private function getMovieData(int $post_id): array {
        $fields = [
            'tmdb_id', 'imdb_id', 'title', 'original_title', 'overview', 'tagline',
            'runtime', 'release_date', 'status', 'vote_average', 'vote_count',
            'popularity', 'budget', 'revenue', 'adult', 'backdrop_path', 'poster_path',
            'homepage', 'original_language', 'production_companies', 'production_countries',
            'spoken_languages', 'genres', 'keywords', 'credits'
        ];
        
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = $this->storage->get($post_id, $field);
        }
        
        // Decode JSON fields
        $json_fields = ['production_companies', 'production_countries', 'spoken_languages', 'genres', 'keywords', 'credits'];
        foreach ($json_fields as $field) {
            if (!empty($data[$field])) {
                $data[$field] = json_decode($data[$field], true);
            }
        }
        
        return $data;
    }
    
    /**
     * Get TV show data
     * 
     * @param int $post_id Post ID
     * @return array TV show data
     */
    private function getTvData(int $post_id): array {
        $fields = [
            'tmdb_id', 'imdb_id', 'title', 'original_title', 'overview', 'tagline',
            'first_air_date', 'last_air_date', 'status', 'vote_average', 'vote_count',
            'popularity', 'number_of_episodes', 'number_of_seasons', 'type',
            'backdrop_path', 'poster_path', 'homepage', 'original_language',
            'origin_country', 'production_companies', 'production_countries',
            'spoken_languages', 'genres', 'networks', 'created_by', 'seasons', 'credits'
        ];
        
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = $this->storage->get($post_id, $field);
        }
        
        // Decode JSON fields
        $json_fields = ['origin_country', 'production_companies', 'production_countries', 'spoken_languages', 'genres', 'networks', 'created_by', 'seasons', 'credits'];
        foreach ($json_fields as $field) {
            if (!empty($data[$field])) {
                $data[$field] = json_decode($data[$field], true);
            }
        }
        
        return $data;
    }
    
    /**
     * Get person data
     * 
     * @param int $post_id Post ID
     * @return array Person data
     */
    private function getPersonData(int $post_id): array {
        $fields = [
            'tmdb_id', 'imdb_id', 'name', 'biography', 'birthday', 'deathday',
            'place_of_birth', 'known_for_department', 'gender', 'popularity',
            'adult', 'profile_path', 'homepage', 'also_known_as', 'movie_credits', 'tv_credits'
        ];
        
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = $this->storage->get($post_id, $field);
        }
        
        // Decode JSON fields
        $json_fields = ['also_known_as', 'movie_credits', 'tv_credits'];
        foreach ($json_fields as $field) {
            if (!empty($data[$field])) {
                $data[$field] = json_decode($data[$field], true);
            }
        }
        
        return $data;
    }
}