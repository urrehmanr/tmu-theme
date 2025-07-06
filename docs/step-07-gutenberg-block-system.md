# Step 07: Gutenberg Block System - Complete Implementation

## Purpose
Implement a comprehensive Gutenberg block system to replace the Meta Box plugin dependency while maintaining 100% functionality. The system uses modern WordPress Block Editor (Gutenberg) API to create custom blocks for post type fields, episode management, taxonomy fields, content blocks, and TMDB sync utilities.

## Dependencies from Previous Steps
- **[REQUIRED]** Post types registration [FROM STEP 5] - Blocks attach to specific post types
- **[REQUIRED]** Taxonomies registration [FROM STEP 6] - Taxonomy integration in blocks
- **[REQUIRED]** PSR-4 autoloading [FROM STEP 4] - PHP class autoloading
- **[REQUIRED]** Asset compilation system [FROM STEP 1] - JavaScript/CSS build process
- **[REQUIRED]** Theme core initialization [FROM STEP 1] - WordPress integration

## Files Created in This Step
- **[CREATE NEW]** `includes/classes/Blocks/BlockRegistry.php` - Main block registration system
- **[CREATE NEW]** `includes/classes/Blocks/BaseBlock.php` - Base block class
- **[CREATE NEW]** `includes/classes/Blocks/MovieMetadataBlock.php` - Movie metadata block
- **[CREATE NEW]** `includes/classes/Blocks/TvSeriesMetadataBlock.php` - TV series metadata block
- **[CREATE NEW]** `includes/classes/Blocks/DramaMetadataBlock.php` - Drama metadata block
- **[CREATE NEW]** `includes/classes/Blocks/PeopleMetadataBlock.php` - People metadata block
- **[CREATE NEW]** `includes/classes/Blocks/TvEpisodeMetadataBlock.php` - TV episode block
- **[CREATE NEW]** `includes/classes/Blocks/DramaEpisodeMetadataBlock.php` - Drama episode block
- **[CREATE NEW]** `includes/classes/Blocks/SeasonMetadataBlock.php` - Season metadata block
- **[CREATE NEW]** `includes/classes/Blocks/VideoMetadataBlock.php` - Video metadata block
- **[CREATE NEW]** `includes/classes/Blocks/TaxonomyImageBlock.php` - Taxonomy image block
- **[CREATE NEW]** `includes/classes/Blocks/TaxonomyFaqsBlock.php` - Taxonomy FAQs block
- **[CREATE NEW]** `includes/classes/Blocks/BlogPostsListBlock.php` - Blog posts list block
- **[CREATE NEW]** `includes/classes/Blocks/TrendingContentBlock.php` - Trending content block
- **[CREATE NEW]** `includes/classes/Blocks/TmdbSyncBlock.php` - TMDB sync block
- **[CREATE NEW]** `assets/src/blocks/MovieMetadataBlock.jsx` - Movie block React component
- **[CREATE NEW]** `assets/src/blocks/TvSeriesMetadataBlock.jsx` - TV series block React component
- **[CREATE NEW]** `assets/src/blocks/DramaMetadataBlock.jsx` - Drama block React component
- **[CREATE NEW]** `assets/src/blocks/PeopleMetadataBlock.jsx` - People block React component
- **[CREATE NEW]** `assets/src/blocks/EpisodeMetadataBlock.jsx` - Episode block React component
- **[CREATE NEW]** `assets/src/blocks/TaxonomyBlocks.jsx` - Taxonomy block components
- **[CREATE NEW]** `assets/src/blocks/ContentBlocks.jsx` - Content curation block components
- **[CREATE NEW]** `assets/src/blocks/TmdbSyncBlock.jsx` - TMDB sync block component
- **[CREATE NEW]** `assets/src/scss/blocks/` - Block-specific SCSS files
- **[UPDATE]** `webpack.config.js` - Block build configuration
- **[CREATE NEW]** `tests/Blocks/BlocksTest.php` - Block system testing

## Tailwind CSS Status
**INTEGRATES** - Blocks use compiled Tailwind CSS for consistent styling with theme design system

## Architecture Overview

