<?php
/**
 * Meta Box Factory
 *
 * @package TMU\Fields
 * @version 1.0.0
 */

namespace TMU\Fields;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Meta Box Factory Class
 * 
 * Creates preconfigured meta boxes for different post types
 */
class MetaBoxFactory {
    
    /**
     * Field Manager instance
     *
     * @var FieldManager
     */
    private $field_manager;
    
    /**
     * Constructor
     *
     * @param FieldManager $field_manager Field Manager instance
     */
    public function __construct(FieldManager $field_manager) {
        $this->field_manager = $field_manager;
        $this->registerMetaBoxes();
    }
    
    /**
     * Register all meta boxes
     */
    public function registerMetaBoxes(): void {
        $this->registerMovieMetaBoxes();
        $this->registerTVShowMetaBoxes();
        $this->registerDramaMetaBoxes();
        $this->registerPeopleMetaBoxes();
        $this->registerVideoMetaBoxes();
    }
    
    /**
     * Register movie meta boxes
     */
    private function registerMovieMetaBoxes(): void {
        // TMDB Integration
        $this->field_manager->registerGroup('movie_tmdb', [
            'title' => __('TMDB Integration', 'tmu'),
            'post_types' => ['movie'],
            'context' => 'side',
            'priority' => 'high',
            'fields' => [
                [
                    'id' => 'tmdb_id',
                    'type' => 'tmdb_sync',
                    'title' => __('TMDB ID', 'tmu'),
                    'description' => __('The Movie Database ID for syncing movie data.', 'tmu'),
                    'tmdb_type' => 'movie',
                    'auto_sync' => false,
                    'validation' => ['numeric' => true],
                ],
            ],
        ]);
        
        // Movie Details
        $this->field_manager->registerGroup('movie_details', [
            'title' => __('Movie Details', 'tmu'),
            'post_types' => ['movie'],
            'context' => 'normal',
            'priority' => 'high',
            'fields' => [
                [
                    'id' => 'release_date',
                    'type' => 'text',
                    'title' => __('Release Date', 'tmu'),
                    'description' => __('Movie release date (YYYY-MM-DD format).', 'tmu'),
                    'input_type' => 'date',
                    'validation' => ['regex' => '/^\d{4}-\d{2}-\d{2}$/'],
                ],
                [
                    'id' => 'runtime',
                    'type' => 'number',
                    'title' => __('Runtime (minutes)', 'tmu'),
                    'description' => __('Movie duration in minutes.', 'tmu'),
                    'min' => 1,
                    'max' => 999,
                ],
                [
                    'id' => 'budget',
                    'type' => 'number',
                    'title' => __('Budget', 'tmu'),
                    'description' => __('Movie production budget in USD.', 'tmu'),
                    'min' => 0,
                    'step' => 1000,
                ],
                [
                    'id' => 'revenue',
                    'type' => 'number',
                    'title' => __('Revenue', 'tmu'),
                    'description' => __('Movie box office revenue in USD.', 'tmu'),
                    'min' => 0,
                    'step' => 1000,
                ],
                [
                    'id' => 'status',
                    'type' => 'select',
                    'title' => __('Status', 'tmu'),
                    'options' => [
                        'rumored' => __('Rumored', 'tmu'),
                        'planned' => __('Planned', 'tmu'),
                        'in_production' => __('In Production', 'tmu'),
                        'post_production' => __('Post Production', 'tmu'),
                        'released' => __('Released', 'tmu'),
                        'canceled' => __('Canceled', 'tmu'),
                    ],
                    'default' => 'released',
                ],
            ],
        ]);
        
        // Images and Media
        $this->field_manager->registerGroup('movie_media', [
            'title' => __('Images & Media', 'tmu'),
            'post_types' => ['movie'],
            'context' => 'normal',
            'priority' => 'default',
            'fields' => [
                [
                    'id' => 'poster_url',
                    'type' => 'image',
                    'title' => __('Movie Poster', 'tmu'),
                    'description' => __('Upload or enter URL for movie poster.', 'tmu'),
                    'preview_size' => 'tmu-poster-medium',
                    'allow_url' => true,
                ],
                [
                    'id' => 'backdrop_url',
                    'type' => 'image',
                    'title' => __('Backdrop Image', 'tmu'),
                    'description' => __('Upload or enter URL for backdrop image.', 'tmu'),
                    'preview_size' => 'tmu-backdrop-medium',
                    'allow_url' => true,
                ],
                [
                    'id' => 'trailer_url',
                    'type' => 'text',
                    'title' => __('Trailer URL', 'tmu'),
                    'description' => __('YouTube, Vimeo, or direct video URL.', 'tmu'),
                    'input_type' => 'url',
                    'validation' => ['url' => true],
                ],
            ],
        ]);
        
        // Ratings and Reviews
        $this->field_manager->registerGroup('movie_ratings', [
            'title' => __('Ratings & Reviews', 'tmu'),
            'post_types' => ['movie'],
            'context' => 'side',
            'priority' => 'default',
            'fields' => [
                [
                    'id' => 'average_rating',
                    'type' => 'number',
                    'title' => __('Average Rating', 'tmu'),
                    'description' => __('Average rating (0-10 scale).', 'tmu'),
                    'min' => 0,
                    'max' => 10,
                    'step' => 0.1,
                ],
                [
                    'id' => 'vote_count',
                    'type' => 'number',
                    'title' => __('Vote Count', 'tmu'),
                    'description' => __('Number of votes/reviews.', 'tmu'),
                    'min' => 0,
                ],
                [
                    'id' => 'imdb_id',
                    'type' => 'text',
                    'title' => __('IMDb ID', 'tmu'),
                    'description' => __('IMDb identifier (e.g., tt1234567).', 'tmu'),
                    'validation' => ['regex' => '/^tt\d+$/'],
                ],
            ],
        ]);
    }
    
