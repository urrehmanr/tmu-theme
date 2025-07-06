# TMU Theme - AI Implementation Guide

## Overview

This guide provides precise instructions for AI models to implement the TMU theme. Every file is clearly marked with its status, dependencies, implementation order, and purpose.

## File Status Legend

- **[CREATE NEW - STEP X]**: Create this file in the specified step
- **[CREATE DIR - STEP X]**: Create this directory in the specified step  
- **[AUTO-GENERATED]**: File is created automatically by build tools
- **[UPDATE - STEP X]**: Modify existing file in the specified step
- **[REFERENCE ONLY]**: File mentioned for context, no action needed
- **[OPTIONAL]**: File is optional based on requirements
- **[FIRST TIME]**: This is the first implementation of this technology
- **[DEPENDS ON]**: Lists file dependencies

## Implementation Phases

### Phase 1: Directory Structure
**Status**: [REQUIRED FIRST] - Must be completed before any files are created

```bash
mkdir wp-content/themes/tmu
mkdir -p wp-content/themes/tmu/{assets/src/{css,js},assets/build/{css,js},includes/classes/{Admin,API,Database,Frontend,PostTypes,Taxonomies,Blocks,Utils},includes/config,includes/migrations,includes/helpers,templates/{archive,single,parts/{components,header,footer,content},blocks,search},languages}
```

### Phase 2: Configuration Files (Step 1)

#### 1. package.json
- **Status**: [CREATE NEW - STEP 1]
- **Path**: `tmu-theme/package.json`
- **Dependencies**: None
- **Purpose**: FIRST TIME Tailwind CSS setup, Node.js dependencies
- **Tailwind Status**: FIRST TIME IMPLEMENTATION
- **AI Action**: Create file with exact content from documentation

#### 2. tailwind.config.js
- **Status**: [CREATE NEW - STEP 1]
- **Path**: `tmu-theme/tailwind.config.js`
- **Dependencies**: [DEPENDS ON] package.json
- **Purpose**: FIRST TIME Tailwind CSS configuration
- **Tailwind Status**: FIRST TIME IMPLEMENTATION - Core configuration
- **AI Action**: Create file with TMU-specific colors and settings

#### 3. webpack.config.js
- **Status**: [CREATE NEW - STEP 1]
- **Path**: `tmu-theme/webpack.config.js`
- **Dependencies**: [DEPENDS ON] package.json
- **Purpose**: Asset bundling and compilation
- **Tailwind Status**: INTEGRATES - Compiles Tailwind CSS
- **AI Action**: Create file for development and production builds

#### 4. composer.json
- **Status**: [CREATE NEW - STEP 1]
- **Path**: `tmu-theme/composer.json`
- **Dependencies**: None
- **Purpose**: PHP dependency management, PSR-4 autoloading
- **AI Action**: Create file with TMU namespace configuration

#### 5. .gitignore
- **Status**: [CREATE NEW - STEP 1]
- **Path**: `tmu-theme/.gitignore`
- **Dependencies**: None
- **Purpose**: Version control ignore rules
- **AI Action**: Create file with Node.js and build artifact ignores

#### 6. style.css
- **Status**: [CREATE NEW - STEP 1]
- **Path**: `tmu-theme/style.css`
- **Dependencies**: None
- **Purpose**: WordPress theme identification - REQUIRED by WordPress
- **Tailwind Status**: REFERENCED - Mentions Tailwind in description
- **AI Action**: Create WordPress theme header with Tailwind reference

### Phase 3: Core Configuration Files (Step 1)

#### 1. includes/config/constants.php
- **Status**: [CREATE NEW - STEP 1]
- **Path**: `tmu-theme/includes/config/constants.php`
- **Dependencies**: None
- **Purpose**: Central theme constants, database table names
- **AI Action**: Create file with all TMU constants

#### 2. includes/config/database.php
- **Status**: [CREATE NEW - STEP 1]
- **Path**: `tmu-theme/includes/config/database.php`
- **Dependencies**: [DEPENDS ON] constants.php
- **Purpose**: Database schema definitions preserving plugin structure
- **AI Action**: Create file with existing plugin table schemas

#### 3. includes/config/assets.php
- **Status**: [CREATE NEW - STEP 1]
- **Path**: `tmu-theme/includes/config/assets.php`
- **Dependencies**: [DEPENDS ON] constants.php
- **Purpose**: Asset management configuration
- **Tailwind Status**: CONFIGURES - Asset loading configuration
- **AI Action**: Create file with CSS/JS asset configurations

### Phase 4: Asset Files (Step 1)

