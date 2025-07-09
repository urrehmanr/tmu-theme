<?php
/**
 * URL Rewrite Rules Manager
 *
 * @package TMU\Frontend
 * @version 1.0.0
 */

namespace TMU\Frontend;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rewrite Rules Manager Class
 */
class RewriteRules {
    
    /**
     * Manager instance
     *
     * @var RewriteRules
     */
    private static $instance = null;
    
    /**
     * Get manager instance
     *
     * @return RewriteRules
     */
    public static function getInstance(): RewriteRules {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize rewrite rules
     */
    public function init(): void {
        add_action('init', [$this, 'addRewriteRules']);
        add_filter('post_type_link', [$this, 'customPostTypeLinks'], 10, 2);
        add_filter('query_vars', [$this, 'addQueryVars']);
        add_action('parse_request', [$this, 'parseCustomRequests']);
    }
    
    /**
     * Add custom rewrite rules
     */
    public function addRewriteRules(): void {
        // Season rewrite rules
        add_rewrite_rule(
            '^tv-show/([^/]+)/season-([0-9]+)/?$',
            'index.php?post_type=season&tv_show=$matches[1]&season_number=$matches[2]',
            'top'
        );
        
        // Episode rewrite rules
        add_rewrite_rule(
            '^tv-show/([^/]+)/season-([0-9]+)/episode-([0-9]+)/?$',
            'index.php?post_type=episode&tv_show=$matches[1]&season_number=$matches[2]&episode_number=$matches[3]',
            'top'
        );
        
        // Drama episode rewrite rules
        add_rewrite_rule(
            '^drama/([^/]+)/episode-([0-9]+)/?$',
            'index.php?post_type=drama-episode&drama=$matches[1]&episode_number=$matches[2]',
            'top'
        );
        
        // Person filmography
        add_rewrite_rule(
            '^person/([^/]+)/filmography/?$',
            'index.php?post_type=people&name=$matches[1]&section=filmography',
            'top'
        );
        
        // Movie year archive
        add_rewrite_rule(
            '^movies/year/([0-9]{4})/?$',
            'index.php?post_type=movie&year=$matches[1]',
            'top'
        );
        
        // TV show year archive
        add_rewrite_rule(
            '^tv-shows/year/([0-9]{4})/?$',
            'index.php?post_type=tv&year=$matches[1]',
            'top'
        );
    }
    
    /**
     * Add custom query variables
     *
     * @param array $vars Query variables
     * @return array
     */
    public function addQueryVars(array $vars): array {
        $vars[] = 'tv_show';
        $vars[] = 'season_number';
        $vars[] = 'episode_number';
        $vars[] = 'drama';
        $vars[] = 'section';
        $vars[] = 'year';
        
        return $vars;
    }
    
    /**
     * Parse custom requests
     *
     * @param WP $wp WordPress environment instance
     */
    public function parseCustomRequests(WP $wp): void {
        // Handle season requests
        if (isset($wp->query_vars['post_type']) && $wp->query_vars['post_type'] === 'season' && isset($wp->query_vars['tv_show'])) {
            $this->handleSeasonRequest($wp);
        }
        
        // Handle episode requests
        if (isset($wp->query_vars['post_type']) && $wp->query_vars['post_type'] === 'episode' && isset($wp->query_vars['tv_show'])) {
            $this->handleEpisodeRequest($wp);
        }
        
        // Handle drama episode requests
        if (isset($wp->query_vars['post_type']) && $wp->query_vars['post_type'] === 'drama-episode' && isset($wp->query_vars['drama'])) {
            $this->handleDramaEpisodeRequest($wp);
        }
    }
    
    /**
     * Handle season request
     *
     * @param WP $wp WordPress environment instance
     */
    private function handleSeasonRequest(WP $wp): void {
        $tv_show_slug = $wp->query_vars['tv_show'];
        $season_number = $wp->query_vars['season_number'] ?? null;
        
        // Find TV show by slug
        $tv_show = get_posts([
            'post_type' => 'tv',
            'name' => $tv_show_slug,
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        
        if ($tv_show && $season_number) {
            // Find season by TV show ID and season number
            $seasons = get_posts([
                'post_type' => 'season',
                'meta_query' => [
                    [
                        'key' => 'tv_show_id',
                        'value' => $tv_show[0]->ID,
                        'compare' => '='
                    ],
                    [
                        'key' => 'season_number',
                        'value' => $season_number,
                        'compare' => '='
                    ]
                ],
                'posts_per_page' => 1
            ]);
            
            if ($seasons) {
                $wp->query_vars['p'] = $seasons[0]->ID;
                $wp->query_vars['post_type'] = 'season';
            }
        }
    }
    
    /**
     * Handle episode request
     *
     * @param WP $wp WordPress environment instance
     */
    private function handleEpisodeRequest(WP $wp): void {
        $tv_show_slug = $wp->query_vars['tv_show'];
        $season_number = $wp->query_vars['season_number'] ?? null;
        $episode_number = $wp->query_vars['episode_number'] ?? null;
        
        if ($tv_show_slug && $season_number && $episode_number) {
            // Find TV show
            $tv_show = get_posts([
                'post_type' => 'tv',
                'name' => $tv_show_slug,
                'posts_per_page' => 1,
                'post_status' => 'publish'
            ]);
            
            if ($tv_show) {
                // Find episode
                $episodes = get_posts([
                    'post_type' => 'episode',
                    'meta_query' => [
                        [
                            'key' => 'tv_show_id',
                            'value' => $tv_show[0]->ID,
                            'compare' => '='
                        ],
                        [
                            'key' => 'season_number',
                            'value' => $season_number,
                            'compare' => '='
                        ],
                        [
                            'key' => 'episode_number',
                            'value' => $episode_number,
                            'compare' => '='
                        ]
                    ],
                    'posts_per_page' => 1
                ]);
                
                if ($episodes) {
                    $wp->query_vars['p'] = $episodes[0]->ID;
                    $wp->query_vars['post_type'] = 'episode';
                }
            }
        }
    }
    
    /**
     * Handle drama episode request
     *
     * @param WP $wp WordPress environment instance
     */
    private function handleDramaEpisodeRequest(WP $wp): void {
        $drama_slug = $wp->query_vars['drama'];
        $episode_number = $wp->query_vars['episode_number'] ?? null;
        
        if ($drama_slug && $episode_number) {
            // Find drama
            $drama = get_posts([
                'post_type' => 'drama',
                'name' => $drama_slug,
                'posts_per_page' => 1,
                'post_status' => 'publish'
            ]);
            
            if ($drama) {
                // Find episode
                $episodes = get_posts([
                    'post_type' => 'drama-episode',
                    'meta_query' => [
                        [
                            'key' => 'drama_id',
                            'value' => $drama[0]->ID,
                            'compare' => '='
                        ],
                        [
                            'key' => 'episode_number',
                            'value' => $episode_number,
                            'compare' => '='
                        ]
                    ],
                    'posts_per_page' => 1
                ]);
                
                if ($episodes) {
                    $wp->query_vars['p'] = $episodes[0]->ID;
                    $wp->query_vars['post_type'] = 'drama-episode';
                }
            }
        }
    }
    
    /**
     * Custom post type permalinks
     *
     * @param string $link Default link
     * @param \WP_Post $post Post object
     * @return string Modified link
     */
    public function customPostTypeLinks(string $link, \WP_Post $post): string {
        switch ($post->post_type) {
            case 'season':
                return $this->getSeasonLink($post);
                
            case 'episode':
                return $this->getEpisodeLink($post);
                
            case 'drama-episode':
                return $this->getDramaEpisodeLink($post);
        }
        
        return $link;
    }
    
    /**
     * Get season link
     *
     * @param \WP_Post $post Season post object
     * @return string Season link
     */
    private function getSeasonLink(\WP_Post $post): string {
        $tv_id = get_post_meta($post->ID, 'tv_show_id', true);
        $season_number = get_post_meta($post->ID, 'season_number', true);
        
        if ($tv_id && $season_number) {
            $tv_post = get_post($tv_id);
            if ($tv_post) {
                return home_url(sprintf('%s/season/%s/', $tv_post->post_name, $season_number));
            }
        }
        
        return get_permalink($post);
    }
    
    /**
     * Get episode link
     *
     * @param \WP_Post $post Episode post object
     * @return string Episode link
     */
    private function getEpisodeLink(\WP_Post $post): string {
        $tv_id = get_post_meta($post->ID, 'tv_show_id', true);
        $season_number = get_post_meta($post->ID, 'season_number', true);
        $episode_number = get_post_meta($post->ID, 'episode_number', true);
        
        if ($tv_id && $season_number && $episode_number) {
            $tv_post = get_post($tv_id);
            if ($tv_post) {
                return home_url(sprintf('%s/season/%s/episode/%s/', $tv_post->post_name, $season_number, $episode_number));
            }
        }
        
        return get_permalink($post);
    }
    
    /**
     * Get drama episode link
     *
     * @param \WP_Post $post Drama episode post object
     * @return string Drama episode link
     */
    private function getDramaEpisodeLink(\WP_Post $post): string {
        $drama_id = get_post_meta($post->ID, 'drama_id', true);
        $episode_number = get_post_meta($post->ID, 'episode_number', true);
        
        if ($drama_id && $episode_number) {
            $drama_post = get_post($drama_id);
            if ($drama_post) {
                return home_url(sprintf('%s/episode/%s/', $drama_post->post_name, $episode_number));
            }
        }
        
        return get_permalink($post);
    }
    
    /**
     * Flush rewrite rules
     */
    public function flushRewriteRules(): void {
        $this->addRewriteRules();
        flush_rewrite_rules();
        
        tmu_log('Rewrite rules flushed', 'info');
    }
}