    /**
     * Register TV show meta boxes
     */
    private function registerTVShowMetaBoxes(): void {
        // TMDB Integration
        $this->field_manager->registerGroup('tv_tmdb', [
            'title' => __('TMDB Integration', 'tmu'),
            'post_types' => ['tv'],
            'context' => 'side',
            'priority' => 'high',
            'fields' => [
                [
                    'id' => 'tmdb_id',
                    'type' => 'tmdb_sync',
                    'title' => __('TMDB ID', 'tmu'),
                    'description' => __('The Movie Database ID for syncing TV show data.', 'tmu'),
                    'tmdb_type' => 'tv',
                    'auto_sync' => false,
                    'validation' => ['numeric' => true],
                ],
            ],
        ]);
        
        // TV Show Details
        $this->field_manager->registerGroup('tv_details', [
            'title' => __('TV Show Details', 'tmu'),
            'post_types' => ['tv'],
            'context' => 'normal',
            'priority' => 'high',
            'fields' => [
                [
                    'id' => 'first_air_date',
                    'type' => 'text',
                    'title' => __('First Air Date', 'tmu'),
                    'input_type' => 'date',
                    'validation' => ['regex' => '/^\d{4}-\d{2}-\d{2}$/'],
                ],
                [
                    'id' => 'last_air_date',
                    'type' => 'text',
                    'title' => __('Last Air Date', 'tmu'),
                    'input_type' => 'date',
                    'validation' => ['regex' => '/^\d{4}-\d{2}-\d{2}$/'],
                ],
                [
                    'id' => 'number_of_seasons',
                    'type' => 'number',
                    'title' => __('Number of Seasons', 'tmu'),
                    'min' => 1,
                    'max' => 100,
                ],
                [
                    'id' => 'number_of_episodes',
                    'type' => 'number',
                    'title' => __('Total Episodes', 'tmu'),
                    'min' => 1,
                    'max' => 9999,
                ],
                [
                    'id' => 'episode_runtime',
                    'type' => 'number',
                    'title' => __('Episode Runtime (minutes)', 'tmu'),
                    'min' => 1,
                    'max' => 999,
                ],
                [
                    'id' => 'status',
                    'type' => 'select',
                    'title' => __('Status', 'tmu'),
                    'options' => [
                        'returning_series' => __('Returning Series', 'tmu'),
                        'planned' => __('Planned', 'tmu'),
                        'in_production' => __('In Production', 'tmu'),
                        'ended' => __('Ended', 'tmu'),
                        'canceled' => __('Canceled', 'tmu'),
                        'pilot' => __('Pilot', 'tmu'),
                    ],
                ],
            ],
        ]);
    }
    
