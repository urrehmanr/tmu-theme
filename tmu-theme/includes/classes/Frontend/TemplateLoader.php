<?php
/**
 * Template Loader
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
 * Template Loader Class
 * 
 * Handles loading custom templates for TMU post types
 */
class TemplateLoader {
    
    /**
     * Singleton instance
     *
     * @var TemplateLoader
     */
    private static $instance = null;
    
    /**
     * Template directories
     *
     * @var array
     */
    private $template_dirs = [];
    
    /**
     * Template data
     *
     * @var array
     */
    private $template_data = [];
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->setTemplateDirs();
        $this->addHooks();
    }
    
    /**
     * Get singleton instance
     *
     * @return TemplateLoader
     */
    public static function getInstance(): TemplateLoader {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Set template directories
     */
    private function setTemplateDirs(): void {
        $this->template_dirs = [
            'theme' => get_stylesheet_directory() . '/templates/',
            'parent' => get_template_directory() . '/templates/',
            'plugin' => TMU_THEME_DIR . '/templates/',
        ];
    }
    
    /**
     * Add WordPress hooks
     */
    private function addHooks(): void {
        // Template hierarchy filters
        add_filter('single_template', [$this, 'loadSingleTemplate'], 10, 3);
        add_filter('archive_template', [$this, 'loadArchiveTemplate'], 10, 3);
        add_filter('taxonomy_template', [$this, 'loadTaxonomyTemplate'], 10, 3);
        add_filter('page_template', [$this, 'loadPageTemplate'], 10, 3);
        
        // Template part filters
        add_action('get_template_part', [$this, 'getTemplatePart'], 10, 3);
        
        // Enqueue template-specific assets
        add_action('wp_enqueue_scripts', [$this, 'enqueueTemplateAssets']);
        
        // Add body classes for templates
        add_filter('body_class', [$this, 'addBodyClasses']);
    }
    
    /**
     * Load single post template
     *
     * @param string $template Template path
     * @param string $type Post type
     * @param array $templates Template hierarchy
     * @return string
     */
    public function loadSingleTemplate(string $template, string $type, array $templates): string {
        if (!$this->isTMUPostType($type)) {
            return $template;
        }
        
        $custom_template = $this->locateTemplate([
            "single-{$type}.php",
            "single.php",
            "index.php"
        ]);
        
        return $custom_template ?: $template;
    }
    
    /**
     * Load archive template
     *
     * @param string $template Template path
     * @param string $type Post type
     * @param array $templates Template hierarchy
     * @return string
     */
    public function loadArchiveTemplate(string $template, string $type, array $templates): string {
        if (!$this->isTMUPostType($type)) {
            return $template;
        }
        
        $custom_template = $this->locateTemplate([
            "archive-{$type}.php",
            "archive.php",
            "index.php"
        ]);
        
        return $custom_template ?: $template;
    }
    
    /**
     * Load taxonomy template
     *
     * @param string $template Template path
     * @param string $type Template type
     * @param array $templates Template hierarchy
     * @return string
     */
    public function loadTaxonomyTemplate(string $template, string $type, array $templates): string {
        global $wp_query;
        
        $taxonomy = get_query_var('taxonomy');
        
        if (!$this->isTMUTaxonomy($taxonomy)) {
            return $template;
        }
        
        $term = get_query_var('term');
        
        $custom_template = $this->locateTemplate([
            "taxonomy-{$taxonomy}-{$term}.php",
            "taxonomy-{$taxonomy}.php",
            "taxonomy.php",
            "archive.php",
            "index.php"
        ]);
        
        return $custom_template ?: $template;
    }
    
    /**
     * Load page template
     *
     * @param string $template Template path
     * @param string $type Template type
     * @param array $templates Template hierarchy
     * @return string
     */
    public function loadPageTemplate(string $template, string $type, array $templates): string {
        global $post;
        
        if (!$post || $post->post_type !== 'page') {
            return $template;
        }
        
        // Check for TMU-specific page templates
        $page_template = get_post_meta($post->ID, '_wp_page_template', true);
        
        if ($page_template && strpos($page_template, 'tmu-') === 0) {
            $custom_template = $this->locateTemplate([$page_template]);
            return $custom_template ?: $template;
        }
        
        return $template;
    }
    
    /**
     * Get template part
     *
     * @param string $slug Template slug
     * @param string $name Template name
     * @param array $args Template arguments
     */
    public function getTemplatePart(string $slug, string $name = '', array $args = []): void {
        // Set template data for use in templates
        if (!empty($args)) {
            $this->setTemplateData($args);
        }
    }
    
    /**
     * Locate template file
     *
     * @param array $template_names Template names to search for
     * @return string|false
     */
    public function locateTemplate(array $template_names) {
        foreach ($template_names as $template_name) {
            foreach ($this->template_dirs as $dir) {
                $template_path = $dir . $template_name;
                
                if (file_exists($template_path)) {
                    return $template_path;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Include template file
     *
     * @param string $template_name Template name
     * @param array $args Template arguments
     * @param bool $return Return output instead of echoing
     * @return string|void
     */
    public function includeTemplate(string $template_name, array $args = [], bool $return = false) {
        $template_path = $this->locateTemplate([$template_name]);
        
        if (!$template_path) {
            if ($return) {
                return '';
            }
            return;
        }
        
        // Set template data
        if (!empty($args)) {
            $this->setTemplateData($args);
        }
        
        if ($return) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        include $template_path;
    }
    
    /**
     * Get template part with TMU hierarchy
     *
     * @param string $slug Template slug
     * @param string $name Template name
     * @param array $args Template arguments
     * @return string|void
     */
    public function getTMUTemplatePart(string $slug, string $name = '', array $args = [], bool $return = false) {
        $templates = [];
        
        if ($name) {
            $templates[] = "parts/{$slug}-{$name}.php";
        }
        
        $templates[] = "parts/{$slug}.php";
        
        foreach ($templates as $template) {
            $located = $this->locateTemplate([$template]);
            
            if ($located) {
                // Set template data
                if (!empty($args)) {
                    $this->setTemplateData($args);
                }
                
                if ($return) {
                    ob_start();
                    include $located;
                    return ob_get_clean();
                }
                
                include $located;
                return;
            }
        }
        
        if ($return) {
            return '';
        }
    }
    
    /**
     * Set template data
     *
     * @param array $data Template data
     */
    public function setTemplateData(array $data): void {
        $this->template_data = array_merge($this->template_data, $data);
    }
    
    /**
     * Get template data
     *
     * @param string $key Data key
     * @param mixed $default Default value
     * @return mixed
     */
    public function getTemplateData(string $key = '', $default = null) {
        if (empty($key)) {
            return $this->template_data;
        }
        
        return $this->template_data[$key] ?? $default;
    }
    
    /**
     * Clear template data
     */
    public function clearTemplateData(): void {
        $this->template_data = [];
    }
    
    /**
     * Check if post type is TMU post type
     *
     * @param string $post_type Post type
     * @return bool
     */
    private function isTMUPostType(string $post_type): bool {
        $tmu_post_types = ['movie', 'tv', 'season', 'episode', 'drama', 'drama_episode', 'people', 'video'];
        return in_array($post_type, $tmu_post_types);
    }
    
    /**
     * Check if taxonomy is TMU taxonomy
     *
     * @param string $taxonomy Taxonomy
     * @return bool
     */
    private function isTMUTaxonomy(string $taxonomy): bool {
        $tmu_taxonomies = ['genre', 'country', 'language', 'by-year', 'production-company', 'network', 'profession'];
        return in_array($taxonomy, $tmu_taxonomies);
    }
    
    /**
     * Enqueue template-specific assets
     */
    public function enqueueTemplateAssets(): void {
        global $post;
        
        if (is_singular() && $post && $this->isTMUPostType($post->post_type)) {
            // Enqueue post type specific styles
            $style_file = "css/{$post->post_type}.css";
            $style_path = TMU_ASSETS_BUILD_URL . '/' . $style_file;
            
            if (file_exists(TMU_ASSETS_BUILD_DIR . '/' . $style_file)) {
                wp_enqueue_style(
                    "tmu-{$post->post_type}",
                    $style_path,
                    ['tmu-main-style'],
                    TMU_VERSION
                );
            }
            
            // Enqueue post type specific scripts
            $script_file = "js/{$post->post_type}.js";
            $script_path = TMU_ASSETS_BUILD_URL . '/' . $script_file;
            
            if (file_exists(TMU_ASSETS_BUILD_DIR . '/' . $script_file)) {
                wp_enqueue_script(
                    "tmu-{$post->post_type}",
                    $script_path,
                    ['tmu-main-script'],
                    TMU_VERSION,
                    true
                );
            }
        }
        
        // Archive pages
        if (is_archive()) {
            $queried_object = get_queried_object();
            
            if ($queried_object && isset($queried_object->name)) {
                $object_name = $queried_object->name;
                
                if ($this->isTMUPostType($object_name) || $this->isTMUTaxonomy($object_name)) {
                    $style_file = "css/archive-{$object_name}.css";
                    $style_path = TMU_ASSETS_BUILD_URL . '/' . $style_file;
                    
                    if (file_exists(TMU_ASSETS_BUILD_DIR . '/' . $style_file)) {
                        wp_enqueue_style(
                            "tmu-archive-{$object_name}",
                            $style_path,
                            ['tmu-main-style'],
                            TMU_VERSION
                        );
                    }
                }
            }
        }
    }
    
    /**
     * Add body classes for TMU templates
     *
     * @param array $classes Body classes
     * @return array
     */
    public function addBodyClasses(array $classes): array {
        global $post;
        
        if (is_singular() && $post && $this->isTMUPostType($post->post_type)) {
            $classes[] = 'tmu-single';
            $classes[] = "tmu-single-{$post->post_type}";
            
            // Add TMDB sync status class
            $tmdb_id = get_post_meta($post->ID, 'tmdb_id', true);
            if ($tmdb_id) {
                $classes[] = 'tmu-has-tmdb';
            }
        }
        
        if (is_archive()) {
            $queried_object = get_queried_object();
            
            if ($queried_object) {
                if (isset($queried_object->name) && $this->isTMUPostType($queried_object->name)) {
                    $classes[] = 'tmu-archive';
                    $classes[] = "tmu-archive-{$queried_object->name}";
                }
                
                if (isset($queried_object->taxonomy) && $this->isTMUTaxonomy($queried_object->taxonomy)) {
                    $classes[] = 'tmu-taxonomy';
                    $classes[] = "tmu-taxonomy-{$queried_object->taxonomy}";
                }
            }
        }
        
        return $classes;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    private function __wakeup() {}
}