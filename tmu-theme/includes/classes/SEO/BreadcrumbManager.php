<?php
namespace TMU\SEO;

class BreadcrumbManager {
    public function init(): void {
        add_action('wp_head', [$this, 'output_breadcrumb_schema'], 7);
        add_action('tmu_breadcrumbs', [$this, 'render_breadcrumbs']);
    }
    
    public function render_breadcrumbs(): void {
        $breadcrumbs = $this->get_breadcrumbs();
        
        if (empty($breadcrumbs)) return;
        
        echo '<nav class="tmu-breadcrumbs bg-gray-100 py-3 px-4 rounded-lg mb-6" aria-label="Breadcrumb">';
        echo '<ol class="flex items-center space-x-2 text-sm">';
        
        foreach ($breadcrumbs as $index => $breadcrumb) {
            $is_last = ($index === count($breadcrumbs) - 1);
            
            echo '<li class="flex items-center">';
            
            if ($index > 0) {
                echo '<svg class="w-4 h-4 text-gray-400 mx-2" fill="currentColor" viewBox="0 0 20 20">';
                echo '<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>';
                echo '</svg>';
            }
            
            if ($is_last) {
                echo '<span class="text-gray-600 font-medium" aria-current="page">';
                echo esc_html($breadcrumb['name']);
                echo '</span>';
            } else {
                echo '<a href="' . esc_url($breadcrumb['url']) . '" class="text-blue-600 hover:text-blue-800 transition-colors duration-200">';
                echo esc_html($breadcrumb['name']);
                echo '</a>';
            }
            
            echo '</li>';
        }
        
        echo '</ol>';
        echo '</nav>';
    }
    
    public function output_breadcrumb_schema(): void {
        $breadcrumbs = $this->get_breadcrumbs();
        
        if (empty($breadcrumbs)) return;
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];
        
        foreach ($breadcrumbs as $position => $breadcrumb) {
            $schema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $breadcrumb['name'],
                'item' => $breadcrumb['url']
            ];
        }
        
        echo '<script type="application/ld+json">';
        echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo '</script>' . "\n";
    }
    
    private function get_breadcrumbs(): array {
        $breadcrumbs = [
            ['name' => 'Home', 'url' => home_url('/')]
        ];
        
        if (is_singular()) {
            $this->add_singular_breadcrumbs($breadcrumbs);
        } elseif (is_post_type_archive()) {
            $this->add_archive_breadcrumbs($breadcrumbs);
        } elseif (is_tax()) {
            $this->add_taxonomy_breadcrumbs($breadcrumbs);
        } elseif (is_search()) {
            $this->add_search_breadcrumbs($breadcrumbs);
        } elseif (is_404()) {
            $this->add_404_breadcrumbs($breadcrumbs);
        }
        
        return $breadcrumbs;
    }
    
    private function add_singular_breadcrumbs(&$breadcrumbs): void {
        $post_type = get_post_type();
        $post_type_object = get_post_type_object($post_type);
        
        // Add post type archive
        if ($post_type_object && $post_type_object->has_archive) {
            $breadcrumbs[] = [
                'name' => $post_type_object->labels->name,
                'url' => get_post_type_archive_link($post_type)
            ];
        }
        
        // Add taxonomy breadcrumbs for specific post types
        if (in_array($post_type, ['movie', 'tv', 'drama'])) {
            $this->add_content_taxonomy_breadcrumbs($breadcrumbs, get_the_ID());
        }
        
        // Add current post
        $breadcrumbs[] = [
            'name' => get_the_title(),
            'url' => get_permalink()
        ];
    }
    
    private function add_archive_breadcrumbs(&$breadcrumbs): void {
        $post_type_object = get_queried_object();
        
        $breadcrumbs[] = [
            'name' => $post_type_object->labels->name,
            'url' => get_post_type_archive_link($post_type_object->name)
        ];
    }
    
    private function add_taxonomy_breadcrumbs(&$breadcrumbs): void {
        $term = get_queried_object();
        $taxonomy = get_taxonomy($term->taxonomy);
        
        // Add post type archive if taxonomy is specific to a post type
        $post_type = $this->get_taxonomy_post_type($term->taxonomy);
        if ($post_type) {
            $post_type_object = get_post_type_object($post_type);
            if ($post_type_object && $post_type_object->has_archive) {
                $breadcrumbs[] = [
                    'name' => $post_type_object->labels->name,
                    'url' => get_post_type_archive_link($post_type)
                ];
            }
        }
        
        // Add parent terms if hierarchical
        if ($taxonomy->hierarchical && $term->parent) {
            $parent_terms = $this->get_parent_terms($term);
            foreach ($parent_terms as $parent_term) {
                $breadcrumbs[] = [
                    'name' => $parent_term->name,
                    'url' => get_term_link($parent_term)
                ];
            }
        }
        
        // Add current term
        $breadcrumbs[] = [
            'name' => $term->name,
            'url' => get_term_link($term)
        ];
    }
    
    private function add_content_taxonomy_breadcrumbs(&$breadcrumbs, $post_id): void {
        // Add primary genre if exists
        $genres = get_the_terms($post_id, 'genre');
        if ($genres && !is_wp_error($genres)) {
            $primary_genre = $genres[0];
            $breadcrumbs[] = [
                'name' => $primary_genre->name,
                'url' => get_term_link($primary_genre)
            ];
        }
    }
    
    private function add_search_breadcrumbs(&$breadcrumbs): void {
        $search_query = get_search_query();
        
        $breadcrumbs[] = [
            'name' => 'Search Results',
            'url' => get_search_link()
        ];
        
        if ($search_query) {
            $breadcrumbs[] = [
                'name' => 'Results for "' . $search_query . '"',
                'url' => get_search_link($search_query)
            ];
        }
    }
    
    private function add_404_breadcrumbs(&$breadcrumbs): void {
        $breadcrumbs[] = [
            'name' => '404 - Page Not Found',
            'url' => ''
        ];
    }
    
    private function get_parent_terms($term): array {
        $parents = [];
        $parent_id = $term->parent;
        
        while ($parent_id) {
            $parent = get_term($parent_id, $term->taxonomy);
            if (is_wp_error($parent)) break;
            
            array_unshift($parents, $parent);
            $parent_id = $parent->parent;
        }
        
        return $parents;
    }
    
    private function get_taxonomy_post_type($taxonomy): ?string {
        $taxonomy_map = [
            'genre' => 'movie',
            'country' => 'movie',
            'language' => 'movie',
            'by-year' => 'movie',
            'network' => 'tv',
            'channel' => 'tv'
        ];
        
        return $taxonomy_map[$taxonomy] ?? null;
    }
    
    public function get_breadcrumbs_for_display(): array {
        return $this->get_breadcrumbs();
    }
}