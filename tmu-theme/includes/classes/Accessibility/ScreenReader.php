<?php
/**
 * Screen Reader Support
 * 
 * Comprehensive screen reader support and optimization.
 * Ensures content is properly accessible to assistive technologies.
 * 
 * @package TMU\Accessibility
 * @since 1.0.0
 */

namespace TMU\Accessibility;

/**
 * ScreenReader class
 * 
 * Handles screen reader optimization and support
 */
class ScreenReader {
    
    /**
     * Screen reader text elements
     * @var array
     */
    private $screen_reader_elements = [];
    
    /**
     * Live region announcements
     * @var array
     */
    private $live_announcements = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        // Content enhancement for screen readers
        add_filter('the_content', [$this, 'enhance_content_for_screen_readers'], 998);
        add_filter('the_excerpt', [$this, 'enhance_excerpt_for_screen_readers'], 998);
        
        // Navigation enhancement
        add_filter('nav_menu_css_class', [$this, 'add_screen_reader_nav_classes'], 10, 4);
        add_filter('wp_nav_menu_items', [$this, 'enhance_nav_for_screen_readers'], 10, 2);
        
        // Form enhancement
        add_filter('comment_form_defaults', [$this, 'enhance_forms_for_screen_readers']);
        add_filter('get_search_form', [$this, 'enhance_search_for_screen_readers']);
        
        // Post/page content enhancement
        add_action('wp_head', [$this, 'add_screen_reader_styles'], 2);
        add_action('wp_footer', [$this, 'add_screen_reader_scripts'], 999);
        
        // Live regions and announcements
        add_action('wp_ajax_tmu_announce', [$this, 'handle_live_announcement']);
        add_action('wp_ajax_nopriv_tmu_announce', [$this, 'handle_live_announcement']);
        
        // ARIA live region updates
        add_action('wp_enqueue_scripts', [$this, 'enqueue_screen_reader_assets']);
        
        // Dynamic content updates
        add_filter('wp_update_comment_count', [$this, 'announce_comment_count_update']);
        
        // Reading progress and context
        add_action('wp_head', [$this, 'add_reading_context_meta']);
        
        // Custom post type support
        add_filter('post_type_labels', [$this, 'enhance_post_type_labels_for_screen_readers']);
        
        // Pagination enhancement
        add_filter('paginate_links', [$this, 'enhance_pagination_for_screen_readers']);
        
        // Widget enhancement
        add_filter('dynamic_sidebar_params', [$this, 'enhance_widgets_for_screen_readers']);
        