    /**
     * Register drama meta boxes
     */
    private function registerDramaMetaBoxes(): void {
        // Drama Details
        $this->field_manager->registerGroup('drama_details', [
            'title' => __('Drama Details', 'tmu'),
            'post_types' => ['drama'],
            'context' => 'normal',
            'priority' => 'high',
            'fields' => [
                [
                    'id' => 'first_air_date',
                    'type' => 'text',
                    'title' => __('First Air Date', 'tmu'),
                    'input_type' => 'date',
                ],
                [
                    'id' => 'last_air_date',
                    'type' => 'text',
                    'title' => __('Last Air Date', 'tmu'),
                    'input_type' => 'date',
                ],
                [
                    'id' => 'number_of_episodes',
                    'type' => 'number',
                    'title' => __('Total Episodes', 'tmu'),
                    'min' => 1,
                ],
                [
                    'id' => 'episode_runtime',
                    'type' => 'number',
                    'title' => __('Episode Runtime (minutes)', 'tmu'),
                    'min' => 1,
                ],
                [
                    'id' => 'network',
                    'type' => 'text',
                    'title' => __('Network/Platform', 'tmu'),
                ],
            ],
        ]);
    }
    
    /**
     * Register people meta boxes
     */
    private function registerPeopleMetaBoxes(): void {
        // TMDB Integration
        $this->field_manager->registerGroup('people_tmdb', [
            'title' => __('TMDB Integration', 'tmu'),
            'post_types' => ['people'],
            'context' => 'side',
            'priority' => 'high',
            'fields' => [
                [
                    'id' => 'tmdb_id',
                    'type' => 'tmdb_sync',
                    'title' => __('TMDB ID', 'tmu'),
                    'description' => __('The Movie Database ID for syncing person data.', 'tmu'),
                    'tmdb_type' => 'person',
                    'auto_sync' => false,
                    'validation' => ['numeric' => true],
                ],
            ],
        ]);
        
        // Personal Details
        $this->field_manager->registerGroup('people_details', [
            'title' => __('Personal Details', 'tmu'),
            'post_types' => ['people'],
            'context' => 'normal',
            'priority' => 'high',
            'fields' => [
                [
                    'id' => 'date_of_birth',
                    'type' => 'text',
                    'title' => __('Date of Birth', 'tmu'),
                    'input_type' => 'date',
                    'validation' => ['regex' => '/^\d{4}-\d{2}-\d{2}$/'],
                ],
                [
                    'id' => 'date_of_death',
                    'type' => 'text',
                    'title' => __('Date of Death', 'tmu'),
                    'input_type' => 'date',
                    'validation' => ['regex' => '/^\d{4}-\d{2}-\d{2}$/'],
                ],
                [
                    'id' => 'place_of_birth',
                    'type' => 'text',
                    'title' => __('Place of Birth', 'tmu'),
                ],
                [
                    'id' => 'gender',
                    'type' => 'select',
                    'title' => __('Gender', 'tmu'),
                    'options' => [
                        '0' => __('Not specified', 'tmu'),
                        '1' => __('Female', 'tmu'),
                        '2' => __('Male', 'tmu'),
                        '3' => __('Non-binary', 'tmu'),
                    ],
                    'default' => '0',
                ],
                [
                    'id' => 'known_for_department',
                    'type' => 'select',
                    'title' => __('Known For', 'tmu'),
                    'options' => [
                        'Acting' => __('Acting', 'tmu'),
                        'Directing' => __('Directing', 'tmu'),
                        'Writing' => __('Writing', 'tmu'),
                        'Production' => __('Production', 'tmu'),
                        'Sound' => __('Sound', 'tmu'),
                        'Art' => __('Art', 'tmu'),
                        'Camera' => __('Camera', 'tmu'),
                        'Editing' => __('Editing', 'tmu'),
                        'Crew' => __('Crew', 'tmu'),
                    ],
                    'default' => 'Acting',
                ],
            ],
        ]);
        
        // Additional Info
        $this->field_manager->registerGroup('people_additional', [
            'title' => __('Additional Information', 'tmu'),
            'post_types' => ['people'],
            'context' => 'normal',
            'priority' => 'default',
            'fields' => [
                [
                    'id' => 'profile_path',
                    'type' => 'image',
                    'title' => __('Profile Photo', 'tmu'),
                    'description' => __('Upload or enter URL for profile photo.', 'tmu'),
                    'allow_url' => true,
                ],
                [
                    'id' => 'biography',
                    'type' => 'textarea',
                    'title' => __('Biography', 'tmu'),
                    'description' => __('Brief biography of the person.', 'tmu'),
                ],
                [
                    'id' => 'imdb_id',
                    'type' => 'text',
                    'title' => __('IMDb ID', 'tmu'),
                    'description' => __('IMDb identifier (e.g., nm1234567).', 'tmu'),
                    'validation' => ['regex' => '/^nm\d+$/'],
                ],
            ],
        ]);
    }
    
