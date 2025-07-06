<?php
/**
 * Term Manager Utility Class
 *
 * @package TMU\Taxonomies
 * @version 1.0.0
 */

namespace TMU\Taxonomies;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Term Manager Class
 * 
 * Utility functions for managing taxonomy terms
 */
class TermManager {
    
    /**
     * Bulk create terms from array
     *
     * @param string $taxonomy Taxonomy slug
     * @param array $terms Array of term names or term data arrays
     * @return array Created term IDs
     */
    public static function bulkCreateTerms(string $taxonomy, array $terms): array {
        $created_terms = [];
        
        foreach ($terms as $term_data) {
            if (is_string($term_data)) {
                // Simple term name
                $term_name = $term_data;
                $term_args = [];
            } elseif (is_array($term_data)) {
                // Term data array
                $term_name = $term_data['name'] ?? '';
                $term_args = $term_data;
                unset($term_args['name']);
            } else {
                continue;
            }
            
            if (empty($term_name)) {
                continue;
            }
            
            // Check if term already exists
            if (!term_exists($term_name, $taxonomy)) {
                $term = wp_insert_term($term_name, $taxonomy, $term_args);
                
                if (!is_wp_error($term)) {
                    $created_terms[] = $term['term_id'];
                    
                    // Add meta data if provided
                    if (isset($term_data['meta']) && is_array($term_data['meta'])) {
                        foreach ($term_data['meta'] as $meta_key => $meta_value) {
                            update_term_meta($term['term_id'], $meta_key, $meta_value);
                        }
                    }
                }
            }
        }
        
        return $created_terms;
    }
    
    /**
     * Get terms with meta data
     *
     * @param string $taxonomy Taxonomy slug
     * @param array $args Query arguments
     * @param array $meta_keys Meta keys to include
     * @return array Terms with meta data
     */
    public static function getTermsWithMeta(string $taxonomy, array $args = [], array $meta_keys = []): array {
        $terms = get_terms(array_merge([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ], $args));
        
        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }
        
        // Add meta data to terms
        foreach ($terms as &$term) {
            $term->meta = [];
            
            if (!empty($meta_keys)) {
                foreach ($meta_keys as $meta_key) {
                    $term->meta[$meta_key] = get_term_meta($term->term_id, $meta_key, true);
                }
            } else {
                // Get all meta for the term
                $term->meta = get_term_meta($term->term_id);
                // Flatten single values
                foreach ($term->meta as $key => $value) {
                    if (is_array($value) && count($value) === 1) {
                        $term->meta[$key] = $value[0];
                    }
                }
            }
        }
        