### Directory Structure with File Status
```
includes/classes/Blocks/                          # [CREATE DIR - STEP 7] Block system classes
├── BlockRegistry.php       # [CREATE NEW - STEP 7] Main block registration system
├── BaseBlock.php          # [CREATE NEW - STEP 7] Base block class - Shared functionality
├── MovieMetadataBlock.php  # [CREATE NEW - STEP 7] Movie metadata block
├── TvSeriesMetadataBlock.php # [CREATE NEW - STEP 7] TV series metadata block
├── DramaMetadataBlock.php  # [CREATE NEW - STEP 7] Drama metadata block
├── PeopleMetadataBlock.php # [CREATE NEW - STEP 7] People metadata block
├── TvEpisodeMetadataBlock.php # [CREATE NEW - STEP 7] TV episode block
├── DramaEpisodeMetadataBlock.php # [CREATE NEW - STEP 7] Drama episode block
├── SeasonMetadataBlock.php # [CREATE NEW - STEP 7] Season metadata block
├── VideoMetadataBlock.php  # [CREATE NEW - STEP 7] Video metadata block
├── TaxonomyImageBlock.php  # [CREATE NEW - STEP 7] Taxonomy image block
├── TaxonomyFaqsBlock.php   # [CREATE NEW - STEP 7] Taxonomy FAQs block
├── BlogPostsListBlock.php  # [CREATE NEW - STEP 7] Blog posts list block
├── TrendingContentBlock.php # [CREATE NEW - STEP 7] Trending content block
└── TmdbSyncBlock.php       # [CREATE NEW - STEP 7] TMDB sync block

assets/src/blocks/                               # [CREATE DIR - STEP 7] React block components
├── MovieMetadataBlock.jsx  # [CREATE NEW - STEP 7] Movie block React component
├── TvSeriesMetadataBlock.jsx # [CREATE NEW - STEP 7] TV series block React component
├── DramaMetadataBlock.jsx  # [CREATE NEW - STEP 7] Drama block React component
├── PeopleMetadataBlock.jsx # [CREATE NEW - STEP 7] People block React component
├── EpisodeMetadataBlock.jsx # [CREATE NEW - STEP 7] Episode block React component
├── TaxonomyBlocks.jsx      # [CREATE NEW - STEP 7] Taxonomy block components
├── ContentBlocks.jsx       # [CREATE NEW - STEP 7] Content curation blocks
├── TmdbSyncBlock.jsx       # [CREATE NEW - STEP 7] TMDB sync block component
└── index.js               # [CREATE NEW - STEP 7] Block registration entry point

assets/src/scss/blocks/                          # [CREATE DIR - STEP 7] Block styling
├── movie-metadata.scss     # [CREATE NEW - STEP 7] Movie block styles
├── tv-series-metadata.scss # [CREATE NEW - STEP 7] TV series block styles
├── drama-metadata.scss     # [CREATE NEW - STEP 7] Drama block styles
├── people-metadata.scss    # [CREATE NEW - STEP 7] People block styles
├── episode-metadata.scss   # [CREATE NEW - STEP 7] Episode block styles
├── taxonomy-blocks.scss    # [CREATE NEW - STEP 7] Taxonomy block styles
├── content-blocks.scss     # [CREATE NEW - STEP 7] Content curation styles
└── tmdb-sync.scss         # [CREATE NEW - STEP 7] TMDB sync block styles

tests/Blocks/                                   # [CREATE DIR - STEP 7] Block system tests
└── BlocksTest.php         # [CREATE NEW - STEP 7] Block system testing
```

### **Dependencies from Previous Steps:**
- **[REQUIRED]** Post types [FROM STEP 5] - movie, tv, drama, people, season, episode
- **[REQUIRED]** Taxonomies [FROM STEP 6] - genre, country, language, network, channel
- **[REQUIRED]** Asset compilation [FROM STEP 1] - webpack, Tailwind CSS
- **[REQUIRED]** PSR-4 autoloading [FROM STEP 4] - Block class loading
- **[REQUIRED]** Helper functions [FROM STEP 4] - Utility functions

