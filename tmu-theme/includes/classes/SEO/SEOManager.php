<?php
namespace TMU\SEO;

/**
 * SEO Manager - Coordinates all SEO components
 */
class SEOManager {
    
    /**
     * SEO Manager instance
     *
     * @var SEOManager
     */
    private static $instance = null;
    
    /**
     * SEO components
     *
     * @var array
     */
    private $components = [];
    
    /**
     * Get instance
     *
     * @return SEOManager
     */
    public static function getInstance(): SEOManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        $this->initializeComponents();
        $this->initHooks();
    }
    
    /**
     * Initialize all SEO components
     */
    private function initializeComponents(): void {
        // Only initialize if built-in SEO is enabled
        if (!apply_filters('tmu_enable_built_in_seo', true)) {
            return;
        }
        
        // Initialize core SEO components
        $this->components = [
            'schema_manager' => new SchemaManager(),
            'meta_tags' => new MetaTags(),
            'open_graph' => new OpenGraph(),
            'twitter_card' => new TwitterCard(),
            'breadcrumb_manager' => new BreadcrumbManager(),
            'sitemap_generator' => new SitemapGenerator(),
            'analytics' => new Analytics(),
        ];
        
        // Initialize each component
        foreach ($this->components as $component) {
            if (method_exists($component, 'init')) {
                $component->init();
            }
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void {
        add_action('init', [$this, 'onInit'], 20);
        add_action('wp_head', [$this, 'outputSEOTags'], 1);
        add_action('wp_footer', [$this, 'outputFooterSEO'], 99);
    }
    
    /**
     * Handle init action
     */
    public function onInit(): void {
        // Disable default WordPress SEO features that we're replacing
        remove_action('wp_head', 'rel_canonical');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
        
        // Remove WordPress generator meta tag
        remove_action('wp_head', 'wp_generator');
        
        // Clean up WordPress head
        $this->cleanupWordPressHead();
    }
    
    /**
     * Output SEO tags
     */
    public function outputSEOTags(): void {
        // Components handle their own output via wp_head hooks
        // This method is here for any additional coordination needed
        
        // Add JSON-LD for organization/website
        if (is_front_page()) {
            $this->outputOrganizationSchema();
        }
    }
    
    /**
     * Output footer SEO elements
     */
    public function outputFooterSEO(): void {
        // Components handle their own footer output
        // This method is here for any additional coordination needed
    }
    
    /**
     * Output organization schema
     */
    private function outputOrganizationSchema(): void {
        $site_name = get_bloginfo('name');
        $site_url = home_url('/');
        $site_description = get_bloginfo('description');
        
        $logo_id = get_theme_mod('custom_logo');
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : null;
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $site_name,
            'url' => $site_url,
            'description' => $site_description,
        ];
        
        if ($logo_url) {
            $schema['logo'] = [
                '@type' => 'ImageObject',
                'url' => $logo_url
            ];
        }
        
        // Add social media profiles if configured
        $social_profiles = $this->getSocialProfiles();
        if (!empty($social_profiles)) {
            $schema['sameAs'] = $social_profiles;
        }
        
        echo '<script type="application/ld+json">';
        echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo '</script>' . "\n";
    }
    
    /**
     * Get social media profiles
     *
     * @return array
     */
    private function getSocialProfiles(): array {
        $profiles = [];
        
        $social_options = [
            'tmu_facebook_url',
            'tmu_twitter_url', 
            'tmu_instagram_url',
            'tmu_youtube_url',
            'tmu_linkedin_url'
        ];
        
        foreach ($social_options as $option) {
            $url = get_option($option);
            if ($url) {
                $profiles[] = $url;
            }
        }
        
        return $profiles;
    }
    
    /**
     * Clean up WordPress head
     */
    private function cleanupWordPressHead(): void {
        // Remove WordPress version
        remove_action('wp_head', 'wp_generator');
        
        // Remove Windows Live Writer
        remove_action('wp_head', 'wlwmanifest_link');
        
        // Remove RSD link
        remove_action('wp_head', 'rsd_link');
        
        // Remove shortlink
        remove_action('wp_head', 'wp_shortlink_wp_head', 10);
        
        // Remove feed links (optional - can be re-enabled if needed)
        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'feed_links', 2);
        
        // Remove EditURI link
        remove_action('wp_head', 'rsd_link');
        
        // Remove WordPress version from RSS feeds
        add_filter('the_generator', '__return_empty_string');
    }
    
    /**
     * Get component instance
     *
     * @param string $component_name
     * @return mixed|null
     */
    public function getComponent(string $component_name) {
        return $this->components[$component_name] ?? null;
    }
    
    /**
     * Check if SEO is enabled
     *
     * @return bool
     */
    public function isSEOEnabled(): bool {
        return apply_filters('tmu_enable_built_in_seo', true);
    }
    
    /**
     * Get all components
     *
     * @return array
     */
    public function getComponents(): array {
        return $this->components;
    }
}