#### 1. assets/src/css/main.css
- **Status**: [CREATE NEW - STEP 1]
- **Path**: `tmu-theme/assets/src/css/main.css`
- **Dependencies**: [DEPENDS ON] tailwind.config.js
- **Purpose**: FIRST TIME Tailwind CSS implementation with TMU components
- **Tailwind Status**: FIRST TIME IMPLEMENTATION - Core CSS file
- **Output**: Compiled to assets/build/css/main.css
- **AI Action**: Create file with @tailwind directives and TMU components

#### 2. assets/src/css/admin.css
- **Status**: [CREATE NEW - STEP 1]
- **Path**: `tmu-theme/assets/src/css/admin.css`
- **Dependencies**: [DEPENDS ON] tailwind.config.js
- **Purpose**: Admin-specific Tailwind styles
- **Tailwind Status**: FIRST TIME IMPLEMENTATION - Admin styling
- **Output**: Compiled to assets/build/css/admin.css
- **AI Action**: Create file with admin-specific Tailwind components

#### 3. assets/src/js/main.js
- **Status**: [CREATE NEW - STEP 1]
- **Path**: `tmu-theme/assets/src/js/main.js`
- **Dependencies**: [DEPENDS ON] main.css, package.json
- **Purpose**: Frontend JavaScript with Alpine.js integration
- **Features**: Search, filters, AJAX, lazy loading
- **Output**: Compiled to assets/build/js/main.js
- **AI Action**: Create file with Alpine.js and frontend functionality

#### 4. assets/src/js/admin.js
- **Status**: [CREATE NEW - STEP 1]
- **Path**: `tmu-theme/assets/src/js/admin.js`
- **Dependencies**: [DEPENDS ON] package.json
- **Purpose**: Admin functionality, TMDB integration prep
- **Output**: Compiled to assets/build/js/admin.js
- **AI Action**: Create file with admin JavaScript functionality

### Phase 5: Core PHP Classes (Step 1)

#### 1. includes/classes/ThemeCore.php
- **Status**: [CREATE NEW - STEP 1]
- **Path**: `tmu-theme/includes/classes/ThemeCore.php`
- **Dependencies**: [DEPENDS ON] All config files
- **Purpose**: Main theme class, Tailwind CSS asset management
- **Tailwind Status**: INTEGRATES - Loads compiled Tailwind CSS
- **AI Action**: Create singleton class with asset enqueuing

#### 2. functions.php
- **Status**: [CREATE NEW - STEP 1]
- **Path**: `tmu-theme/functions.php`
- **Dependencies**: [DEPENDS ON] ThemeCore.php, config files
- **Purpose**: WordPress theme bootstrap - REQUIRED by WordPress
- **Tailwind Status**: INTEGRATES - Initializes Tailwind loading
- **AI Action**: Create bootstrap file that initializes ThemeCore

### Phase 6: Build Process (Step 1)