### **Files Created in Future Steps:**
- **`includes/classes/API/BlockEndpoints.php`** - [CREATE NEW - STEP 9] REST API for blocks
- **`includes/classes/Admin/BlockSettings.php`** - [CREATE NEW - STEP 8] Block configuration
- **`templates/blocks/`** - [CREATE NEW - STEP 10] Block template files

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
- **TypeScript** for type safety (optional)
- **SCSS + Tailwind CSS** for styling
- **webpack** for build process
- **REST API** for data persistence

## 1. Block Registration System

### 1.1 Block Registry (`includes/classes/Blocks/BlockRegistry.php`)
**File Status**: [CREATE NEW - STEP 7]
**File Path**: `tmu-theme/includes/classes/Blocks/BlockRegistry.php`
**Purpose**: Main block registration system that manages all Gutenberg blocks
**Dependencies**: 
- [DEPENDS ON] Individual block classes [CREATE NEW - STEP 7] - All metadata blocks
- [DEPENDS ON] WordPress Block API - register_block_type, enqueue functions
- [DEPENDS ON] Asset compilation [FROM STEP 1] - JavaScript and CSS files
- [DEPENDS ON] PSR-4 autoloading [FROM STEP 4] - Block class loading
**Integration**: Central registration system for all TMU blocks
**Used By**: 
- `includes/classes/ThemeCore.php` [FROM STEP 1] - Theme initialization
- WordPress block editor - Block availability
- Admin post editing interfaces - Block insertion
**Features**: 
- Dynamic block registration
- Asset enqueueing for editor and frontend
- Block category management
- Editor script and style loading
**AI Action**: Create block registry class that manages all TMU Gutenberg blocks

```php
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

### 1.2 Base Block Class (`includes/classes/Blocks/BaseBlock.php`)
**File Status**: [CREATE NEW - STEP 7]
**File Path**: `tmu-theme/includes/classes/Blocks/BaseBlock.php`
**Purpose**: Abstract base class providing shared functionality for all block implementations
**Dependencies**: 
- [DEPENDS ON] WordPress Block API - Block registration functions
- [DEPENDS ON] WordPress translation functions - Block labels
**Integration**: Base class for all TMU blocks
**Used By**: 
- All metadata block classes [CREATE NEW - STEP 7] - Inheritance
- Block registration system [CREATE NEW - STEP 7] - Configuration
**Features**: 
- Abstract methods for attributes and rendering
- Default block configuration
- Shared block properties
- Standardized block interface
**AI Action**: Create abstract base class with common block functionality

```php
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

### 2.1 Movie Metadata Block (`includes/classes/Blocks/MovieMetadataBlock.php`)
**File Status**: [CREATE NEW - STEP 7]
**File Path**: `tmu-theme/includes/classes/Blocks/MovieMetadataBlock.php`
**Purpose**: Movie metadata block handling comprehensive movie data fields
**Dependencies**: 
- [EXTENDS] `BaseBlock.php` [CREATE NEW - STEP 7] - Base block functionality
- [DEPENDS ON] Movie post type [FROM STEP 5] - Content type integration
- [DEPENDS ON] TMDB API integration [FROM STEP 9] - Data synchronization
**Integration**: Movie-specific metadata management block
**Used By**: 
- Movie post editor - Metadata input
- Block registry [CREATE NEW - STEP 7] - Registration
- TMDB sync system [FROM STEP 9] - Data population
**Features**: 
- Comprehensive movie attributes (TMDB ID, IMDB ID, title, overview, etc.)
- Release information management
- Financial data tracking
- Rating and popularity metrics
- Media and external links
**AI Action**: Create movie metadata block class with all movie-specific fields