        return $terms;
    }
    
    /**
     * Search terms across multiple taxonomies
     *
     * @param array $taxonomies Array of taxonomy slugs
     * @param string $search Search query
     * @param int $limit Number of results to return
     * @return array Search results
     */
    public static function searchTermsMultiple(array $taxonomies, string $search, int $limit = 20): array {
        $results = [];
        
        foreach ($taxonomies as $taxonomy) {
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }
            
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'search' => $search,
                'number' => $limit,
                'hide_empty' => false,
            ]);
            
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $term->taxonomy_name = get_taxonomy($taxonomy)->labels->singular_name ?? $taxonomy;
                    $results[] = $term;
                }
            }
        }
        
        // Sort by relevance (name match first, then description)
        usort($results, function($a, $b) use ($search) {
            $a_score = 0;
            $b_score = 0;
            
            // Exact name match gets highest score
            if (stripos($a->name, $search) === 0) $a_score += 100;
            if (stripos($b->name, $search) === 0) $b_score += 100;
            
            // Name contains search term
            if (stripos($a->name, $search) !== false) $a_score += 50;
            if (stripos($b->name, $search) !== false) $b_score += 50;
            
            // Description contains search term
            if (stripos($a->description, $search) !== false) $a_score += 25;
            if (stripos($b->description, $search) !== false) $b_score += 25;
            
            return $b_score - $a_score;
        });
        
        return array_slice($results, 0, $limit);
    }
    
    /**
     * Get term hierarchy for hierarchical taxonomies
     *
     * @param string $taxonomy Taxonomy slug
     * @param int $parent_id Parent term ID (0 for top level)
     * @return array Hierarchical term structure
     */
    public static function getTermHierarchy(string $taxonomy, int $parent_id = 0): array {
        if (!is_taxonomy_hierarchical($taxonomy)) {
            return [];
        }
        
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'parent' => $parent_id,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        
        if (is_wp_error($terms)) {
            return [];
        }
        
        $hierarchy = [];
        
        foreach ($terms as $term) {
            $term_data = [
                'term' => $term,
                'children' => self::getTermHierarchy($taxonomy, $term->term_id),
            ];
            
            $hierarchy[] = $term_data;
        }
        
        return $hierarchy;
    }
    
    /**
     * Merge duplicate terms
     *
     * @param string $taxonomy Taxonomy slug
     * @param int $keep_term_id Term ID to keep
     * @param array $merge_term_ids Term IDs to merge into the kept term
     * @return bool Success status
     */
    public static function mergeTerms(string $taxonomy, int $keep_term_id, array $merge_term_ids): bool {
        $keep_term = get_term($keep_term_id, $taxonomy);
        
        if (is_wp_error($keep_term)) {
            return false;
        }
        
        foreach ($merge_term_ids as $merge_term_id) {
            $merge_term = get_term($merge_term_id, $taxonomy);
            
            if (is_wp_error($merge_term)) {
                continue;
            }
            
            // Get all posts with the merge term
            $posts = get_posts([
                'post_type' => 'any',
                'posts_per_page' => -1,
                'tax_query' => [
                    [
                        'taxonomy' => $taxonomy,
                        'field' => 'term_id',
                        'terms' => $merge_term_id,
                    ],
                ],
                'fields' => 'ids',
            ]);
            
            // Assign the keep term to all posts
            foreach ($posts as $post_id) {
                $current_terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'ids']);
                
                // Remove the merge term and add the keep term
                $new_terms = array_diff($current_terms, [$merge_term_id]);
                $new_terms[] = $keep_term_id;
                $new_terms = array_unique($new_terms);
                
                wp_set_post_terms($post_id, $new_terms, $taxonomy);
            }
            
            // Delete the merge term
            wp_delete_term($merge_term_id, $taxonomy);
        }
        
        return true;
    }
    
    /**
     * Get term usage statistics
     *
     * @param string $taxonomy Taxonomy slug
     * @param int $term_id Term ID
     * @return array Usage statistics
     */
    public static function getTermUsageStats(string $taxonomy, int $term_id): array {
        $term = get_term($term_id, $taxonomy);
        
        if (is_wp_error($term)) {
            return [];
        }
        
        $taxonomy_object = get_taxonomy($taxonomy);
        $post_types = $taxonomy_object->object_type ?? [];
        
        $stats = [
            'total' => 0,
            'by_post_type' => [],
            'by_status' => [],
        ];
        
        foreach ($post_types as $post_type) {
            if (!post_type_exists($post_type)) {
                continue;
            }
            
            $posts = get_posts([
                'post_type' => $post_type,
                'posts_per_page' => -1,
                'post_status' => 'any',
                'tax_query' => [
                    [
                        'taxonomy' => $taxonomy,
                        'field' => 'term_id',
                        'terms' => $term_id,
                    ],
                ],
                'fields' => 'ids',
            ]);
            
            $stats['by_post_type'][$post_type] = count($posts);
            $stats['total'] += count($posts);
            
            // Get post status breakdown
            foreach ($posts as $post_id) {
                $post_status = get_post_status($post_id);
                $stats['by_status'][$post_status] = ($stats['by_status'][$post_status] ?? 0) + 1;
            }
        }
        
        return $stats;
    }
    
    /**
     * Auto-assign terms based on post content
     *
     * @param int $post_id Post ID
     * @param string $taxonomy Taxonomy slug
     * @param array $keywords Array of keyword => term_id mappings
     * @return array Assigned term IDs
     */
    public static function autoAssignTerms(int $post_id, string $taxonomy, array $keywords): array {
        $post = get_post($post_id);
        
        if (!$post) {
            return [];
        }
        
        $content = $post->post_title . ' ' . $post->post_content . ' ' . $post->post_excerpt;
        $content = strtolower(strip_tags($content));
        
        $assigned_terms = [];
        
        foreach ($keywords as $keyword => $term_id) {
            if (strpos($content, strtolower($keyword)) !== false) {
                $assigned_terms[] = $term_id;
            }
        }
        
        if (!empty($assigned_terms)) {
            wp_set_post_terms($post_id, $assigned_terms, $taxonomy, true); // Append to existing terms
        }
        
        return $assigned_terms;
    }
    
    /**
     * Export terms to CSV
     *
     * @param string $taxonomy Taxonomy slug
     * @param array $meta_keys Meta keys to include in export
     * @return string CSV content
     */
    public static function exportTermsToCSV(string $taxonomy, array $meta_keys = []): string {
        $terms = self::getTermsWithMeta($taxonomy, [], $meta_keys);
        
        if (empty($terms)) {
            return '';
        }
        
        $csv_data = [];
        
        // Header row
        $headers = ['ID', 'Name', 'Slug', 'Description', 'Count', 'Parent'];
        foreach ($meta_keys as $meta_key) {
            $headers[] = ucfirst(str_replace('_', ' ', $meta_key));
        }
        $csv_data[] = $headers;
        
        // Data rows
        foreach ($terms as $term) {
            $row = [
                $term->term_id,
                $term->name,
                $term->slug,
                $term->description,
                $term->count,
                $term->parent,
            ];
            
            foreach ($meta_keys as $meta_key) {
                $row[] = $term->meta[$meta_key] ?? '';
            }
            
            $csv_data[] = $row;
        }
        
        // Convert to CSV string
        $csv_output = '';
        foreach ($csv_data as $row) {
            $csv_output .= '"' . implode('","', $row) . '"' . "\n";
        }
        
        return $csv_output;
    }
    
    /**
     * Import terms from CSV
     *
     * @param string $taxonomy Taxonomy slug
     * @param string $csv_content CSV content
     * @param array $meta_mapping Meta field mapping
     * @return array Import results
     */
    public static function importTermsFromCSV(string $taxonomy, string $csv_content, array $meta_mapping = []): array {
        $lines = explode("\n", trim($csv_content));
        
        if (empty($lines)) {
            return ['success' => false, 'message' => 'Empty CSV content'];
        }
        
        $headers = str_getcsv(array_shift($lines));
        $imported = 0;
        $skipped = 0;
        $errors = [];
        
        foreach ($lines as $line_number => $line) {
            if (empty(trim($line))) {
                continue;
            }
            
            $data = str_getcsv($line);
            
            if (count($data) < 2) {
                $skipped++;
                continue;
            }
            
            $term_name = trim($data[1] ?? ''); // Name is usually second column
            
            if (empty($term_name)) {
                $skipped++;
                continue;
            }
            
            // Check if term exists
            if (term_exists($term_name, $taxonomy)) {
                $skipped++;
                continue;
            }
            
            // Create term
            $term_args = [];
            if (!empty($data[2])) $term_args['slug'] = sanitize_title($data[2]);
            if (!empty($data[3])) $term_args['description'] = $data[3];
            if (!empty($data[5]) && is_numeric($data[5])) $term_args['parent'] = (int) $data[5];
            
            $term = wp_insert_term($term_name, $taxonomy, $term_args);
            
            if (is_wp_error($term)) {
                $errors[] = "Line " . ($line_number + 2) . ": " . $term->get_error_message();
                continue;
            }
            
            // Add meta data
            foreach ($meta_mapping as $csv_column => $meta_key) {
                if (isset($data[$csv_column]) && !empty($data[$csv_column])) {
                    update_term_meta($term['term_id'], $meta_key, $data[$csv_column]);
                }
            }
            
            $imported++;
        }
        
        return [
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }
}