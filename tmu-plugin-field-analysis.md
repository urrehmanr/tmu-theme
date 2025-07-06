# TMU Plugin Field Analysis & Theme Implementation Guide

## Overview
This document provides a comprehensive analysis of the TMU plugin's field structure to ensure our theme implementation captures every field, including complex cast/crew relationships, departments, jobs, and cross-post-type relationships.

## Field Storage Structure
All fields use `storage_type: 'custom_table'` with dedicated database tables:
- `tmu_movies` - Movie metadata
- `tmu_tv_series` - TV series metadata  
- `tmu_tv_series_seasons` - Season metadata
- `tmu_tv_series_episodes` - Episode metadata
- `tmu_people` - People metadata
- `tmu_dramas` - Drama metadata (similar to movies)

## Post Types and Their Fields

### 1. Movie Fields (`movie` post type)
**Storage Table**: `tmu_movies`

#### Basic Metadata Fields
- `tmdb_id` (text) - TMDB API ID
- `release_date` (date) - Movie release date
- `original_title` (text) - Original title
- `tagline` (text) - Movie tagline
- `production_house` (text) - Production company
- `streaming_platforms` (text) - Where to watch URLs
- `runtime` (text) - Movie duration
- `certification` (select) - Rating with 40+ options (U/A, G, PG, R, NC-17, TV-MA, etc.)
- `revenue` (text) - Box office revenue in USD
- `budget` (text) - Production budget in USD
- `popularity` (text) - Popularity score

#### Star Cast Group Field
**Field**: `star_cast` (group, cloneable, max 4)
- `person` (post relationship to `people` post type)
- `character` (text) - Character name

#### Complex Credits System
**Field**: `credits` (group container)

##### Cast Sub-Group
**Field**: `cast` (nested group, cloneable)
- `person` (post relationship to `people` post type)
- `department` (select) - Fixed to "Acting"
- `acting_job` (text) - Character/role description

##### Crew Sub-Group  
**Field**: `crew` (nested group, cloneable)
- `person` (post relationship to `people` post type)
- `department` (select) - 12 departments:
  - Acting
  - Directing  
  - Writing
  - Sound
  - Camera
  - Art
  - Visual Effects
  - Editing
  - Lighting
  - Production
  - Costume & Make-Up
  - Crew
  - Actors

##### Department-Specific Job Fields (Conditional)
Each department has its own job dropdown with dozens to hundreds of specific roles:

**Directing Jobs** (27 options):
- Director, Co-Director, First Assistant Director, Script Supervisor, etc.

**Crew Jobs** (200+ options):
- Special Effects, Stunts, Choreographer, Technical Advisor, etc.

**Production Jobs** (100+ options):
- Producer, Executive Producer, Line Producer, Casting Director, etc.

**Writing Jobs** (40+ options):
- Writer, Screenplay, Author, Story Editor, etc.

**Sound Jobs** (80+ options):
- Sound Designer, Music Composer, Sound Mixer, etc.

**Costume & Make-Up Jobs** (70+ options):
- Costume Designer, Makeup Artist, Hairstylist, etc.

**Lighting Jobs** (30+ options):
- Gaffer, Lighting Technician, Electrician, etc.

**Camera Jobs** (80+ options):
- Director of Photography, Camera Operator, Focus Puller, etc.

**Art Jobs** (100+ options):
- Production Designer, Art Director, Set Designer, etc.

**Visual Effects Jobs** (120+ options):
- VFX Supervisor, 3D Artist, Compositor, etc.

**Editing Jobs** (40+ options):
- Editor, Colorist, Assistant Editor, etc.

**Actors Jobs** (5 options):
- Actor, Cameo, Special Guest, Voice, Stunt Double

#### Media Fields
- `videos` (post relationship to `video` post type, multiple)
- `images` (image_advanced, unlimited)

### 2. TV Series Fields (`tv` post type)
**Storage Table**: `tmu_tv_series`

#### Basic Metadata Fields
- `tmdb_id` (text)
- `release_date` (date)
- `original_title` (text)
- `finished` (checkbox) - Series completion status
- `tagline` (text)
- `production_house` (text)
- `streaming_platforms` (text)
- `schedule_time` (text) - Airing schedule
- `runtime` (text)
- `certification` (select) - Same 40+ options as movies
- `revenue` (text)
- `budget` (text)
- `popularity` (text)

#### Where To Watch Group
**Field**: `where_to_watch` (group, cloneable)
- `channel` (taxonomy relationship to `network` taxonomy)
- `url` (text) - Channel URL

#### Cast/Crew System
**Identical to movies** - Same star_cast and credits structure with all departments and jobs

### 3. TV Season Fields (`season` post type)
**Storage Table**: `tmu_tv_series_seasons`

#### Season Metadata
- `season_no` (number) - Season number
- `season_name` (text) - Season title
- `tv_series` (post relationship to `tv` post type, required)
- `air_date` (date) - Season premiere date

### 4. TV Episode Fields (`episode` post type)
**Storage Table**: `tmu_tv_series_episodes`

#### Episode Metadata
- `tv_series` (post relationship to `tv` post type)
- `season_no` (number) - Season number
- `episode_no` (number) - Episode number
- `episode_title` (text) - Episode title
- `air_date` (date) - Episode air date
- `episode_type` (select) - 4 options: standard, finale, mid_season, special
- `runtime` (text) - Episode duration
- `overview` (textarea) - Episode description

#### Cast/Crew System
**Identical to movies** - Same credits structure with cast/crew groups and all department jobs

### 5. People Fields (`people` post type)
**Storage Table**: `tmu_people`