```php
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

### 2.2 TV Series Metadata Block (`includes/classes/Blocks/TvSeriesMetadataBlock.php`)
**File Status**: [CREATE NEW - STEP 7]
**File Path**: `tmu-theme/includes/classes/Blocks/TvSeriesMetadataBlock.php`
**Purpose**: TV series metadata block handling comprehensive TV show data fields
**Dependencies**: 
- [EXTENDS] `BaseBlock.php` [CREATE NEW - STEP 7] - Base block functionality
- [DEPENDS ON] TV show post type [FROM STEP 5] - Content type integration
- [DEPENDS ON] Network taxonomy [FROM STEP 6] - Network integration
- [DEPENDS ON] TMDB API integration [FROM STEP 9] - Data synchronization
**Integration**: TV series-specific metadata management block
**Used By**: 
- TV show post editor - Metadata input
- Block registry [CREATE NEW - STEP 7] - Registration
- Season/episode management [CREATE NEW - STEP 7] - Hierarchical relationship
**Features**: 
- TV series attributes (name, seasons, episodes, air dates)
- Network and creator information
- Production details
- Season and episode counts
- Series status tracking
**AI Action**: Create TV series metadata block class with all TV-specific fields

```php
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

### 3.1 TV Episode Metadata Block (`includes/classes/Blocks/TvEpisodeMetadataBlock.php`)
**File Status**: [CREATE NEW - STEP 7]
**File Path**: `tmu-theme/includes/classes/Blocks/TvEpisodeMetadataBlock.php`
**Purpose**: TV episode metadata block for individual episode management
**Dependencies**: 
- [EXTENDS] `BaseBlock.php` [CREATE NEW - STEP 7] - Base block functionality
- [DEPENDS ON] Episode post type [FROM STEP 5] - Content type integration
- [DEPENDS ON] TV show post type [FROM STEP 5] - Parent relationship
- [DEPENDS ON] Season management [CREATE NEW - STEP 7] - Hierarchical structure
**Integration**: Episode-specific metadata management within TV shows
**Used By**: 
- Episode post editor - Episode data input
- TV show management - Episode listing
- Season organization - Episode grouping
**Features**: 
- Episode identification (series, season, episode number)
- Episode details (name, overview, air date, runtime)
- Cast and crew information
- Rating and voting data
- TMDB synchronization
**AI Action**: Create TV episode metadata block class with episode-specific fields

```php
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

### 3.2 Drama Episode Metadata Block (`includes/classes/Blocks/DramaEpisodeMetadataBlock.php`)
**File Status**: [CREATE NEW - STEP 7]
**File Path**: `tmu-theme/includes/classes/Blocks/DramaEpisodeMetadataBlock.php`
**Purpose**: Drama episode metadata block for drama series episode management
**Dependencies**: 
- [EXTENDS] `BaseBlock.php` [CREATE NEW - STEP 7] - Base block functionality
- [DEPENDS ON] Drama episode post type [FROM STEP 5] - Content type integration
- [DEPENDS ON] Drama post type [FROM STEP 5] - Parent relationship
- [DEPENDS ON] Channel taxonomy [FROM STEP 6] - Channel integration
**Integration**: Drama episode-specific metadata management
**Used By**: 
- Drama episode post editor - Episode data input
- Drama management - Episode listing
- Channel organization - Episode categorization
**Features**: 
- Episode identification (drama, episode number)
- Episode details (name, overview, air date, runtime)
- Cast and crew information
- Special features tracking
- Channel-specific metadata
**AI Action**: Create drama episode metadata block class with drama-specific fields

```php
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

### 4.1 Movie Metadata Block Component (`assets/src/blocks/MovieMetadataBlock.jsx`)
**File Status**: [CREATE NEW - STEP 7]
**File Path**: `tmu-theme/assets/src/blocks/MovieMetadataBlock.jsx`
**Purpose**: React component for movie metadata block editor interface
**Dependencies**: 
- [DEPENDS ON] WordPress Block API - @wordpress/blocks, @wordpress/components
- [DEPENDS ON] React hooks - useState, useEffect
- [DEPENDS ON] TMDB API endpoints [FROM STEP 9] - Data fetching
- [DEPENDS ON] Tailwind CSS compilation [FROM STEP 1] - Component styling
**Integration**: Frontend editor interface for movie metadata
**Used By**: 
- WordPress block editor - Movie block interface
- Block registration [CREATE NEW - STEP 7] - Component registration
- Movie post editor - Metadata input interface
**Features**: 
- TMDB integration with auto-fetch
- Inspector controls for all movie fields
- Real-time data validation
- Responsive form layouts
- Tailwind CSS styling
**AI Action**: Create React component with comprehensive movie metadata interface

