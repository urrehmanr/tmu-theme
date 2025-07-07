<?php
/**
 * Asset Optimizer - CSS and JavaScript Optimization
 *
 * @package TMU\Performance
 * @version 1.0.0
 */

namespace TMU\Performance;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Asset Optimization System
 */
class AssetOptimizer {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'optimize_css'], 999);
        add_action('wp_enqueue_scripts', [$this, 'optimize_js'], 999);
        add_action('wp_head', [$this, 'add_resource_hints'], 1);
    }
    
    /**
     * Optimize CSS
     */
    public function optimize_css(): void {
        // Combine and minify CSS files
        if (!is_admin() && !wp_is_json_request()) {
            $this->combine_css_files();
            $this->add_critical_css();
        }
    }
    
    /**
     * Optimize JavaScript
     */
    public function optimize_js(): void {
        // Defer non-critical JavaScript
        add_filter('script_loader_tag', [$this, 'defer_scripts'], 10, 3);
        
        // Combine JavaScript files
        $this->combine_js_files();
    }
    
    /**
     * Combine CSS files
     */
    public function combine_css_files(): void {
        global $wp_styles;
        
        $combined_css = '';
        $handles_to_remove = [];
        
        foreach ($wp_styles->queue as $handle) {
            if (strpos($handle, 'tmu-') === 0) {
                $style = $wp_styles->registered[$handle];
                
                // Skip if no source or external resource
                if (empty($style->src) || strpos($style->src, home_url()) !== 0) {
                    continue;
                }
                
                // Get local file path
                $file_path = str_replace(home_url(), ABSPATH, $style->src);
                
                if (file_exists($file_path)) {
                    $css_content = file_get_contents($file_path);
                    $combined_css .= $this->minify_css($css_content);
                    $handles_to_remove[] = $handle;
                }
            }
        }
        
        if (!empty($combined_css)) {
            // Ensure assets directory exists
            $assets_dir = get_template_directory() . '/assets/css';
            if (!file_exists($assets_dir)) {
                wp_mkdir_p($assets_dir);
            }
            
            $combined_file = $assets_dir . '/combined.min.css';
            file_put_contents($combined_file, $combined_css);
            
            // Remove individual stylesheets
            foreach ($handles_to_remove as $handle) {
                wp_dequeue_style($handle);
            }
            
            // Enqueue combined stylesheet
            wp_enqueue_style('tmu-combined', get_template_directory_uri() . '/assets/css/combined.min.css', [], filemtime($combined_file));
        }
    }
    
    /**
     * Combine JavaScript files
     */
    public function combine_js_files(): void {
        global $wp_scripts;
        
        $combined_js = '';
        $handles_to_remove = [];
        
        foreach ($wp_scripts->queue as $handle) {
            if (strpos($handle, 'tmu-') === 0 && !in_array($handle, ['tmu-critical', 'tmu-lazy-load'])) {
                $script = $wp_scripts->registered[$handle];
                
                // Skip if no source or external resource
                if (empty($script->src) || strpos($script->src, home_url()) !== 0) {
                    continue;
                }
                
                // Get local file path
                $file_path = str_replace(home_url(), ABSPATH, $script->src);
                
                if (file_exists($file_path)) {
                    $js_content = file_get_contents($file_path);
                    $combined_js .= $js_content . "\n";
                    $handles_to_remove[] = $handle;
                }
            }
        }
        
        if (!empty($combined_js)) {
            // Ensure assets directory exists
            $assets_dir = get_template_directory() . '/assets/js';
            if (!file_exists($assets_dir)) {
                wp_mkdir_p($assets_dir);
            }
            
            $combined_file = $assets_dir . '/combined.min.js';
            file_put_contents($combined_file, $combined_js);
            
            // Remove individual scripts
            foreach ($handles_to_remove as $handle) {
                wp_dequeue_script($handle);
            }
            
            // Enqueue combined script
            wp_enqueue_script('tmu-combined', get_template_directory_uri() . '/assets/js/combined.min.js', [], filemtime($combined_file), true);
        }
    }
    
    /**
     * Add critical CSS
     */
    public function add_critical_css(): void {
        $critical_css = $this->get_critical_css();
        if ($critical_css) {
            echo "<style id='tmu-critical-css'>{$critical_css}</style>";
        }
    }
    
    /**
     * Get critical CSS
     */
    public function get_critical_css(): string {
        // Above-the-fold CSS
        return '
            body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
            .tmu-header { background: #1a1a1a; color: white; padding: 1rem 0; }
            .tmu-navigation { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
            .tmu-hero { min-height: 400px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
            .tmu-content-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; padding: 2rem; }
        ';
    }
    
    /**
     * Defer scripts
     */
    public function defer_scripts($tag, $handle, $src): string {
        // Defer non-critical scripts
        $defer_scripts = ['tmu-interactions', 'tmu-search', 'tmu-lazy-load'];
        
        if (in_array($handle, $defer_scripts)) {
            return str_replace('<script ', '<script defer ', $tag);
        }
        
        return $tag;
    }
    
    /**
     * Add resource hints
     */
    public function add_resource_hints(): void {
        // DNS prefetch for external resources
        echo '<link rel="dns-prefetch" href="//image.tmdb.org">' . "\n";
        echo '<link rel="dns-prefetch" href="//api.themoviedb.org">' . "\n";
        echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">' . "\n";
        
        // Preconnect for critical resources
        echo '<link rel="preconnect" href="https://image.tmdb.org" crossorigin>' . "\n";
        
        // Preload critical resources
        $combined_css = get_template_directory_uri() . '/assets/css/combined.min.css';
        $critical_js = get_template_directory_uri() . '/assets/js/critical.min.js';
        
        if (file_exists(get_template_directory() . '/assets/css/combined.min.css')) {
            echo '<link rel="preload" href="' . $combined_css . '" as="style">' . "\n";
        }
        
        if (file_exists(get_template_directory() . '/assets/js/critical.min.js')) {
            echo '<link rel="preload" href="' . $critical_js . '" as="script">' . "\n";
        }
        
        // Add viewport meta tag for mobile optimization
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        
        // Add theme color for PWA
        echo '<meta name="theme-color" content="#1a1a1a">' . "\n";
    }
    
    /**
     * Minify CSS
     */
    private function minify_css($css): string {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove unnecessary whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove unnecessary semicolons
        $css = str_replace(';}', '}', $css);
        
        // Remove space around operators
        $css = str_replace([' {', '{ ', ' }', '} ', ': ', ' :', '; ', ' ;'], ['{', '{', '}', '}', ':', ':', ';', ';'], $css);
        
        return trim($css);
    }
    
    /**
     * Minify JavaScript
     */
    private function minify_js($js): string {
        // Basic JavaScript minification
        // Remove single line comments (be careful with URLs)
        $js = preg_replace('/(?<!:)\/\/.*$/m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // Remove extra whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Remove whitespace around operators
        $js = str_replace([' = ', ' + ', ' - ', ' * ', ' / ', ' == ', ' != ', ' === ', ' !== '], ['=', '+', '-', '*', '/', '==', '!=', '===', '!=='], $js);
        
        return trim($js);
    }
    
    /**
     * Get asset statistics
     */
    public function get_asset_stats(): array {
        $stats = [
            'css_files_combined' => 0,
            'js_files_combined' => 0,
            'total_size_saved' => 0,
            'compression_ratio' => 0
        ];
        
        // Calculate CSS stats
        $combined_css_file = get_template_directory() . '/assets/css/combined.min.css';
        if (file_exists($combined_css_file)) {
            $stats['combined_css_size'] = filesize($combined_css_file);
        }
        
        // Calculate JS stats
        $combined_js_file = get_template_directory() . '/assets/js/combined.min.js';
        if (file_exists($combined_js_file)) {
            $stats['combined_js_size'] = filesize($combined_js_file);
        }
        
        return $stats;
    }
    
    /**
     * Clear combined assets
     */
    public function clear_combined_assets(): void {
        $files_to_clear = [
            get_template_directory() . '/assets/css/combined.min.css',
            get_template_directory() . '/assets/js/combined.min.js'
        ];
        
        foreach ($files_to_clear as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Generate critical CSS for specific page types
     */
    public function generate_page_specific_critical_css(): string {
        $critical_css = $this->get_critical_css();
        
        // Add page-specific critical CSS
        if (is_front_page()) {
            $critical_css .= '
                .hero-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 80vh; }
                .featured-content { padding: 4rem 0; }
            ';
        } elseif (is_single()) {
            $critical_css .= '
                .single-header { padding: 2rem 0; background: #f8f9fa; }
                .content-area { max-width: 800px; margin: 0 auto; padding: 2rem; }
            ';
        } elseif (is_archive()) {
            $critical_css .= '
                .archive-header { padding: 3rem 0; text-align: center; }
                .archive-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; }
            ';
        }
        
        return $critical_css;
    }
    
    /**
     * Check if optimization should run
     */
    private function should_optimize(): bool {
        // Don't optimize in admin, preview, or customizer
        if (is_admin() || is_preview() || is_customize_preview()) {
            return false;
        }
        
        // Don't optimize during development
        if (defined('WP_DEBUG') && WP_DEBUG && !get_option('tmu_optimize_in_debug', false)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Add async/defer attributes to scripts
     */
    public function add_async_defer_attributes($tag, $handle, $src): string {
        // Scripts that should be loaded asynchronously
        $async_scripts = ['google-analytics', 'gtag'];
        
        // Scripts that should be deferred
        $defer_scripts = ['tmu-interactions', 'tmu-search', 'tmu-analytics'];
        
        if (in_array($handle, $async_scripts)) {
            return str_replace('<script ', '<script async ', $tag);
        }
        
        if (in_array($handle, $defer_scripts)) {
            return str_replace('<script ', '<script defer ', $tag);
        }
        
        return $tag;
    }
    
    /**
     * Preload important assets
     */
    public function preload_important_assets(): void {
        // Preload fonts
        $fonts_to_preload = [
            get_template_directory_uri() . '/assets/fonts/roboto-regular.woff2',
            get_template_directory_uri() . '/assets/fonts/roboto-bold.woff2'
        ];
        
        foreach ($fonts_to_preload as $font) {
            if (file_exists(str_replace(get_template_directory_uri(), get_template_directory(), $font))) {
                echo '<link rel="preload" href="' . $font . '" as="font" type="font/woff2" crossorigin>' . "\n";
            }
        }
        
        // Preload hero images
        if (is_front_page()) {
            $hero_image = get_template_directory_uri() . '/assets/images/hero-bg.jpg';
            if (file_exists(str_replace(get_template_directory_uri(), get_template_directory(), $hero_image))) {
                echo '<link rel="preload" href="' . $hero_image . '" as="image">' . "\n";
            }
        }
    }
}