    /**
     * Register video meta boxes
     */
    private function registerVideoMetaBoxes(): void {
        // Video Details
        $this->field_manager->registerGroup('video_details', [
            'title' => __('Video Details', 'tmu'),
            'post_types' => ['video'],
            'context' => 'normal',
            'priority' => 'high',
            'fields' => [
                [
                    'id' => 'video_url',
                    'type' => 'text',
                    'title' => __('Video URL', 'tmu'),
                    'description' => __('YouTube, Vimeo, or direct video URL.', 'tmu'),
                    'input_type' => 'url',
                    'required' => true,
                    'validation' => ['url' => true],
                ],
                [
                    'id' => 'video_type',
                    'type' => 'select',
                    'title' => __('Video Type', 'tmu'),
                    'options' => [
                        'trailer' => __('Trailer', 'tmu'),
                        'teaser' => __('Teaser', 'tmu'),
                        'clip' => __('Clip', 'tmu'),
                        'featurette' => __('Featurette', 'tmu'),
                        'behind_scenes' => __('Behind the Scenes', 'tmu'),
                        'bloopers' => __('Bloopers', 'tmu'),
                        'interview' => __('Interview', 'tmu'),
                        'review' => __('Review', 'tmu'),
                    ],
                    'default' => 'trailer',
                ],
                [
                    'id' => 'duration',
                    'type' => 'number',
                    'title' => __('Duration (seconds)', 'tmu'),
                    'min' => 1,
                ],
                [
                    'id' => 'video_quality',
                    'type' => 'select',
                    'title' => __('Quality', 'tmu'),
                    'options' => [
                        '480p' => __('480p', 'tmu'),
                        '720p' => __('720p (HD)', 'tmu'),
                        '1080p' => __('1080p (Full HD)', 'tmu'),
                        '1440p' => __('1440p (2K)', 'tmu'),
                        '2160p' => __('2160p (4K)', 'tmu'),
                    ],
                    'default' => '1080p',
                ],
            ],
        ]);
    }
}