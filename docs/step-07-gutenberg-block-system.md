# Step 07: Gutenberg Block System - Complete Implementation

## Overview
This step implements a comprehensive Gutenberg block system to replace the Meta Box plugin dependency while maintaining 100% functionality. The system uses modern WordPress Block Editor (Gutenberg) API to create custom blocks for post type fields, episode management, taxonomy fields, content blocks, and TMDB sync utilities.

## Architecture Overview

### Block Categories
1. **Content Blocks** - Movie, TV Series, Drama, People metadata blocks
2. **Episode Management Blocks** - TV Episodes, Drama Episodes, Season blocks
3. **Media Blocks** - Video content management blocks
4. **Taxonomy Blocks** - Channel/Network images, FAQs blocks
5. **Content Curation Blocks** - Blog posts lists, trending content blocks
6. **TMDB Sync Blocks** - Data fetching and synchronization blocks

### Modern Block Development Stack
- **React/JSX** for block interfaces
- **WordPress Block API** (@wordpress/blocks, @wordpress/components)
- **TypeScript** for type safety
- **SCSS** for styling
- **webpack** for build process
- **REST API** for data persistence

## 1. Block Registration System

### 1.1 Block Registry
```php
// src/Blocks/BlockRegistry.php
<?php
namespace TMU\Blocks;

class BlockRegistry {
    private $blocks = [];
    
    public function __construct() {
        add_action('init', [$this, 'register_blocks']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
    }
    
    public function register_blocks(): void {
        $this->blocks = [
            'movie-metadata' => MovieMetadataBlock::class,
            'tv-series-metadata' => TvSeriesMetadataBlock::class,
            'drama-metadata' => DramaMetadataBlock::class,
            'people-metadata' => PeopleMetadataBlock::class,
            'tv-episode-metadata' => TvEpisodeMetadataBlock::class,
            'drama-episode-metadata' => DramaEpisodeMetadataBlock::class,
            'season-metadata' => SeasonMetadataBlock::class,
            'video-metadata' => VideoMetadataBlock::class,
            'taxonomy-image' => TaxonomyImageBlock::class,
            'taxonomy-faqs' => TaxonomyFaqsBlock::class,
            'blog-posts-list' => BlogPostsListBlock::class,
            'trending-content' => TrendingContentBlock::class,
            'tmdb-sync' => TmdbSyncBlock::class,
        ];
        
        foreach ($this->blocks as $name => $class) {
            register_block_type("tmu/{$name}", [
                'editor_script' => 'tmu-blocks-editor',
                'editor_style' => 'tmu-blocks-editor',
                'style' => 'tmu-blocks',
                'render_callback' => [$class, 'render'],
                'attributes' => $class::get_attributes(),
            ]);
        }
    }
    
    public function enqueue_editor_assets(): void {
        wp_enqueue_script(
            'tmu-blocks-editor',
            get_template_directory_uri() . '/assets/js/blocks.js',
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'],
            '1.0.0',
            true
        );
        
        wp_enqueue_style(
            'tmu-blocks-editor',
            get_template_directory_uri() . '/assets/css/blocks-editor.css',
            ['wp-edit-blocks'],
            '1.0.0'
        );
    }
}
```

### 1.2 Base Block Class
```php
// src/Blocks/BaseBlock.php
<?php
namespace TMU\Blocks;

abstract class BaseBlock {
    protected $name;
    protected $title;
    protected $description;
    protected $category = 'tmu-blocks';
    protected $icon = 'admin-post';
    protected $supports = [];
    
    abstract public static function get_attributes(): array;
    abstract public static function render($attributes, $content): string;
    
    public function get_block_config(): array {
        return [
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'icon' => $this->icon,
            'supports' => $this->supports,
            'attributes' => static::get_attributes(),
        ];
    }
}
```

## 2. Content Metadata Blocks

