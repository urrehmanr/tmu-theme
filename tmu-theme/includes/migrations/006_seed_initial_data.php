<?php
/**
 * Seed Initial Data Migration
 *
 * @package TMU\Database\Migrations
 * @version 1.0.0
 */

namespace TMU\Database\Migrations;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Seed Initial Data
 */
class SeedInitialData {
    
    /**
     * Run the migration
     */
    public function up(): void {
        global $wpdb;
        
        // Seed default SEO options
        $this->seedSEOOptions();
        
        // Seed default taxonomies
        $this->seedTaxonomies();
        
        // Seed default settings
        $this->seedDefaultSettings();
        
        // Log migration
        error_log('TMU Migration: Initial data seeded successfully');
    }
    
    /**
     * Seed default SEO options
     */
    private function seedSEOOptions(): void {
        global $wpdb;
        
        $seo_options = [
            // Movie SEO options
            [
                'name' => 'movie_title_template',
                'title' => '%movie_title% (%release_year%) - %site_name%',
                'description' => 'Watch %movie_title% (%release_year%) online. %movie_overview%',
                'keywords' => '%movie_title%, %release_year%, movie, watch online, streaming',
                'robots' => 'index, follow',
                'post_type' => 'movie',
                'section' => 'title'
            ],
            [
                'name' => 'movie_meta_description',
                'title' => '',
                'description' => '%movie_title% (%release_year%) - %movie_tagline%. Runtime: %runtime% minutes. Rating: %rating%/10. %movie_overview%',
                'keywords' => '',
                'robots' => '',
                'post_type' => 'movie',
                'section' => 'meta'
            ],
            
            // TV Series SEO options
            [
                'name' => 'tv_series_title_template',
                'title' => '%tv_series_title% (%release_year%) - %site_name%',
                'description' => 'Watch %tv_series_title% (%release_year%) online. %tv_series_overview%',
                'keywords' => '%tv_series_title%, %release_year%, tv series, watch online, streaming',
                'robots' => 'index, follow',
                'post_type' => 'tv_series',
                'section' => 'title'
            ],
            [
                'name' => 'tv_series_meta_description',
                'title' => '',
                'description' => '%tv_series_title% (%release_year%) - %tv_series_tagline%. %seasons_count% seasons. Rating: %rating%/10. %tv_series_overview%',
                'keywords' => '',
                'robots' => '',
                'post_type' => 'tv_series',
                'section' => 'meta'
            ],
            
            // Drama SEO options
            [
                'name' => 'drama_title_template',
                'title' => '%drama_title% (%release_year%) - %site_name%',
                'description' => 'Watch %drama_title% (%release_year%) online. %drama_overview%',
                'keywords' => '%drama_title%, %release_year%, drama, watch online, streaming',
                'robots' => 'index, follow',
                'post_type' => 'drama',
                'section' => 'title'
            ],
            [
                'name' => 'drama_meta_description',
                'title' => '',
                'description' => '%drama_title% (%release_year%) - %drama_tagline%. Rating: %rating%/10. %drama_overview%',
                'keywords' => '',
                'robots' => '',
                'post_type' => 'drama',
                'section' => 'meta'
            ],
            
            // People SEO options
            [
                'name' => 'person_title_template',
                'title' => '%person_name% - %site_name%',
                'description' => '%person_name% - %profession%. Biography, filmography, and more.',
                'keywords' => '%person_name%, %profession%, biography, filmography, actor, actress',
                'robots' => 'index, follow',
                'post_type' => 'person',
                'section' => 'title'
            ],
            [
                'name' => 'person_meta_description',
                'title' => '',
                'description' => '%person_name% - %profession%. Born: %birth_date%. %person_biography%',
                'keywords' => '',
                'robots' => '',
                'post_type' => 'person',
                'section' => 'meta'
            ],
            
            // Archive pages SEO options
            [
                'name' => 'archive_movies_title',
                'title' => 'Movies - %site_name%',
                'description' => 'Browse and watch the latest movies online. Stream your favorite films in HD quality.',
                'keywords' => 'movies, watch online, streaming, cinema, films',
                'robots' => 'index, follow',
                'post_type' => 'archive',
                'section' => 'movies'
            ],
            [
                'name' => 'archive_tv_series_title',
                'title' => 'TV Series - %site_name%',
                'description' => 'Watch the latest TV series and shows online. Stream your favorite TV series in HD quality.',
                'keywords' => 'tv series, tv shows, watch online, streaming, television',
                'robots' => 'index, follow',
                'post_type' => 'archive',
                'section' => 'tv_series'
            ],
            [
                'name' => 'archive_dramas_title',
                'title' => 'Dramas - %site_name%',
                'description' => 'Watch the latest dramas online. Stream your favorite dramatic series in HD quality.',
                'keywords' => 'dramas, watch online, streaming, dramatic series',
                'robots' => 'index, follow',
                'post_type' => 'archive',
                'section' => 'dramas'
            ],
            [
                'name' => 'archive_people_title',
                'title' => 'People - %site_name%',
                'description' => 'Browse actors, actresses, directors, and other entertainment industry professionals.',
                'keywords' => 'actors, actresses, directors, celebrities, entertainment industry',
                'robots' => 'index, follow',
                'post_type' => 'archive',
                'section' => 'people'
            ]
        ];
        
        foreach ($seo_options as $option) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->prefix}tmu_seo_options WHERE name = %s",
                $option['name']
            ));
            
            if (!$existing) {
                $wpdb->insert(
                    "{$wpdb->prefix}tmu_seo_options",
                    $option,
                    ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
                );
            }
        }
    }
    
    /**
     * Seed default taxonomies
     */
    private function seedTaxonomies(): void {
        // Register default taxonomy terms
        $this->seedGenres();
        $this->seedCountries();
        $this->seedLanguages();
        $this->seedProductionCompanies();
    }
    
    /**
     * Seed default genres
     */
    private function seedGenres(): void {
        $genres = [
            'Action', 'Adventure', 'Animation', 'Comedy', 'Crime', 'Documentary',
            'Drama', 'Family', 'Fantasy', 'History', 'Horror', 'Music',
            'Mystery', 'Romance', 'Science Fiction', 'Thriller', 'War', 'Western',
            'Biography', 'Sport', 'Musical', 'Film-Noir', 'Short'
        ];
        
        foreach ($genres as $genre) {
            if (!term_exists($genre, 'genre')) {
                wp_insert_term($genre, 'genre');
            }
        }
    }
    
    /**
     * Seed default countries
     */
    private function seedCountries(): void {
        $countries = [
            'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany',
            'France', 'Italy', 'Spain', 'Japan', 'South Korea', 'China',
            'India', 'Brazil', 'Mexico', 'Russia', 'Sweden', 'Denmark',
            'Norway', 'Netherlands', 'Belgium', 'Switzerland', 'Austria',
            'Ireland', 'New Zealand', 'Argentina', 'Colombia', 'Chile',
            'Turkey', 'Greece', 'Poland', 'Czech Republic', 'Hungary',
            'Romania', 'Bulgaria', 'Croatia', 'Serbia', 'Slovenia',
            'Slovakia', 'Estonia', 'Latvia', 'Lithuania', 'Finland',
            'Iceland', 'Portugal', 'Israel', 'South Africa', 'Egypt',
            'Morocco', 'Nigeria', 'Kenya', 'Ghana', 'Tunisia', 'Algeria',
            'Thailand', 'Vietnam', 'Philippines', 'Malaysia', 'Singapore',
            'Indonesia', 'Hong Kong', 'Taiwan', 'Mongolia', 'Kazakhstan',
            'Uzbekistan', 'Afghanistan', 'Pakistan', 'Bangladesh', 'Sri Lanka',
            'Myanmar', 'Cambodia', 'Laos', 'Nepal', 'Bhutan', 'Maldives'
        ];
        
        foreach ($countries as $country) {
            if (!term_exists($country, 'country')) {
                wp_insert_term($country, 'country');
            }
        }
    }
    
    /**
     * Seed default languages
     */
    private function seedLanguages(): void {
        $languages = [
            'English', 'Spanish', 'French', 'German', 'Italian', 'Portuguese',
            'Russian', 'Chinese', 'Japanese', 'Korean', 'Arabic', 'Hindi',
            'Bengali', 'Urdu', 'Tamil', 'Telugu', 'Marathi', 'Gujarati',
            'Punjabi', 'Malayalam', 'Kannada', 'Odia', 'Assamese', 'Nepali',
            'Sinhala', 'Burmese', 'Thai', 'Vietnamese', 'Indonesian', 'Malay',
            'Tagalog', 'Dutch', 'Swedish', 'Danish', 'Norwegian', 'Finnish',
            'Polish', 'Czech', 'Hungarian', 'Romanian', 'Bulgarian', 'Croatian',
            'Serbian', 'Bosnian', 'Slovenian', 'Slovak', 'Estonian', 'Latvian',
            'Lithuanian', 'Albanian', 'Macedonian', 'Montenegrin', 'Maltese',
            'Irish', 'Welsh', 'Scottish Gaelic', 'Catalan', 'Basque', 'Galician',
            'Turkish', 'Greek', 'Hebrew', 'Persian', 'Kurdish', 'Armenian',
            'Georgian', 'Azerbaijani', 'Kazakh', 'Kyrgyz', 'Tajik', 'Turkmen',
            'Uzbek', 'Mongolian', 'Tibetan', 'Swahili', 'Amharic', 'Hausa',
            'Yoruba', 'Igbo', 'Zulu', 'Afrikaans', 'Somali', 'Oromo'
        ];
        
        foreach ($languages as $language) {
            if (!term_exists($language, 'language')) {
                wp_insert_term($language, 'language');
            }
        }
    }
    
    /**
     * Seed default production companies
     */
    private function seedProductionCompanies(): void {
        $companies = [
            'Warner Bros. Pictures', 'Universal Pictures', 'Sony Pictures',
            'Paramount Pictures', 'Walt Disney Pictures', '20th Century Studios',
            'Columbia Pictures', 'Metro-Goldwyn-Mayer', 'Lionsgate', 'New Line Cinema',
            'Marvel Studios', 'Lucasfilm', 'Pixar', 'DreamWorks Pictures',
            'Focus Features', 'Searchlight Pictures', 'A24', 'Neon',
            'Netflix', 'Amazon Studios', 'Apple Original Films', 'HBO Max',
            'Hulu', 'Disney+', 'Paramount+', 'Peacock', 'Discovery+',
            'BBC Films', 'Working Title Films', 'Legendary Entertainment',
            'Blumhouse Productions', 'Annapurna Pictures', 'Plan B Entertainment',
            'Bad Robot Productions', 'Amblin Entertainment', 'Skydance Media',
            'Village Roadshow Pictures', 'Relativity Media', 'Screen Gems',
            'TriStar Pictures', 'Castle Rock Entertainment', 'Miramax',
            'The Weinstein Company', 'FilmDistrict', 'Open Road Films',
            'Roadside Attractions', 'IFC Films', 'Magnolia Pictures',
            'Oscilloscope Pictures', 'Strand Releasing', 'Kino Lorber'
        ];
        
        foreach ($companies as $company) {
            if (!term_exists($company, 'production_company')) {
                wp_insert_term($company, 'production_company');
            }
        }
    }
    
    /**
     * Seed default settings
     */
    private function seedDefaultSettings(): void {
        $default_settings = [
            // Theme settings
            'tmu_theme_version' => '1.0.0',
            'tmu_database_version' => '1.0.0',
            'tmu_migration_completed' => true,
            'tmu_setup_completed' => false,
            
            // TMDB API settings
            'tmu_tmdb_api_key' => '',
            'tmu_tmdb_api_enabled' => false,
            'tmu_tmdb_auto_sync' => false,
            'tmu_tmdb_sync_interval' => 'daily',
            'tmu_tmdb_image_size' => 'w500',
            'tmu_tmdb_backdrop_size' => 'w1280',
            'tmu_tmdb_poster_size' => 'w500',
            'tmu_tmdb_profile_size' => 'w185',
            'tmu_tmdb_still_size' => 'w300',
            
            // Display settings
            'tmu_posts_per_page' => 12,
            'tmu_enable_ratings' => true,
            'tmu_enable_reviews' => true,
            'tmu_enable_watchlists' => true,
            'tmu_enable_favorites' => true,
            'tmu_enable_social_sharing' => true,
            'tmu_enable_related_content' => true,
            'tmu_related_content_count' => 6,
            
            // SEO settings
            'tmu_enable_seo' => true,
            'tmu_seo_title_separator' => ' - ',
            'tmu_seo_meta_description_length' => 160,
            'tmu_seo_meta_keywords_enabled' => true,
            'tmu_seo_og_enabled' => true,
            'tmu_seo_twitter_enabled' => true,
            'tmu_seo_json_ld_enabled' => true,
            
            // Performance settings
            'tmu_enable_caching' => true,
            'tmu_cache_timeout' => 3600,
            'tmu_enable_lazy_loading' => true,
            'tmu_enable_image_optimization' => true,
            'tmu_enable_cdn' => false,
            'tmu_cdn_url' => '',
            
            // Archive settings
            'tmu_archive_layout' => 'grid',
            'tmu_archive_columns' => 3,
            'tmu_archive_show_filters' => true,
            'tmu_archive_show_sorting' => true,
            'tmu_archive_show_search' => true,
            'tmu_archive_pagination_type' => 'numbers',
            
            // Single post settings
            'tmu_single_show_cast' => true,
            'tmu_single_show_crew' => true,
            'tmu_single_show_episodes' => true,
            'tmu_single_show_seasons' => true,
            'tmu_single_show_videos' => true,
            'tmu_single_show_images' => true,
            'tmu_single_show_reviews' => true,
            'tmu_single_show_related' => true,
            
            // Email settings
            'tmu_email_notifications' => false,
            'tmu_admin_email' => get_option('admin_email'),
            'tmu_email_from_name' => get_option('blogname'),
            'tmu_email_from_address' => get_option('admin_email'),
            
            // Security settings
            'tmu_enable_rate_limiting' => true,
            'tmu_rate_limit_requests' => 100,
            'tmu_rate_limit_window' => 3600,
            'tmu_enable_captcha' => false,
            'tmu_captcha_provider' => 'recaptcha',
            'tmu_captcha_site_key' => '',
            'tmu_captcha_secret_key' => '',
            
            // Advanced settings
            'tmu_enable_debug' => false,
            'tmu_debug_log_level' => 'error',
            'tmu_enable_analytics' => false,
            'tmu_analytics_provider' => 'google',
            'tmu_analytics_tracking_id' => '',
            'tmu_enable_maintenance_mode' => false,
            'tmu_maintenance_message' => 'Site is under maintenance. Please check back later.'
        ];
        
        foreach ($default_settings as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
    
    /**
     * Rollback the migration
     */
    public function down(): void {
        global $wpdb;
        
        // Remove seeded SEO options
        $wpdb->query("DELETE FROM {$wpdb->prefix}tmu_seo_options WHERE name LIKE '%template%' OR name LIKE '%meta%' OR name LIKE '%archive%'");
        
        // Remove seeded settings
        $settings_to_remove = [
            'tmu_theme_version', 'tmu_database_version', 'tmu_migration_completed',
            'tmu_setup_completed', 'tmu_tmdb_api_key', 'tmu_tmdb_api_enabled',
            'tmu_posts_per_page', 'tmu_enable_ratings', 'tmu_enable_reviews',
            'tmu_enable_seo', 'tmu_enable_caching', 'tmu_archive_layout',
            'tmu_single_show_cast', 'tmu_email_notifications', 'tmu_enable_rate_limiting',
            'tmu_enable_debug', 'tmu_enable_analytics', 'tmu_enable_maintenance_mode'
        ];
        
        foreach ($settings_to_remove as $setting) {
            delete_option($setting);
        }
        
        // Note: We don't remove taxonomy terms as they might be in use
        // by existing content
        
        error_log('TMU Migration: Initial data removed');
    }
}