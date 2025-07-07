<?php
namespace TMU\SEO\Schema;

class MovieSchema {
    public function generate($post_id): array {
        $movie_data = $this->get_movie_data($post_id);
        
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
            'contentRating' => $movie_data['certification'],
            'inLanguage' => $this->get_languages($post_id),
            'countryOfOrigin' => $this->get_countries($post_id)
        ];
        
        // Add rating if available
        if (!empty($movie_data['vote_average'])) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $movie_data['vote_average'],
                'ratingCount' => $movie_data['vote_count'] ?: 1,
                'bestRating' => 10,
                'worstRating' => 1
            ];
        }
        
        // Add production companies
        $production_companies = $this->get_production_companies($movie_data);
        if ($production_companies) {
            $schema['productionCompany'] = $production_companies;
        }
        
        // Add cast and crew
        $director = $this->get_director($post_id);
        if ($director) {
            $schema['director'] = $director;
        }
        
        $actors = $this->get_actors($post_id);
        if ($actors) {
            $schema['actor'] = $actors;
        }
        
        // Add trailer if available
        $trailer = $this->get_trailer($post_id);
        if ($trailer) {
            $schema['trailer'] = $trailer;
        }
        
        // Add external links
        $external_links = $this->get_external_links($post_id, $movie_data);
        if ($external_links) {
            $schema['sameAs'] = $external_links;
        }
        
        return $schema;
    }
    
    private function get_movie_data($post_id): array {
        return [
            'overview' => get_post_meta($post_id, 'tmu_movie_overview', true),
            'tagline' => get_post_meta($post_id, 'tmu_movie_tagline', true),
            'release_date' => get_post_meta($post_id, 'tmu_movie_release_date', true),
            'runtime' => get_post_meta($post_id, 'tmu_movie_runtime', true),
            'vote_average' => get_post_meta($post_id, 'tmu_movie_vote_average', true),
            'vote_count' => get_post_meta($post_id, 'tmu_movie_vote_count', true),
            'certification' => get_post_meta($post_id, 'tmu_movie_certification', true),
            'production_companies' => get_post_meta($post_id, 'tmu_movie_production_companies', true),
            'imdb_id' => get_post_meta($post_id, 'tmu_movie_imdb_id', true),
            'tmdb_id' => get_post_meta($post_id, 'tmu_movie_tmdb_id', true),
        ];
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
    
    private function get_languages($post_id): array {
        $languages = get_the_terms($post_id, 'language');
        if (!$languages || is_wp_error($languages)) return [];
        
        return array_map(function($language) {
            return $language->name;
        }, $languages);
    }
    
    private function get_countries($post_id): array {
        $countries = get_the_terms($post_id, 'country');
        if (!$countries || is_wp_error($countries)) return [];
        
        return array_map(function($country) {
            return [
                '@type' => 'Country',
                'name' => $country->name
            ];
        }, $countries);
    }
    
    private function get_production_companies($movie_data): array {
        if (empty($movie_data['production_companies'])) return [];
        
        $companies = json_decode($movie_data['production_companies'], true);
        if (!is_array($companies)) return [];
        
        return array_map(function($company) {
            return [
                '@type' => 'Organization',
                'name' => $company['name'],
                'logo' => $company['logo_path'] ?? null
            ];
        }, $companies);
    }
    
    private function get_director($post_id): ?array {
        $crew = get_post_meta($post_id, 'tmu_movie_crew', true);
        if (!$crew) return null;
        
        $crew_data = json_decode($crew, true);
        if (!is_array($crew_data)) return null;
        
        foreach ($crew_data as $member) {
            if ($member['job'] === 'Director') {
                return [
                    '@type' => 'Person',
                    'name' => $member['name'],
                    'url' => $this->get_person_url($member)
                ];
            }
        }
        
        return null;
    }
    
    private function get_actors($post_id): array {
        $cast = get_post_meta($post_id, 'tmu_movie_cast', true);
        if (!$cast) return [];
        
        $cast_data = json_decode($cast, true);
        if (!is_array($cast_data)) return [];
        
        $actors = [];
        foreach (array_slice($cast_data, 0, 10) as $member) {
            $actors[] = [
                '@type' => 'Person',
                'name' => $member['name'],
                'url' => $this->get_person_url($member)
            ];
        }
        
        return $actors;
    }
    
    private function get_trailer($post_id): ?array {
        $trailer_url = get_post_meta($post_id, 'tmu_movie_trailer_url', true);
        if (!$trailer_url) return null;
        
        return [
            '@type' => 'VideoObject',
            'name' => get_the_title($post_id) . ' - Trailer',
            'url' => $trailer_url,
            'embedUrl' => $trailer_url,
            'thumbnailUrl' => get_the_post_thumbnail_url($post_id, 'large')
        ];
    }
    
    private function get_external_links($post_id, $movie_data): array {
        $links = [];
        
        if (!empty($movie_data['imdb_id'])) {
            $links[] = 'https://www.imdb.com/title/' . $movie_data['imdb_id'];
        }
        
        if (!empty($movie_data['tmdb_id'])) {
            $links[] = 'https://www.themoviedb.org/movie/' . $movie_data['tmdb_id'];
        }
        
        return $links;
    }
    
    private function get_person_url($person_data): ?string {
        if (empty($person_data['id'])) return null;
        
        // Try to find the person post
        $person_post = get_posts([
            'post_type' => 'people',
            'meta_key' => 'tmu_person_tmdb_id',
            'meta_value' => $person_data['id'],
            'posts_per_page' => 1
        ]);
        
        if ($person_post) {
            return get_permalink($person_post[0]->ID);
        }
        
        return null;
    }
}