```jsx
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

## AI Implementation Instructions for Step 7

### **Prerequisites Check**
Before implementing Step 7, verify these files exist from previous steps:
- **[REQUIRED]** Post types registration [FROM STEP 5] - Blocks attach to specific post types
- **[REQUIRED]** Taxonomies registration [FROM STEP 6] - Taxonomy integration in blocks
- **[REQUIRED]** PSR-4 autoloading [FROM STEP 4] - PHP class autoloading
- **[REQUIRED]** Asset compilation system [FROM STEP 1] - webpack, Tailwind CSS
- **[REQUIRED]** Theme core initialization [FROM STEP 1] - WordPress integration

### **Implementation Order for AI Models**

#### **Phase 1: Create Directories** (Required First)
```bash
mkdir -p tmu-theme/includes/classes/Blocks
mkdir -p tmu-theme/assets/src/blocks
mkdir -p tmu-theme/assets/src/scss/blocks
mkdir -p tmu-theme/tests/Blocks
```

#### **Phase 2: Base Block System** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/Blocks/BaseBlock.php` - Base block functionality
2. **[CREATE SECOND]** `includes/classes/Blocks/BlockRegistry.php` - Block registration system
3. **[UPDATE THIRD]** `webpack.config.js` - Add block build configuration
4. **[UPDATE FOURTH]** `package.json` - Add WordPress block dependencies

#### **Phase 3: Content Metadata Blocks** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/Blocks/MovieMetadataBlock.php` - Movie block PHP
2. **[CREATE SECOND]** `includes/classes/Blocks/TvSeriesMetadataBlock.php` - TV series block PHP
3. **[CREATE THIRD]** `includes/classes/Blocks/DramaMetadataBlock.php` - Drama block PHP
4. **[CREATE FOURTH]** `includes/classes/Blocks/PeopleMetadataBlock.php` - People block PHP

#### **Phase 4: Episode Management Blocks** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/Blocks/TvEpisodeMetadataBlock.php` - TV episode block
2. **[CREATE SECOND]** `includes/classes/Blocks/DramaEpisodeMetadataBlock.php` - Drama episode block
3. **[CREATE THIRD]** `includes/classes/Blocks/SeasonMetadataBlock.php` - Season block
4. **[CREATE FOURTH]** `includes/classes/Blocks/VideoMetadataBlock.php` - Video block

#### **Phase 5: Specialty Blocks** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/Blocks/TaxonomyImageBlock.php` - Taxonomy image block
2. **[CREATE SECOND]** `includes/classes/Blocks/TaxonomyFaqsBlock.php` - Taxonomy FAQs block
3. **[CREATE THIRD]** `includes/classes/Blocks/BlogPostsListBlock.php` - Blog posts list
4. **[CREATE FOURTH]** `includes/classes/Blocks/TrendingContentBlock.php` - Trending content
5. **[CREATE FIFTH]** `includes/classes/Blocks/TmdbSyncBlock.php` - TMDB sync block

#### **Phase 6: React Components** (Exact Order)
1. **[CREATE FIRST]** `assets/src/blocks/index.js` - Block registration entry point
2. **[CREATE SECOND]** `assets/src/blocks/MovieMetadataBlock.jsx` - Movie block React component
3. **[CREATE THIRD]** `assets/src/blocks/TvSeriesMetadataBlock.jsx` - TV series React component
4. **[CREATE FOURTH]** `assets/src/blocks/DramaMetadataBlock.jsx` - Drama React component
5. **[CREATE FIFTH]** `assets/src/blocks/PeopleMetadataBlock.jsx` - People React component
6. **[CREATE SIXTH]** `assets/src/blocks/EpisodeMetadataBlock.jsx` - Episode React component
7. **[CREATE SEVENTH]** `assets/src/blocks/TaxonomyBlocks.jsx` - Taxonomy React components
8. **[CREATE EIGHTH]** `assets/src/blocks/ContentBlocks.jsx` - Content curation components
9. **[CREATE NINTH]** `assets/src/blocks/TmdbSyncBlock.jsx` - TMDB sync component

