<?php
namespace TMU\SEO\Schema;

class TVShowSchema {
    public function generate($post_id): array {
        $tv_data = $this->get_tv_data($post_id);
        
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
            'contentRating' => $tv_data['certification'],
            'inLanguage' => $this->get_languages($post_id),
            'countryOfOrigin' => $this->get_countries($post_id)
        ];
        
        // Add rating if available
        if (!empty($tv_data['vote_average'])) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $tv_data['vote_average'],
                'ratingCount' => $tv_data['vote_count'] ?: 1,
                'bestRating' => 10,
                'worstRating' => 1
            ];
        }
        
        // Add production companies
        $production_companies = $this->get_production_companies($tv_data);
        if ($production_companies) {
            $schema['productionCompany'] = $production_companies;
        }
        
        // Add creators
        $creators = $this->get_creators($post_id);
        if ($creators) {
            $schema['creator'] = $creators;
        }
        
        // Add cast
        $actors = $this->get_actors($post_id);
        if ($actors) {
            $schema['actor'] = $actors;
        }
        
        // Add network/channel information
        $networks = $this->get_networks($post_id);
        if ($networks) {
            $schema['broadcastChannel'] = $networks;
        }
        
        // Add seasons information
        $seasons = $this->get_seasons($post_id);
        if ($seasons) {
            $schema['containsSeason'] = $seasons;
        }
        
        // Add trailer if available
        $trailer = $this->get_trailer($post_id);
        if ($trailer) {
            $schema['trailer'] = $trailer;
        }
        
        // Add external links
        $external_links = $this->get_external_links($post_id, $tv_data);
        if ($external_links) {
            $schema['sameAs'] = $external_links;
        }
        
        return $schema;
    }
    
    private function get_tv_data($post_id): array {
        return [
            'overview' => get_post_meta($post_id, 'tmu_tv_overview', true),
            'tagline' => get_post_meta($post_id, 'tmu_tv_tagline', true),
            'first_air_date' => get_post_meta($post_id, 'tmu_tv_first_air_date', true),
            'last_air_date' => get_post_meta($post_id, 'tmu_tv_last_air_date', true),
            'number_of_seasons' => get_post_meta($post_id, 'tmu_tv_number_of_seasons', true),
            'number_of_episodes' => get_post_meta($post_id, 'tmu_tv_number_of_episodes', true),
            'vote_average' => get_post_meta($post_id, 'tmu_tv_vote_average', true),
            'vote_count' => get_post_meta($post_id, 'tmu_tv_vote_count', true),
            'certification' => get_post_meta($post_id, 'tmu_tv_certification', true),
            'production_companies' => get_post_meta($post_id, 'tmu_tv_production_companies', true),
            'imdb_id' => get_post_meta($post_id, 'tmu_tv_imdb_id', true),
            'tmdb_id' => get_post_meta($post_id, 'tmu_tv_tmdb_id', true),
        ];
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
    
    private function get_production_companies($tv_data): array {
        if (empty($tv_data['production_companies'])) return [];
        
        $companies = json_decode($tv_data['production_companies'], true);
        if (!is_array($companies)) return [];
        
        return array_map(function($company) {
            return [
                '@type' => 'Organization',
                'name' => $company['name'],
                'logo' => $company['logo_path'] ?? null
            ];
        }, $companies);
    }
    
    private function get_creators($post_id): array {
        $creators = get_post_meta($post_id, 'tmu_tv_creators', true);
        if (!$creators) return [];
        
        $creators_data = json_decode($creators, true);
        if (!is_array($creators_data)) return [];
        
        return array_map(function($creator) {
            return [
                '@type' => 'Person',
                'name' => $creator['name'],
                'url' => $this->get_person_url($creator)
            ];
        }, $creators_data);
    }
    
    private function get_actors($post_id): array {
        $cast = get_post_meta($post_id, 'tmu_tv_cast', true);
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
            'meta_key' => 'tmu_season_tv_series_id',
            'meta_value' => $post_id,
            'posts_per_page' => -1,
            'orderby' => 'meta_value_num',
            'meta_key' => 'tmu_season_number',
            'order' => 'ASC'
        ]);
        
        $season_schemas = [];
        foreach ($seasons as $season) {
            $season_data = [
                'season_number' => get_post_meta($season->ID, 'tmu_season_number', true),
                'episode_count' => get_post_meta($season->ID, 'tmu_season_episode_count', true),
                'air_date' => get_post_meta($season->ID, 'tmu_season_air_date', true),
            ];
            
            $season_schemas[] = [
                '@type' => 'TVSeason',
                'name' => $season->post_title,
                'seasonNumber' => $season_data['season_number'],
                'url' => get_permalink($season->ID),
                'numberOfEpisodes' => $season_data['episode_count'],
                'startDate' => $season_data['air_date']
            ];
        }
        
        return $season_schemas;
    }
    
    private function get_trailer($post_id): ?array {
        $trailer_url = get_post_meta($post_id, 'tmu_tv_trailer_url', true);
        if (!$trailer_url) return null;
        
        return [
            '@type' => 'VideoObject',
            'name' => get_the_title($post_id) . ' - Trailer',
            'url' => $trailer_url,
            'embedUrl' => $trailer_url,
            'thumbnailUrl' => get_the_post_thumbnail_url($post_id, 'large')
        ];
    }
    
    private function get_external_links($post_id, $tv_data): array {
        $links = [];
        
        if (!empty($tv_data['imdb_id'])) {
            $links[] = 'https://www.imdb.com/title/' . $tv_data['imdb_id'];
        }
        
        if (!empty($tv_data['tmdb_id'])) {
            $links[] = 'https://www.themoviedb.org/tv/' . $tv_data['tmdb_id'];
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