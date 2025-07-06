# TMU Gutenberg Block Migration Guide

## Overview

This guide provides step-by-step instructions for migrating from the current TMU meta box system to a modern Gutenberg block-based approach while preserving all existing data and maintaining system integrity.

## Prerequisites

Before starting the migration, ensure you have:

1. **Full database backup** - Critical for rollback capability
2. **Staging environment** - Test migration before production
3. **WP-CLI access** - Required for migration commands
4. **SSH/Terminal access** - For running migration scripts
5. **WordPress 5.0+** - Gutenberg block editor support

## Migration Overview

The migration process involves:

1. **Database Schema Updates** - Adding new fields required for Gutenberg blocks
2. **Data Migration** - Moving meta box data to custom tables
3. **Block Implementation** - Creating and registering Gutenberg blocks
4. **Content Generation** - Adding block content to existing posts
5. **Validation** - Ensuring data integrity throughout the process

## Step-by-Step Migration Process

### Step 1: Pre-Migration Preparation

#### 1.1 Create Database Backup
```bash
# Create full database backup
mysqldump -u username -p database_name > tmu_backup_$(date +%Y%m%d_%H%M%S).sql

# Or use WordPress backup plugin
wp db export tmu_backup_$(date +%Y%m%d_%H%M%S).sql
```

#### 1.2 Verify Current State
```bash
# Check current post counts
wp tmu migrate validate

# Verify database tables exist
wp db query "SHOW TABLES LIKE 'wp_tmu_%'"

# Check existing meta data
wp db query "SELECT COUNT(*) FROM wp_postmeta WHERE meta_key LIKE 'tmdb_%'"
```

### Step 2: Database Schema Updates

#### 2.1 Update Database Schema
The migration will automatically update your database schema to include new fields required for Gutenberg blocks. These updates are **additive only** - no existing data will be lost.

**New fields being added:**

**Movies Table:**
- `imdb_id`, `status`, `homepage`, `poster_path`, `backdrop_path`
- `adult`, `video`, `belongs_to_collection`
- `production_companies`, `production_countries`, `spoken_languages`
- `external_ids`, `similar`, `recommendations`

**TV Series Table:**
- `imdb_id`, `name`, `original_name`, `type`, `homepage`
- `in_production`, `number_of_episodes`, `number_of_seasons`
- `episode_run_time`, `languages`, `origin_country`, `original_language`
- `created_by`, `networks`, `genres`

**People Table:**
- `imdb_id`, `also_known_as`, `biography`
- `birthday`, `deathday`, `external_ids`, `images`

### Step 3: Run Migration (Staging Environment)

#### 3.1 Test Migration (Dry Run)
```bash
# Test migration without making changes
wp tmu migrate blocks --dry-run

# Test specific post types only
wp tmu migrate blocks --dry-run --post-types=movie

# Test with smaller batches
wp tmu migrate blocks --dry-run --batch-size=25
```

#### 3.2 Run Actual Migration
```bash
# Full migration with backup
wp tmu migrate blocks

# Custom options
wp tmu migrate blocks --post-types=movie,tv --batch-size=50

# Skip backup (not recommended)
wp tmu migrate blocks --skip-backup
```

#### 3.3 Validate Migration Results
```bash
# Check migration integrity
wp tmu migrate validate

# View migration logs
tail -f wp-content/uploads/tmu-migration-logs/migration_*.log
```

### Step 4: Install Gutenberg Block System

#### 4.1 Install Dependencies
```bash
cd tmu-theme
npm install @wordpress/blocks @wordpress/components @wordpress/element
npm install @babel/preset-react webpack babel-loader
```

#### 4.2 Build Block Assets
```bash
# Development build
npm run dev

# Production build
npm run build

# Watch for changes during development
npm run watch
```

#### 4.3 Verify Block Registration
```bash
# Check if blocks are registered
wp eval "var_dump(get_dynamic_block_names());"

# Test block functionality in WordPress admin
# Go to post editor and look for TMU blocks in inserter
```

### Step 5: Production Deployment

#### 5.1 Pre-Production Checklist
- [ ] Staging migration completed successfully
- [ ] All data validated and verified
- [ ] Block system tested thoroughly
- [ ] Performance benchmarks met
- [ ] User acceptance testing completed

#### 5.2 Production Migration
```bash
# 1. Create production backup
wp db export production_backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Put site in maintenance mode
wp maintenance-mode activate

# 3. Run migration
wp tmu migrate blocks

# 4. Validate results
wp tmu migrate validate

# 5. Remove maintenance mode
wp maintenance-mode deactivate
```

## Field Mapping Reference

### Movies
| Meta Box Field | Block Attribute | Database Column | Notes |
|---------------|----------------|-----------------|--------|
| `tmdb_id` | `tmdb_id` | `tmdb_id` | Direct mapping |
| `release_date` | `release_date` | `release_date` | Direct mapping |
| `poster_url` | `poster_path` | `poster_path` | URL → Path conversion |
| `backdrop_url` | `backdrop_path` | `backdrop_path` | URL → Path conversion |
| `trailer_url` | `videos` | `videos` | Structure change |
| `average_rating` | `tmdb_vote_average` | `average_rating` | Direct mapping |
| `vote_count` | `tmdb_vote_count` | `vote_count` | Direct mapping |