#### **Phase 7: Block Styling** (Exact Order)
1. **[CREATE FIRST]** `assets/src/scss/blocks/editor.scss` - Editor styles
2. **[CREATE SECOND]** `assets/src/scss/blocks/frontend.scss` - Frontend styles
3. **[CREATE THIRD]** Individual block SCSS files for each block type

#### **Phase 8: Data Persistence** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/API/BlockDataController.php` - Data persistence
2. **[CREATE SECOND]** Block data storage methods for each post type

#### **Phase 9: Testing** (Exact Order)
1. **[CREATE FIRST]** `tests/Blocks/BlocksTest.php` - Block system tests

#### **Phase 10: Integration** (Final)
1. **[UPDATE]** `includes/classes/ThemeCore.php` - Include block registry

### **Key Implementation Notes**
- **Block Registration**: All blocks must be registered through BlockRegistry
- **Data Persistence**: Block data is stored in custom TMU tables, not post meta
- **TMDB Integration**: Blocks support automatic data fetching from TMDB API
- **Responsive Design**: All block interfaces must work on mobile devices
- **Tailwind CSS**: Use Tailwind utility classes for all block styling

### **Block Architecture Principles**
1. **Separation of Concerns**: PHP handles data persistence, React handles UI
2. **Data Consistency**: All data stored in TMU custom tables
3. **TMDB Sync**: Blocks can auto-populate from TMDB API
4. **Responsive Design**: Mobile-first block interfaces
5. **Accessibility**: WCAG 2.1 AA compliance for all blocks

### **Critical Dependencies**
- **WordPress Block API**: @wordpress/blocks, @wordpress/components, @wordpress/block-editor
- **React**: JSX components for block interfaces
- **webpack**: Asset compilation and bundling
- **Babel**: JSX and ES6+ transpilation
- **SCSS**: Block styling with Tailwind CSS integration

### **Block Data Flow**
```
1. User enters data in React component (Block Editor)
2. Data stored in block attributes
3. On save_post, PHP extracts block data
4. Data saved to TMU custom tables
5. Frontend renders from database, not block attributes
```

### **Testing Requirements**
1. **Block Registration** - Verify all blocks register correctly
2. **React Components** - Verify block interfaces render properly
3. **Data Persistence** - Verify data saves to correct tables
4. **TMDB Integration** - Verify API data fetching works
5. **Responsive Design** - Verify mobile compatibility
6. **Accessibility** - Verify WCAG compliance

### **Development Workflow**
```bash
# Install dependencies
npm install

# Start development server
npm run dev

# Build for production
npm run build

# Run tests
composer test tests/Blocks/BlocksTest.php
```

### **Common Issues and Solutions**
1. **Block Not Registering**: Check BlockRegistry includes the block class
2. **React Component Errors**: Verify WordPress dependencies are loaded
3. **Data Not Saving**: Check save_post hooks and table structure
4. **TMDB Errors**: Verify API endpoints and authentication
5. **Styling Issues**: Check webpack compilation and CSS loading

### **Verification Commands**
```bash
# Build blocks
npm run build

# Check block assets generated
ls -la assets/js/blocks.js assets/css/blocks.css

# Test block registration in WordPress admin
# Go to post editor and verify TMU blocks appear in inserter

# Test data persistence
# Create content with blocks and verify data in database tables
```

### **Post-Implementation Checklist**
- [ ] All block PHP classes created
- [ ] BlockRegistry implemented and functional
- [ ] All React components created
- [ ] Block styling implemented with Tailwind CSS
- [ ] Data persistence working correctly
- [ ] TMDB integration functional
- [ ] All blocks appear in WordPress editor
- [ ] Block data saves to custom tables
- [ ] Responsive design verified
- [ ] Accessibility compliance met
- [ ] Tests passing
- [ ] ThemeCore integration complete

This modern Gutenberg block system provides a future-proof, maintainable solution that leverages WordPress's native block editor while preserving all existing functionality.

**Step 7 Status**: ✅ READY FOR AI IMPLEMENTATION
**Dependencies**: Steps 1, 4, 5, 6 must be completed
**Next Step**: Step 8 - Admin UI and Meta Boxes