### 2.1 Movie Metadata Block
```php
// src/Blocks/MovieMetadataBlock.php
<?php
namespace TMU\Blocks;

class MovieMetadataBlock extends BaseBlock {
    protected $name = 'movie-metadata';
    protected $title = 'Movie Metadata';
    protected $description = 'Comprehensive movie metadata management';
    
    public static function get_attributes(): array {
        return [
            'tmdb_id' => ['type' => 'number'],
            'imdb_id' => ['type' => 'string'],
            'title' => ['type' => 'string'],
            'original_title' => ['type' => 'string'],
            'tagline' => ['type' => 'string'],
            'overview' => ['type' => 'string'],
            'runtime' => ['type' => 'number'],
            'release_date' => ['type' => 'string'],
            'status' => ['type' => 'string'],
            'budget' => ['type' => 'number'],
            'revenue' => ['type' => 'number'],
            'homepage' => ['type' => 'string'],
            'poster_path' => ['type' => 'string'],
            'backdrop_path' => ['type' => 'string'],
            'tmdb_vote_average' => ['type' => 'number'],
            'tmdb_vote_count' => ['type' => 'number'],
            'tmdb_popularity' => ['type' => 'number'],
            'adult' => ['type' => 'boolean'],
            'video' => ['type' => 'boolean'],
            'belongs_to_collection' => ['type' => 'object'],
            'production_companies' => ['type' => 'array'],
            'production_countries' => ['type' => 'array'],
            'spoken_languages' => ['type' => 'array'],
            'credits' => ['type' => 'object'],
            'external_ids' => ['type' => 'object'],
            'images' => ['type' => 'object'],
            'videos' => ['type' => 'object'],
            'reviews' => ['type' => 'object'],
            'similar' => ['type' => 'array'],
            'recommendations' => ['type' => 'array'],
        ];
    }
    
    public static function render($attributes, $content): string {
        // Server-side rendering for frontend display
        return '<div class="tmu-movie-metadata">' . $content . '</div>';
    }
}
```

### 2.2 TV Series Metadata Block
```php
// src/Blocks/TvSeriesMetadataBlock.php
<?php
namespace TMU\Blocks;

class TvSeriesMetadataBlock extends BaseBlock {
    protected $name = 'tv-series-metadata';
    protected $title = 'TV Series Metadata';
    protected $description = 'Comprehensive TV series metadata management';
    
    public static function get_attributes(): array {
        return [
            'tmdb_id' => ['type' => 'number'],
            'imdb_id' => ['type' => 'string'],
            'name' => ['type' => 'string'],
            'original_name' => ['type' => 'string'],
            'tagline' => ['type' => 'string'],
            'overview' => ['type' => 'string'],
            'first_air_date' => ['type' => 'string'],
            'last_air_date' => ['type' => 'string'],
            'status' => ['type' => 'string'],
            'type' => ['type' => 'string'],
            'homepage' => ['type' => 'string'],
            'in_production' => ['type' => 'boolean'],
            'number_of_episodes' => ['type' => 'number'],
            'number_of_seasons' => ['type' => 'number'],
            'episode_run_time' => ['type' => 'array'],
            'languages' => ['type' => 'array'],
            'origin_country' => ['type' => 'array'],
            'original_language' => ['type' => 'string'],
            'poster_path' => ['type' => 'string'],
            'backdrop_path' => ['type' => 'string'],
            'tmdb_vote_average' => ['type' => 'number'],
            'tmdb_vote_count' => ['type' => 'number'],
            'tmdb_popularity' => ['type' => 'number'],
            'adult' => ['type' => 'boolean'],
            'created_by' => ['type' => 'array'],
            'genres' => ['type' => 'array'],
            'networks' => ['type' => 'array'],
            'production_companies' => ['type' => 'array'],
            'production_countries' => ['type' => 'array'],
            'seasons' => ['type' => 'array'],
            'spoken_languages' => ['type' => 'array'],
            'credits' => ['type' => 'object'],
            'external_ids' => ['type' => 'object'],
            'images' => ['type' => 'object'],
            'videos' => ['type' => 'object'],
            'similar' => ['type' => 'array'],
            'recommendations' => ['type' => 'array'],
        ];
    }
    
    public static function render($attributes, $content): string {
        return '<div class="tmu-tv-series-metadata">' . $content . '</div>';
    }
}
```

