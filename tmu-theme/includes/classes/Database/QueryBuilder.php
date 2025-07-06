<?php
/**
 * TMU Query Builder
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
 * Query Builder Class
 */
class QueryBuilder {
    
    /**
     * WordPress database instance
     *
     * @var wpdb
     */
    private $wpdb;
    
    /**
     * Current query parts
     *
     * @var array
     */
    private $query = [
        'select' => [],
        'from' => '',
        'joins' => [],
        'where' => [],
        'order' => [],
        'limit' => '',
        'offset' => '',
        'group' => [],
        'having' => []
    ];
    
    /**
     * Query parameters for prepared statements
     *
     * @var array
     */
    private $parameters = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Create new query builder instance
     *
     * @return QueryBuilder
     */
    public static function create(): QueryBuilder {
        return new self();
    }
    
    /**
     * Set SELECT clause
     *
     * @param array|string $columns
     * @return QueryBuilder
     */
    public function select($columns = '*'): QueryBuilder {
        if (is_array($columns)) {
            $this->query['select'] = array_merge($this->query['select'], $columns);
        } else {
            $this->query['select'][] = $columns;
        }
        
        return $this;
    }
    
    /**
     * Set FROM clause
     *
     * @param string $table
     * @param string $alias
     * @return QueryBuilder
     */
    public function from(string $table, string $alias = ''): QueryBuilder {
        $table_name = $this->wpdb->prefix . $table;
        $this->query['from'] = $alias ? "`{$table_name}` AS `{$alias}`" : "`{$table_name}`";
        
        return $this;
    }
    
    /**
     * Add INNER JOIN
     *
     * @param string $table
     * @param string $condition
     * @param string $alias
     * @return QueryBuilder
     */
    public function join(string $table, string $condition, string $alias = ''): QueryBuilder {
        return $this->addJoin('INNER JOIN', $table, $condition, $alias);
    }
    
    /**
     * Add LEFT JOIN
     *
     * @param string $table
     * @param string $condition
     * @param string $alias
     * @return QueryBuilder
     */
    public function leftJoin(string $table, string $condition, string $alias = ''): QueryBuilder {
        return $this->addJoin('LEFT JOIN', $table, $condition, $alias);
    }
    
    /**
     * Add RIGHT JOIN
     *
     * @param string $table
     * @param string $condition
     * @param string $alias
     * @return QueryBuilder
     */
    public function rightJoin(string $table, string $condition, string $alias = ''): QueryBuilder {
        return $this->addJoin('RIGHT JOIN', $table, $condition, $alias);
    }
    
    /**
     * Add JOIN clause
     *
     * @param string $type
     * @param string $table
     * @param string $condition
     * @param string $alias
     * @return QueryBuilder
     */
    private function addJoin(string $type, string $table, string $condition, string $alias = ''): QueryBuilder {
        $table_name = $this->wpdb->prefix . $table;
        $table_part = $alias ? "`{$table_name}` AS `{$alias}`" : "`{$table_name}`";
        
        $this->query['joins'][] = "{$type} {$table_part} ON {$condition}";
        
        return $this;
    }
    
    /**
     * Add WHERE clause
     *
     * @param string $condition
     * @param mixed $value
     * @return QueryBuilder
     */
    public function where(string $condition, $value = null): QueryBuilder {
        if ($value !== null) {
            $this->parameters[] = $value;
            $condition = str_replace('?', '%s', $condition);
        }
        
        $this->query['where'][] = $condition;
        
        return $this;
    }
    
    /**
     * Add WHERE IN clause
     *
     * @param string $column
     * @param array $values
     * @return QueryBuilder
     */
    public function whereIn(string $column, array $values): QueryBuilder {
        if (empty($values)) {
            return $this;
        }
        
        $placeholders = array_fill(0, count($values), '%s');
        $this->parameters = array_merge($this->parameters, $values);
        
        $this->query['where'][] = "{$column} IN (" . implode(', ', $placeholders) . ")";
        
        return $this;
    }
    
    /**
     * Add WHERE NOT IN clause
     *
     * @param string $column
     * @param array $values
     * @return QueryBuilder
     */
    public function whereNotIn(string $column, array $values): QueryBuilder {
        if (empty($values)) {
            return $this;
        }
        
        $placeholders = array_fill(0, count($values), '%s');
        $this->parameters = array_merge($this->parameters, $values);
        
        $this->query['where'][] = "{$column} NOT IN (" . implode(', ', $placeholders) . ")";
        
        return $this;
    }
    
    /**
     * Add WHERE LIKE clause
     *
     * @param string $column
     * @param string $value
     * @return QueryBuilder
     */
    public function whereLike(string $column, string $value): QueryBuilder {
        $this->parameters[] = $value;
        $this->query['where'][] = "{$column} LIKE %s";
        
        return $this;
    }
    
