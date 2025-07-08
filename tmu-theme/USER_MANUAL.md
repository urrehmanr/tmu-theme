# TMU Theme User Manual

## Table of Contents
1. [Getting Started](#getting-started)
2. [Dashboard Overview](#dashboard-overview)
3. [Content Management](#content-management)
4. [TMDB Integration](#tmdb-integration)
5. [Search and Navigation](#search-and-navigation)
6. [User Management](#user-management)
7. [Theme Settings](#theme-settings)
8. [Maintenance](#maintenance)
9. [Troubleshooting](#troubleshooting)
10. [FAQ](#faq)

## Getting Started

### Welcome to TMU Theme
The TMU (TheMovieUpdates) theme is a comprehensive movie and TV database management system for WordPress. This manual will guide you through all features and functionalities.

### System Requirements
- WordPress 6.0 or higher
- PHP 7.4 or higher
- Modern web browser (Chrome, Firefox, Safari, Edge)

### First Time Setup

#### 1. Initial Configuration
After theme activation, you'll see a welcome screen with setup steps:

1. **TMDB API Configuration**
   - Navigate to `TMU Settings > TMDB API`
   - Enter your TMDB API key
   - Test the connection
   - Configure sync preferences

2. **Content Types Activation**
   - Go to `TMU Settings > Content Types`
   - Enable desired content types:
     - ✅ Movies
     - ✅ TV Series
     - ✅ Dramas
     - ✅ People (Cast & Crew)
     - ✅ Videos (Trailers, Clips)

3. **Permalink Structure**
   - The theme automatically configures SEO-friendly URLs
   - Verify at `Settings > Permalinks`

#### 2. Quick Start Checklist
- [ ] TMDB API key configured
- [ ] Content types enabled
- [ ] Sample content imported (optional)
- [ ] Theme customization completed
- [ ] User roles assigned

## Dashboard Overview

### Main Dashboard
The WordPress dashboard includes TMU-specific widgets:

#### TMU Content Statistics
- **Total Movies:** Current movie count
- **Total TV Series:** Current TV series count
- **Total Dramas:** Current drama count
- **Total People:** Cast & crew profiles
- **TMDB Sync Status:** Last synchronization info

#### Quick Actions
- **Add New Movie:** Create movie entry
- **Add New TV Series:** Create TV series entry
- **Sync with TMDB:** Manual data synchronization
- **View Reports:** Performance and analytics

#### Recent Activity
- Latest content additions
- Recent TMDB synchronizations
- User activities
- System notifications

### Navigation Menu
The admin menu includes:

```
Dashboard
├── TMU Movies
│   ├── All Movies
│   ├── Add New Movie
│   ├── Genres
│   ├── Countries
│   └── Studios
├── TMU TV Series
│   ├── All TV Series
│   ├── Add New TV Series
│   ├── Genres
│   ├── Networks
│   └── Seasons
├── TMU Dramas
│   ├── All Dramas
│   ├── Add New Drama
│   ├── Genres
│   └── Countries
├── TMU People
│   ├── All People
│   ├── Add New Person
│   └── Departments
├── TMU Videos
│   ├── All Videos
│   ├── Add New Video
│   └── Video Types
├── TMU Settings
│   ├── General Settings
│   ├── TMDB API
│   ├── Content Types
│   ├── Performance
│   └── Advanced
└── TMU Tools
    ├── Import/Export
    ├── TMDB Sync
    ├── Database Tools
    └── System Info
```

## Content Management

### Adding Movies

#### Method 1: Manual Entry
1. Go to `TMU Movies > Add New Movie`
2. Fill in basic information:
   - **Title:** Movie title
   - **Description:** Movie synopsis
   - **Featured Image:** Movie poster
3. Configure movie details using Gutenberg blocks:
   - **Movie Info Block:** Basic details
   - **Cast & Crew Block:** People associated
   - **Release Info Block:** Dates and ratings
   - **Media Block:** Trailers and images

#### Method 2: TMDB Import
1. Go to `TMU Movies > Add New Movie`
2. Click "Import from TMDB" button
3. Search for the movie by title
4. Select the correct movie from results
5. Review and confirm import
6. The system automatically fills all available data

#### Movie Information Fields

**Basic Information:**
- Title (required)
- Original Title
- Tagline
- Overview/Synopsis
- Release Date
- Runtime (minutes)
- Status (Released, In Production, etc.)

**Financial Information:**
- Budget
- Revenue
- Production Companies
- Production Countries

**Ratings & Popularity:**
- TMDB Rating
- User Rating
- TMDB Popularity Score
- Vote Count

**Media:**
- Poster Image
- Backdrop Images
- Trailers
- Clips
- Behind-the-scenes videos

**Taxonomies:**
- Genres
- Countries
- Studios
- Collections

### Adding TV Series

#### Creating a TV Series
1. Navigate to `TMU TV Series > Add New TV Series`
2. Enter basic information:
   - **Title:** Series title
   - **Description:** Series overview
   - **Featured Image:** Series poster
3. Configure series details:
   - **TV Series Info Block:** Basic details
   - **Network Info Block:** Broadcasting networks
   - **Season Overview Block:** Season information

#### TV Series Specific Fields

**Series Information:**
- Title (required)
- Original Title
- Overview
- First Air Date
- Last Air Date
- Status (Returning, Ended, Cancelled)
- Type (Scripted, Reality, Documentary)

**Production Details:**
- Networks
- Production Companies
- Created By
- Number of Seasons
- Number of Episodes
- Episode Runtime

**Content Rating:**
- Age Rating
- Content Warnings
- Parental Guidelines

#### Managing Seasons and Episodes

**Adding Seasons:**
1. Within a TV series, click "Add Season"
2. Enter season information:
   - Season Number
   - Air Date
   - Episode Count
   - Season Overview
   - Season Poster

**Adding Episodes:**
1. Within a season, click "Add Episode"
2. Fill episode details:
   - Episode Number
   - Episode Title
   - Air Date
   - Runtime
   - Overview
   - Still Image (episode screenshot)

### Adding Dramas

#### Drama-Specific Features
Dramas include additional cultural and regional elements:

**Cultural Information:**
- Origin Country
- Language
- Cultural Context
- Traditional Elements

**Drama Classifications:**
- Historical Drama
- Modern Drama
- Romantic Drama
- Family Drama
- Action Drama

### Managing People (Cast & Crew)

#### Adding a Person Profile
1. Go to `TMU People > Add New Person`
2. Enter personal information:
   - **Name:** Full name (required)
   - **Biography:** Personal background
   - **Profile Photo:** Headshot image
3. Configure person details:
   - **Personal Info Block:** Birth, death, place of birth
   - **Career Info Block:** Known for, department
   - **Filmography Block:** Movies and TV shows

#### Person Information Fields

**Personal Details:**
- Name (required)
- Also Known As (alternative names)
- Biography
- Birthday
- Place of Birth
- Deathday (if applicable)
- Profile Photo

**Career Information:**
- Known For Department (Acting, Directing, Writing, etc.)
- Known For (famous works)
- Career Start Year
- Awards and Nominations

**Social Media:**
- IMDB Profile
- Twitter
- Instagram
- Facebook
- Official Website

#### Associating People with Content

**Cast Members:**
1. In movie/TV series edit screen
2. Use "Cast & Crew" block
3. Search for existing people or add new
4. Specify role/character name
5. Set order of appearance

**Crew Members:**
1. Use same "Cast & Crew" block
2. Select department (Directing, Writing, Production, etc.)
3. Specify job title
4. Add crew member to appropriate department

### Video Management

#### Video Types
- **Trailers:** Official movie/TV trailers
- **Teasers:** Short preview clips
- **Clips:** Scene excerpts
- **Behind the Scenes:** Making-of content
- **Interviews:** Cast and crew interviews
- **Featurettes:** Documentary-style content

#### Adding Videos
1. Go to `TMU Videos > Add New Video`
2. Choose video source:
   - **YouTube:** Enter YouTube URL
   - **Vimeo:** Enter Vimeo URL
   - **Upload:** Upload video file
   - **TMDB:** Import from TMDB
3. Configure video details:
   - Video Title
   - Description
   - Video Type
   - Associated Content (link to movie/TV series)
   - Thumbnail Image

## TMDB Integration

### API Configuration

#### Setting Up TMDB API
1. Create account at [TheMovieDB.org](https://www.themoviedb.org/)
2. Generate API key in account settings
3. In WordPress admin, go to `TMU Settings > TMDB API`
4. Enter API key and save
5. Click "Test Connection" to verify

#### TMDB Settings
- **API Key:** Your TMDB API key
- **Cache Duration:** How long to cache TMDB data (default: 1 hour)
- **Rate Limiting:** Requests per second (default: 4)
- **Auto Sync:** Enable automatic daily synchronization
- **Image Download:** Automatically download TMDB images

### Content Synchronization

#### Manual Sync Process
1. Navigate to `TMU Tools > TMDB Sync`
2. Choose sync type:
   - **Single Item:** Sync specific movie/TV series
   - **Bulk Sync:** Sync multiple items
   - **Full Sync:** Sync all content
3. Select items to sync (if applicable)
4. Click "Start Sync"
5. Monitor progress in real-time

#### Automatic Sync
- Configured in `TMU Settings > TMDB API`
- Runs daily at specified time
- Updates popular content first
- Adds new releases automatically
- Sends email reports

#### Sync Status Indicators
- 🟢 **Synced:** Recently updated from TMDB
- 🟡 **Pending:** Scheduled for next sync
- 🔴 **Error:** Sync failed (check logs)
- ⚪ **Not Found:** Not available on TMDB

### Data Mapping

#### Automatic Field Mapping
TMDB data automatically populates:
- Basic information (title, overview, dates)
- Cast and crew details
- Genres and keywords
- Ratings and popularity
- Images (posters, backdrops, profiles)
- Videos (trailers, clips)

#### Manual Override
Users can override TMDB data:
1. Edit the content item
2. Modify any field
3. Save changes
4. System preserves manual changes during sync

### Image Management

#### TMDB Image Download
- **Automatic:** Download during sync
- **Manual:** Click "Download Images" button
- **Selective:** Choose specific image sizes

#### Image Sizes Available
- **Posters:** w92, w154, w185, w342, w500, w780, original
- **Backdrops:** w300, w780, w1280, original
- **Profile Photos:** w45, w185, h632, original

## Search and Navigation

### Frontend Search Features

#### Basic Search
Users can search using the main search bar:
- **Universal Search:** Searches all content types
- **Auto-complete:** Real-time suggestions
- **Recent Searches:** Saves user search history

#### Advanced Search
Accessible via "Advanced Search" link:

**Movie Search Filters:**
- Genre
- Release Year Range
- Rating Range
- Runtime Range
- Country
- Studio
- Language

**TV Series Search Filters:**
- Genre
- First Air Year
- Status (Returning, Ended)
- Network
- Episode Count Range
- Country

**People Search Filters:**
- Department (Actor, Director, Writer)
- Known For
- Birth Year Range
- Gender

#### Search Results
Results display includes:
- Poster/profile image
- Title/name
- Release date/birth year
- Rating/popularity
- Quick action buttons

### Navigation Features

#### Browse Pages
- **Movies:** Browse all movies with filtering
- **TV Series:** Browse all TV series
- **Dramas:** Browse drama content
- **People:** Browse cast and crew
- **Genres:** Browse by genre categories

#### Content Discovery
- **Trending:** Popular content this week
- **New Releases:** Recently added content
- **Top Rated:** Highest-rated content
- **Recommendations:** Personalized suggestions

#### Filter Options
Each browse page includes filters:
- **Sort By:** Popularity, Rating, Release Date, Title
- **Order:** Ascending/Descending
- **Genres:** Multiple genre selection
- **Year Range:** Date range picker
- **Rating Range:** Slider interface

### Content Pages

#### Movie Detail Page
- **Hero Section:** Large backdrop with poster and key info
- **Overview:** Plot synopsis and details
- **Cast & Crew:** Scrollable cast list with photos
- **Media:** Trailers, clips, and images
- **Ratings:** User and TMDB ratings
- **Similar Movies:** Related content suggestions
- **Reviews:** User reviews and comments

#### TV Series Detail Page
- **Series Overview:** Basic series information
- **Seasons & Episodes:** Expandable season list
- **Cast & Crew:** Main cast and recurring guests
- **Episodes Guide:** Detailed episode information
- **Networks & Schedule:** Broadcasting information

#### Person Detail Page
- **Biography:** Personal background
- **Filmography:** Complete work history
- **Photos:** Image gallery
- **Personal Info:** Birth, death, place of birth
- **Social Links:** External profile links

## User Management

### User Roles

#### Administrator
- Full access to all features
- Manage theme settings
- Import/export content
- Manage other users
- Access system tools

#### Editor
- Create and edit all content
- Manage taxonomies
- Upload media
- Access TMDB sync
- View analytics

#### Author
- Create and edit own content
- Upload media
- Limited TMDB access
- Basic content management

#### Contributor
- Create content (pending review)
- Limited media upload
- Basic search access

#### Subscriber
- Frontend access only
- Comment on content
- Rate movies/TV shows
- Create watchlists

### User Capabilities

#### Content Management Permissions
```
Movie Management:
- create_movies
- edit_movies
- edit_others_movies
- delete_movies
- publish_movies

TV Series Management:
- create_tv_series
- edit_tv_series
- edit_others_tv_series
- delete_tv_series
- publish_tv_series

People Management:
- create_people
- edit_people
- edit_others_people
- delete_people
- publish_people

TMDB Integration:
- use_tmdb_sync
- manage_tmdb_settings
- import_tmdb_content
```

### User Registration

#### Frontend Registration
If enabled in settings:
1. Users click "Register" link
2. Fill registration form:
   - Username
   - Email
   - Password
   - Optional: Display name, bio
3. Email verification (if enabled)
4. Admin approval (if required)

#### Admin User Creation
1. Go to `Users > Add New`
2. Fill user information
3. Assign appropriate role
4. Send welcome email

### User Profiles

#### Profile Information
- **Display Name:** Public name
- **Biography:** User description
- **Avatar:** Profile picture
- **Social Links:** External profiles
- **Preferences:** Site preferences

#### Privacy Settings
- **Profile Visibility:** Public/Private
- **Activity Visibility:** Show/hide activity
- **Email Notifications:** Preferences
- **Watchlist Privacy:** Public/Private watchlists

## Theme Settings

### General Settings

#### Site Identity
- **Site Title:** Your site name
- **Tagline:** Brief description
- **Logo:** Site logo image
- **Favicon:** Browser icon

#### Content Types
Enable/disable content types:
- Movies
- TV Series
- Dramas
- People
- Videos

#### Features
Toggle theme features:
- User ratings
- Comments
- Watchlists
- Social sharing
- Advanced search

### TMDB API Settings

#### API Configuration
- **API Key:** TMDB API key
- **Rate Limiting:** Requests per second
- **Cache Duration:** Data cache time
- **Image Download:** Auto-download images
- **Auto Sync:** Automatic synchronization

#### Sync Preferences
- **Sync Frequency:** Daily, weekly, manual
- **Sync Time:** Preferred sync time
- **Content Priority:** Popular, new releases, all
- **Email Reports:** Sync status emails

### Appearance Settings

#### Theme Customization
Access via `Appearance > Customize`:

**Colors:**
- Primary color
- Secondary color
- Accent color
- Text colors
- Background colors

**Typography:**
- Heading fonts
- Body fonts
- Font sizes
- Line heights

**Layout:**
- Container width
- Sidebar layout
- Grid columns
- Card styles

**Header:**
- Logo placement
- Navigation style
- Search bar display
- User menu options

**Footer:**
- Footer layout
- Social links
- Copyright text
- Additional menus

### Performance Settings

#### Caching
- **Page Caching:** Enable/disable
- **Object Caching:** Database query cache
- **TMDB Caching:** API response cache
- **Image Optimization:** Auto-optimize images

#### Database
- **Query Optimization:** Enable optimizations
- **Database Cleanup:** Remove orphaned data
- **Performance Monitoring:** Track performance

#### SEO Settings
- **Meta Tags:** Auto-generate meta descriptions
- **Schema Markup:** Structured data
- **Sitemaps:** XML sitemap generation
- **Social Sharing:** Open Graph tags

## Maintenance

### Regular Maintenance Tasks

#### Daily Tasks (Automated)
- TMDB data synchronization
- Cache cleanup
- Database optimization
- Performance monitoring
- Error log monitoring

#### Weekly Tasks
- Full database backup
- Security scan
- Performance analysis
- Content review
- User activity analysis

#### Monthly Tasks
- System updates check
- Security audit
- Performance optimization
- Content statistics review
- User feedback analysis

### Backup Management

#### Automatic Backups
- **Daily:** Database backup
- **Weekly:** Full site backup
- **Monthly:** Archive backup
- **Pre-update:** Before theme updates

#### Manual Backups
1. Go to `TMU Tools > Database Tools`
2. Click "Create Backup"
3. Choose backup type:
   - Database only
   - Files only
   - Complete backup
4. Download backup file

#### Restore Process
1. Access backup files
2. Go to `TMU Tools > Database Tools`
3. Upload backup file
4. Choose restore options
5. Confirm restoration

### System Monitoring

#### Performance Monitoring
Monitor via `TMU Tools > System Info`:
- **Response Time:** Page load times
- **Memory Usage:** PHP memory consumption
- **Database Queries:** Query count per page
- **Error Rate:** System error percentage

#### Health Checks
- **TMDB API Status:** API connectivity
- **Database Status:** Database health
- **File Permissions:** Correct permissions
- **SSL Certificate:** Security certificate status

### Updates

#### Theme Updates
1. Check for updates in `Dashboard > Updates`
2. Create backup before updating
3. Click "Update Now"
4. Verify functionality after update
5. Clear caches if needed

#### Plugin Compatibility
Ensure compatibility with:
- Caching plugins
- SEO plugins
- Security plugins
- Performance plugins

## Troubleshooting

### Common Issues

#### Site Loading Issues

**Problem:** Site loads slowly
**Solutions:**
1. Enable caching in settings
2. Optimize images
3. Check database queries
4. Contact hosting provider

**Problem:** Pages not found (404 errors)
**Solutions:**
1. Go to `Settings > Permalinks`
2. Click "Save Changes" to flush rewrite rules
3. Check .htaccess file
4. Verify content exists

#### TMDB Integration Issues

**Problem:** TMDB sync not working
**Solutions:**
1. Verify API key in settings
2. Check TMDB API status
3. Review error logs
4. Test with different content

**Problem:** Images not downloading
**Solutions:**
1. Check file permissions
2. Verify server space
3. Test image URLs manually
4. Check TMDB image settings

#### Content Management Issues

**Problem:** Cannot save content
**Solutions:**
1. Check user permissions
2. Increase PHP memory limit
3. Check for plugin conflicts
4. Review error logs

**Problem:** Search not working
**Solutions:**
1. Rebuild search index
2. Check database integrity
3. Clear caches
4. Verify search settings

### Error Messages

#### Common Error Codes

**TMU_001: TMDB API Error**
- Check API key validity
- Verify internet connection
- Review rate limiting settings

**TMU_002: Database Error**
- Check database connection
- Verify table structure
- Run database repair

**TMU_003: Permission Error**
- Check file permissions
- Verify user capabilities
- Review security settings

**TMU_004: Memory Error**
- Increase PHP memory limit
- Optimize large images
- Check plugin compatibility

### Getting Help

#### Support Resources
- **Documentation:** Complete online documentation
- **Video Tutorials:** Step-by-step video guides
- **Community Forum:** User community support
- **Ticket System:** Priority support for issues

#### Before Contacting Support
1. Check this user manual
2. Review error logs
3. Test with default theme
4. Disable other plugins
5. Gather system information

#### Support Information to Provide
- WordPress version
- TMU theme version
- PHP version
- Error messages
- Steps to reproduce issue
- Screenshots (if applicable)

## FAQ

### General Questions

**Q: Is TMDB API key required?**
A: Yes, a TMDB API key is required for automatic data import and synchronization. It's free to obtain from TheMovieDB.org.

**Q: Can I use the theme without TMDB?**
A: Yes, you can manually enter all content information, but you'll miss automatic data synchronization and image downloading.

**Q: How often does TMDB sync run?**
A: By default, sync runs daily. You can configure frequency in TMDB settings or run manual sync anytime.

**Q: Can I customize the theme appearance?**
A: Yes, extensive customization options are available via the WordPress Customizer and theme settings.

### Content Management

**Q: How do I bulk import movies?**
A: Use the bulk import feature in `TMU Tools > Import/Export`. You can import via CSV file or TMDB batch import.

**Q: Can I override TMDB data?**
A: Yes, any manual changes you make will be preserved during automatic sync.

**Q: How do I organize content into collections?**
A: Use the Collections taxonomy available for movies and TV series. Create collections in the respective taxonomy section.

**Q: Can users rate content?**
A: Yes, if enabled in theme settings. Users can rate movies and TV series on a 1-10 scale.

### Technical Questions

**Q: What's the minimum server requirements?**
A: PHP 7.4+, WordPress 6.0+, MySQL 5.7+, and sufficient disk space for images and videos.

**Q: Does the theme affect site performance?**
A: The theme is optimized for performance with caching, image optimization, and efficient database queries.

**Q: Is the theme mobile-responsive?**
A: Yes, the theme is fully responsive and works on all devices and screen sizes.

**Q: Can I translate the theme?**
A: Yes, the theme is translation-ready with gettext support for all strings.

### Customization

**Q: Can I modify the theme files?**
A: It's recommended to use a child theme for modifications to preserve changes during updates.

**Q: How do I add custom CSS?**
A: Use `Appearance > Customize > Additional CSS` or add to your child theme's style.css.

**Q: Can I add custom fields?**
A: Yes, you can add custom fields via hooks and filters or by extending the existing blocks.

### Support and Updates

**Q: How often is the theme updated?**
A: Regular updates include bug fixes, new features, and compatibility improvements. Updates are released monthly or as needed.

**Q: Is support included?**
A: Documentation and community support are included. Premium support is available separately.

**Q: Can I backup my content?**
A: Yes, the theme includes backup tools, or you can use any WordPress backup plugin.

---

## Quick Reference

### Keyboard Shortcuts (Admin)
- `Ctrl + S` - Save content
- `Ctrl + P` - Preview content
- `Esc` - Close modals
- `Tab` - Navigate form fields

### URL Structure
- Movies: `/movies/movie-title/`
- TV Series: `/tv-series/series-title/`
- Dramas: `/dramas/drama-title/`
- People: `/people/person-name/`
- Genres: `/genre/genre-name/`

### Support Contacts
- **Documentation:** [docs.example.com](https://docs.example.com)
- **Support Email:** support@example.com
- **Community Forum:** [forum.example.com](https://forum.example.com)
- **Video Tutorials:** [tutorials.example.com](https://tutorials.example.com)

---

*This user manual covers all aspects of the TMU theme. For additional help, please refer to the support resources listed above.*