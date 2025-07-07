<?php
namespace TMU\SEO;

class Sitemap {
    public function init(): void {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_action('template_redirect', [$this, 'handle_sitemap_request']);
        add_action('wp_loaded', [$this, 'schedule_sitemap_generation']);
        add_filter('query_vars', [$this, 'add_query_vars']);
    }
    
    public function add_query_vars($vars): array {
        $vars[] = 'tmu_sitemap';
        return $vars;
    }
    
    public function add_rewrite_rules(): void {
        add_rewrite_rule('^sitemap\.xml$', 'index.php?tmu_sitemap=index', 'top');
        add_rewrite_rule('^sitemap-([^/]+)\.xml$', 'index.php?tmu_sitemap=$matches[1]', 'top');
        
        // Flush rewrite rules if needed
        if (get_option('tmu_sitemap_rules_flushed') !== 'yes') {
            flush_rewrite_rules();
            update_option('tmu_sitemap_rules_flushed', 'yes');
        }
    }
    
    public function handle_sitemap_request(): void {
        $sitemap_type = get_query_var('tmu_sitemap');
        
        if (!$sitemap_type) return;
        
        header('Content-Type: application/xml; charset=UTF-8');
        header('X-Robots-Tag: noindex, follow');
        
        switch ($sitemap_type) {
            case 'index':
                echo $this->generate_sitemap_index();
                break;
            case 'movies':
                echo $this->generate_post_type_sitemap('movie');
                break;
            case 'tv':
                echo $this->generate_post_type_sitemap('tv');
                break;
            case 'dramas':
                if (get_option('tmu_dramas') === 'on') {
                    echo $this->generate_post_type_sitemap('drama');
                }
                break;
            case 'people':
                echo $this->generate_post_type_sitemap('people');
                break;
            case 'taxonomies':
                echo $this->generate_taxonomy_sitemap();
                break;
            default:
                wp_die('Sitemap not found', '', 404);
        }
        
        exit;
    }
    
    private function generate_sitemap_index(): string {
        $sitemaps = [
            'movies' => [
                'url' => home_url('/sitemap-movies.xml'),
                'lastmod' => $this->get_post_type_lastmod('movie')
            ],
            'tv' => [
                'url' => home_url('/sitemap-tv.xml'),
                'lastmod' => $this->get_post_type_lastmod('tv')
            ],
            'people' => [
                'url' => home_url('/sitemap-people.xml'),
                'lastmod' => $this->get_post_type_lastmod('people')
            ],
            'taxonomies' => [
                'url' => home_url('/sitemap-taxonomies.xml'),
                'lastmod' => current_time('Y-m-d\TH:i:s+00:00')
            ]
        ];
        
        if (get_option('tmu_dramas') === 'on') {
            $sitemaps['dramas'] = [
                'url' => home_url('/sitemap-dramas.xml'),
                'lastmod' => $this->get_post_type_lastmod('drama')
            ];
        }
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($sitemaps as $type => $data) {
            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>" . esc_url($data['url']) . "</loc>\n";
            $xml .= "    <lastmod>" . $data['lastmod'] . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }
        
        $xml .= '</sitemapindex>';
        
        return $xml;
    }
    
    private function generate_post_type_sitemap($post_type): string {
        $posts = get_posts([
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => 50000,
            'orderby' => 'modified',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => 'tmu_exclude_from_sitemap',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
        $xml .= 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        
        foreach ($posts as $post) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . esc_url(get_permalink($post)) . "</loc>\n";
            $xml .= "    <lastmod>" . date('Y-m-d\TH:i:s+00:00', strtotime($post->post_modified)) . "</lastmod>\n";
            $xml .= "    <changefreq>" . $this->get_changefreq($post_type) . "</changefreq>\n";
            $xml .= "    <priority>" . $this->get_priority($post_type) . "</priority>\n";
            
            // Add image sitemap data
            if (has_post_thumbnail($post->ID)) {
                $image_url = get_the_post_thumbnail_url($post->ID, 'large');
                $xml .= "    <image:image>\n";
                $xml .= "      <image:loc>" . esc_url($image_url) . "</image:loc>\n";
                $xml .= "      <image:title>" . esc_html($post->post_title) . "</image:title>\n";
                $xml .= "    </image:image>\n";
            }
            
            $xml .= "  </url>\n";
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    private function generate_taxonomy_sitemap(): string {
        $taxonomies = ['genre', 'country', 'language', 'by-year', 'network', 'channel'];
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => true,
                'number' => 1000
            ]);
            
            if (is_wp_error($terms)) continue;
            
            foreach ($terms as $term) {
                $xml .= "  <url>\n";
                $xml .= "    <loc>" . esc_url(get_term_link($term)) . "</loc>\n";
                $xml .= "    <lastmod>" . current_time('Y-m-d\TH:i:s+00:00') . "</lastmod>\n";
                $xml .= "    <changefreq>weekly</changefreq>\n";
                $xml .= "    <priority>0.6</priority>\n";
                $xml .= "  </url>\n";
            }
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    private function get_post_type_lastmod($post_type): string {
        $latest_post = get_posts([
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);
        
        if ($latest_post) {
            return date('Y-m-d\TH:i:s+00:00', strtotime($latest_post[0]->post_modified));
        }
        
        return current_time('Y-m-d\TH:i:s+00:00');
    }
    
    private function get_changefreq($post_type): string {
        $frequencies = [
            'movie' => 'weekly',
            'tv' => 'weekly',
            'drama' => 'weekly',
            'people' => 'monthly'
        ];
        
        return $frequencies[$post_type] ?? 'weekly';
    }
    
    private function get_priority($post_type): string {
        $priorities = [
            'movie' => '0.8',
            'tv' => '0.8',
            'drama' => '0.8',
            'people' => '0.7'
        ];
        
        return $priorities[$post_type] ?? '0.8';
    }
    
    public function schedule_sitemap_generation(): void {
        if (!wp_next_scheduled('tmu_generate_sitemap')) {
            wp_schedule_event(time(), 'daily', 'tmu_generate_sitemap');
        }
        
        add_action('tmu_generate_sitemap', [$this, 'refresh_sitemap_cache']);
    }
    
    public function refresh_sitemap_cache(): void {
        // Clear any cached sitemap data
        wp_cache_flush();
        
        // Ping search engines
        $this->ping_search_engines();
    }
    
    private function ping_search_engines(): void {
        $sitemap_url = home_url('/sitemap.xml');
        
        $search_engines = [
            'google' => 'https://www.google.com/ping?sitemap=' . urlencode($sitemap_url),
            'bing' => 'https://www.bing.com/ping?sitemap=' . urlencode($sitemap_url)
        ];
        
        foreach ($search_engines as $engine => $ping_url) {
            wp_remote_get($ping_url, [
                'timeout' => 10,
                'user-agent' => 'TMU WordPress Theme Sitemap'
            ]);
        }
    }
}