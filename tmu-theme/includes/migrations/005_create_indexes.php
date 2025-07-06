<?php
/**
 * Create Performance Indexes Migration
 *
 * @package TMU\Database\Migrations
 * @version 1.0.0
 */

namespace TMU\Database\Migrations;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create Performance Indexes
 */
class CreateIndexes {
    
    /**
     * Run the migration
     */
    public function up(): void {
        global $wpdb;
        
        // Create indexes for TMU movies table
        $this->createMovieIndexes();
        
        // Create indexes for TMU people table
        $this->createPeopleIndexes();
        
        // Create indexes for TMU TV series table
        $this->createTVSeriesIndexes();
        
        // Create indexes for TMU dramas table
        $this->createDramaIndexes();
        
        // Create indexes for cast/crew tables
        $this->createCastCrewIndexes();
        
        // Create indexes for episode tables
        $this->createEpisodeIndexes();
        
        // Create composite indexes for complex queries
        $this->createCompositeIndexes();
        
        // Log migration
        error_log('TMU Migration: Performance indexes created successfully');
    }
    
    /**
     * Create indexes for movies table
     */
    private function createMovieIndexes(): void {
        global $wpdb;
        
        $indexes = [
            "CREATE INDEX `idx_movies_tmdb_id` ON `{$wpdb->prefix}tmu_movies` (`tmdb_id`)",
            "CREATE INDEX `idx_movies_release_date` ON `{$wpdb->prefix}tmu_movies` (`release_timestamp`)",
            "CREATE INDEX `idx_movies_rating` ON `{$wpdb->prefix}tmu_movies` (`average_rating`)",
            "CREATE INDEX `idx_movies_popularity` ON `{$wpdb->prefix}tmu_movies` (`popularity`)",
            "CREATE INDEX `idx_movies_vote_count` ON `{$wpdb->prefix}tmu_movies` (`vote_count`)",
            "CREATE INDEX `idx_movies_runtime` ON `{$wpdb->prefix}tmu_movies` (`runtime`)",
            "CREATE INDEX `idx_movies_created_at` ON `{$wpdb->prefix}tmu_movies` (`created_at`)",
            "CREATE INDEX `idx_movies_updated_at` ON `{$wpdb->prefix}tmu_movies` (`updated_at`)"
        ];
        
        $this->executeIndexes($indexes);
    }
    
    /**
     * Create indexes for people table
     */
    private function createPeopleIndexes(): void {
        global $wpdb;
        
        $indexes = [
            "CREATE INDEX `idx_people_tmdb_id` ON `{$wpdb->prefix}tmu_people` (`tmdb_id`)",
            "CREATE INDEX `idx_people_gender` ON `{$wpdb->prefix}tmu_people` (`gender`(10))",
            "CREATE INDEX `idx_people_popularity` ON `{$wpdb->prefix}tmu_people` (`popularity`)",
            "CREATE INDEX `idx_people_profession` ON `{$wpdb->prefix}tmu_people` (`profession`(50))",
            "CREATE INDEX `idx_people_created_at` ON `{$wpdb->prefix}tmu_people` (`created_at`)",
            "CREATE INDEX `idx_people_updated_at` ON `{$wpdb->prefix}tmu_people` (`updated_at`)"
        ];
        
        $this->executeIndexes($indexes);
    }
    
    /**
     * Create indexes for TV series table
     */
    private function createTVSeriesIndexes(): void {
        global $wpdb;
        
        $indexes = [
            "CREATE INDEX `idx_tv_series_tmdb_id` ON `{$wpdb->prefix}tmu_tv_series` (`tmdb_id`)",
            "CREATE INDEX `idx_tv_series_release_date` ON `{$wpdb->prefix}tmu_tv_series` (`release_timestamp`)",
            "CREATE INDEX `idx_tv_series_rating` ON `{$wpdb->prefix}tmu_tv_series` (`average_rating`)",
            "CREATE INDEX `idx_tv_series_popularity` ON `{$wpdb->prefix}tmu_tv_series` (`popularity`)",
            "CREATE INDEX `idx_tv_series_finished` ON `{$wpdb->prefix}tmu_tv_series` (`finished`(10))",
            "CREATE INDEX `idx_tv_series_created_at` ON `{$wpdb->prefix}tmu_tv_series` (`created_at`)",
            "CREATE INDEX `idx_tv_series_updated_at` ON `{$wpdb->prefix}tmu_tv_series` (`updated_at`)"
        ];
        
        $this->executeIndexes($indexes);
    }
    
