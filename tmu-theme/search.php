<?php
/**
 * Search Results Template
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<main class="tmu-main-content">
    <!-- Search Header -->
    <div class="tmu-search-header bg-gradient-to-r from-blue-600 to-purple-600 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">
                    <?php 
                    if (have_posts()) {
                        printf(__('Search Results for "%s"', 'tmu'), '<span class="text-yellow-300">' . get_search_query() . '</span>');
                    } else {
                        printf(__('No Results for "%s"', 'tmu'), '<span class="text-yellow-300">' . get_search_query() . '</span>');
                    }
                    ?>
                </h1>
                
                <?php if (have_posts()) : ?>
                    <p class="text-xl opacity-90">
                        <?php 
                        global $wp_query;
                        printf(__('Found %d results', 'tmu'), $wp_query->found_posts);
                        ?>
                    </p>
                <?php endif; ?>
                
                <!-- Enhanced Search Form -->
                <div class="mt-8 max-w-2xl mx-auto">
                    <?php get_template_part('templates/partials/search-form'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search Results Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- Filters Sidebar -->
            <aside class="lg:w-1/4">
                <div class="tmu-search-filters bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold text-gray-800"><?php _e('Filters', 'tmu'); ?></h2>
                        <button id="clear-all-filters" class="text-sm text-blue-600 hover:text-blue-800">
                            <?php _e('Clear All', 'tmu'); ?>
                        </button>
                    </div>
                    
                    <!-- Content Type Filter -->
                    <div class="tmu-filter-group mb-6">
                        <h3 class="text-lg font-medium text-gray-700 mb-3"><?php _e('Content Type', 'tmu'); ?></h3>
                        <div class="space-y-2">
                            <?php
                            $post_types = ['movie' => __('Movies', 'tmu'), 'tv' => __('TV Shows', 'tmu'), 'people' => __('People', 'tmu')];
                            if (get_option('tmu_dramas') === 'on') {
                                $post_types['drama'] = __('Dramas', 'tmu');
                            }
                            
                            foreach ($post_types as $type => $label) :
                                $count = wp_count_posts($type)->publish;
                            ?>
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           class="tmu-filter-checkbox rounded border-gray-300 text-blue-600 mr-3" 
                                           data-filter-type="post_type" 
                                           value="<?php echo esc_attr($type); ?>">
                                    <span class="text-gray-700"><?php echo esc_html($label); ?></span>
                                    <span class="ml-auto text-sm text-gray-500">(<?php echo $count; ?>)</span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Genre Filter -->
                    <div class="tmu-filter-group mb-6">
                        <h3 class="text-lg font-medium text-gray-700 mb-3"><?php _e('Genres', 'tmu'); ?></h3>
                        <div class="max-h-48 overflow-y-auto space-y-2">
                            <?php
                            $genres = get_terms([
                                'taxonomy' => 'genre',
                                'hide_empty' => true,
                                'orderby' => 'count',
                                'order' => 'DESC',
                                'number' => 20
                            ]);
                            
                            foreach ($genres as $genre) :
                            ?>
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           class="tmu-filter-checkbox rounded border-gray-300 text-blue-600 mr-3" 
                                           data-filter-type="genre" 
                                           value="<?php echo esc_attr($genre->slug); ?>">
                                    <span class="text-gray-700"><?php echo esc_html($genre->name); ?></span>
                                    <span class="ml-auto text-sm text-gray-500">(<?php echo $genre->count; ?>)</span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Year Filter -->
                    <div class="tmu-filter-group mb-6">
                        <h3 class="text-lg font-medium text-gray-700 mb-3"><?php _e('Release Year', 'tmu'); ?></h3>
                        <div class="space-y-3">
                            <div class="tmu-year-range-slider">
                                <input type="range" 
                                       class="w-full tmu-year-min" 
                                       min="1900" 
                                       max="<?php echo date('Y'); ?>" 
                                       value="1900"
                                       id="year-min">
                                <input type="range" 
                                       class="w-full tmu-year-max" 
                                       min="1900" 
                                       max="<?php echo date('Y'); ?>" 
                                       value="<?php echo date('Y'); ?>"
                                       id="year-max">
                                <div class="flex justify-between text-sm text-gray-600 mt-2">
                                    <span id="year-min-display">1900</span>
                                    <span id="year-max-display"><?php echo date('Y'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Rating Filter -->
                    <div class="tmu-filter-group mb-6">
                        <h3 class="text-lg font-medium text-gray-700 mb-3"><?php _e('Minimum Rating', 'tmu'); ?></h3>
                        <div class="space-y-3">
                            <input type="range" 
                                   class="w-full tmu-rating-slider" 
                                   min="0" 
                                   max="10" 
                                   step="0.5" 
                                   value="0"
                                   id="rating-min">
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>0.0 ⭐</span>
                                <span id="rating-display">0.0+ ⭐</span>
                                <span>10.0 ⭐</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sort Options -->
                    <div class="tmu-filter-group">
                        <h3 class="text-lg font-medium text-gray-700 mb-3"><?php _e('Sort By', 'tmu'); ?></h3>
                        <select class="w-full tmu-sort-select rounded border-gray-300 text-gray-700">
                            <option value="relevance"><?php _e('Relevance', 'tmu'); ?></option>
                            <option value="date"><?php _e('Latest', 'tmu'); ?></option>
                            <option value="rating"><?php _e('Highest Rated', 'tmu'); ?></option>
                            <option value="popularity"><?php _e('Most Popular', 'tmu'); ?></option>
                            <option value="title"><?php _e('Title A-Z', 'tmu'); ?></option>
                        </select>
                    </div>
                </div>
            </aside>
            
            <!-- Main Results -->
            <div class="lg:w-3/4">
                
                <!-- Results Info -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 bg-gray-50 rounded-lg p-4">
                    <div class="tmu-results-info">
                        <?php if (have_posts()) : ?>
                            <p class="text-gray-700">
                                <?php 
                                global $wp_query;
                                $paged = get_query_var('paged') ? get_query_var('paged') : 1;
                                $posts_per_page = get_query_var('posts_per_page');
                                $start = ($paged - 1) * $posts_per_page + 1;
                                $end = min($paged * $posts_per_page, $wp_query->found_posts);
                                
                                printf(__('Showing %d-%d of %d results', 'tmu'), $start, $end, $wp_query->found_posts);
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tmu-view-toggle mt-3 sm:mt-0">
                        <div class="flex rounded border overflow-hidden">
                            <button class="px-3 py-2 bg-blue-600 text-white active" data-view="grid">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                            </button>
                            <button class="px-3 py-2 bg-gray-200 text-gray-700 hover:bg-gray-300" data-view="list">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 16a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Search Results -->
                <div class="tmu-search-results" id="search-results-container">
                    <?php if (have_posts()) : ?>
                        
                        <div class="tmu-search-results-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="results-grid">
                            <?php while (have_posts()) : the_post(); ?>
                                <?php
                                $post_type = get_post_type();
                                
                                switch ($post_type) {
                                    case 'people':
                                        get_template_part('templates/components/person-card', null, [
                                            'person_data' => tmu_get_person_data(get_the_ID()),
                                            'post_id' => get_the_ID()
                                        ]);
                                        break;
                                    
                                    default:
                                        get_template_part('templates/components/movie-card', null, [
                                            'movie_data' => tmu_get_movie_data(get_the_ID()),
                                            'post_id' => get_the_ID()
                                        ]);
                                        break;
                                }
                                ?>
                            <?php endwhile; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="tmu-search-pagination mt-12">
                            <?php
                            the_posts_pagination([
                                'mid_size' => 2,
                                'prev_text' => __('← Previous', 'tmu'),
                                'next_text' => __('Next →', 'tmu'),
                                'class' => 'flex justify-center'
                            ]);
                            ?>
                        </div>
                        
                    <?php else : ?>
                        
                        <!-- No Results -->
                        <div class="tmu-no-results text-center py-12">
                            <div class="max-w-md mx-auto">
                                <svg class="w-24 h-24 mx-auto text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                
                                <h2 class="text-2xl font-semibold text-gray-700 mb-4">
                                    <?php _e('No results found', 'tmu'); ?>
                                </h2>
                                
                                <p class="text-gray-600 mb-6">
                                    <?php _e('Try adjusting your search terms or filters to find what you\'re looking for.', 'tmu'); ?>
                                </p>
                                
                                <!-- Search Suggestions -->
                                <div class="text-left">
                                    <h3 class="text-lg font-medium text-gray-700 mb-3"><?php _e('Search Suggestions:', 'tmu'); ?></h3>
                                    <ul class="space-y-2 text-gray-600">
                                        <li>• <?php _e('Check your spelling', 'tmu'); ?></li>
                                        <li>• <?php _e('Try more general keywords', 'tmu'); ?></li>
                                        <li>• <?php _e('Use fewer keywords', 'tmu'); ?></li>
                                        <li>• <?php _e('Try different search terms', 'tmu'); ?></li>
                                    </ul>
                                </div>
                                
                                <!-- Popular Content -->
                                <?php
                                $popular_posts = get_posts([
                                    'post_type' => ['movie', 'tv', 'drama'],
                                    'posts_per_page' => 6,
                                    'meta_key' => 'tmu_movie_popularity',
                                    'orderby' => 'meta_value_num',
                                    'order' => 'DESC'
                                ]);
                                
                                if ($popular_posts) :
                                ?>
                                    <div class="mt-8">
                                        <h3 class="text-lg font-medium text-gray-700 mb-4"><?php _e('Popular Content', 'tmu'); ?></h3>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                            <?php foreach ($popular_posts as $popular_post) : ?>
                                                <a href="<?php echo get_permalink($popular_post->ID); ?>" 
                                                   class="block text-center hover:opacity-75 transition-opacity">
                                                    <?php echo get_the_post_thumbnail($popular_post->ID, 'thumbnail', [
                                                        'class' => 'w-full h-32 object-cover rounded mb-2'
                                                    ]); ?>
                                                    <p class="text-sm text-gray-700 font-medium">
                                                        <?php echo esc_html($popular_post->post_title); ?>
                                                    </p>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading Spinner -->
    <div class="tmu-loading-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-700"><?php _e('Searching...', 'tmu'); ?></span>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize search filters
    const filterCheckboxes = document.querySelectorAll('.tmu-filter-checkbox');
    const sortSelect = document.querySelector('.tmu-sort-select');
    const yearMin = document.getElementById('year-min');
    const yearMax = document.getElementById('year-max');
    const ratingSlider = document.getElementById('rating-min');
    const clearAllButton = document.getElementById('clear-all-filters');
    const viewToggleButtons = document.querySelectorAll('[data-view]');
    
    // Filter change handlers
    filterCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', handleFilterChange);
    });
    
    if (sortSelect) {
        sortSelect.addEventListener('change', handleFilterChange);
    }
    
    if (yearMin && yearMax) {
        yearMin.addEventListener('input', updateYearDisplay);
        yearMax.addEventListener('input', updateYearDisplay);
        yearMin.addEventListener('change', handleFilterChange);
        yearMax.addEventListener('change', handleFilterChange);
    }
    
    if (ratingSlider) {
        ratingSlider.addEventListener('input', updateRatingDisplay);
        ratingSlider.addEventListener('change', handleFilterChange);
    }
    
    if (clearAllButton) {
        clearAllButton.addEventListener('click', clearAllFilters);
    }
    
    // View toggle
    viewToggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const view = this.dataset.view;
            toggleView(view);
            
            // Update button states
            viewToggleButtons.forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white');
                btn.classList.add('bg-gray-200', 'text-gray-700');
            });
            this.classList.remove('bg-gray-200', 'text-gray-700');
            this.classList.add('bg-blue-600', 'text-white');
        });
    });
    
    function handleFilterChange() {
        // This would trigger AJAX search in a full implementation
        console.log('Filter changed - would trigger search');
    }
    
    function updateYearDisplay() {
        const minVal = yearMin.value;
        const maxVal = yearMax.value;
        document.getElementById('year-min-display').textContent = minVal;
        document.getElementById('year-max-display').textContent = maxVal;
    }
    
    function updateRatingDisplay() {
        const value = ratingSlider.value;
        document.getElementById('rating-display').textContent = value + '+ ⭐';
    }
    
    function clearAllFilters() {
        filterCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        
        if (sortSelect) {
            sortSelect.value = 'relevance';
        }
        
        if (yearMin && yearMax) {
            yearMin.value = 1900;
            yearMax.value = new Date().getFullYear();
            updateYearDisplay();
        }
        
        if (ratingSlider) {
            ratingSlider.value = 0;
            updateRatingDisplay();
        }
        
        handleFilterChange();
    }
    
    function toggleView(view) {
        const resultsGrid = document.getElementById('results-grid');
        if (!resultsGrid) return;
        
        if (view === 'list') {
            resultsGrid.classList.remove('grid-cols-1', 'md:grid-cols-2', 'lg:grid-cols-3');
            resultsGrid.classList.add('grid-cols-1');
        } else {
            resultsGrid.classList.remove('grid-cols-1');
            resultsGrid.classList.add('grid-cols-1', 'md:grid-cols-2', 'lg:grid-cols-3');
        }
    }
    
    // Initialize displays
    updateYearDisplay();
    updateRatingDisplay();
});
</script>

<?php get_footer(); ?>