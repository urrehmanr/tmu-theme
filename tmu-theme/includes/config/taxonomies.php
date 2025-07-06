<?php
/**
 * TMU Taxonomies Configuration
 *
 * @package TMU\Config
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Taxonomies Configuration Array
 * 
 * This configuration defines all custom taxonomies for the TMU theme
 * Each taxonomy can be conditionally registered based on theme settings
 */
return [
    'genre' => [
        'enabled' => true,
        'hierarchical' => true,
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'show_in_rest' => true,
        'rest_base' => 'genres',
        'labels' => [
            'name' => __('Genres', 'tmu'),
            'singular_name' => __('Genre', 'tmu'),
            'menu_name' => __('Genres', 'tmu'),
            'all_items' => __('All Genres', 'tmu'),
            'edit_item' => __('Edit Genre', 'tmu'),
            'view_item' => __('View Genre', 'tmu'),
            'update_item' => __('Update Genre', 'tmu'),
            'add_new_item' => __('Add New Genre', 'tmu'),
            'new_item_name' => __('New Genre Name', 'tmu'),
            'parent_item' => __('Parent Genre', 'tmu'),
            'parent_item_colon' => __('Parent Genre:', 'tmu'),
            'search_items' => __('Search Genres', 'tmu'),
            'popular_items' => __('Popular Genres', 'tmu'),
            'separate_items_with_commas' => __('Separate genres with commas', 'tmu'),
            'add_or_remove_items' => __('Add or remove genres', 'tmu'),
            'choose_from_most_used' => __('Choose from the most used genres', 'tmu'),
            'not_found' => __('No genres found', 'tmu'),
        ],
        'rewrite' => [
            'slug' => 'genre',
            'with_front' => false,
            'hierarchical' => true,
        ],
        'post_types' => ['movie', 'tv-series', 'drama'],
        'meta_fields' => [
            'tmdb_id' => [
                'type' => 'number',
                'description' => 'TMDB Genre ID'
            ],
            'color' => [
                'type' => 'color',
                'description' => 'Genre color for styling'
            ],
            'icon' => [
                'type' => 'text',
                'description' => 'Genre icon class or URL'
            ],
            'description_extended' => [
                'type' => 'textarea',
                'description' => 'Extended genre description'
            ]
        ],
        'default_terms' => [
            'Action', 'Adventure', 'Animation', 'Comedy', 'Crime', 'Documentary',
            'Drama', 'Family', 'Fantasy', 'History', 'Horror', 'Music', 'Mystery',
            'Romance', 'Science Fiction', 'TV Movie', 'Thriller', 'War', 'Western'
        ]
    ],
    
    'country' => [
        'enabled' => true,
        'hierarchical' => true,
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'show_in_rest' => true,
        'rest_base' => 'countries',
        'labels' => [
            'name' => __('Countries', 'tmu'),
            'singular_name' => __('Country', 'tmu'),
            'menu_name' => __('Countries', 'tmu'),
            'all_items' => __('All Countries', 'tmu'),
            'edit_item' => __('Edit Country', 'tmu'),
            'view_item' => __('View Country', 'tmu'),
            'update_item' => __('Update Country', 'tmu'),
            'add_new_item' => __('Add New Country', 'tmu'),
            'new_item_name' => __('New Country Name', 'tmu'),
            'parent_item' => __('Parent Country', 'tmu'),
            'parent_item_colon' => __('Parent Country:', 'tmu'),
            'search_items' => __('Search Countries', 'tmu'),
            'popular_items' => __('Popular Countries', 'tmu'),
            'not_found' => __('No countries found', 'tmu'),
        ],
        'rewrite' => [
            'slug' => 'country',
            'with_front' => false,
            'hierarchical' => true,
        ],
        'post_types' => ['movie', 'tv-series', 'drama'],
        'meta_fields' => [
            'iso_3166_1' => [
                'type' => 'text',
                'description' => 'ISO 3166-1 country code'
            ],
            'flag_emoji' => [
                'type' => 'text',
                'description' => 'Country flag emoji'
            ],
            'continent' => [
                'type' => 'select',
                'description' => 'Continent',
                'options' => ['Africa', 'Asia', 'Europe', 'North America', 'South America', 'Oceania', 'Antarctica']
            ],
            'native_name' => [
                'type' => 'text',
                'description' => 'Native country name'
            ]
        ],
        'default_terms' => [
            'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany',
            'France', 'Italy', 'Spain', 'Japan', 'South Korea', 'India', 'China',
            'Brazil', 'Mexico', 'Russia', 'Netherlands', 'Sweden', 'Norway'
        ]
    ],
    
    'language' => [
        'enabled' => true,
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'show_in_rest' => true,
        'rest_base' => 'languages',
        'labels' => [
            'name' => __('Languages', 'tmu'),
            'singular_name' => __('Language', 'tmu'),
            'menu_name' => __('Languages', 'tmu'),
            'all_items' => __('All Languages', 'tmu'),
            'edit_item' => __('Edit Language', 'tmu'),
            'view_item' => __('View Language', 'tmu'),
            'update_item' => __('Update Language', 'tmu'),
            'add_new_item' => __('Add New Language', 'tmu'),
            'new_item_name' => __('New Language Name', 'tmu'),
            'search_items' => __('Search Languages', 'tmu'),
            'popular_items' => __('Popular Languages', 'tmu'),
            'separate_items_with_commas' => __('Separate languages with commas', 'tmu'),
            'add_or_remove_items' => __('Add or remove languages', 'tmu'),
            'choose_from_most_used' => __('Choose from the most used languages', 'tmu'),
            'not_found' => __('No languages found', 'tmu'),
        ],
        'rewrite' => [
            'slug' => 'language',
            'with_front' => false,
            'hierarchical' => false,
        ],
        'post_types' => ['movie', 'tv-series', 'drama'],
        'meta_fields' => [
            'iso_639_1' => [
                'type' => 'text',
                'description' => 'ISO 639-1 language code'
            ],
            'native_name' => [
                'type' => 'text',
                'description' => 'Native language name'
            ],
            'rtl' => [
                'type' => 'checkbox',
                'description' => 'Right-to-left language'
            ]
        ],
        'default_terms' => [
            'English', 'Spanish', 'French', 'German', 'Italian', 'Portuguese',
            'Russian', 'Japanese', 'Korean', 'Chinese', 'Hindi', 'Arabic',
            'Dutch', 'Swedish', 'Norwegian', 'Danish', 'Polish', 'Turkish'
        ]
    ],
    
    'network' => [
        'enabled' => true,
        'hierarchical' => true,
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'show_in_rest' => true,
        'rest_base' => 'networks',
        'labels' => [
            'name' => __('Networks', 'tmu'),
            'singular_name' => __('Network', 'tmu'),
            'menu_name' => __('Networks', 'tmu'),
            'all_items' => __('All Networks', 'tmu'),
            'edit_item' => __('Edit Network', 'tmu'),
            'view_item' => __('View Network', 'tmu'),
            'update_item' => __('Update Network', 'tmu'),
            'add_new_item' => __('Add New Network', 'tmu'),
            'new_item_name' => __('New Network Name', 'tmu'),
            'parent_item' => __('Parent Network', 'tmu'),
            'parent_item_colon' => __('Parent Network:', 'tmu'),
            'search_items' => __('Search Networks', 'tmu'),
            'popular_items' => __('Popular Networks', 'tmu'),
            'not_found' => __('No networks found', 'tmu'),
        ],
        'rewrite' => [
            'slug' => 'network',
            'with_front' => false,
            'hierarchical' => true,
        ],
        'post_types' => ['tv-series', 'drama'],
        'meta_fields' => [
            'tmdb_id' => [
                'type' => 'number',
                'description' => 'TMDB Network ID'
            ],
            'network_type' => [
                'type' => 'select',
                'description' => 'Network type',
                'options' => ['Broadcast', 'Cable', 'Streaming', 'Premium', 'Public']
            ],
            'country' => [
                'type' => 'text',
                'description' => 'Network origin country'
            ],
            'logo_url' => [
                'type' => 'url',
                'description' => 'Network logo URL'
            ],
            'website' => [
                'type' => 'url',
                'description' => 'Network official website'
            ]
        ],
        'default_terms' => [
            'Netflix', 'HBO', 'Amazon Prime Video', 'Disney+', 'Hulu', 'ABC',
            'CBS', 'NBC', 'FOX', 'The CW', 'BBC', 'ITV', 'Channel 4',
            'Showtime', 'Starz', 'FX', 'AMC', 'TNT', 'USA Network'
        ]
    ],
    
    'year' => [
        'enabled' => true,
        'hierarchical' => true,
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'show_in_rest' => true,
        'rest_base' => 'years',
        'labels' => [
            'name' => __('Years', 'tmu'),
            'singular_name' => __('Year', 'tmu'),
            'menu_name' => __('Years', 'tmu'),
            'all_items' => __('All Years', 'tmu'),
            'edit_item' => __('Edit Year', 'tmu'),
            'view_item' => __('View Year', 'tmu'),
            'update_item' => __('Update Year', 'tmu'),
            'add_new_item' => __('Add New Year', 'tmu'),
            'new_item_name' => __('New Year', 'tmu'),
            'parent_item' => __('Parent Year', 'tmu'),
            'parent_item_colon' => __('Parent Year:', 'tmu'),
            'search_items' => __('Search Years', 'tmu'),
            'popular_items' => __('Popular Years', 'tmu'),
            'not_found' => __('No years found', 'tmu'),
        ],
        'rewrite' => [
            'slug' => 'year',
            'with_front' => false,
            'hierarchical' => true,
        ],
        'post_types' => ['movie', 'tv-series', 'drama'],
        'meta_fields' => [
            'decade' => [
                'type' => 'text',
                'description' => 'Decade (e.g., 2020s)'
            ],
            'century' => [
                'type' => 'text',
                'description' => 'Century (e.g., 21st Century)'
            ]
        ],
        'auto_generate' => true,
        'generation_range' => [1900, 2030]
    ],
    
    'profession' => [
        'enabled' => true,
        'hierarchical' => true,
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'show_in_rest' => true,
        'rest_base' => 'professions',
        'labels' => [
            'name' => __('Professions', 'tmu'),
            'singular_name' => __('Profession', 'tmu'),
            'menu_name' => __('Professions', 'tmu'),
            'all_items' => __('All Professions', 'tmu'),
            'edit_item' => __('Edit Profession', 'tmu'),
            'view_item' => __('View Profession', 'tmu'),
            'update_item' => __('Update Profession', 'tmu'),
            'add_new_item' => __('Add New Profession', 'tmu'),
            'new_item_name' => __('New Profession Name', 'tmu'),
            'parent_item' => __('Parent Profession', 'tmu'),
            'parent_item_colon' => __('Parent Profession:', 'tmu'),
            'search_items' => __('Search Professions', 'tmu'),
            'popular_items' => __('Popular Professions', 'tmu'),
            'not_found' => __('No professions found', 'tmu'),
        ],
        'rewrite' => [
            'slug' => 'profession',
            'with_front' => false,
            'hierarchical' => true,
        ],
        'post_types' => ['people'],
        'meta_fields' => [
            'department' => [
                'type' => 'select',
                'description' => 'Department',
                'options' => [
                    'Acting', 'Directing', 'Writing', 'Production', 'Camera',
                    'Editing', 'Sound', 'Art', 'Costume & Make-Up', 'Visual Effects',
                    'Lighting', 'Crew'
                ]
            ],
            'is_primary' => [
                'type' => 'checkbox',
                'description' => 'Primary profession'
            ]
        ],
        'default_terms' => [
            'Actor', 'Actress', 'Director', 'Producer', 'Writer', 'Screenwriter',
            'Cinematographer', 'Editor', 'Composer', 'Production Designer',
            'Costume Designer', 'Makeup Artist', 'Visual Effects Supervisor',
            'Stunt Coordinator', 'Casting Director', 'Executive Producer'
        ]
    ],
    
    'nationality' => [
        'enabled' => true,
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'show_in_rest' => true,
        'rest_base' => 'nationalities',
        'labels' => [
            'name' => __('Nationalities', 'tmu'),
            'singular_name' => __('Nationality', 'tmu'),
            'menu_name' => __('Nationalities', 'tmu'),
            'all_items' => __('All Nationalities', 'tmu'),
            'edit_item' => __('Edit Nationality', 'tmu'),
            'view_item' => __('View Nationality', 'tmu'),
            'update_item' => __('Update Nationality', 'tmu'),
            'add_new_item' => __('Add New Nationality', 'tmu'),
            'new_item_name' => __('New Nationality Name', 'tmu'),
            'search_items' => __('Search Nationalities', 'tmu'),
            'popular_items' => __('Popular Nationalities', 'tmu'),
            'separate_items_with_commas' => __('Separate nationalities with commas', 'tmu'),
            'add_or_remove_items' => __('Add or remove nationalities', 'tmu'),
            'choose_from_most_used' => __('Choose from the most used nationalities', 'tmu'),
            'not_found' => __('No nationalities found', 'tmu'),
        ],
        'rewrite' => [
            'slug' => 'nationality',
            'with_front' => false,
            'hierarchical' => false,
        ],
        'post_types' => ['people'],
        'meta_fields' => [
            'country_code' => [
                'type' => 'text',
                'description' => 'Country code'
            ],
            'flag_emoji' => [
                'type' => 'text',
                'description' => 'Flag emoji'
            ]
        ],
        'default_terms' => [
            'American', 'British', 'Canadian', 'Australian', 'German', 'French',
            'Italian', 'Spanish', 'Japanese', 'Korean', 'Indian', 'Chinese',
            'Brazilian', 'Mexican', 'Russian', 'Dutch', 'Swedish', 'Norwegian'
        ]
    ],
    
    'video-type' => [
        'enabled' => true,
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => false,
        'show_tagcloud' => false,
        'show_in_rest' => true,
        'rest_base' => 'video-types',
        'labels' => [
            'name' => __('Video Types', 'tmu'),
            'singular_name' => __('Video Type', 'tmu'),
            'menu_name' => __('Video Types', 'tmu'),
            'all_items' => __('All Video Types', 'tmu'),
            'edit_item' => __('Edit Video Type', 'tmu'),
            'view_item' => __('View Video Type', 'tmu'),
            'update_item' => __('Update Video Type', 'tmu'),
            'add_new_item' => __('Add New Video Type', 'tmu'),
            'new_item_name' => __('New Video Type Name', 'tmu'),
            'search_items' => __('Search Video Types', 'tmu'),
            'popular_items' => __('Popular Video Types', 'tmu'),
            'separate_items_with_commas' => __('Separate video types with commas', 'tmu'),
            'add_or_remove_items' => __('Add or remove video types', 'tmu'),
            'choose_from_most_used' => __('Choose from the most used video types', 'tmu'),
            'not_found' => __('No video types found', 'tmu'),
        ],
        'rewrite' => [
            'slug' => 'video-type',
            'with_front' => false,
            'hierarchical' => false,
        ],
        'post_types' => ['video'],
        'meta_fields' => [
            'display_order' => [
                'type' => 'number',
                'description' => 'Display order for video types'
            ]
        ],
        'default_terms' => [
            'Trailer', 'Teaser', 'Clip', 'Behind the Scenes', 'Bloopers',
            'Featurette', 'Opening Credits', 'Recap', 'Interview'
        ]
    ]
];