#### Personal Information
- `known_for` (post relationship to `tv`, `movie`, `drama` post types, multiple)
- `tmdb_id` (text)
- `gender` (select) - Male, Female, Not Specified
- `nick_name` (text)
- `marital_status` (select) - 8 options: Single, Married, Divorced, etc.
- `profession` (text)
- `date_of_birth` (date)
- `birthplace` (text)
- `dead_on` (date) - Death date
- `popularity` (text)

#### Basic Info Group
**Field**: `basic` (group container)
- `height` (text)
- `weight` (text)

##### Parents Sub-Group
**Field**: `parents` (nested group)
- `father` (text)
- `mother` (text)

##### Family Relationships
- `spouse` (post relationship to `people` post type, conditional on marital_status)
- `siblings` (text)

#### Social Media Group
**Field**: `social_media_account` (group, cloneable, max 4)
- `platform` (select) - Facebook, Instagram, YouTube, X
- `url` (url) - Profile URL

#### Additional Fields
- `net_worth` (text)
- `videos` (post relationship to `video` post type, multiple)
- `photos` (image_advanced, unlimited)

### 6. Drama Fields (`drama` post type)
**Expected to be identical to movies** - Same structure with cast/crew system

## Critical Implementation Requirements

### 1. Cast/Crew System Architecture
The most complex aspect is the cast/crew system that appears across all content types:

#### WordPress Block Implementation
```php
// Cast/Crew Block Structure
register_block_type('tmu/cast-crew-manager', [
    'attributes' => [
        'cast_members' => [
            'type' => 'array',
            'items' => [
                'person_id' => 'number',
                'character' => 'string',
                'department' => 'string',
                'job' => 'string'
            ]
        ],
        'crew_members' => [
            'type' => 'array', 
            'items' => [
                'person_id' => 'number',
                'department' => 'string',
                'job' => 'string'
            ]
        ]
    ]
]);
```

#### Department/Job Constants
Create comprehensive constants file with all job options:

```php
// includes/config/departments-jobs.php
class TMU_Departments_Jobs {
    const DEPARTMENTS = [
        'acting' => 'Acting',
        'directing' => 'Directing',
        'writing' => 'Writing',
        // ... all 12 departments
    ];
    
    const DIRECTING_JOBS = [
        'director' => 'Director',
        'co_director' => 'Co-Director',
        'first_assistant_director' => 'First Assistant Director',
        // ... all 27 directing jobs
    ];
    
    // ... separate arrays for each department's jobs
}
```

### 2. Database Schema Preservation
**CRITICAL**: All existing database tables must be preserved exactly:
- `tmu_movies`
- `tmu_tv_series`
- `tmu_tv_series_seasons`
- `tmu_tv_series_episodes`
- `tmu_people`
- `tmu_dramas`

### 3. Custom Field Implementation Strategy

#### Option 1: Gutenberg Blocks (Recommended)
- Cast/Crew Manager Block
- Star Cast Block
- Social Media Block
- Video Gallery Block
- Image Gallery Block

#### Option 2: ACF Pro Alternative
- Replicate exact field structure
- Maintain conditional logic
- Preserve cloneable groups

#### Option 3: Custom Meta Boxes
- Native WordPress implementation
- Custom UI for complex fields
- AJAX-powered person selection

### 4. Person Post Type Relationships
Critical relationship mappings:
- Movies → People (cast/crew)
- TV Series → People (cast/crew)
- Episodes → People (cast/crew)
- People → Known For (movies/TV/dramas)
- People → Spouse (people)

### 5. Taxonomy Integration
- `network` taxonomy for TV channels
- Standard taxonomies (genre, etc.)
- Custom taxonomies as needed

### 6. TMDB Integration
All content types have `tmdb_id` fields for API integration:
- Automated cast/crew import
- Metadata synchronization
- Image/video fetching

## Theme Implementation Checklist

### Phase 1: Core Structure
- [ ] Create all post types with identical field structures
- [ ] Implement database schema migration
- [ ] Set up cast/crew relationship system
- [ ] Create department/job constants

### Phase 2: Admin Interface
- [ ] Cast/crew manager blocks
- [ ] Person selection interface
- [ ] Department/job dropdowns
- [ ] Media upload interfaces

### Phase 3: Frontend Templates
- [ ] Cast/crew display templates
- [ ] Person profile templates
- [ ] Relationship navigation
- [ ] Media galleries

### Phase 4: Advanced Features
- [ ] TMDB API integration
- [ ] Automated data import
- [ ] Search/filter by cast/crew
- [ ] Advanced relationships

## Missing Fields Analysis

Based on the plugin analysis, these fields are properly included:
- ✅ All cast/crew department jobs (200+ jobs per department)
- ✅ TV season fields (season_no, season_name, tv_series, air_date)
- ✅ TV episode fields (episode_no, episode_title, air_date, runtime)
- ✅ People family relationships (parents, spouse, siblings)
- ✅ Social media accounts (platform, URL)
- ✅ All certification options (40+ rating systems)
- ✅ Complex nested group structures
- ✅ Post type relationships
- ✅ Conditional field visibility

## Conclusion

The TMU plugin has an incredibly sophisticated field structure that requires careful implementation. The cast/crew system alone involves 12 departments with hundreds of specific job roles. Our theme implementation must preserve this complexity while modernizing the interface and moving to Gutenberg blocks.

The key success factors are:
1. Exact database schema preservation
2. Complete cast/crew system recreation
3. All person relationships maintained
4. Department/job data completeness
5. Conditional field logic preservation
6. TMDB integration compatibility