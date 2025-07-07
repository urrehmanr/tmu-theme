<?php
namespace TMU\SEO;

class Analytics {
    public function init(): void {
        add_action('wp_head', [$this, 'output_gtag'], 1);
        add_action('wp_footer', [$this, 'output_analytics'], 99);
    }
    
    public function output_gtag(): void {
        $ga_id = get_option('tmu_google_analytics_id');
        if (!$ga_id) return;
        
        ?>
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($ga_id); ?>"></script>
        <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', '<?php echo esc_js($ga_id); ?>', {
            custom_map: {'custom_parameter_1': 'content_type'}
          });
          
          <?php if (is_singular(['movie', 'tv', 'drama'])): ?>
          gtag('event', 'page_view', {
            'custom_parameter_1': '<?php echo esc_js(get_post_type()); ?>',
            'content_id': '<?php echo esc_js(get_the_ID()); ?>',
            'content_name': '<?php echo esc_js(get_the_title()); ?>'
          });
          <?php endif; ?>
        </script>
        <?php
    }
    
    public function output_analytics(): void {
        ?>
        <script>
        // Track content interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Track trailer views
            document.querySelectorAll('.tmu-trailer-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'video_play', {
                            'event_category': 'engagement',
                            'event_label': 'trailer',
                            'content_id': this.dataset.postId
                        });
                    }
                });
            });
            
            // Track watchlist additions
            document.querySelectorAll('.tmu-add-watchlist').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'add_to_wishlist', {
                            'event_category': 'engagement',
                            'event_label': 'watchlist',
                            'content_id': this.dataset.postId
                        });
                    }
                });
            });
            
            // Track search usage
            const searchForm = document.querySelector('.tmu-search-form');
            if (searchForm) {
                searchForm.addEventListener('submit', function() {
                    const query = this.querySelector('input[type="search"]').value;
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'search', {
                            'search_term': query
                        });
                    }
                });
            }
            
            // Track filter usage
            document.querySelectorAll('.tmu-filter-option').forEach(function(filter) {
                filter.addEventListener('change', function() {
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'filter_used', {
                            'event_category': 'engagement',
                            'event_label': this.name,
                            'value': this.value
                        });
                    }
                });
            });
        });
        </script>
        <?php
    }
}