## 3. Episode Management Blocks

### 3.1 TV Episode Metadata Block
```php
// src/Blocks/TvEpisodeMetadataBlock.php
<?php
namespace TMU\Blocks;

class TvEpisodeMetadataBlock extends BaseBlock {
    protected $name = 'tv-episode-metadata';
    protected $title = 'TV Episode Metadata';
    protected $description = 'Individual TV episode metadata management';
    
    public static function get_attributes(): array {
        return [
            'tv_series' => ['type' => 'number'], // Post ID
            'season_number' => ['type' => 'number'],
            'episode_number' => ['type' => 'number'],
            'name' => ['type' => 'string'],
            'overview' => ['type' => 'string'],
            'air_date' => ['type' => 'string'],
            'episode_type' => ['type' => 'string'],
            'runtime' => ['type' => 'number'],
            'still_path' => ['type' => 'string'],
            'vote_average' => ['type' => 'number'],
            'vote_count' => ['type' => 'number'],
            'crew' => ['type' => 'array'],
            'guest_stars' => ['type' => 'array'],
            'tmdb_id' => ['type' => 'number'],
            'production_code' => ['type' => 'string'],
        ];
    }
    
    public static function render($attributes, $content): string {
        return '<div class="tmu-tv-episode-metadata">' . $content . '</div>';
    }
}
```

### 3.2 Drama Episode Metadata Block
```php
// src/Blocks/DramaEpisodeMetadataBlock.php
<?php
namespace TMU\Blocks;

class DramaEpisodeMetadataBlock extends BaseBlock {
    protected $name = 'drama-episode-metadata';
    protected $title = 'Drama Episode Metadata';
    protected $description = 'Individual drama episode metadata management';
    
    public static function get_attributes(): array {
        return [
            'drama' => ['type' => 'number'], // Post ID
            'episode_number' => ['type' => 'number'],
            'name' => ['type' => 'string'],
            'overview' => ['type' => 'string'],
            'air_date' => ['type' => 'string'],
            'episode_type' => ['type' => 'string'],
            'runtime' => ['type' => 'number'],
            'still_path' => ['type' => 'string'],
            'vote_average' => ['type' => 'number'],
            'vote_count' => ['type' => 'number'],
            'crew' => ['type' => 'array'],
            'guest_stars' => ['type' => 'array'],
            'special_features' => ['type' => 'array'],
        ];
    }
    
    public static function render($attributes, $content): string {
        return '<div class="tmu-drama-episode-metadata">' . $content . '</div>';
    }
}
```

## 4. Frontend Block Components (React/JSX)

