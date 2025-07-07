<?php
namespace TMU\SEO\Schema;

class PersonSchema {
    public function generate($post_id): array {
        $person_data = $this->get_person_data($post_id);
        
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
            'gender' => $this->format_gender($person_data['gender']),
            'knowsAbout' => $this->get_known_for($post_id)
        ];
        
        // Add death date if available
        if (!empty($person_data['deathday'])) {
            $schema['deathDate'] = $person_data['deathday'];
        }
        
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
        
        // Add awards if available
        $awards = $this->get_awards($post_id);
        if ($awards) {
            $schema['award'] = $awards;
        }
        
        // Add external links
        $external_links = $this->get_external_links($post_id, $person_data);
        if ($external_links) {
            $schema['sameAs'] = array_merge($schema['sameAs'] ?? [], $external_links);
        }
        
        return $schema;
    }
    
    private function get_person_data($post_id): array {
        return [
            'biography' => get_post_meta($post_id, 'tmu_person_biography', true),
            'known_for_department' => get_post_meta($post_id, 'tmu_person_known_for_department', true),
            'birthday' => get_post_meta($post_id, 'tmu_person_birthday', true),
            'deathday' => get_post_meta($post_id, 'tmu_person_deathday', true),
            'place_of_birth' => get_post_meta($post_id, 'tmu_person_place_of_birth', true),
            'gender' => get_post_meta($post_id, 'tmu_person_gender', true),
            'homepage' => get_post_meta($post_id, 'tmu_person_homepage', true),
            'imdb_id' => get_post_meta($post_id, 'tmu_person_imdb_id', true),
            'tmdb_id' => get_post_meta($post_id, 'tmu_person_tmdb_id', true),
            'instagram_id' => get_post_meta($post_id, 'tmu_person_instagram_id', true),
            'twitter_id' => get_post_meta($post_id, 'tmu_person_twitter_id', true),
            'facebook_id' => get_post_meta($post_id, 'tmu_person_facebook_id', true),
        ];
    }
    
    private function get_nationality($post_id): ?string {
        // Extract nationality from place of birth
        $place_of_birth = get_post_meta($post_id, 'tmu_person_place_of_birth', true);
        if (!$place_of_birth) return null;
        
        // Simple extraction - get last part after comma
        $parts = explode(',', $place_of_birth);
        return trim(end($parts));
    }
    
    private function format_gender($gender): ?string {
        if (!$gender) return null;
        
        $gender_map = [
            1 => 'Female',
            2 => 'Male',
            3 => 'Non-binary'
        ];
        
        return $gender_map[$gender] ?? null;
    }
    
    private function get_known_for($post_id): array {
        $known_for_department = get_post_meta($post_id, 'tmu_person_known_for_department', true);
        if (!$known_for_department) return [];
        
        $known_for = [$known_for_department];
        
        // Add additional skills/departments
        $additional_departments = get_post_meta($post_id, 'tmu_person_departments', true);
        if ($additional_departments) {
            $departments = json_decode($additional_departments, true);
            if (is_array($departments)) {
                $known_for = array_merge($known_for, $departments);
            }
        }
        
        return array_unique($known_for);
    }
    
    private function get_social_profiles($person_data): array {
        $profiles = [];
        
        if (!empty($person_data['homepage'])) {
            $profiles[] = $person_data['homepage'];
        }
        
        if (!empty($person_data['imdb_id'])) {
            $profiles[] = 'https://www.imdb.com/name/' . $person_data['imdb_id'];
        }
        
        if (!empty($person_data['instagram_id'])) {
            $profiles[] = 'https://www.instagram.com/' . $person_data['instagram_id'];
        }
        
        if (!empty($person_data['twitter_id'])) {
            $profiles[] = 'https://twitter.com/' . $person_data['twitter_id'];
        }
        
        if (!empty($person_data['facebook_id'])) {
            $profiles[] = 'https://www.facebook.com/' . $person_data['facebook_id'];
        }
        
        return $profiles;
    }
    
    private function get_filmography($post_id): array {
        global $wpdb;
        
        $filmography = [];
        
        // Get movies this person appeared in
        $movies = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT p.ID, p.post_title, p.post_type
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type IN ('movie', 'tv', 'drama')
            AND p.post_status = 'publish'
            AND (
                (pm.meta_key = 'tmu_movie_cast' AND pm.meta_value LIKE %s)
                OR (pm.meta_key = 'tmu_movie_crew' AND pm.meta_value LIKE %s)
                OR (pm.meta_key = 'tmu_tv_cast' AND pm.meta_value LIKE %s)
                OR (pm.meta_key = 'tmu_tv_crew' AND pm.meta_value LIKE %s)
            )
            ORDER BY p.post_date DESC
            LIMIT 20
        ", 
            '%"id":' . get_post_meta($post_id, 'tmu_person_tmdb_id', true) . '%',
            '%"id":' . get_post_meta($post_id, 'tmu_person_tmdb_id', true) . '%',
            '%"id":' . get_post_meta($post_id, 'tmu_person_tmdb_id', true) . '%',
            '%"id":' . get_post_meta($post_id, 'tmu_person_tmdb_id', true) . '%'
        ));
        
        foreach ($movies as $movie) {
            $type = in_array($movie->post_type, ['tv', 'drama']) ? 'TVSeries' : 'Movie';
            
            $filmography[] = [
                '@type' => $type,
                'name' => $movie->post_title,
                'url' => get_permalink($movie->ID)
            ];
        }
        
        return $filmography;
    }
    
    private function get_awards($post_id): array {
        $awards = get_post_meta($post_id, 'tmu_person_awards', true);
        if (!$awards) return [];
        
        $awards_data = json_decode($awards, true);
        if (!is_array($awards_data)) return [];
        
        return array_map(function($award) {
            return [
                '@type' => 'Award',
                'name' => $award['name'],
                'description' => $award['description'] ?? null,
                'dateAwarded' => $award['date'] ?? null
            ];
        }, $awards_data);
    }
    
    private function get_external_links($post_id, $person_data): array {
        $links = [];
        
        if (!empty($person_data['tmdb_id'])) {
            $links[] = 'https://www.themoviedb.org/person/' . $person_data['tmdb_id'];
        }
        
        return $links;
    }
}