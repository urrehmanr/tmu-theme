<?php
/**
 * TMU Data Manager
 *
 * @package TMU\Database
 * @version 1.0.0
 */

namespace TMU\Database;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Data Manager Class
 */
class DataManager {
    
    /**
     * WordPress database instance
     *
     * @var wpdb
     */
    private $wpdb;
    
    /**
     * Query builder instance
     *
     * @var QueryBuilder
     */
    private $queryBuilder;
    
    /**
     * Data validator instance
     *
     * @var DataValidator
     */
    private $validator;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->queryBuilder = new QueryBuilder();
        $this->validator = new DataValidator();
    }
    
    /**
     * Create a new record
     *
     * @param string $table
     * @param array $data
     * @return int|false Insert ID on success, false on failure
     */
    public function create(string $table, array $data) {
        $table_name = $this->wpdb->prefix . $table;
        
        // Validate data before inserting
        if (!$this->validateData($table, $data, 'create')) {
            return false;
        }
        
        // Sanitize data
        $sanitized_data = $this->sanitizeData($data);
        
        $result = $this->wpdb->insert($table_name, $sanitized_data);
        
        if ($result === false) {
            error_log("TMU Data Manager: Failed to create record in {$table}. Error: " . $this->wpdb->last_error);
            return false;
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Read records from database
     *
     * @param string $table
     * @param array $conditions
     * @param array $options
     * @return array
     */
    public function read(string $table, array $conditions = [], array $options = []): array {
        $query = $this->queryBuilder->reset()->from($table);
        
        // Apply conditions
        foreach ($conditions as $column => $value) {
            if (is_array($value)) {
                $query->whereIn($column, $value);
            } else {
                $query->where("{$column} = ?", $value);
            }
        }
        
        // Apply options
        if (isset($options['order_by'])) {
            $direction = $options['order_direction'] ?? 'ASC';
            $query->orderBy($options['order_by'], $direction);
        }
        
        if (isset($options['limit'])) {
            $query->limit($options['limit']);
        }
        
        if (isset($options['offset'])) {
            $query->offset($options['offset']);
        }
        
        return $query->get();
    }
    
    /**
     * Update records in database
     *
     * @param string $table
     * @param array $data
     * @param array $conditions
     * @return int|false Number of rows updated, false on failure
     */
    public function update(string $table, array $data, array $conditions) {
        $table_name = $this->wpdb->prefix . $table;
        
        // Validate data before updating
        if (!$this->validateData($table, $data, 'update')) {
            return false;
        }
        
        // Sanitize data
        $sanitized_data = $this->sanitizeData($data);
        $sanitized_conditions = $this->sanitizeData($conditions);
        
        $result = $this->wpdb->update($table_name, $sanitized_data, $sanitized_conditions);
        
        if ($result === false) {
            error_log("TMU Data Manager: Failed to update record in {$table}. Error: " . $this->wpdb->last_error);
            return false;
        }
        
        return $result;
    }
    
    /**
     * Delete records from database
     *
     * @param string $table
     * @param array $conditions
     * @return int|false Number of rows deleted, false on failure
     */
    public function delete(string $table, array $conditions) {
        $table_name = $this->wpdb->prefix . $table;
        
        if (empty($conditions)) {
            error_log("TMU Data Manager: Delete operation requires conditions");
            return false;
        }
        
        $sanitized_conditions = $this->sanitizeData($conditions);
        
        $result = $this->wpdb->delete($table_name, $sanitized_conditions);
        
        if ($result === false) {
            error_log("TMU Data Manager: Failed to delete record from {$table}. Error: " . $this->wpdb->last_error);
            return false;
        }
        
        return $result;
    }
    
    /**
     * Get a single record by ID
     *
     * @param string $table
     * @param int $id
     * @return object|null
     */
    public function find(string $table, int $id): ?object {
        $results = $this->read($table, ['ID' => $id], ['limit' => 1]);
        return $results[0] ?? null;
    }
    
    /**
     * Check if record exists
     *
     * @param string $table
     * @param array $conditions
     * @return bool
     */
    public function exists(string $table, array $conditions): bool {
        $count = $this->queryBuilder->reset()
            ->from($table)
            ->where('1=1');
        
        foreach ($conditions as $column => $value) {
            $count->where("{$column} = ?", $value);
        }
        
        return $count->count() > 0;
    }
    
    /**
     * Get count of records
     *
     * @param string $table
     * @param array $conditions
     * @return int
     */
    public function count(string $table, array $conditions = []): int {
        $query = $this->queryBuilder->reset()->from($table);
        
        foreach ($conditions as $column => $value) {
            $query->where("{$column} = ?", $value);
        }
        
        return $query->count();
    }
    
    /**
     * Get movies with cast and crew information
     *
     * @param array $conditions
     * @param array $options
     * @return array
     */
    public function getMoviesWithCast(array $conditions = [], array $options = []): array {
        $query = $this->queryBuilder->reset()
            ->select([
                'm.ID',
                'm.tmdb_id',
                'm.release_date',
                'm.release_timestamp',
                'm.average_rating',
                'm.vote_count',
                'm.popularity',
                'p.post_title',
                'p.post_content',
                'p.post_excerpt',
                'GROUP_CONCAT(DISTINCT CONCAT(cast_person.post_title, ":", mc.character_name) SEPARATOR "|") as cast_info',
                'GROUP_CONCAT(DISTINCT CONCAT(crew_person.post_title, ":", mcr.job) SEPARATOR "|") as crew_info'
            ])
            ->from('tmu_movies', 'm')
            ->join('posts', 'm.ID = p.ID', 'p')
            ->leftJoin('tmu_movies_cast', 'm.ID = mc.movie', 'mc')
            ->leftJoin('posts', 'mc.person = cast_person.ID', 'cast_person')
            ->leftJoin('tmu_movies_crew', 'm.ID = mcr.movie', 'mcr')
            ->leftJoin('posts', 'mcr.person = crew_person.ID', 'crew_person')
            ->where("p.post_status = ?", 'publish')
            ->groupBy('m.ID');
        
        // Apply additional conditions
        foreach ($conditions as $column => $value) {
            $query->where("{$column} = ?", $value);
        }
        
        // Apply options
        if (isset($options['order_by'])) {
            $direction = $options['order_direction'] ?? 'ASC';
            $query->orderBy($options['order_by'], $direction);
        }
        
        if (isset($options['limit'])) {
            $query->limit($options['limit']);
        }
        
        return $query->get();
    }
    
    /**
     * Get person's filmography
     *
     * @param int $person_id
     * @return array
     */
    public function getPersonFilmography(int $person_id): array {
        $filmography = [
            'movies' => [],
            'tv_series' => [],
            'dramas' => []
        ];
        
        // Get movies
        $movies = $this->queryBuilder->reset()
            ->select([
                'p.ID',
                'p.post_title',
                'm.release_date',
                'mc.character_name',
                'mc.job',
                '"movie" as content_type'
            ])
            ->from('tmu_movies_cast', 'mc')
            ->join('posts', 'mc.movie = p.ID', 'p')
            ->join('tmu_movies', 'p.ID = m.ID', 'm')
            ->where("mc.person = ?", $person_id)
            ->where("p.post_status = ?", 'publish')
            ->orderBy('m.release_timestamp', 'DESC')
            ->get();
        
        $filmography['movies'] = $movies;
        
        // Get TV series
        $tv_series = $this->queryBuilder->reset()
            ->select([
                'p.ID',
                'p.post_title',
                't.release_date',
                'tc.character_name',
                'tc.job',
                '"tv_series" as content_type'
            ])
            ->from('tmu_tv_series_cast', 'tc')
            ->join('posts', 'tc.tv_series = p.ID', 'p')
            ->join('tmu_tv_series', 'p.ID = t.ID', 't')
            ->where("tc.person = ?", $person_id)
            ->where("p.post_status = ?", 'publish')
            ->orderBy('t.release_timestamp', 'DESC')
            ->get();
        
        $filmography['tv_series'] = $tv_series;
        
        // Get dramas
        $dramas = $this->queryBuilder->reset()
            ->select([
                'p.ID',
                'p.post_title',
                'd.release_date',
                'dc.character_name',
                'dc.job',
                '"drama" as content_type'
            ])
            ->from('tmu_dramas_cast', 'dc')
            ->join('posts', 'dc.drama = p.ID', 'p')
            ->join('tmu_dramas', 'p.ID = d.ID', 'd')
            ->where("dc.person = ?", $person_id)
            ->where("p.post_status = ?", 'publish')
            ->orderBy('d.release_timestamp', 'DESC')
            ->get();
        
        $filmography['dramas'] = $dramas;
        
        return $filmography;
    }
    
    /**
     * Search content by title
     *
     * @param string $search_term
     * @param array $content_types
     * @param int $limit
     * @return array
     */
    public function searchContent(string $search_term, array $content_types = ['movies', 'tv_series', 'dramas'], int $limit = 20): array {
        $results = [];
        
        foreach ($content_types as $type) {
            $table_map = [
                'movies' => 'tmu_movies',
                'tv_series' => 'tmu_tv_series',
                'dramas' => 'tmu_dramas',
                'people' => 'tmu_people'
            ];
            
            if (!isset($table_map[$type])) {
                continue;
            }
            
            $table = $table_map[$type];
            $search_results = $this->queryBuilder->reset()
                ->select([
                    't.ID',
                    'p.post_title',
                    'p.post_excerpt',
                    't.average_rating',
                    't.popularity',
                    "'{$type}' as content_type"
                ])
                ->from($table, 't')
                ->join('posts', 't.ID = p.ID', 'p')
                ->where("p.post_status = ?", 'publish')
                ->whereLike('p.post_title', "%{$search_term}%")
                ->orderBy('t.popularity', 'DESC')
                ->limit($limit)
                ->get();
            
            $results[$type] = $search_results;
        }
        
        return $results;
    }
    
    /**
     * Get popular content
     *
     * @param string $content_type
     * @param int $limit
     * @return array
     */
    public function getPopularContent(string $content_type = 'movies', int $limit = 10): array {
        return $this->queryBuilder->reset()
            ->popularContent($content_type)
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get content by genre
     *
     * @param int $genre_id
     * @param string $content_type
     * @param int $limit
     * @return array
     */
    public function getContentByGenre(int $genre_id, string $content_type = 'movies', int $limit = 20): array {
        return $this->queryBuilder->reset()
            ->contentByGenre($genre_id, $content_type)
            ->limit($limit)
            ->get();
    }
    
    /**
     * Sync data from TMDB
     *
     * @param string $content_type
     * @param int $tmdb_id
     * @param array $tmdb_data
     * @return int|false
     */
    public function syncFromTMDB(string $content_type, int $tmdb_id, array $tmdb_data) {
        $table_map = [
            'movie' => 'tmu_movies',
            'tv' => 'tmu_tv_series',
            'person' => 'tmu_people'
        ];
        
        if (!isset($table_map[$content_type])) {
            return false;
        }
        
        $table = $table_map[$content_type];
        
        // Check if record already exists
        $existing = $this->read($table, ['tmdb_id' => $tmdb_id]);
        
        if (!empty($existing)) {
            // Update existing record
            return $this->update($table, $tmdb_data, ['tmdb_id' => $tmdb_id]);
        } else {
            // Create new record
            $tmdb_data['tmdb_id'] = $tmdb_id;
            return $this->create($table, $tmdb_data);
        }
    }
    
    /**
     * Validate data before operations
     *
     * @param string $table
     * @param array $data
     * @param string $operation
     * @return bool
     */
    private function validateData(string $table, array $data, string $operation): bool {
        // Basic validation rules
        $validation_rules = [
            'tmu_movies' => [
                'required' => ['ID'],
                'optional' => ['tmdb_id', 'release_date', 'average_rating']
            ],
            'tmu_people' => [
                'required' => ['ID'],
                'optional' => ['tmdb_id', 'name', 'popularity']
            ]
        ];
        
        if (!isset($validation_rules[$table])) {
            return true; // No specific rules, allow operation
        }
        
        $rules = $validation_rules[$table];
        
        // Check required fields for create operations
        if ($operation === 'create') {
            foreach ($rules['required'] as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    error_log("TMU Data Manager: Required field '{$field}' missing for {$table}");
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Sanitize data for database operations
     *
     * @param array $data
     * @return array
     */
    private function sanitizeData(array $data): array {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = sanitize_text_field($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = wp_json_encode($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get database statistics
     *
     * @return array
     */
    public function getStatistics(): array {
        return $this->validator->getDatabaseStatistics();
    }
    
    /**
     * Clean orphaned data
     *
     * @return array
     */
    public function cleanOrphanedData(): array {
        return $this->validator->cleanOrphanedData();
    }
    
    /**
     * Execute raw SQL query
     *
     * @param string $sql
     * @param array $parameters
     * @return array
     */
    public function query(string $sql, array $parameters = []): array {
        return $this->queryBuilder->raw($sql, $parameters);
    }
    
    /**
     * Begin transaction
     *
     * @return bool
     */
    public function beginTransaction(): bool {
        return $this->wpdb->query('START TRANSACTION');
    }
    
    /**
     * Commit transaction
     *
     * @return bool
     */
    public function commit(): bool {
        return $this->wpdb->query('COMMIT');
    }
    
    /**
     * Rollback transaction
     *
     * @return bool
     */
    public function rollback(): bool {
        return $this->wpdb->query('ROLLBACK');
    }
    
    /**
     * Get WordPress database instance
     *
     * @return wpdb
     */
    public function getDatabase(): \wpdb {
        return $this->wpdb;
    }
    
    /**
     * Get query builder instance
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder {
        return $this->queryBuilder;
    }
}