### 4.1 Movie Metadata Block Component
```jsx
// assets/src/blocks/MovieMetadataBlock.jsx
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { 
    PanelBody, 
    TextControl, 
    TextareaControl, 
    NumberControl,
    SelectControl,
    ToggleControl,
    Button,
    Placeholder
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';

registerBlockType('tmu/movie-metadata', {
    title: 'Movie Metadata',
    icon: 'video-alt3',
    category: 'tmu-blocks',
    attributes: {
        tmdb_id: { type: 'number' },
        imdb_id: { type: 'string' },
        title: { type: 'string' },
        original_title: { type: 'string' },
        tagline: { type: 'string' },
        overview: { type: 'string' },
        runtime: { type: 'number' },
        release_date: { type: 'string' },
        status: { type: 'string' },
        budget: { type: 'number' },
        revenue: { type: 'number' },
        homepage: { type: 'string' },
        poster_path: { type: 'string' },
        backdrop_path: { type: 'string' },
        tmdb_vote_average: { type: 'number' },
        tmdb_vote_count: { type: 'number' },
        tmdb_popularity: { type: 'number' },
        adult: { type: 'boolean' },
        video: { type: 'boolean' },
    },
    
    edit: ({ attributes, setAttributes }) => {
        const [isLoading, setIsLoading] = useState(false);
        const [tmdbData, setTmdbData] = useState(null);
        
        const fetchTmdbData = async (tmdbId) => {
            if (!tmdbId) return;
            
            setIsLoading(true);
            try {
                const response = await fetch(`/wp-json/tmu/v1/tmdb/movie/${tmdbId}`);
                const data = await response.json();
                setTmdbData(data);
                
                // Auto-populate attributes from TMDB data
                Object.keys(data).forEach(key => {
                    if (attributes.hasOwnProperty(key)) {
                        setAttributes({ [key]: data[key] });
                    }
                });
            } catch (error) {
                console.error('Error fetching TMDB data:', error);
            } finally {
                setIsLoading(false);
            }
        };
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title="TMDB Integration" initialOpen={true}>
                        <NumberControl
                            label="TMDB ID"
                            value={attributes.tmdb_id}
                            onChange={(value) => setAttributes({ tmdb_id: value })}
                        />
                        <Button
                            isPrimary
                            isLarge
                            onClick={() => fetchTmdbData(attributes.tmdb_id)}
                            disabled={!attributes.tmdb_id || isLoading}
                        >
                            {isLoading ? 'Fetching...' : 'Fetch TMDB Data'}
                        </Button>
                    </PanelBody>
                    
                    <PanelBody title="Basic Information" initialOpen={false}>
                        <TextControl
                            label="Title"
                            value={attributes.title}
                            onChange={(value) => setAttributes({ title: value })}
                        />
                        <TextControl
                            label="Original Title"
                            value={attributes.original_title}
                            onChange={(value) => setAttributes({ original_title: value })}
                        />
                        <TextControl
                            label="Tagline"
                            value={attributes.tagline}
                            onChange={(value) => setAttributes({ tagline: value })}
                        />
                        <TextareaControl
                            label="Overview"
                            value={attributes.overview}
                            onChange={(value) => setAttributes({ overview: value })}
                            rows={4}
                        />
                    </PanelBody>
                    
                    <PanelBody title="Release Information" initialOpen={false}>
                        <TextControl
                            label="Release Date"
                            type="date"
                            value={attributes.release_date}
                            onChange={(value) => setAttributes({ release_date: value })}
                        />
                        <SelectControl
                            label="Status"
                            value={attributes.status}
                            options={[
                                { label: 'Released', value: 'Released' },
                                { label: 'In Production', value: 'In Production' },
                                { label: 'Post Production', value: 'Post Production' },
                                { label: 'Planned', value: 'Planned' },
                                { label: 'Canceled', value: 'Canceled' },
                            ]}
                            onChange={(value) => setAttributes({ status: value })}
                        />
                        <NumberControl
                            label="Runtime (minutes)"
                            value={attributes.runtime}
                            onChange={(value) => setAttributes({ runtime: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title="Financial Information" initialOpen={false}>
                        <NumberControl
                            label="Budget ($)"
                            value={attributes.budget}
                            onChange={(value) => setAttributes({ budget: value })}
                        />
                        <NumberControl
                            label="Revenue ($)"
                            value={attributes.revenue}
                            onChange={(value) => setAttributes({ revenue: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title="Ratings & Popularity" initialOpen={false}>
                        <NumberControl
                            label="TMDB Vote Average"
                            value={attributes.tmdb_vote_average}
                            onChange={(value) => setAttributes({ tmdb_vote_average: value })}
                            step={0.1}
                            min={0}
                            max={10}
                        />
                        <NumberControl
                            label="TMDB Vote Count"
                            value={attributes.tmdb_vote_count}
                            onChange={(value) => setAttributes({ tmdb_vote_count: value })}
                        />
                        <NumberControl
                            label="TMDB Popularity"
                            value={attributes.tmdb_popularity}
                            onChange={(value) => setAttributes({ tmdb_popularity: value })}
                            step={0.1}
                        />
                    </PanelBody>
                    
                    <PanelBody title="Media & Links" initialOpen={false}>
                        <TextControl
                            label="IMDB ID"
                            value={attributes.imdb_id}
                            onChange={(value) => setAttributes({ imdb_id: value })}
                        />
                        <TextControl
                            label="Homepage"
                            type="url"
                            value={attributes.homepage}
                            onChange={(value) => setAttributes({ homepage: value })}
                        />
                        <TextControl
                            label="Poster Path"
                            value={attributes.poster_path}
                            onChange={(value) => setAttributes({ poster_path: value })}
                        />
                        <TextControl
                            label="Backdrop Path"
                            value={attributes.backdrop_path}
                            onChange={(value) => setAttributes({ backdrop_path: value })}
                        />
                    </PanelBody>
                    
                    <PanelBody title="Content Flags" initialOpen={false}>
                        <ToggleControl
                            label="Adult Content"
                            checked={attributes.adult}
                            onChange={(value) => setAttributes({ adult: value })}
                        />
                        <ToggleControl
                            label="Has Video"
                            checked={attributes.video}
                            onChange={(value) => setAttributes({ video: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className="tmu-movie-metadata-block">
                    {attributes.title ? (
                        <div className="tmu-metadata-preview">
                            <h3>{attributes.title}</h3>
                            {attributes.poster_path && (
                                <img 
                                    src={`https://image.tmdb.org/t/p/w200${attributes.poster_path}`}
                                    alt={attributes.title}
                                    style={{ maxWidth: '100px', height: 'auto' }}
                                />
                            )}
                            <p><strong>Release Date:</strong> {attributes.release_date}</p>
                            <p><strong>Runtime:</strong> {attributes.runtime} minutes</p>
                            <p><strong>Status:</strong> {attributes.status}</p>
                            {attributes.tmdb_vote_average && (
                                <p><strong>TMDB Rating:</strong> {attributes.tmdb_vote_average}/10</p>
                            )}
                        </div>
                    ) : (
                        <Placeholder
                            icon="video-alt3"
                            label="Movie Metadata"
                            instructions="Configure movie metadata in the block settings panel."
                        />
                    )}
                </div>
            </>
        );
    },
    
    save: ({ attributes }) => {
        return (
            <div className="tmu-movie-metadata">
                {/* Server-side rendering will handle the frontend display */}
            </div>
        );
    },
});
```

## 5. Block Build System

### 5.1 webpack Configuration
```javascript
// webpack.config.js
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
    entry: {
        blocks: './assets/src/blocks/index.js',
        'blocks-editor': './assets/src/blocks/editor.scss',
    },
    output: {
        path: path.resolve(__dirname, 'assets/js'),
        filename: '[name].js',
    },
    module: {
        rules: [
            {
                test: /\.jsx?$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env', '@babel/preset-react'],
                    },
                },
            },
            {
                test: /\.scss$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    'css-loader',
                    'sass-loader',
                ],
            },
        ],
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: '../css/[name].css',
        }),
    ],
    externals: {
        '@wordpress/blocks': 'wp.blocks',
        '@wordpress/element': 'wp.element',
        '@wordpress/components': 'wp.components',
        '@wordpress/block-editor': 'wp.blockEditor',
        '@wordpress/data': 'wp.data',
        '@wordpress/i18n': 'wp.i18n',
    },
};
```

### 5.2 Package.json Scripts
```json
{
    "name": "tmu-theme-blocks",
    "version": "1.0.0",
    "scripts": {
        "build": "webpack --mode=production",
        "dev": "webpack --mode=development --watch",
        "start": "webpack serve --mode=development"
    },
    "devDependencies": {
        "@babel/core": "^7.21.0",
        "@babel/preset-env": "^7.20.0",
        "@babel/preset-react": "^7.18.0",
        "babel-loader": "^9.1.0",
        "css-loader": "^6.7.0",
        "mini-css-extract-plugin": "^2.7.0",
        "sass": "^1.58.0",
        "sass-loader": "^13.2.0",
        "webpack": "^5.75.0",
        "webpack-cli": "^5.0.0",
        "webpack-dev-server": "^4.11.0"
    },
    "dependencies": {
        "@wordpress/blocks": "^12.0.0",
        "@wordpress/components": "^25.0.0",
        "@wordpress/element": "^5.0.0",
        "@wordpress/block-editor": "^12.0.0",
        "@wordpress/data": "^9.0.0",
        "@wordpress/i18n": "^4.0.0"
    }
}
```

## 6. Data Persistence & REST API

### 6.1 Block Data Storage
```php
// src/API/BlockDataController.php
<?php
namespace TMU\API;

