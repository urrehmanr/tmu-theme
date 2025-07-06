<?php
/**
 * TMU Custom Fields Configuration
 *
 * @package TMU\Config
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Custom Fields Configuration Array
 * 
 * This configuration defines all custom fields for the TMU theme
 * Fields are organized by post type and field groups
 */
return [
    'movie' => [
        'tmdb_sync' => [
            'title' => __('TMDB Synchronization', 'tmu'),
            'context' => 'side',
            'priority' => 'high',
            'fields' => [
                'tmdb_id' => [
                    'type' => 'number',
                    'label' => __('TMDB ID', 'tmu'),
                    'description' => __('The Movie Database ID for automatic data synchronization', 'tmu'),
                    'placeholder' => 'e.g., 550',
                    'attributes' => [
                        'min' => 1,
                        'step' => 1
                    ],
                    'validation' => 'required|numeric|min:1'
                ],
                'last_sync' => [
                    'type' => 'datetime',
                    'label' => __('Last Sync', 'tmu'),
                    'description' => __('Last synchronization with TMDB', 'tmu'),
                    'readonly' => true
                ],
                'sync_status' => [
                    'type' => 'select',
                    'label' => __('Sync Status', 'tmu'),
                    'options' => [
                        'pending' => __('Pending', 'tmu'),
                        'synced' => __('Synced', 'tmu'),
                        'error' => __('Error', 'tmu'),
                        'manual' => __('Manual', 'tmu')
                    ],
                    'default' => 'pending',
                    'readonly' => true
                ]
            ]
        ],
        'movie_details' => [
            'title' => __('Movie Details', 'tmu'),
            'context' => 'normal',
            'priority' => 'high',
            'fields' => [
                'original_title' => [
                    'type' => 'text',
                    'label' => __('Original Title', 'tmu'),
                    'description' => __('Original title of the movie', 'tmu'),
                    'placeholder' => 'Enter original title'
                ],
                'tagline' => [
                    'type' => 'text',
                    'label' => __('Tagline', 'tmu'),
                    'description' => __('Movie tagline or slogan', 'tmu'),
                    'placeholder' => 'Enter tagline'
                ],
                'release_date' => [
                    'type' => 'date',
                    'label' => __('Release Date', 'tmu'),
                    'description' => __('Official release date', 'tmu'),
                    'validation' => 'date'
                ],
                'runtime' => [
                    'type' => 'number',
                    'label' => __('Runtime (minutes)', 'tmu'),
                    'description' => __('Movie duration in minutes', 'tmu'),
                    'attributes' => [
                        'min' => 1,
                        'max' => 999,
                        'step' => 1
                    ],
                    'validation' => 'numeric|min:1|max:999'
                ],
                'status' => [
                    'type' => 'select',
                    'label' => __('Status', 'tmu'),
                    'options' => [
                        'rumored' => __('Rumored', 'tmu'),
                        'planned' => __('Planned', 'tmu'),
                        'in_production' => __('In Production', 'tmu'),
                        'post_production' => __('Post Production', 'tmu'),
                        'released' => __('Released', 'tmu'),
                        'canceled' => __('Canceled', 'tmu')
                    ],
                    'default' => 'released'
                ]
            ]
        ],
        'production_info' => [
            'title' => __('Production Information', 'tmu'),
            'context' => 'normal',
            'priority' => 'default',
            'fields' => [
                'budget' => [
                    'type' => 'number',
                    'label' => __('Budget ($)', 'tmu'),
                    'description' => __('Production budget in USD', 'tmu'),
                    'attributes' => [
                        'min' => 0,
                        'step' => 1000
                    ]
                ],
                'revenue' => [
                    'type' => 'number',
                    'label' => __('Revenue ($)', 'tmu'),
                    'description' => __('Box office revenue in USD', 'tmu'),
                    'attributes' => [
                        'min' => 0,
                        'step' => 1000
                    ]
                ],
                'production_companies' => [
                    'type' => 'textarea',
                    'label' => __('Production Companies', 'tmu'),
                    'description' => __('Production companies (JSON format)', 'tmu'),
                    'rows' => 3
                ],
                'production_countries' => [
                    'type' => 'textarea',
                    'label' => __('Production Countries', 'tmu'),
                    'description' => __('Production countries (JSON format)', 'tmu'),
                    'rows' => 2
                ]
            ]
        ],
        'ratings_and_stats' => [
            'title' => __('Ratings & Statistics', 'tmu'),
            'context' => 'side',
            'priority' => 'default',
            'fields' => [
                'vote_average' => [
                    'type' => 'number',
                    'label' => __('TMDB Rating', 'tmu'),
                    'description' => __('Average rating from TMDB', 'tmu'),
                    'attributes' => [
                        'min' => 0,
                        'max' => 10,
                        'step' => 0.1
                    ],
                    'readonly' => true
                ],
                'vote_count' => [
                    'type' => 'number',
                    'label' => __('Vote Count', 'tmu'),
                    'description' => __('Number of votes from TMDB', 'tmu'),
                    'readonly' => true
                ],
                'popularity' => [
                    'type' => 'number',
                    'label' => __('Popularity', 'tmu'),
                    'description' => __('Popularity score from TMDB', 'tmu'),
                    'attributes' => [
                        'step' => 0.01
                    ],
                    'readonly' => true
                ],
                'imdb_id' => [
                    'type' => 'text',
                    'label' => __('IMDb ID', 'tmu'),
                    'description' => __('IMDb identifier', 'tmu'),
                    'placeholder' => 'tt0137523'
                ]
            ]
        ]
    ],
    
    'tv-series' => [
        'tmdb_sync' => [
            'title' => __('TMDB Synchronization', 'tmu'),
            'context' => 'side',
            'priority' => 'high',
            'fields' => [
                'tmdb_id' => [
                    'type' => 'number',
                    'label' => __('TMDB ID', 'tmu'),
                    'description' => __('The Movie Database ID for automatic data synchronization', 'tmu'),
                    'placeholder' => 'e.g., 1399',
                    'attributes' => [
                        'min' => 1,
                        'step' => 1
                    ],
                    'validation' => 'required|numeric|min:1'
                ],
                'last_sync' => [
                    'type' => 'datetime',
                    'label' => __('Last Sync', 'tmu'),
                    'description' => __('Last synchronization with TMDB', 'tmu'),
                    'readonly' => true
                ]
            ]
        ],
        'series_details' => [
            'title' => __('Series Details', 'tmu'),
            'context' => 'normal',
            'priority' => 'high',
            'fields' => [
                'original_name' => [
                    'type' => 'text',
                    'label' => __('Original Name', 'tmu'),
                    'description' => __('Original name of the series', 'tmu')
                ],
                'first_air_date' => [
                    'type' => 'date',
                    'label' => __('First Air Date', 'tmu'),
                    'description' => __('Date when the series first aired', 'tmu')
                ],
                'last_air_date' => [
                    'type' => 'date',
                    'label' => __('Last Air Date', 'tmu'),
                    'description' => __('Date when the series last aired', 'tmu')
                ],
                'status' => [
                    'type' => 'select',
                    'label' => __('Status', 'tmu'),
                    'options' => [
                        'returning_series' => __('Returning Series', 'tmu'),
                        'planned' => __('Planned', 'tmu'),
                        'in_production' => __('In Production', 'tmu'),
                        'ended' => __('Ended', 'tmu'),
                        'canceled' => __('Canceled', 'tmu'),
                        'pilot' => __('Pilot', 'tmu')
                    ],
                    'default' => 'returning_series'
                ],
                'type' => [
                    'type' => 'select',
                    'label' => __('Type', 'tmu'),
                    'options' => [
                        'scripted' => __('Scripted', 'tmu'),
                        'reality' => __('Reality', 'tmu'),
                        'documentary' => __('Documentary', 'tmu'),
                        'news' => __('News', 'tmu'),
                        'talk_show' => __('Talk Show', 'tmu'),
                        'miniseries' => __('Miniseries', 'tmu')
                    ],
                    'default' => 'scripted'
                ]
            ]
        ],
        'episodes_and_seasons' => [
            'title' => __('Episodes & Seasons', 'tmu'),
            'context' => 'normal',
            'priority' => 'default',
            'fields' => [
                'number_of_seasons' => [
                    'type' => 'number',
                    'label' => __('Number of Seasons', 'tmu'),
                    'attributes' => [
                        'min' => 0,
                        'step' => 1
                    ]
                ],
                'number_of_episodes' => [
                    'type' => 'number',
                    'label' => __('Number of Episodes', 'tmu'),
                    'attributes' => [
                        'min' => 0,
                        'step' => 1
                    ]
                ],
                'episode_run_time' => [
                    'type' => 'textarea',
                    'label' => __('Episode Runtime', 'tmu'),
                    'description' => __('Episode runtime in minutes (JSON array)', 'tmu'),
                    'rows' => 2,
                    'placeholder' => '[45, 60]'
                ],
                'seasons_data' => [
                    'type' => 'textarea',
                    'label' => __('Seasons Data', 'tmu'),
                    'description' => __('Detailed seasons information (JSON format)', 'tmu'),
                    'rows' => 5
                ]
            ]
        ],
        'network_info' => [
            'title' => __('Network Information', 'tmu'),
            'context' => 'side',
            'priority' => 'default',
            'fields' => [
                'networks' => [
                    'type' => 'textarea',
                    'label' => __('Networks', 'tmu'),
                    'description' => __('Broadcasting networks (JSON format)', 'tmu'),
                    'rows' => 3
                ],
                'origin_country' => [
                    'type' => 'text',
                    'label' => __('Origin Country', 'tmu'),
                    'description' => __('Country of origin', 'tmu')
                ],
                'original_language' => [
                    'type' => 'text',
                    'label' => __('Original Language', 'tmu'),
                    'description' => __('Original language code', 'tmu'),
                    'placeholder' => 'en'
                ]
            ]
        ]
    ],
    
    'drama' => [
        'tmdb_sync' => [
            'title' => __('TMDB Synchronization', 'tmu'),
            'context' => 'side',
            'priority' => 'high',
            'fields' => [
                'tmdb_id' => [
                    'type' => 'number',
                    'label' => __('TMDB ID', 'tmu'),
                    'description' => __('The Movie Database ID for automatic data synchronization', 'tmu'),
                    'attributes' => [
                        'min' => 1,
                        'step' => 1
                    ]
                ]
            ]
        ],
        'drama_details' => [
            'title' => __('Drama Details', 'tmu'),
            'context' => 'normal',
            'priority' => 'high',
            'fields' => [
                'original_title' => [
                    'type' => 'text',
                    'label' => __('Original Title', 'tmu'),
                    'description' => __('Original title of the drama', 'tmu')
                ],
                'first_air_date' => [
                    'type' => 'date',
                    'label' => __('First Air Date', 'tmu'),
                    'description' => __('Date when the drama first aired', 'tmu')
                ],
                'last_air_date' => [
                    'type' => 'date',
                    'label' => __('Last Air Date', 'tmu'),
                    'description' => __('Date when the drama last aired', 'tmu')
                ],
                'status' => [
                    'type' => 'select',
                    'label' => __('Status', 'tmu'),
                    'options' => [
                        'airing' => __('Currently Airing', 'tmu'),
                        'completed' => __('Completed', 'tmu'),
                        'upcoming' => __('Upcoming', 'tmu'),
                        'canceled' => __('Canceled', 'tmu')
                    ],
                    'default' => 'airing'
                ]
            ]
        ],
        'broadcast_info' => [
            'title' => __('Broadcast Information', 'tmu'),
            'context' => 'normal',
            'priority' => 'default',
            'fields' => [
                'broadcast_network' => [
                    'type' => 'text',
                    'label' => __('Broadcast Network', 'tmu'),
                    'description' => __('Network that broadcasts the drama', 'tmu')
                ],
                'schedule_day' => [
                    'type' => 'select',
                    'label' => __('Schedule Day', 'tmu'),
                    'options' => [
                        'monday' => __('Monday', 'tmu'),
                        'tuesday' => __('Tuesday', 'tmu'),
                        'wednesday' => __('Wednesday', 'tmu'),
                        'thursday' => __('Thursday', 'tmu'),
                        'friday' => __('Friday', 'tmu'),
                        'saturday' => __('Saturday', 'tmu'),
                        'sunday' => __('Sunday', 'tmu')
                    ]
                ],
                'schedule_time' => [
                    'type' => 'time',
                    'label' => __('Schedule Time', 'tmu'),
                    'description' => __('Time when the drama airs', 'tmu')
                ],
                'episode_length' => [
                    'type' => 'number',
                    'label' => __('Episode Length (minutes)', 'tmu'),
                    'attributes' => [
                        'min' => 1,
                        'max' => 240,
                        'step' => 1
                    ]
                ]
            ]
        ]
    ],
    
    'people' => [
        'tmdb_sync' => [
            'title' => __('TMDB Synchronization', 'tmu'),
            'context' => 'side',
            'priority' => 'high',
            'fields' => [
                'tmdb_id' => [
                    'type' => 'number',
                    'label' => __('TMDB ID', 'tmu'),
                    'description' => __('The Movie Database ID for automatic data synchronization', 'tmu'),
                    'attributes' => [
                        'min' => 1,
                        'step' => 1
                    ]
                ]
            ]
        ],
        'personal_info' => [
            'title' => __('Personal Information', 'tmu'),
            'context' => 'normal',
            'priority' => 'high',
            'fields' => [
                'birthday' => [
                    'type' => 'date',
                    'label' => __('Birthday', 'tmu'),
                    'description' => __('Date of birth', 'tmu')
                ],
                'deathday' => [
                    'type' => 'date',
                    'label' => __('Deathday', 'tmu'),
                    'description' => __('Date of death (if applicable)', 'tmu')
                ],
                'place_of_birth' => [
                    'type' => 'text',
                    'label' => __('Place of Birth', 'tmu'),
                    'description' => __('Birthplace of the person', 'tmu')
                ],
                'gender' => [
                    'type' => 'select',
                    'label' => __('Gender', 'tmu'),
                    'options' => [
                        '0' => __('Not specified', 'tmu'),
                        '1' => __('Female', 'tmu'),
                        '2' => __('Male', 'tmu'),
                        '3' => __('Non-binary', 'tmu')
                    ],
                    'default' => '0'
                ]
            ]
        ],
        'career_info' => [
            'title' => __('Career Information', 'tmu'),
            'context' => 'normal',
            'priority' => 'default',
            'fields' => [
                'known_for_department' => [
                    'type' => 'select',
                    'label' => __('Known For Department', 'tmu'),
                    'options' => [
                        'Acting' => __('Acting', 'tmu'),
                        'Directing' => __('Directing', 'tmu'),
                        'Production' => __('Production', 'tmu'),
                        'Writing' => __('Writing', 'tmu'),
                        'Camera' => __('Camera', 'tmu'),
                        'Editing' => __('Editing', 'tmu'),
                        'Sound' => __('Sound', 'tmu'),
                        'Art' => __('Art', 'tmu'),
                        'Costume & Make-Up' => __('Costume & Make-Up', 'tmu'),
                        'Visual Effects' => __('Visual Effects', 'tmu')
                    ],
                    'default' => 'Acting'
                ],
                'popularity' => [
                    'type' => 'number',
                    'label' => __('Popularity', 'tmu'),
                    'description' => __('Popularity score from TMDB', 'tmu'),
                    'attributes' => [
                        'step' => 0.01
                    ],
                    'readonly' => true
                ]
            ]
        ],
        'external_links' => [
            'title' => __('External Links', 'tmu'),
            'context' => 'side',
            'priority' => 'default',
            'fields' => [
                'imdb_id' => [
                    'type' => 'text',
                    'label' => __('IMDb ID', 'tmu'),
                    'description' => __('IMDb identifier', 'tmu'),
                    'placeholder' => 'nm0000123'
                ],
                'homepage' => [
                    'type' => 'url',
                    'label' => __('Homepage', 'tmu'),
                    'description' => __('Official website or homepage', 'tmu')
                ],
                'social_media' => [
                    'type' => 'textarea',
                    'label' => __('Social Media', 'tmu'),
                    'description' => __('Social media links (JSON format)', 'tmu'),
                    'rows' => 3
                ]
            ]
        ]
    ],
    
    'season' => [
        'season_details' => [
            'title' => __('Season Details', 'tmu'),
            'context' => 'normal',
            'priority' => 'high',
            'fields' => [
                'season_number' => [
                    'type' => 'number',
                    'label' => __('Season Number', 'tmu'),
                    'description' => __('Season number in the series', 'tmu'),
                    'attributes' => [
                        'min' => 0,
                        'step' => 1
                    ],
                    'validation' => 'required|numeric|min:0'
                ],
                'episode_count' => [
                    'type' => 'number',
                    'label' => __('Episode Count', 'tmu'),
                    'description' => __('Number of episodes in this season', 'tmu'),
                    'attributes' => [
                        'min' => 0,
                        'step' => 1
                    ]
                ],
                'air_date' => [
                    'type' => 'date',
                    'label' => __('Air Date', 'tmu'),
                    'description' => __('Date when the season premiered', 'tmu')
                ],
                'series_id' => [
                    'type' => 'post_select',
                    'label' => __('TV Series', 'tmu'),
                    'description' => __('The TV series this season belongs to', 'tmu'),
                    'post_type' => 'tv-series',
                    'validation' => 'required'
                ]
            ]
        ]
    ],
    
    'episode' => [
        'episode_details' => [
            'title' => __('Episode Details', 'tmu'),
            'context' => 'normal',
            'priority' => 'high',
            'fields' => [
                'episode_number' => [
                    'type' => 'number',
                    'label' => __('Episode Number', 'tmu'),
                    'description' => __('Episode number within the season', 'tmu'),
                    'attributes' => [
                        'min' => 1,
                        'step' => 1
                    ],
                    'validation' => 'required|numeric|min:1'
                ],
                'season_number' => [
                    'type' => 'number',
                    'label' => __('Season Number', 'tmu'),
                    'description' => __('Season number this episode belongs to', 'tmu'),
                    'attributes' => [
                        'min' => 0,
                        'step' => 1
                    ],
                    'validation' => 'required|numeric|min:0'
                ],
                'air_date' => [
                    'type' => 'date',
                    'label' => __('Air Date', 'tmu'),
                    'description' => __('Date when the episode aired', 'tmu')
                ],
                'runtime' => [
                    'type' => 'number',
                    'label' => __('Runtime (minutes)', 'tmu'),
                    'description' => __('Episode duration in minutes', 'tmu'),
                    'attributes' => [
                        'min' => 1,
                        'max' => 480,
                        'step' => 1
                    ]
                ],
                'series_id' => [
                    'type' => 'post_select',
                    'label' => __('TV Series', 'tmu'),
                    'description' => __('The TV series this episode belongs to', 'tmu'),
                    'post_type' => 'tv-series',
                    'validation' => 'required'
                ],
                'season_id' => [
                    'type' => 'post_select',
                    'label' => __('Season', 'tmu'),
                    'description' => __('The season this episode belongs to', 'tmu'),
                    'post_type' => 'season'
                ]
            ]
        ],
        'episode_rating' => [
            'title' => __('Episode Rating', 'tmu'),
            'context' => 'side',
            'priority' => 'default',
            'fields' => [
                'vote_average' => [
                    'type' => 'number',
                    'label' => __('TMDB Rating', 'tmu'),
                    'description' => __('Average rating from TMDB', 'tmu'),
                    'attributes' => [
                        'min' => 0,
                        'max' => 10,
                        'step' => 0.1
                    ],
                    'readonly' => true
                ],
                'vote_count' => [
                    'type' => 'number',
                    'label' => __('Vote Count', 'tmu'),
                    'description' => __('Number of votes from TMDB', 'tmu'),
                    'readonly' => true
                ]
            ]
        ]
    ],
    
    'video' => [
        'video_details' => [
            'title' => __('Video Details', 'tmu'),
            'context' => 'normal',
            'priority' => 'high',
            'fields' => [
                'video_key' => [
                    'type' => 'text',
                    'label' => __('Video Key', 'tmu'),
                    'description' => __('Video identifier (e.g., YouTube ID)', 'tmu'),
                    'validation' => 'required'
                ],
                'video_site' => [
                    'type' => 'select',
                    'label' => __('Video Site', 'tmu'),
                    'options' => [
                        'YouTube' => __('YouTube', 'tmu'),
                        'Vimeo' => __('Vimeo', 'tmu'),
                        'Dailymotion' => __('Dailymotion', 'tmu')
                    ],
                    'default' => 'YouTube'
                ],
                'video_size' => [
                    'type' => 'select',
                    'label' => __('Video Size', 'tmu'),
                    'options' => [
                        '360' => __('360p', 'tmu'),
                        '480' => __('480p', 'tmu'),
                        '720' => __('720p (HD)', 'tmu'),
                        '1080' => __('1080p (Full HD)', 'tmu')
                    ],
                    'default' => '720'
                ],
                'published_at' => [
                    'type' => 'datetime',
                    'label' => __('Published At', 'tmu'),
                    'description' => __('Date and time when the video was published', 'tmu')
                ],
                'official' => [
                    'type' => 'checkbox',
                    'label' => __('Official Video', 'tmu'),
                    'description' => __('Check if this is an official video', 'tmu'),
                    'default' => true
                ]
            ]
        ],
        'related_content' => [
            'title' => __('Related Content', 'tmu'),
            'context' => 'side',
            'priority' => 'default',
            'fields' => [
                'related_type' => [
                    'type' => 'select',
                    'label' => __('Related To', 'tmu'),
                    'options' => [
                        'movie' => __('Movie', 'tmu'),
                        'tv-series' => __('TV Series', 'tmu'),
                        'drama' => __('Drama', 'tmu'),
                        'people' => __('Person', 'tmu')
                    ],
                    'validation' => 'required'
                ],
                'related_id' => [
                    'type' => 'number',
                    'label' => __('Related ID', 'tmu'),
                    'description' => __('ID of the related post', 'tmu'),
                    'attributes' => [
                        'min' => 1,
                        'step' => 1
                    ],
                    'validation' => 'required|numeric|min:1'
                ]
            ]
        ]
    ]
];