    /**
     * Create indexes for dramas table
     */
    private function createDramaIndexes(): void {
        global $wpdb;
        
        $indexes = [
            "CREATE INDEX `idx_dramas_tmdb_id` ON `{$wpdb->prefix}tmu_dramas` (`tmdb_id`)",
            "CREATE INDEX `idx_dramas_release_date` ON `{$wpdb->prefix}tmu_dramas` (`release_timestamp`)",
            "CREATE INDEX `idx_dramas_schedule` ON `{$wpdb->prefix}tmu_dramas` (`schedule_timestamp`)",
            "CREATE INDEX `idx_dramas_rating` ON `{$wpdb->prefix}tmu_dramas` (`average_rating`)",
            "CREATE INDEX `idx_dramas_popularity` ON `{$wpdb->prefix}tmu_dramas` (`popularity`)",
            "CREATE INDEX `idx_dramas_finished` ON `{$wpdb->prefix}tmu_dramas` (`finished`(10))",
            "CREATE INDEX `idx_dramas_created_at` ON `{$wpdb->prefix}tmu_dramas` (`created_at`)",
            "CREATE INDEX `idx_dramas_updated_at` ON `{$wpdb->prefix}tmu_dramas` (`updated_at`)"
        ];
        
        $this->executeIndexes($indexes);
    }
    
    /**
     * Create indexes for cast/crew tables
     */
    private function createCastCrewIndexes(): void {
        global $wpdb;
        
        $indexes = [
            // Movies cast/crew indexes
            "CREATE INDEX `idx_movies_cast_movie_person` ON `{$wpdb->prefix}tmu_movies_cast` (`movie`, `person`)",
            "CREATE INDEX `idx_movies_cast_person_movie` ON `{$wpdb->prefix}tmu_movies_cast` (`person`, `movie`)",
            "CREATE INDEX `idx_movies_cast_job` ON `{$wpdb->prefix}tmu_movies_cast` (`job`)",
            "CREATE INDEX `idx_movies_cast_year` ON `{$wpdb->prefix}tmu_movies_cast` (`release_year`)",
            "CREATE INDEX `idx_movies_cast_order` ON `{$wpdb->prefix}tmu_movies_cast` (`order_no`)",
            
            "CREATE INDEX `idx_movies_crew_movie_person` ON `{$wpdb->prefix}tmu_movies_crew` (`movie`, `person`)",
            "CREATE INDEX `idx_movies_crew_person_movie` ON `{$wpdb->prefix}tmu_movies_crew` (`person`, `movie`)",
            "CREATE INDEX `idx_movies_crew_job` ON `{$wpdb->prefix}tmu_movies_crew` (`job`)",
            "CREATE INDEX `idx_movies_crew_department` ON `{$wpdb->prefix}tmu_movies_crew` (`department`)",
            
            // TV series cast/crew indexes
            "CREATE INDEX `idx_tv_cast_series_person` ON `{$wpdb->prefix}tmu_tv_series_cast` (`tv_series`, `person`)",
            "CREATE INDEX `idx_tv_cast_person_series` ON `{$wpdb->prefix}tmu_tv_series_cast` (`person`, `tv_series`)",
            "CREATE INDEX `idx_tv_cast_job` ON `{$wpdb->prefix}tmu_tv_series_cast` (`job`)",
            
            "CREATE INDEX `idx_tv_crew_series_person` ON `{$wpdb->prefix}tmu_tv_series_crew` (`tv_series`, `person`)",
            "CREATE INDEX `idx_tv_crew_person_series` ON `{$wpdb->prefix}tmu_tv_series_crew` (`person`, `tv_series`)",
            "CREATE INDEX `idx_tv_crew_job` ON `{$wpdb->prefix}tmu_tv_series_crew` (`job`)",
            
            // Drama cast/crew indexes
            "CREATE INDEX `idx_drama_cast_drama_person` ON `{$wpdb->prefix}tmu_dramas_cast` (`drama`, `person`)",
            "CREATE INDEX `idx_drama_cast_person_drama` ON `{$wpdb->prefix}tmu_dramas_cast` (`person`, `drama`)",
            "CREATE INDEX `idx_drama_cast_job` ON `{$wpdb->prefix}tmu_dramas_cast` (`job`)",
            
            "CREATE INDEX `idx_drama_crew_drama_person` ON `{$wpdb->prefix}tmu_dramas_crew` (`drama`, `person`)",
            "CREATE INDEX `idx_drama_crew_person_drama` ON `{$wpdb->prefix}tmu_dramas_crew` (`person`, `drama`)",
            "CREATE INDEX `idx_drama_crew_job` ON `{$wpdb->prefix}tmu_dramas_crew` (`job`)"
        ];
        
        $this->executeIndexes($indexes);
    }
    