class BlockDataController {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('save_post', [$this, 'save_block_data']);
    }
    
    public function register_routes(): void {
        register_rest_route('tmu/v1', '/block-data/(?P<post_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_block_data'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
        
        register_rest_route('tmu/v1', '/block-data/(?P<post_id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'save_block_data_api'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
    }
    
    public function get_block_data($request): array {
        $post_id = $request['post_id'];
        $post_type = get_post_type($post_id);
        
        // Get data from custom tables based on post type
        switch ($post_type) {
            case 'movie':
                return $this->get_movie_data($post_id);
            case 'tv':
                return $this->get_tv_data($post_id);
            case 'drama':
                return $this->get_drama_data($post_id);
            case 'people':
                return $this->get_people_data($post_id);
            default:
                return [];
        }
    }
    
    public function save_block_data($post_id): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        $post_content = get_post_field('post_content', $post_id);
        $blocks = parse_blocks($post_content);
        
        foreach ($blocks as $block) {
            if (strpos($block['blockName'], 'tmu/') === 0) {
                $this->save_block_attributes($post_id, $block);
            }
        }
    }
    
    private function save_block_attributes($post_id, $block): void {
        $block_type = str_replace('tmu/', '', $block['blockName']);
        $attributes = $block['attrs'] ?? [];
        
        switch ($block_type) {
            case 'movie-metadata':
                $this->save_movie_metadata($post_id, $attributes);
                break;
            case 'tv-series-metadata':
                $this->save_tv_metadata($post_id, $attributes);
                break;
            case 'drama-metadata':
                $this->save_drama_metadata($post_id, $attributes);
                break;
            case 'people-metadata':
                $this->save_people_metadata($post_id, $attributes);
                break;
        }
    }
    
    private function save_movie_metadata($post_id, $attributes): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_movies';
        
        $data = [
            'post_id' => $post_id,
            'tmdb_id' => $attributes['tmdb_id'] ?? null,
            'imdb_id' => $attributes['imdb_id'] ?? null,
            'title' => $attributes['title'] ?? null,
            'original_title' => $attributes['original_title'] ?? null,
            'tagline' => $attributes['tagline'] ?? null,
            'overview' => $attributes['overview'] ?? null,
            'runtime' => $attributes['runtime'] ?? null,
            'release_date' => $attributes['release_date'] ?? null,
            'status' => $attributes['status'] ?? null,
            'budget' => $attributes['budget'] ?? null,
            'revenue' => $attributes['revenue'] ?? null,
            'homepage' => $attributes['homepage'] ?? null,
            'poster_path' => $attributes['poster_path'] ?? null,
            'backdrop_path' => $attributes['backdrop_path'] ?? null,
            'tmdb_vote_average' => $attributes['tmdb_vote_average'] ?? null,
            'tmdb_vote_count' => $attributes['tmdb_vote_count'] ?? null,
            'tmdb_popularity' => $attributes['tmdb_popularity'] ?? null,
            'adult' => $attributes['adult'] ?? false,
            'video' => $attributes['video'] ?? false,
        ];
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE post_id = %d",
            $post_id
        ));
        
        if ($existing) {
            $wpdb->update($table_name, $data, ['post_id' => $post_id]);
        } else {
            $wpdb->insert($table_name, $data);
        }
    }
}
```

## 7. Block Styling

### 7.1 Editor Styles
```scss
// assets/src/blocks/editor.scss
.tmu-movie-metadata-block {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
    
    .tmu-metadata-preview {
        h3 {
            margin: 0 0 15px 0;
            color: #1e1e1e;
        }
        
        img {
            float: left;
            margin: 0 15px 15px 0;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        p {
            margin: 5px 0;
            
            strong {
                color: #666;
            }
        }
        
        &::after {
            content: '';
            display: table;
            clear: both;
        }
    }
}

.tmu-tv-series-metadata-block {
    @extend .tmu-movie-metadata-block;
}

.tmu-drama-metadata-block {
    @extend .tmu-movie-metadata-block;
}

.tmu-people-metadata-block {
    @extend .tmu-movie-metadata-block;
}

.tmu-episode-metadata-block {
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 15px;
    margin: 15px 0;
    background: #f9f9f9;
    
    .tmu-episode-preview {
        h4 {
            margin: 0 0 10px 0;
            color: #1e1e1e;
        }
        
        .episode-info {
            display: flex;
            gap: 20px;
            margin-bottom: 10px;
            
            .episode-number {
                font-weight: bold;
                color: #0073aa;
            }
            
            .air-date {
                color: #666;
            }
        }
        
        .episode-overview {
            color: #444;
            line-height: 1.5;
        }
    }
}
```

### 7.2 Frontend Styles
```scss
// assets/src/blocks/frontend.scss
.tmu-movie-metadata {
    .movie-info {
        display: grid;
        grid-template-columns: 200px 1fr;
        gap: 20px;
        margin: 20px 0;
        
        @media (max-width: 768px) {
            grid-template-columns: 1fr;
        }
        
        .movie-poster {
            img {
                width: 100%;
                height: auto;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
        }
        
        .movie-details {
            .movie-title {
                font-size: 2em;
                margin: 0 0 10px 0;
                color: #1e1e1e;
            }
            
            .movie-tagline {
                font-style: italic;
                color: #666;
                margin-bottom: 15px;
            }
            
            .movie-overview {
                line-height: 1.6;
                margin-bottom: 20px;
            }
            
            .movie-meta {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                
                .meta-item {
                    .meta-label {
                        font-weight: bold;
                        color: #666;
                        display: block;
                        margin-bottom: 5px;
                    }
                    
                    .meta-value {
                        color: #1e1e1e;
                    }
                }
            }
        }
    }
}
```

## 8. Implementation Timeline

### Phase 1: Core Block System (Week 1-2)
- Set up block registration system
- Create base block classes
- Implement webpack build system
- Create basic movie metadata block

### Phase 2: Content Blocks (Week 3-4)
- TV Series metadata block
- Drama metadata block
- People metadata block
- Data persistence system

### Phase 3: Episode Management (Week 5)
- TV Episode metadata block
- Drama Episode metadata block
- Season metadata block
- Episode relationships

### Phase 4: Media & Taxonomy Blocks (Week 6)
- Video metadata block
- Taxonomy image blocks
- FAQ blocks
- Content curation blocks

### Phase 5: TMDB Integration (Week 7)
- TMDB sync blocks
- API integration
- Data fetching utilities
- Auto-population features

### Phase 6: Testing & Optimization (Week 8)
- Block testing
- Performance optimization
- Accessibility compliance
- Documentation completion

## Success Metrics

- **Functionality**: 100% feature parity with Meta Box plugin
- **Performance**: Block loading time < 200ms
- **User Experience**: Intuitive block interface
- **Data Integrity**: Zero data loss during migration
- **Compatibility**: Works with all WordPress themes
- **Accessibility**: WCAG 2.1 AA compliance
- **Mobile**: Responsive block editor interface

This modern Gutenberg block system provides a future-proof, maintainable solution that leverages WordPress's native block editor while preserving all existing functionality.