        // Admin enhancement
        if (is_admin()) {
            add_action('admin_head', [$this, 'add_admin_screen_reader_styles']);
            add_action('admin_footer', [$this, 'add_admin_screen_reader_scripts']);
        }
    }
    
    /**
     * Enhance content for screen readers
     */
    public function enhance_content_for_screen_readers($content): string {
        // Add context for movie/TV content
        if (is_singular(['movie', 'tv', 'drama'])) {
            $post_type = get_post_type();
            $context_label = $this->get_content_type_label($post_type);
            
            // Add screen reader context at the beginning
            $content = '<span class="screen-reader-text">' . 
                sprintf(__('Beginning of %s content', 'tmu-theme'), $context_label) . 
                '</span>' . $content;
            
            // Add context at the end
            $content .= '<span class="screen-reader-text">' . 
                sprintf(__('End of %s content', 'tmu-theme'), $context_label) . 
                '</span>';
        }
        
        // Enhance lists for better screen reader navigation
        $content = $this->enhance_lists_for_screen_readers($content);
        
        // Add reading landmarks
        $content = $this->add_reading_landmarks($content);
        
        // Enhance tables
        $content = $this->enhance_tables_for_screen_readers($content);
        
        // Add contextual information for media
        $content = $this->add_media_context($content);
        
        return $content;
    }
    
    /**
     * Enhance excerpt for screen readers
     */
    public function enhance_excerpt_for_screen_readers($excerpt): string {
        if (empty($excerpt)) {
            return $excerpt;
        }
        
        $post_title = get_the_title();
        $post_type = get_post_type();
        $context_label = $this->get_content_type_label($post_type);
        
        return '<div class="excerpt-container" role="region" aria-labelledby="excerpt-heading-' . get_the_ID() . '">' .
            '<h3 id="excerpt-heading-' . get_the_ID() . '" class="screen-reader-text">' .
            sprintf(__('Excerpt from %s: %s', 'tmu-theme'), $context_label, $post_title) . '</h3>' .
            $excerpt . '</div>';
    }
    
    /**
     * Add screen reader navigation classes
     */
    public function add_screen_reader_nav_classes($classes, $item, $args, $depth): array {
        // Add contextual classes for screen readers
        if ($depth === 0) {
            $classes[] = 'main-menu-item';
        } else {
            $classes[] = 'submenu-item';
            $classes[] = 'submenu-level-' . $depth;
        }
        
        // Add position indicators
        if (isset($args->walker) && is_object($args->walker)) {
            $position = $args->walker->get_item_position($item);
            if ($position) {
                $classes[] = 'menu-position-' . $position;
            }
        }
        
        return $classes;
    }
    
    /**
     * Enhance navigation for screen readers
     */
    public function enhance_nav_for_screen_readers($items, $args): string {
        // Add navigation context
        if ($args->theme_location === 'primary') {
            $items = '<span class="screen-reader-text">' . 
                __('Main navigation menu. Use tab to navigate through menu items.', 'tmu-theme') . 
                '</span>' . $items;
        }
        
        // Add menu item count
        $item_count = substr_count($items, '<li');
        $items .= '<span class="screen-reader-text">' . 
            sprintf(_n('%d menu item', '%d menu items', $item_count, 'tmu-theme'), $item_count) . 
            '</span>';
        
        return $items;
    }
    
    /**
     * Enhance forms for screen readers
     */
    public function enhance_forms_for_screen_readers($defaults): array {
        // Add form context
        $defaults['comment_notes_before'] = '<p class="comment-notes">' .
            '<span class="screen-reader-text">' . __('Comment form. Required fields are marked with an asterisk.', 'tmu-theme') . '</span>' .
            __('Your email address will not be published.', 'tmu-theme') . '</p>';
        
        // Enhanced comment field
        $defaults['comment_field'] = '<div class="comment-form-comment">' .
            '<label for="comment">' . _x('Comment', 'noun', 'tmu-theme') . 
            ' <span class="required" aria-label="required">*</span></label>' .
            '<span class="screen-reader-text">' . __('Enter your comment in the text area below.', 'tmu-theme') . '</span>' .
            '<textarea id="comment" name="comment" cols="45" rows="8" maxlength="65525" ' .
            'aria-describedby="comment-notes" aria-required="true" required="required">' .
            '</textarea></div>';
        
        return $defaults;
    }
    
    /**
     * Enhance search for screen readers
     */
    public function enhance_search_for_screen_readers($form): string {
        // Add search instructions
        $instructions = '<span class="screen-reader-text search-instructions">' .
            __('Search our database of movies, TV shows, and dramas. Type your search term and press enter or click the search button.', 'tmu-theme') .
            '</span>';
        
        $form = str_replace('<form', $instructions . '<form', $form);
        
        // Add submit button label
        $form = str_replace(
            '<input type="submit"',
            '<input type="submit" aria-label="' . esc_attr__('Execute search', 'tmu-theme') . '"',
            $form
        );
        
        return $form;
    }
    
    /**
     * Add screen reader styles
     */
    public function add_screen_reader_styles(): void {
        echo '<style>
        .screen-reader-text {
            border: 0;
            clip: rect(1px, 1px, 1px, 1px);
            clip-path: inset(50%);
            height: 1px;
            margin: -1px;
            overflow: hidden;
            padding: 0;
            position: absolute !important;
            width: 1px;
            word-wrap: normal !important;
        }
        
        .screen-reader-text:focus {
            background-color: #f1f1f1;
            border-radius: 3px;
            box-shadow: 0 0 2px 2px rgba(0, 0, 0, 0.6);
            clip: auto !important;
            clip-path: none;
            color: #21759b;
            display: block;
            font-size: 14px;
            font-weight: bold;
            height: auto;
            left: 5px;
            line-height: normal;
            padding: 15px 23px 14px;
            text-decoration: none;
            top: 5px;
            width: auto;
            z-index: 100000;
        }
        
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        .sr-only:focus {
            position: static;
            width: auto;
            height: auto;
            padding: 0;
            margin: 0;
            overflow: visible;
            clip: auto;
            white-space: normal;
        }
        
        /* High contrast mode improvements */
        @media (prefers-contrast: high) {
            .screen-reader-text:focus {
                background-color: #000;
                color: #fff;
                border: 2px solid #fff;
            }
        }
        </style>' . "\n";
    }
    
    /**
     * Add screen reader scripts
     */
    public function add_screen_reader_scripts(): void {
        echo '<script>
        (function() {
            // Screen reader announcement function
            window.announceToScreenReader = function(message, priority) {
                var liveRegion = document.getElementById(priority === "assertive" ? "tmu-live-region-assertive" : "tmu-live-region");
                if (liveRegion) {
                    liveRegion.textContent = "";
                    setTimeout(function() {
                        liveRegion.textContent = message;
                    }, 100);
                }
            };
            
            // Enhanced navigation announcements
            document.addEventListener("DOMContentLoaded", function() {
                var menuToggles = document.querySelectorAll("[aria-expanded]");
                menuToggles.forEach(function(toggle) {
                    toggle.addEventListener("click", function() {
                        var expanded = this.getAttribute("aria-expanded") === "true";
                        var message = expanded ? "Menu collapsed" : "Menu expanded";
                        announceToScreenReader(message);
                    });
                });
                
                // Announce page loading completion
                announceToScreenReader("Page loaded and ready for interaction");
                
                // Form submission announcements
                var forms = document.querySelectorAll("form");
                forms.forEach(function(form) {
                    form.addEventListener("submit", function() {
                        announceToScreenReader("Form submitted, please wait...", "assertive");
                    });
                });
                
                // AJAX loading announcements
                if (window.jQuery) {
                    jQuery(document).ajaxStart(function() {
                        announceToScreenReader("Loading content, please wait...");
                    }).ajaxComplete(function() {
                        announceToScreenReader("Content loaded");
                    });
                }
            });
            
            // Reading progress indicator
            function updateReadingProgress() {
                var winHeight = window.innerHeight;
                var docHeight = document.documentElement.scrollHeight;
                var scrollTop = window.pageYOffset;
                var trackLength = docHeight - winHeight;
                var pctScrolled = Math.floor(scrollTop/trackLength * 100);
                
                if (pctScrolled && pctScrolled % 25 === 0) {
                    announceToScreenReader("Reading progress: " + pctScrolled + " percent");
                }
            }
            
            var throttledProgress = false;
            window.addEventListener("scroll", function() {
                if (!throttledProgress) {
                    setTimeout(function() {
                        updateReadingProgress();
                        throttledProgress = false;
                    }, 1000);
                    throttledProgress = true;
                }
            });
        })();
        </script>' . "\n";
    }
    
    /**
     * Enqueue screen reader assets
     */
    public function enqueue_screen_reader_assets(): void {
        wp_enqueue_script(
            'tmu-screen-reader-utils',
            get_template_directory_uri() . '/assets/js/screen-reader-utils.js',
            [],
            '1.0.0',
            true
        );
        
        wp_localize_script('tmu-screen-reader-utils', 'tmu_sr', [
            'strings' => [
                'loading' => __('Loading...', 'tmu-theme'),
                'loaded' => __('Content loaded', 'tmu-theme'),
                'error' => __('Error loading content', 'tmu-theme'),
                'menu_expanded' => __('Menu expanded', 'tmu-theme'),
                'menu_collapsed' => __('Menu collapsed', 'tmu-theme'),
                'page_of' => __('Page %1$d of %2$d', 'tmu-theme'),
                'reading_progress' => __('Reading progress: %d percent', 'tmu-theme'),
                'form_submitted' => __('Form submitted, please wait...', 'tmu-theme'),
                'search_results' => __('%d search results found', 'tmu-theme'),
                'no_results' => __('No results found', 'tmu-theme')
            ]
        ]);
    }
    
    /**
     * Handle live announcements
     */
    public function handle_live_announcement(): void {
        if (!isset($_POST['message'])) {
            wp_die('No message provided');
        }
        
        $message = sanitize_text_field($_POST['message']);
        $priority = sanitize_text_field($_POST['priority'] ?? 'polite');
        
        // Store announcement for later retrieval
        $this->live_announcements[] = [
            'message' => $message,
            'priority' => $priority,
            'timestamp' => time()
        ];
        
        wp_send_json_success(['announced' => $message]);
    }
    
    /**
     * Announce comment count update
     */
    public function announce_comment_count_update($post_id): void {
        $comment_count = get_comments_number($post_id);
        $message = sprintf(
            _n('%d comment', '%d comments', $comment_count, 'tmu-theme'),
            $comment_count
        );
        
        echo '<script>
        if (window.announceToScreenReader) {
            announceToScreenReader("' . esc_js($message) . '");
        }
        </script>';
    }
    
    /**
     * Add reading context meta
     */
    public function add_reading_context_meta(): void {
        if (is_singular()) {
            $post = get_queried_object();
            $reading_time = $this->estimate_reading_time($post->post_content);
            $word_count = str_word_count(strip_tags($post->post_content));
            
            echo '<meta name="reading-time" content="' . esc_attr($reading_time) . '">' . "\n";
            echo '<meta name="word-count" content="' . esc_attr($word_count) . '">' . "\n";
            
            // Add structured data for screen readers
            echo '<script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "Article",
                "wordCount": ' . $word_count . ',
                "timeRequired": "PT' . $reading_time . 'M"
            }
            </script>' . "\n";
        }
    }
    
    /**
     * Enhance post type labels for screen readers
     */
    public function enhance_post_type_labels_for_screen_readers($labels): object {
        // Add more descriptive labels for screen readers
        if (isset($labels->name)) {
            $labels->screen_reader_name = $labels->name . ' ' . __('content type', 'tmu-theme');
        }
        
        return $labels;
    }
    
    /**
     * Enhance pagination for screen readers
     */
    public function enhance_pagination_for_screen_readers($link): string {
        // Add context to pagination links
        $link = str_replace(
            'class="page-numbers',
            'aria-label="' . esc_attr__('Go to page', 'tmu-theme') . '" class="page-numbers',
            $link
        );
        
        // Add context for previous/next links
        $link = str_replace(
            'class="prev page-numbers',
            'aria-label="' . esc_attr__('Go to previous page', 'tmu-theme') . '" class="prev page-numbers',
            $link
        );
        
        $link = str_replace(
            'class="next page-numbers',
            'aria-label="' . esc_attr__('Go to next page', 'tmu-theme') . '" class="next page-numbers',
            $link
        );
        
        return $link;
    }
    
    /**
     * Enhance widgets for screen readers
     */
    public function enhance_widgets_for_screen_readers($params): array {
        $widget_name = $params[0]['widget_name'] ?? '';
        
        if ($widget_name) {
            // Add screen reader context
            $params[0]['before_widget'] = str_replace(
                'class="widget',
                'class="widget" aria-label="' . esc_attr($widget_name . ' widget') . '"',
                $params[0]['before_widget']
            );
        }
        
        return $params;
    }
    
    /**
     * Add admin screen reader styles
     */
    public function add_admin_screen_reader_styles(): void {
        echo '<style>
        .admin-screen-reader-text {
            position: absolute !important;
            clip: rect(1px 1px 1px 1px);
            clip: rect(1px, 1px, 1px, 1px);
            overflow: hidden;
            height: 1px;
            width: 1px;
        }
        
        .admin-screen-reader-text:focus {
            clip: auto;
            height: auto;
            width: auto;
            background: #fff;
            color: #333;
            padding: 5px;
            border: 1px solid #ccc;
            z-index: 9999;
        }
        </style>' . "\n";
    }
    
    /**
     * Add admin screen reader scripts
     */
    public function add_admin_screen_reader_scripts(): void {
        echo '<script>
        jQuery(document).ready(function($) {
            // Announce admin actions
            $(".page-title-action").attr("aria-label", "Add new item");
            
            // Form validation announcements
            $("form").on("submit", function() {
                var errors = $(this).find(".error, .form-invalid");
                if (errors.length > 0) {
                    window.announceToScreenReader("Form has " + errors.length + " errors", "assertive");
                }
            });
            
            // Tab announcements
            $(".nav-tab").on("click", function() {
                var tabName = $(this).text();
                window.announceToScreenReader("Switched to " + tabName + " tab");
            });
        });
        </script>' . "\n";
    }
    
    /**
     * Helper methods
     */
    private function get_content_type_label($post_type): string {
        $labels = [
            'movie' => __('movie', 'tmu-theme'),
            'tv' => __('TV series', 'tmu-theme'),
            'drama' => __('drama', 'tmu-theme'),
            'people' => __('person', 'tmu-theme')
        ];
        
        return $labels[$post_type] ?? __('content', 'tmu-theme');
    }
    
    private function enhance_lists_for_screen_readers($content): string {
        // Add list context
        $content = preg_replace_callback(
            '/<(ul|ol)([^>]*)>/i',
            function($matches) {
                $list_type = strtolower($matches[1]);
                $context = $list_type === 'ol' ? 'ordered list' : 'unordered list';
                
                return '<' . $matches[1] . $matches[2] . ' role="list" aria-label="' . 
                    esc_attr($context) . '">';
            },
            $content
        );
        
        return $content;
    }
    
    private function add_reading_landmarks($content): string {
        // Add reading landmarks for long content
        if (str_word_count(strip_tags($content)) > 500) {
            $paragraphs = explode('</p>', $content);
            $landmark_count = 0;
            
            foreach ($paragraphs as $index => $paragraph) {
                if ($index > 0 && $index % 5 === 0) {
                    $landmark_count++;
                    $paragraphs[$index] = '<span class="reading-landmark screen-reader-text" id="landmark-' . 
                        $landmark_count . '">Reading landmark ' . $landmark_count . '</span>' . $paragraph;
                }
            }
            
            $content = implode('</p>', $paragraphs);
        }
        
        return $content;
    }
    
    private function enhance_tables_for_screen_readers($content): string {
        // Add table context and navigation
        $content = preg_replace_callback(
            '/<table([^>]*)>/i',
            function($matches) {
                return '<table' . $matches[1] . ' role="table" aria-label="Data table">';
            },
            $content
        );
        
        return $content;
    }
    
    private function add_media_context($content): string {
        // Add context for embedded media
        $content = preg_replace_callback(
            '/<(iframe|video|audio)([^>]*)>/i',
            function($matches) {
                $media_type = strtolower($matches[1]);
                $context = ucfirst($media_type) . ' content';
                
                return '<' . $matches[1] . $matches[2] . ' aria-label="' . 
                    esc_attr($context) . '">';
            },
            $content
        );
        
        return $content;
    }
    
    private function estimate_reading_time($content): int {
        $word_count = str_word_count(strip_tags($content));
        $reading_speed = 200; // words per minute
        
        return max(1, ceil($word_count / $reading_speed));
    }
    
    /**
     * Public methods for external use
     */
    public function announce($message, $priority = 'polite'): void {
        echo '<script>
        if (window.announceToScreenReader) {
            announceToScreenReader("' . esc_js($message) . '", "' . esc_js($priority) . '");
        }
        </script>';
    }
    
    public function add_screen_reader_text($text): string {
        return '<span class="screen-reader-text">' . esc_html($text) . '</span>';
    }
    
    public function get_live_announcements(): array {
        return $this->live_announcements;
    }
    
    public function clear_announcements(): void {
        $this->live_announcements = [];
    }
}