    /**
     * Create indexes for episode tables
     */
    private function createEpisodeIndexes(): void {
        global $wpdb;
        
        $indexes = [
            // TV series episode indexes
            "CREATE INDEX `idx_tv_episodes_series_season` ON `{$wpdb->prefix}tmu_tv_series_episodes` (`tv_series`, `season_number`)",
            "CREATE INDEX `idx_tv_episodes_season_episode` ON `{$wpdb->prefix}tmu_tv_series_episodes` (`season_number`, `episode_number`)",
            "CREATE INDEX `idx_tv_episodes_air_date` ON `{$wpdb->prefix}tmu_tv_series_episodes` (`air_date`)",
            "CREATE INDEX `idx_tv_episodes_rating` ON `{$wpdb->prefix}tmu_tv_series_episodes` (`vote_average`)",
            
            "CREATE INDEX `idx_tv_seasons_series_number` ON `{$wpdb->prefix}tmu_tv_series_seasons` (`tv_series`, `season_number`)",
            "CREATE INDEX `idx_tv_seasons_air_date` ON `{$wpdb->prefix}tmu_tv_series_seasons` (`air_date`)",
            
            // Drama episode indexes
            "CREATE INDEX `idx_drama_episodes_drama_season` ON `{$wpdb->prefix}tmu_dramas_episodes` (`drama`, `season_number`)",
            "CREATE INDEX `idx_drama_episodes_season_episode` ON `{$wpdb->prefix}tmu_dramas_episodes` (`season_number`, `episode_number`)",
            "CREATE INDEX `idx_drama_episodes_air_date` ON `{$wpdb->prefix}tmu_dramas_episodes` (`air_date`)",
            "CREATE INDEX `idx_drama_episodes_rating` ON `{$wpdb->prefix}tmu_dramas_episodes` (`vote_average`)",
            
            "CREATE INDEX `idx_drama_seasons_drama_number` ON `{$wpdb->prefix}tmu_dramas_seasons` (`drama`, `season_number`)",
            "CREATE INDEX `idx_drama_seasons_air_date` ON `{$wpdb->prefix}tmu_dramas_seasons` (`air_date`)"
        ];
        
        $this->executeIndexes($indexes);
    }
    
