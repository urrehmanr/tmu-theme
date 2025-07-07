<?php
/**
 * ARIA Labels Management
 * 
 * Comprehensive ARIA labels and attributes management.
 * Ensures proper ARIA implementation for enhanced accessibility.
 * 
 * @package TMU\Accessibility
 * @since 1.0.0
 */

namespace TMU\Accessibility;

/**
 * AriaLabels class
 * 
 * Handles ARIA labels, attributes, and semantic markup
 */
class AriaLabels {
    
    /**
     * ARIA label mappings
     * @var array
     */
    private $aria_mappings = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_aria_mappings();
        $this->init_hooks();
    }
    
    /**
     * Initialize ARIA label mappings
     */
    private function init_aria_mappings(): void {
        $this->aria_mappings = [
            'post_types' => [
                'movie' => __('Movie information', 'tmu-theme'),
                'tv' => __('TV series information', 'tmu-theme'),
                'drama' => __('Drama information', 'tmu-theme'),
                'people' => __('Person information', 'tmu-theme')
            ],
            'navigation' => [
                'primary' => __('Main navigation', 'tmu-theme'),
                'secondary' => __('Secondary navigation', 'tmu-theme'),
                'footer' => __('Footer navigation', 'tmu-theme'),
                'breadcrumb' => __('Breadcrumb navigation', 'tmu-theme')
            ],
            'content_areas' => [
                'main' => __('Main content', 'tmu-theme'),
                'sidebar' => __('Sidebar', 'tmu-theme'),
                'header' => __('Site header', 'tmu-theme'),
                'footer' => __('Site footer', 'tmu-theme')
            ],
            'forms' => [
                'search' => __('Search form', 'tmu-theme'),
                'contact' => __('Contact form', 'tmu-theme'),
                'comment' => __('Comment form', 'tmu-theme'),
                'newsletter' => __('Newsletter subscription form', 'tmu-theme')
            ],
            'interactive' => [
                'modal' => __('Modal dialog', 'tmu-theme'),
                'dropdown' => __('Dropdown menu', 'tmu-theme'),
                'tab' => __('Tab panel', 'tmu-theme'),
                'accordion' => __('Accordion section', 'tmu-theme')
            ]
        ];
        
        // Allow customization via filters
        $this->aria_mappings = apply_filters('tmu_aria_mappings', $this->aria_mappings);
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        // Content ARIA enhancement
        add_filter('the_content', [$this, 'add_content_aria_labels'], 997);
        add_filter('the_excerpt', [$this, 'add_excerpt_aria_labels'], 997);
        
        // Navigation ARIA enhancement
        add_filter('wp_nav_menu_args', [$this, 'add_navigation_aria_attributes']);
        add_filter('wp_nav_menu', [$this, 'enhance_navigation_aria'], 10, 2);
        
        // Form ARIA enhancement
        add_filter('comment_form_defaults', [$this, 'add_form_aria_attributes']);
        add_filter('get_search_form', [$this, 'add_search_form_aria']);
        
        // Post/page ARIA enhancement
        add_filter('post_class', [$this, 'add_post_aria_classes'], 10, 3);
        add_filter('body_class', [$this, 'add_body_aria_classes']);
        
        // Widget ARIA enhancement
        add_filter('dynamic_sidebar_params', [$this, 'add_widget_aria_attributes']);
        
        // Image ARIA enhancement
        add_filter('wp_get_attachment_image_attributes', [$this, 'add_image_aria_attributes'], 10, 3);
        
        // Table ARIA enhancement
        add_filter('the_content', [$this, 'add_table_aria_attributes'], 996);
        
        // Link ARIA enhancement
        add_filter('the_content', [$this, 'add_link_aria_attributes'], 995);
        
        // Heading ARIA enhancement
        add_action('wp_head', [$this, 'add_heading_aria_structure'], 3);
        
        // Custom post type ARIA
        add_action('init', [$this, 'add_post_type_aria_support']);
        
        // Admin ARIA enhancement
        if (is_admin()) {
            add_action('admin_head', [$this, 'add_admin_aria_labels']);
        }
        
        // AJAX response ARIA
        add_filter('wp_ajax_*', [$this, 'add_ajax_aria_response']);
        add_filter('wp_ajax_nopriv_*', [$this, 'add_ajax_aria_response']);
    }
    
    /**
     * Add content ARIA labels
     */
    public function add_content_aria_labels($content): string {
        if (is_singular()) {
            $post_type = get_post_type();
            $aria_label = $this->get_post_type_aria_label($post_type);
            
            if ($aria_label) {
                $content = '<div role="article" aria-label="' . esc_attr($aria_label) . '">' . 
                    $content . '</div>';
            }
        }
        
        // Add ARIA landmarks to content sections
        $content = $this->add_content_landmarks($content);
        
        // Add ARIA attributes to interactive elements
        $content = $this->add_interactive_aria($content);
        
        return $content;
    }
    
    /**
     * Add excerpt ARIA labels
     */
    public function add_excerpt_aria_labels($excerpt): string {
        if (!empty($excerpt)) {
            $post_type = get_post_type();
            $post_title = get_the_title();
            $aria_label = sprintf(
                __('Excerpt from %s: %s', 'tmu-theme'),
                $this->get_post_type_aria_label($post_type),
                $post_title
            );
            
            $excerpt = '<div role="contentinfo" aria-label="' . esc_attr($aria_label) . '">' . 
                $excerpt . '</div>';
        }
        
        return $excerpt;
    }
    
    /**
     * Add navigation ARIA attributes
     */
    public function add_navigation_aria_attributes($args): array {
        $location = $args['theme_location'] ?? '';
        
        if (isset($this->aria_mappings['navigation'][$location])) {
            $args['container_aria_label'] = $this->aria_mappings['navigation'][$location];
        }
        
        // Add role and additional ARIA attributes
        if (!isset($args['container_role'])) {
            $args['container_role'] = 'navigation';
        }
        
        return $args;
    }
    
    /**
     * Enhance navigation ARIA
     */
    public function enhance_navigation_aria($nav_menu, $args): string {
        $location = $args->theme_location ?? '';
        
        if (isset($this->aria_mappings['navigation'][$location])) {
            $aria_label = $this->aria_mappings['navigation'][$location];
            
            // Add ARIA label if not already present
            if (strpos($nav_menu, 'aria-label') === false) {
                $nav_menu = str_replace(
                    '<ul',
                    '<ul aria-label="' . esc_attr($aria_label) . '"',
                    $nav_menu
                );
            }
        }
        
        // Add submenu ARIA attributes
        $nav_menu = $this->add_submenu_aria($nav_menu);
        
        return $nav_menu;
    }
    
    /**
     * Add form ARIA attributes
     */
    public function add_form_aria_attributes($defaults): array {
        // Add ARIA labels to form fields
        $defaults['fields']['author'] = str_replace(
            'id="author"',
            'id="author" aria-describedby="author-description" aria-required="true"',
            $defaults['fields']['author']
        );
        
        $defaults['fields']['email'] = str_replace(
            'id="email"',
            'id="email" aria-describedby="email-description" aria-required="true"',
            $defaults['fields']['email']
        );
        
        $defaults['fields']['url'] = str_replace(
            'id="url"',
            'id="url" aria-describedby="url-description"',
            $defaults['fields']['url']
        );
        
        // Add ARIA attributes to comment field
        $defaults['comment_field'] = str_replace(
            'id="comment"',
            'id="comment" aria-describedby="comment-description" aria-required="true"',
            $defaults['comment_field']
        );
        
        // Add field descriptions
        $defaults['comment_notes_after'] = '<p id="author-description" class="screen-reader-text">' . 
            __('Enter your full name', 'tmu-theme') . '</p>' .
            '<p id="email-description" class="screen-reader-text">' . 
            __('Enter your email address', 'tmu-theme') . '</p>' .
            '<p id="url-description" class="screen-reader-text">' . 
            __('Enter your website URL (optional)', 'tmu-theme') . '</p>' .
            '<p id="comment-description" class="screen-reader-text">' . 
            __('Enter your comment', 'tmu-theme') . '</p>';
        
        return $defaults;
    }
    
    /**
     * Add search form ARIA
     */
    public function add_search_form_aria($form): string {
        // Add ARIA label to search form
        $form = str_replace(
            '<form',
            '<form role="search" aria-label="' . esc_attr($this->aria_mappings['forms']['search']) . '"',
            $form
        );
        
        // Add ARIA attributes to search input
        $form = str_replace(
            'type="search"',
            'type="search" aria-label="' . esc_attr__('Search query', 'tmu-theme') . '" aria-describedby="search-description"',
            $form
        );
        
        // Add search description
        $form = str_replace(
            '</form>',
            '<span id="search-description" class="screen-reader-text">' . 
            __('Enter your search terms and press enter', 'tmu-theme') . '</span></form>',
            $form
        );
        
        return $form;
    }
    
    /**
     * Add post ARIA classes
     */
    public function add_post_aria_classes($classes, $class, $post_id): array {
        $post_type = get_post_type($post_id);
        
        if (isset($this->aria_mappings['post_types'][$post_type])) {
            $classes[] = 'aria-' . $post_type;
            $classes[] = 'has-aria-labels';
        }
        
        return $classes;
    }
    
    /**
     * Add body ARIA classes
     */
    public function add_body_aria_classes($classes): array {
        $classes[] = 'aria-enhanced';
        
        if (is_singular()) {
            $post_type = get_post_type();
            $classes[] = 'singular-' . $post_type . '-aria';
        }
        
        if (is_archive()) {
            $classes[] = 'archive-aria';
        }
        
        if (is_search()) {
            $classes[] = 'search-results-aria';
        }
        
        return $classes;
    }
    
    /**
     * Add widget ARIA attributes
     */
    public function add_widget_aria_attributes($params): array {
        $widget_id = $params[0]['widget_id'] ?? '';
        $widget_name = $params[0]['widget_name'] ?? '';
        
        if ($widget_name) {
            // Add ARIA label to widget
            $aria_label = sprintf(__('%s widget', 'tmu-theme'), $widget_name);
            
            $params[0]['before_widget'] = str_replace(
                'class="widget',
                'class="widget" role="complementary" aria-label="' . esc_attr($aria_label) . '"',
                $params[0]['before_widget']
            );
            
            // Add ARIA attributes to widget title
            if (!empty($params[0]['before_title'])) {
                $params[0]['before_title'] = str_replace(
                    '<h',
                    '<h role="heading" aria-level="3"',
                    $params[0]['before_title']
                );
            }
        }
        
        return $params;
    }
    
    /**
     * Add image ARIA attributes
     */
    public function add_image_aria_attributes($attr, $attachment, $size): array {
        // Ensure proper ARIA attributes for images
        if (empty($attr['alt'])) {
            $attr['role'] = 'presentation';
            $attr['aria-hidden'] = 'true';
        } else {
            $attr['role'] = 'img';
        }
        
        // Add descriptive ARIA label if needed
        $caption = wp_get_attachment_caption($attachment->ID);
        if ($caption && !empty($attr['alt'])) {
            $attr['aria-describedby'] = 'caption-' . $attachment->ID;
        }
        
        return $attr;
    }
    
    /**
     * Add table ARIA attributes
     */
    public function add_table_aria_attributes($content): string {
        // Add ARIA attributes to tables
        $content = preg_replace_callback(
            '/<table([^>]*)>/i',
            function($matches) {
                $existing_attrs = $matches[1];
                
                // Add basic table ARIA attributes
                $aria_attrs = ' role="table"';
                
                if (strpos($existing_attrs, 'aria-label') === false) {
                    $aria_attrs .= ' aria-label="' . esc_attr__('Data table', 'tmu-theme') . '"';
                }
                
                return '<table' . $existing_attrs . $aria_attrs . '>';
            },
            $content
        );
        
        // Add ARIA attributes to table headers
        $content = preg_replace(
            '/<th([^>]*)>/i',
            '<th$1 role="columnheader">',
            $content
        );
        
        // Add ARIA attributes to table cells
        $content = preg_replace(
            '/<td([^>]*)>/i',
            '<td$1 role="cell">',
            $content
        );
        
        return $content;
    }
    
    /**
     * Add link ARIA attributes
     */
    public function add_link_aria_attributes($content): string {
        // Add ARIA attributes to external links
        $content = preg_replace_callback(
            '/<a([^>]*href=["\'][^"\']*["\'][^>]*)>/i',
            function($matches) {
                $link_attrs = $matches[1];
                
                // Check if it's an external link
                if (preg_match('/href=["\']([^"\']*)["\']/', $link_attrs, $href_matches)) {
                    $url = $href_matches[1];
                    
                    if ($this->is_external_link($url)) {
                        // Add ARIA label for external links
                        if (strpos($link_attrs, 'aria-label') === false) {
                            $link_attrs .= ' aria-label="' . esc_attr__('External link', 'tmu-theme') . '"';
                        }
                        
                        // Add aria-describedby for external link warning
                        if (strpos($link_attrs, 'aria-describedby') === false) {
                            $link_attrs .= ' aria-describedby="external-link-warning"';
                        }
                    }
                }
                
                // Check for download links
                if (strpos($link_attrs, 'download') !== false) {
                    if (strpos($link_attrs, 'aria-label') === false) {
                        $link_attrs .= ' aria-label="' . esc_attr__('Download file', 'tmu-theme') . '"';
                    }
                }
                
                return '<a' . $link_attrs . '>';
            },
            $content
        );
        
        // Add external link warning element
        if (strpos($content, 'external-link-warning') !== false) {
            $content .= '<span id="external-link-warning" class="screen-reader-text">' . 
                __('This link opens in a new window', 'tmu-theme') . '</span>';
        }
        
        return $content;
    }
    
    /**
     * Add heading ARIA structure
     */
    public function add_heading_aria_structure(): void {
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            // Add ARIA structure to headings
            var headings = document.querySelectorAll("h1, h2, h3, h4, h5, h6");
            headings.forEach(function(heading, index) {
                var level = parseInt(heading.tagName.substring(1));
                heading.setAttribute("role", "heading");
                heading.setAttribute("aria-level", level);
                
                // Add unique ID if not present
                if (!heading.id) {
                    heading.id = "heading-" + (index + 1);
                }
            });
        });
        </script>' . "\n";
    }
    
    /**
     * Add post type ARIA support
     */
    public function add_post_type_aria_support(): void {
        $post_types = ['movie', 'tv', 'drama', 'people'];
        
        foreach ($post_types as $post_type) {
            add_post_type_support($post_type, 'aria-labels');
        }
    }
    
    /**
     * Add admin ARIA labels
     */
    public function add_admin_aria_labels(): void {
        echo '<script>
        jQuery(document).ready(function($) {
            // Add ARIA labels to admin elements
            $(".page-title-action").attr("aria-label", "Add new item");
            $(".tablenav .bulkactions select").attr("aria-label", "Bulk actions");
            $(".tablenav .actions select").attr("aria-label", "Filter options");
            
            // Add ARIA labels to meta boxes
            $(".postbox .hndle").each(function() {
                var title = $(this).text().trim();
                $(this).closest(".postbox").attr("aria-label", title + " section");
            });
            
            // Add ARIA labels to form fields
            $("input[type=text], input[type=email], input[type=url], textarea").each(function() {
                var label = $("label[for=" + this.id + "]").text();
                if (label && !$(this).attr("aria-label")) {
                    $(this).attr("aria-label", label);
                }
            });
        });
        </script>' . "\n";
    }
    
    /**
     * Add AJAX ARIA response
     */
    public function add_ajax_aria_response(): void {
        // This method can be used to add ARIA attributes to AJAX responses
        // Implementation depends on specific AJAX handlers
    }
    
    /**
     * Helper methods
     */
    private function get_post_type_aria_label($post_type): string {
        return $this->aria_mappings['post_types'][$post_type] ?? '';
    }
    
    private function add_content_landmarks($content): string {
        // Add ARIA landmarks to content sections
        $content = preg_replace(
            '/<section([^>]*)>/i',
            '<section$1 role="region">',
            $content
        );
        
        $content = preg_replace(
            '/<article([^>]*)>/i',
            '<article$1 role="article">',
            $content
        );
        
        $content = preg_replace(
            '/<aside([^>]*)>/i',
            '<aside$1 role="complementary">',
            $content
        );
        
        return $content;
    }
    
    private function add_interactive_aria($content): string {
        // Add ARIA attributes to buttons
        $content = preg_replace_callback(
            '/<button([^>]*)>/i',
            function($matches) {
                $attrs = $matches[1];
                
                if (strpos($attrs, 'aria-label') === false && strpos($attrs, 'aria-labelledby') === false) {
                    // Try to get button text or add generic label
                    $attrs .= ' aria-label="' . esc_attr__('Button', 'tmu-theme') . '"';
                }
                
                return '<button' . $attrs . '>';
            },
            $content
        );
        
        // Add ARIA attributes to form controls
        $content = preg_replace(
            '/<input([^>]*type=["\'](?:button|submit|reset)["\'][^>]*)>/i',
            '<input$1 role="button">',
            $content
        );
        
        return $content;
    }
    
    private function add_submenu_aria($nav_menu): string {
        // Add ARIA attributes to submenus
        $nav_menu = preg_replace(
            '/<ul([^>]*class=["\'][^"\']*sub-menu[^"\']*["\'][^>]*)>/i',
            '<ul$1 role="menu" aria-hidden="true">',
            $nav_menu
        );
        
        // Add ARIA attributes to menu items with submenus
        $nav_menu = preg_replace(
            '/<li([^>]*class=["\'][^"\']*menu-item-has-children[^"\']*["\'][^>]*)>/i',
            '<li$1 role="menuitem" aria-haspopup="true" aria-expanded="false">',
            $nav_menu
        );
        
        return $nav_menu;
    }
    
    private function is_external_link($url): bool {
        $site_url = home_url();
        $parsed_site = parse_url($site_url);
        $parsed_url = parse_url($url);
        
        // Check if it's an external link
        if (isset($parsed_url['host']) && isset($parsed_site['host'])) {
            return $parsed_url['host'] !== $parsed_site['host'];
        }
        
        return false;
    }
    
    /**
     * Public methods for external use
     */
    public function add_aria_label($element, $label): string {
        return str_replace(
            '<' . $element,
            '<' . $element . ' aria-label="' . esc_attr($label) . '"',
            $element
        );
    }
    
    public function add_aria_describedby($element, $description_id): string {
        return str_replace(
            '<' . $element,
            '<' . $element . ' aria-describedby="' . esc_attr($description_id) . '"',
            $element
        );
    }
    
    public function get_aria_mappings(): array {
        return $this->aria_mappings;
    }
    
    public function update_aria_mapping($category, $key, $label): void {
        if (isset($this->aria_mappings[$category])) {
            $this->aria_mappings[$category][$key] = $label;
        }
    }
}