#### Auto-Generated Files (Do Not Create Manually)
- **assets/build/css/main.css** - [AUTO-GENERATED] by Webpack
- **assets/build/css/admin.css** - [AUTO-GENERATED] by Webpack  
- **assets/build/js/main.js** - [AUTO-GENERATED] by Webpack
- **assets/build/js/admin.js** - [AUTO-GENERATED] by Webpack
- **vendor/** - [AUTO-GENERATED] by Composer
- **node_modules/** - [AUTO-GENERATED] by npm
- **composer.lock** - [AUTO-GENERATED] by Composer
- **package-lock.json** - [AUTO-GENERATED] by npm

#### Required Commands After File Creation:
```bash
# Install Node.js dependencies
npm install

# Install PHP dependencies  
composer install

# Build assets for development
npm run dev

# Or build for production
npm run build
```

## Files for Future Steps

### Step 2: Theme Initialization
- **includes/classes/Admin/Settings.php** - [CREATE NEW - STEP 2]

### Step 3: Database Migration System
- **includes/classes/Database/Migration.php** - [CREATE NEW - STEP 3]
- **includes/classes/Database/Schema.php** - [CREATE NEW - STEP 3]
- **includes/classes/Database/QueryBuilder.php** - [CREATE NEW - STEP 3]
- **includes/classes/Database/DataManager.php** - [CREATE NEW - STEP 3]

### Step 5: Post Types Registration
- **includes/classes/PostTypes/PostTypeManager.php** - [CREATE NEW - STEP 5]
- **includes/classes/PostTypes/Movie.php** - [CREATE NEW - STEP 5]
- **includes/classes/PostTypes/TVShow.php** - [CREATE NEW - STEP 5]
- **includes/classes/PostTypes/Drama.php** - [CREATE NEW - STEP 5]
- **includes/classes/PostTypes/People.php** - [CREATE NEW - STEP 5]
- **includes/classes/PostTypes/Season.php** - [CREATE NEW - STEP 5]
- **includes/classes/PostTypes/Episode.php** - [CREATE NEW - STEP 5]
- **includes/classes/PostTypes/Video.php** - [CREATE NEW - STEP 5]

### Step 6: Taxonomies Registration
- **includes/classes/Taxonomies/TaxonomyManager.php** - [CREATE NEW - STEP 6]
- **includes/classes/Taxonomies/Genre.php** - [CREATE NEW - STEP 6]
- **includes/classes/Taxonomies/Country.php** - [CREATE NEW - STEP 6]
- **includes/classes/Taxonomies/Language.php** - [CREATE NEW - STEP 6]
- **includes/classes/Taxonomies/Network.php** - [CREATE NEW - STEP 6]

### Step 7: Gutenberg Block System
- **includes/classes/Blocks/BlockManager.php** - [CREATE NEW - STEP 7]
- **includes/classes/Blocks/MovieBlock.php** - [CREATE NEW - STEP 7]
- **includes/classes/Blocks/SearchBlock.php** - [CREATE NEW - STEP 7]
- **includes/classes/Blocks/RatingBlock.php** - [CREATE NEW - STEP 7]
- **templates/blocks/** - [CREATE DIR - STEP 7]

### Step 8: Admin UI and Meta Boxes
- **includes/classes/Admin/AdminInterface.php** - [CREATE NEW - STEP 8]
- **includes/classes/Admin/MetaBoxes.php** - [CREATE NEW - STEP 8]
- **includes/classes/Admin/AdminColumns.php** - [CREATE NEW - STEP 8]
- **includes/classes/Admin/BulkActions.php** - [CREATE NEW - STEP 8]
- **includes/classes/Fields/FieldManager.php** - [CREATE NEW - STEP 8]
- **includes/classes/Fields/MetaField.php** - [CREATE NEW - STEP 8]
- **includes/classes/Fields/MovieFields.php** - [CREATE NEW - STEP 8]
- **includes/classes/Fields/TVShowFields.php** - [CREATE NEW - STEP 8]
- **includes/classes/Fields/PeopleFields.php** - [CREATE NEW - STEP 8]

### Step 9: TMDB API Integration
- **includes/classes/API/TMDBClient.php** - [CREATE NEW - STEP 9]
- **includes/classes/API/DataProcessor.php** - [CREATE NEW - STEP 9]
- **includes/classes/API/ImageManager.php** - [CREATE NEW - STEP 9]
- **includes/classes/API/CreditProcessor.php** - [CREATE NEW - STEP 9]

### Step 10: Frontend Templates
- **includes/classes/Frontend/TemplateLoader.php** - [CREATE NEW - STEP 10]
- **includes/classes/Frontend/AssetManager.php** - [CREATE NEW - STEP 1] (Already created)
- **templates/archive/** - [CREATE DIR - STEP 10]
- **templates/single/** - [CREATE DIR - STEP 10]
- **index.php** - [CREATE NEW - STEP 10]

### Step 12: Search and Filtering
- **includes/classes/Frontend/SearchHandler.php** - [CREATE NEW - STEP 12]
- **includes/classes/Frontend/AjaxHandler.php** - [CREATE NEW - STEP 12]
- **templates/search/** - [CREATE DIR - STEP 12]

## AI Implementation Notes

### For Step 1 Implementation:
1. **Always create directories first** before any files
2. **Follow exact creation order** listed in phases
3. **Use exact file paths** as specified
4. **Include all dependencies** mentioned in documentation
5. **Mark Tailwind status** correctly in each file
6. **Add proper documentation** in code comments
7. **Include all required imports** and namespaces
8. **Test build process** after file creation

### Tailwind CSS Implementation Status:
- **FIRST TIME SETUP**: Step 1 establishes Tailwind CSS foundation
- **COMPILATION**: Webpack compiles Tailwind source to build directory
- **LOADING**: ThemeCore.php loads compiled CSS via wp_enqueue_style
- **CUSTOMIZATION**: TMU-specific colors, components, and utilities
- **RESPONSIVE**: Mobile-first responsive design approach

### Critical Success Factors:
1. **Directory structure must be created first**
2. **Configuration files must be created in exact order**
3. **Asset compilation must work before PHP files are created**
4. **All file dependencies must be satisfied**
5. **Tailwind CSS must compile successfully**
6. **WordPress must recognize the theme**

This guide ensures AI models can implement the TMU theme systematically with full understanding of file relationships and dependencies.