### TV Series
| Meta Box Field | Block Attribute | Database Column | Notes |
|---------------|----------------|-----------------|--------|
| `tmdb_id` | `tmdb_id` | `tmdb_id` | Direct mapping |
| `first_air_date` | `first_air_date` | `first_air_date` | Direct mapping |
| `episode_runtime` | `episode_run_time` | `episode_run_time` | Array format |
| `number_of_seasons` | `number_of_seasons` | `number_of_seasons` | Direct mapping |

### People
| Meta Box Field | Block Attribute | Database Column | Notes |
|---------------|----------------|-----------------|--------|
| `date_of_birth` | `birthday` | `birthday` | Date format |
| `date_of_death` | `deathday` | `deathday` | Date format |
| `profile_path` | `profile_path` | `profile_path` | Direct mapping |
| `biography` | `biography` | `biography` | Direct mapping |

## Rollback Procedure

### If Migration Fails

#### 1. List Available Backups
```bash
wp tmu migrate rollback
```

#### 2. Restore from Backup
```bash
# Restore specific backup
wp tmu migrate rollback --backup-date=20231215_143022

# Skip confirmation (use with caution)
wp tmu migrate rollback --backup-date=20231215_143022 --confirm
```

#### 3. Verify Rollback
```bash
# Check data integrity after rollback
wp tmu migrate validate

# Verify post counts match original
wp post list --post_type=movie --format=count
```

### Complete System Restore

If you need to completely restore the system:

```bash
# 1. Restore database from full backup
mysql -u username -p database_name < tmu_backup_20231215_143022.sql

# 2. Clear any cache
wp cache flush

# 3. Verify restoration
wp tmu migrate validate
```

## Troubleshooting

### Common Issues

#### 1. Migration Fails with Database Errors
```bash
# Check database permissions
wp db query "SHOW GRANTS"

# Verify table structure
wp db query "DESCRIBE wp_tmu_movies"

# Check for locked tables
wp db query "SHOW PROCESSLIST"
```

#### 2. Blocks Don't Appear in Editor
```bash
# Verify block registration
wp eval "var_dump(get_dynamic_block_names());"

# Check for JavaScript errors in browser console
# Ensure assets are built correctly
npm run build
```

#### 3. Data Missing After Migration
```bash
# Run validation
wp tmu migrate validate

# Check specific post
wp post get 123 --format=json

# Verify custom table data
wp db query "SELECT * FROM wp_tmu_movies WHERE ID = 123"
```

#### 4. Performance Issues
```bash
# Check database indexes
wp db query "SHOW INDEX FROM wp_tmu_movies"

# Monitor query performance
wp db query "SHOW PROCESSLIST"

# Clear cache
wp cache flush
```

### Migration Recovery

#### Partial Migration Failure
```bash
# 1. Stop current migration
# Kill any running migration processes

# 2. Check what was migrated
wp tmu migrate validate

# 3. Resume migration for specific post types
wp tmu migrate blocks --post-types=tv,drama,people
```

#### Data Inconsistency
```bash
# 1. Identify inconsistencies
wp tmu migrate validate

# 2. Fix specific issues manually
wp db query "UPDATE wp_tmu_movies SET status = 'Released' WHERE status IS NULL"

# 3. Re-validate
wp tmu migrate validate
```

## Post-Migration Tasks

### 1. Performance Optimization
```bash
# Rebuild search indexes
wp search-replace --dry-run old-url new-url

# Clear all cache
wp cache flush

# Optimize database tables
wp db optimize
```

### 2. User Training
- Provide documentation for new block interface
- Train content editors on new workflow
- Create video tutorials if needed
- Monitor user feedback and issues

### 3. Monitoring
```bash
# Set up monitoring for:
# - Block registration errors
# - Database performance
# - User interface issues
# - Data integrity checks

# Schedule regular validation
wp cron event schedule wp_tmu_validate_data daily
```

### 4. Cleanup (After Successful Migration)
```bash
# Remove backup tables (be very careful)
wp tmu migrate cleanup --remove-backups --confirm

# Remove migration logs
wp tmu migrate cleanup --remove-logs --confirm

# Remove old meta box code (if not needed)
# This should be done carefully after thorough testing
```

## Support and Maintenance

### Regular Maintenance
1. **Weekly**: Run `wp tmu migrate validate` to check data integrity
2. **Monthly**: Review performance metrics and optimize if needed
3. **Quarterly**: Update dependencies and test new WordPress versions

### Getting Help
1. Check logs: `wp-content/uploads/tmu-migration-logs/`
2. Enable WordPress debug: `WP_DEBUG = true`
3. Review validation reports: `wp tmu migrate validate`
4. Check database consistency manually if needed

### Best Practices
1. Always test in staging first
2. Keep regular backups
3. Monitor performance after migration
4. Document any customizations made
5. Train users on new interface
6. Plan for gradual rollout if possible

## Conclusion

This migration represents a significant architectural upgrade that will:
- Provide a modern, intuitive editing experience
- Improve performance and maintainability
- Future-proof the system for WordPress evolution
- Maintain complete data integrity throughout the process

The key to success is careful planning, thorough testing, and maintaining comprehensive backups throughout the process.