    /**
     * Add WHERE BETWEEN clause
     *
     * @param string $column
     * @param mixed $start
     * @param mixed $end
     * @return QueryBuilder
     */
    public function whereBetween(string $column, $start, $end): QueryBuilder {
        $this->parameters[] = $start;
        $this->parameters[] = $end;
        $this->query['where'][] = "{$column} BETWEEN %s AND %s";
        
        return $this;
    }
    
    /**
     * Add WHERE NULL clause
     *
     * @param string $column
     * @return QueryBuilder
     */
    public function whereNull(string $column): QueryBuilder {
        $this->query['where'][] = "{$column} IS NULL";
        
        return $this;
    }
    
    /**
     * Add WHERE NOT NULL clause
     *
     * @param string $column
     * @return QueryBuilder
     */
    public function whereNotNull(string $column): QueryBuilder {
        $this->query['where'][] = "{$column} IS NOT NULL";
        
        return $this;
    }
    
    /**
     * Add ORDER BY clause
     *
     * @param string $column
     * @param string $direction
     * @return QueryBuilder
     */
    public function orderBy(string $column, string $direction = 'ASC'): QueryBuilder {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }
        
        $this->query['order'][] = "{$column} {$direction}";
        
        return $this;
    }
    
    /**
     * Add GROUP BY clause
     *
     * @param string $column
     * @return QueryBuilder
     */
    public function groupBy(string $column): QueryBuilder {
        $this->query['group'][] = $column;
        
        return $this;
    }
    
    /**
     * Add HAVING clause
     *
     * @param string $condition
     * @param mixed $value
     * @return QueryBuilder
     */
    public function having(string $condition, $value = null): QueryBuilder {
        if ($value !== null) {
            $this->parameters[] = $value;
            $condition = str_replace('?', '%s', $condition);
        }
        
        $this->query['having'][] = $condition;
        
        return $this;
    }
    
    /**
     * Set LIMIT clause
     *
     * @param int $limit
     * @return QueryBuilder
     */
    public function limit(int $limit): QueryBuilder {
        $this->query['limit'] = "LIMIT {$limit}";
        
        return $this;
    }
    
    /**
     * Set OFFSET clause
     *
     * @param int $offset
     * @return QueryBuilder
     */
    public function offset(int $offset): QueryBuilder {
        $this->query['offset'] = "OFFSET {$offset}";
        
        return $this;
    }
    
    /**
     * Build the complete SQL query
     *
     * @return string
     */
    public function toSql(): string {
        $sql = 'SELECT ';
        
        // SELECT clause
        if (empty($this->query['select'])) {
            $sql .= '*';
        } else {
            $sql .= implode(', ', $this->query['select']);
        }
        
        // FROM clause
        if (empty($this->query['from'])) {
            throw new \InvalidArgumentException('FROM clause is required');
        }
        $sql .= ' FROM ' . $this->query['from'];
        
        // JOIN clauses
        if (!empty($this->query['joins'])) {
            $sql .= ' ' . implode(' ', $this->query['joins']);
        }
        
        // WHERE clause
        if (!empty($this->query['where'])) {
            $sql .= ' WHERE ' . implode(' AND ', $this->query['where']);
        }
        
        // GROUP BY clause
        if (!empty($this->query['group'])) {
            $sql .= ' GROUP BY ' . implode(', ', $this->query['group']);
        }
        
        // HAVING clause
        if (!empty($this->query['having'])) {
            $sql .= ' HAVING ' . implode(' AND ', $this->query['having']);
        }
        
        // ORDER BY clause
        if (!empty($this->query['order'])) {
            $sql .= ' ORDER BY ' . implode(', ', $this->query['order']);
        }
        
        // LIMIT clause
        if (!empty($this->query['limit'])) {
            $sql .= ' ' . $this->query['limit'];
        }
        
        // OFFSET clause
        if (!empty($this->query['offset'])) {
            $sql .= ' ' . $this->query['offset'];
        }
        
        return $sql;
    }
    
    /**
     * Execute the query and return results
     *
     * @param string $output_type
     * @return array|object|string|int|null
     */
    public function get(string $output_type = OBJECT): array {
        $sql = $this->toSql();
        
        if (!empty($this->parameters)) {
            $sql = $this->wpdb->prepare($sql, $this->parameters);
        }
        
        return $this->wpdb->get_results($sql, $output_type);
    }
    
    /**
     * Execute the query and return first result
     *
     * @param string $output_type
     * @return object|array|null
     */
    public function first(string $output_type = OBJECT) {
        $this->limit(1);
        $results = $this->get($output_type);
        
        return $results[0] ?? null;
    }
    
    /**
     * Execute the query and return count
     *
     * @return int
     */
    public function count(): int {
        // Store original select
        $original_select = $this->query['select'];
        
        // Replace with COUNT(*)
        $this->query['select'] = ['COUNT(*) as total'];
        
        $sql = $this->toSql();
        
        if (!empty($this->parameters)) {
            $sql = $this->wpdb->prepare($sql, $this->parameters);
        }
        
        $result = $this->wpdb->get_var($sql);
        
        // Restore original select
        $this->query['select'] = $original_select;
        
        return (int) $result;
    }
    
    /**
     * Execute raw SQL query
     *
     * @param string $sql
     * @param array $parameters
     * @return array
     */
    public function raw(string $sql, array $parameters = []): array {
        if (!empty($parameters)) {
            $sql = $this->wpdb->prepare($sql, $parameters);
        }
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Get query for movies with cast information
     *
     * @return QueryBuilder
     */
    public function moviesWithCast(): QueryBuilder {
        return $this->select([
                'm.ID',
                'm.tmdb_id',
                'm.release_date',
                'm.average_rating',
                'p.post_title',
                'p.post_content',
                'COUNT(mc.ID) as cast_count'
            ])
            ->from('tmu_movies', 'm')
            ->join('posts', 'm.ID = p.ID', 'p')
            ->leftJoin('tmu_movies_cast', 'm.ID = mc.movie', 'mc')
            ->where("p.post_status = ?", 'publish')
            ->groupBy('m.ID');
    }
    
    /**
     * Get query for people with their filmography
     *
     * @return QueryBuilder
     */
    public function peopleWithFilmography(): QueryBuilder {
        return $this->select([
                'pe.ID',
                'pe.tmdb_id',
                'pe.popularity',
                'p.post_title',
                '(pe.no_movies + pe.no_tv_series + pe.no_dramas) as total_works'
            ])
            ->from('tmu_people', 'pe')
            ->join('posts', 'pe.ID = p.ID', 'p')
            ->where("p.post_status = ?", 'publish')
            ->where("pe.popularity > ?", 0);
    }
    
    /**
     * Get query for popular content
     *
     * @param string $content_type
     * @return QueryBuilder
     */
    public function popularContent(string $content_type = 'movies'): QueryBuilder {
        $table_map = [
            'movies' => 'tmu_movies',
            'tv_series' => 'tmu_tv_series',
            'dramas' => 'tmu_dramas'
        ];
        
        if (!isset($table_map[$content_type])) {
            throw new \InvalidArgumentException("Invalid content type: {$content_type}");
        }
        
        $table = $table_map[$content_type];
        
        return $this->select([
                't.ID',
                't.tmdb_id',
                't.popularity',
                't.average_rating',
                't.vote_count',
                'p.post_title',
                'p.post_excerpt'
            ])
            ->from($table, 't')
            ->join('posts', 't.ID = p.ID', 'p')
            ->where("p.post_status = ?", 'publish')
            ->where("t.popularity > ?", 5)
            ->orderBy('t.popularity', 'DESC');
    }
    
    /**
     * Get query for content by genre
     *
     * @param int $genre_id
     * @param string $content_type
     * @return QueryBuilder
     */
    public function contentByGenre(int $genre_id, string $content_type = 'movies'): QueryBuilder {
        $taxonomy_map = [
            'movies' => 'movie_genre',
            'tv_series' => 'tv_series_genre',
            'dramas' => 'drama_genre'
        ];
        
        if (!isset($taxonomy_map[$content_type])) {
            throw new \InvalidArgumentException("Invalid content type: {$content_type}");
        }
        
        $taxonomy = $taxonomy_map[$content_type];
        
        return $this->select([
                'p.ID',
                'p.post_title',
                'p.post_excerpt',
                'tr.object_id'
            ])
            ->from('posts', 'p')
            ->join('term_relationships', 'p.ID = tr.object_id', 'tr')
            ->join('term_taxonomy', 'tr.term_taxonomy_id = tt.term_taxonomy_id', 'tt')
            ->join('terms', 'tt.term_id = t.term_id', 't')
            ->where("p.post_type = ?", $content_type)
            ->where("p.post_status = ?", 'publish')
            ->where("tt.taxonomy = ?", $taxonomy)
            ->where("t.term_id = ?", $genre_id);
    }
    
    /**
     * Reset query builder
     *
     * @return QueryBuilder
     */
    public function reset(): QueryBuilder {
        $this->query = [
            'select' => [],
            'from' => '',
            'joins' => [],
            'where' => [],
            'order' => [],
            'limit' => '',
            'offset' => '',
            'group' => [],
            'having' => []
        ];
        
        $this->parameters = [];
        
        return $this;
    }
    
    /**
     * Clone current query builder
     *
     * @return QueryBuilder
     */
    public function copy(): QueryBuilder {
        $new_builder = new self();
        $new_builder->query = $this->query;
        $new_builder->parameters = $this->parameters;
        
        return $new_builder;
    }
}