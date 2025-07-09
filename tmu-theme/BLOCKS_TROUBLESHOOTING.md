# TMU Theme Blocks Troubleshooting Guide

## Issue: Custom Blocks Not Showing in Post Editor

### Problem Description
The TMU theme has custom blocks for metadata fields (Movie, Drama, TV Series, People, etc.) but they are not appearing in the WordPress block editor when editing posts.

### Root Cause
The blocks were created but not properly loaded and initialized in the theme. The issue was in the `ThemeCore.php` file where:

1. Block PHP files were not being loaded in the `loadDependencies()` method
2. The `BlockRegistry` was not being initialized in the `initTheme()` method
3. Block assets (JavaScript and CSS) were missing

### Solution Applied

#### 1. Fixed Block Loading in ThemeCore.php

**Added block file loading in `loadDependencies()` method:**
```php
// Load Step 07 - Custom Fields (Native WordPress)
// Block system files - loaded conditionally if they exist
require_once TMU_INCLUDES_DIR . '/classes/Blocks/BaseBlock.php';
require_once TMU_INCLUDES_DIR . '/classes/Blocks/BlockRegistry.php';
require_once TMU_INCLUDES_DIR . '/classes/Blocks/MovieMetadataBlock.php';
require_once TMU_INCLUDES_DIR . '/classes/Blocks/TvSeriesMetadataBlock.php';
require_once TMU_INCLUDES_DIR . '/classes/Blocks/DramaMetadataBlock.php';
require_once TMU_INCLUDES_DIR . '/classes/Blocks/PeopleMetadataBlock.php';
require_once TMU_INCLUDES_DIR . '/classes/Blocks/TvEpisodeMetadataBlock.php';
require_once TMU_INCLUDES_DIR . '/classes/Blocks/DramaEpisodeMetadataBlock.php';
require_once TMU_INCLUDES_DIR . '/classes/Blocks/SeasonMetadataBlock.php';
require_once TMU_INCLUDES_DIR . '/classes/Blocks/VideoMetadataBlock.php';
require_once TMU_INCLUDES_DIR . '/classes/Blocks/TaxonomyImageBlock.php';
require_once TMU_INCLUDES_DIR . '/classes/Blocks/TaxonomyFaqsBlock.php';
require_once TMU_INCLUDES_DIR . '/classes/Blocks/BlogPostsListBlock.php';
require_once TMU_INCLUDES_DIR . '/classes/Blocks/TrendingContentBlock.php';
require_once TMU_INCLUDES_DIR . '/classes/Blocks/TmdbSyncBlock.php';
```

**Added BlockRegistry initialization in `initTheme()` method:**
```php
// Initialize Block system
Blocks\BlockRegistry::getInstance();
```

#### 2. Created Block Assets

Created the following build assets that were missing:

- `/assets/build/js/blocks-editor.js` - JavaScript for block editor
- `/assets/build/css/blocks-editor.css` - CSS for block editor
- `/assets/build/css/blocks.css` - CSS for frontend blocks
- `/assets/build/js/blocks.js` - JavaScript for frontend blocks

#### 3. Added Debug Tool

Created `TMU\Admin\BlocksDebug` class accessible at **Tools > TMU Blocks Debug** to help troubleshoot block registration issues.

### Available Blocks

After the fix, the following blocks should appear in the editor:

1. **Movie Metadata** - For `movie` post type
2. **TV Series Metadata** - For `tv` post type  
3. **Drama Metadata** - For `drama` post type
4. **People Metadata** - For `people` post type
5. **TV Episode Metadata** - For `episode` post type
6. **Drama Episode Metadata** - For `drama_episode` post type
7. **Season Metadata** - For `season` post type
8. **Video Metadata** - For `video` post type
9. **Taxonomy Image Block**
10. **Taxonomy FAQs Block**
11. **Blog Posts List Block**
12. **Trending Content Block**
13. **TMDB Sync Block**

### Post Type Restrictions

Blocks are restricted to specific post types:

- Movie Metadata → Only appears on `movie` posts
- Drama Metadata → Only appears on `drama` posts
- TV Series Metadata → Only appears on `tv` posts
- People Metadata → Only appears on `people` posts
- etc.

### Verification Steps

1. **Check Debug Page**: Go to **Tools > TMU Blocks Debug** to verify:
   - All block files exist ✅
   - All blocks are registered with TMU BlockRegistry ✅
   - All blocks are registered with WordPress ✅
   - Block assets exist ✅

2. **Test Block Insertion**: 
   - Create/edit a post of the appropriate type (e.g., drama)
   - Click the "+" button to add a block
   - Look for "TMU Blocks" category
   - Select the appropriate metadata block

3. **Check Browser Console**: Look for any JavaScript errors that might prevent blocks from loading

### Common Issues & Solutions

#### Issue: Blocks still not appearing
**Solution**: 
- Clear any caching plugins
- Check that the post type matches the block's allowed post types
- Verify assets are loading by checking Network tab in browser dev tools

#### Issue: "TMU Blocks" category not showing
**Solution**: 
- Check that `BlockRegistry` is properly initialized
- Verify the `register_block_category` filter is working

#### Issue: Blocks appear but fields don't save
**Solution**: 
- Check that the database tables exist for the post type
- Verify the `save_to_database` methods in block classes

### Technical Details

The blocks use:
- **Server-side rendering** (PHP)
- **React/JavaScript** for editor interface
- **Database integration** for metadata storage
- **Post type restrictions** for relevance
- **WordPress Block API** standards

### Files Modified

1. `includes/classes/ThemeCore.php` - Added block loading and initialization
2. `assets/build/js/blocks-editor.js` - Created block editor JavaScript
3. `assets/build/css/blocks-editor.css` - Created block editor styles
4. `assets/build/css/blocks.css` - Created frontend block styles
5. `assets/build/js/blocks.js` - Created frontend JavaScript
6. `includes/classes/Admin/BlocksDebug.php` - Created debug tool

The blocks should now be fully functional and appear in the post editor for the appropriate post types.