    /**
     * Create composite indexes for complex queries
     */
    private function createCompositeIndexes(): void {
        global $wpdb;
        
        $indexes = [
            // Complex search indexes
            "CREATE INDEX `idx_movies_rating_popularity` ON `{$wpdb->prefix}tmu_movies` (`average_rating`, `popularity`)",
            "CREATE INDEX `idx_movies_date_rating` ON `{$wpdb->prefix}tmu_movies` (`release_timestamp`, `average_rating`)",
            "CREATE INDEX `idx_tv_rating_popularity` ON `{$wpdb->prefix}tmu_tv_series` (`average_rating`, `popularity`)",
            "CREATE INDEX `idx_drama_rating_popularity` ON `{$wpdb->prefix}tmu_dramas` (`average_rating`, `popularity`)",
            
            // SEO options indexes
            "CREATE INDEX `idx_seo_post_type_section` ON `{$wpdb->prefix}tmu_seo_options` (`post_type`(50), `section`(50))",
            "CREATE INDEX `idx_seo_name` ON `{$wpdb->prefix}tmu_seo_options` (`name`(100))",
            
            // Video indexes
            "CREATE INDEX `idx_videos_post_id` ON `{$wpdb->prefix}tmu_videos` (`post_id`)"
        ];
        
        $this->executeIndexes($indexes);
    }
    
    /**
     * Execute index creation
     *
     * @param array $indexes
     */
    private function executeIndexes(array $indexes): void {
        global $wpdb;
        
        foreach ($indexes as $index_sql) {
            $result = $wpdb->query($index_sql);
            
            if ($result === false) {
                // Log the error but continue with other indexes
                error_log("TMU Migration Warning: Index creation failed - {$wpdb->last_error}");
                error_log("TMU Migration Warning: Failed SQL: {$index_sql}");
            }
        }
    }
    
    /**
     * Rollback the migration
     */
    public function down(): void {
        global $wpdb;
        
        // List of all indexes to drop
        $indexes_to_drop = [
            // Movies table indexes
            "DROP INDEX `idx_movies_tmdb_id` ON `{$wpdb->prefix}tmu_movies`",
            "DROP INDEX `idx_movies_release_date` ON `{$wpdb->prefix}tmu_movies`",
            "DROP INDEX `idx_movies_rating` ON `{$wpdb->prefix}tmu_movies`",
            "DROP INDEX `idx_movies_popularity` ON `{$wpdb->prefix}tmu_movies`",
            "DROP INDEX `idx_movies_vote_count` ON `{$wpdb->prefix}tmu_movies`",
            "DROP INDEX `idx_movies_runtime` ON `{$wpdb->prefix}tmu_movies`",
            "DROP INDEX `idx_movies_created_at` ON `{$wpdb->prefix}tmu_movies`",
            "DROP INDEX `idx_movies_updated_at` ON `{$wpdb->prefix}tmu_movies`",
            
            // People table indexes
            "DROP INDEX `idx_people_tmdb_id` ON `{$wpdb->prefix}tmu_people`",
            "DROP INDEX `idx_people_gender` ON `{$wpdb->prefix}tmu_people`",
            "DROP INDEX `idx_people_popularity` ON `{$wpdb->prefix}tmu_people`",
            "DROP INDEX `idx_people_profession` ON `{$wpdb->prefix}tmu_people`",
            "DROP INDEX `idx_people_created_at` ON `{$wpdb->prefix}tmu_people`",
            "DROP INDEX `idx_people_updated_at` ON `{$wpdb->prefix}tmu_people`",
            
            // Composite indexes
            "DROP INDEX `idx_movies_rating_popularity` ON `{$wpdb->prefix}tmu_movies`",
            "DROP INDEX `idx_movies_date_rating` ON `{$wpdb->prefix}tmu_movies`",
            "DROP INDEX `idx_seo_post_type_section` ON `{$wpdb->prefix}tmu_seo_options`",
            "DROP INDEX `idx_seo_name` ON `{$wpdb->prefix}tmu_seo_options`",
            "DROP INDEX `idx_videos_post_id` ON `{$wpdb->prefix}tmu_videos`"
        ];
        
        foreach ($indexes_to_drop as $drop_sql) {
            $result = $wpdb->query($drop_sql);
            
            if ($result === false) {
                // Indexes may not exist, so just log as warning
                error_log("TMU Migration Warning: Index removal failed - {$wpdb->last_error}");
            }
        }
        
        error_log('TMU Migration: Performance indexes removed');
    }
}