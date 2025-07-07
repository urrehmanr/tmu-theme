<?php
/**
 * TV Show Archive Template
 * 
 * Template for displaying TV show archive/listing pages
 * 
 * @package TMU
 * @since 1.0.0
 */

get_header();

// Get filter parameters
$current_genre = get_query_var('genre_filter');
$current_year = get_query_var('year_filter');
$current_sort = get_query_var('sort_by') ?: 'date';
$current_search = get_search_query();
$current_status = get_query_var('status_filter');

// Pagination
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
?>

<div class="tmu-tv-archive bg-gray-50 min-h-screen">
    <!-- Archive Header -->
    <section class="bg-white shadow-sm">
        <div class="tmu-container py-8">
            <!-- Breadcrumb -->
            <?php echo tmu_render_breadcrumbs(); ?>
            
            <!-- Page Title -->
            <div class="mt-6">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">
                    <?php
                    if ($current_search) {
                        printf(__('TV Shows: Search results for "%s"', 'tmu-theme'), esc_html($current_search));
                    } elseif ($current_genre) {
                        $genre_term = get_term_by('slug', $current_genre, 'genre');
                        if ($genre_term) {
                            printf(__('TV Shows: %s', 'tmu-theme'), esc_html($genre_term->name));
                        }
                    } elseif ($current_year) {
                        printf(__('TV Shows: %s', 'tmu-theme'), esc_html($current_year));
                    } else {
                        _e('All TV Shows', 'tmu-theme');
                    }
                    ?>
                </h1>
                
                <!-- Results count -->
                <p class="text-gray-600">
                    <?php
                    global $wp_query;
                    $total = $wp_query->found_posts;
                    printf(
                        _n('%s TV show found', '%s TV shows found', $total, 'tmu-theme'),
                        number_format($total)
                    );
                    ?>
                </p>
            </div>
        </div>
    </section>
    
    <!-- Filters and Content -->
    <section class="py-8">
        <div class="tmu-container">
            <div class="lg:flex lg:gap-8">
                <!-- Sidebar Filters -->
                <aside class="lg:w-1/4 mb-8 lg:mb-0">
                    <div class="bg-white rounded-lg shadow-sm p-6 sticky top-8">
                        <h2 class="text-xl font-semibold mb-6"><?php _e('Filter TV Shows', 'tmu-theme'); ?></h2>
                        
                        <!-- Search Filter -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?php _e('Search', 'tmu-theme'); ?>
                            </label>
                            <form method="get" action="<?php echo esc_url(get_post_type_archive_link('tv')); ?>">
                                <div class="flex">
                                    <input type="text" 
                                           name="s" 
                                           value="<?php echo esc_attr($current_search); ?>" 
                                           placeholder="<?php esc_attr_e('Search TV shows...', 'tmu-theme'); ?>"
                                           class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <button type="submit" 
                                            class="px-4 py-2 bg-blue-600 text-white rounded-r-md hover:bg-blue-700 transition-colors">
                                        🔍
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Genre Filter -->
                        <div class="mb-6">
                            <label for="genre-filter" class="block text-sm font-medium text-gray-700 mb-2">
                                <?php _e('Genre', 'tmu-theme'); ?>
                            </label>
                            <select id="genre-filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value=""><?php _e('All Genres', 'tmu-theme'); ?></option>
                                <?php
                                $genres = get_terms([
                                    'taxonomy' => 'genre',
                                    'hide_empty' => true,
                                    'orderby' => 'name'
                                ]);
                                
                                foreach ($genres as $genre):
                                    ?>
                                    <option value="<?php echo esc_attr($genre->slug); ?>" 
                                            <?php selected($current_genre, $genre->slug); ?>>
                                        <?php echo esc_html($genre->name); ?> (<?php echo $genre->count; ?>)
                                    </option>
                                    <?php
                                endforeach;
                                ?>
                            </select>
                        </div>
                        
                        <!-- Year Filter -->
                        <div class="mb-6">
                            <label for="year-filter" class="block text-sm font-medium text-gray-700 mb-2">
                                <?php _e('First Air Date', 'tmu-theme'); ?>
                            </label>
                            <select id="year-filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value=""><?php _e('All Years', 'tmu-theme'); ?></option>
                                <?php
                                $years = get_terms([
                                    'taxonomy' => 'by-year',
                                    'hide_empty' => true,
                                    'orderby' => 'name',
                                    'order' => 'DESC'
                                ]);
                                
                                foreach ($years as $year):
                                    ?>
                                    <option value="<?php echo esc_attr($year->name); ?>" 
                                            <?php selected($current_year, $year->name); ?>>
                                        <?php echo esc_html($year->name); ?> (<?php echo $year->count; ?>)
                                    </option>
                                    <?php
                                endforeach;
                                ?>
                            </select>
                        </div>
                        
                        <!-- Status Filter -->
                        <div class="mb-6">
                            <label for="status-filter" class="block text-sm font-medium text-gray-700 mb-2">
                                <?php _e('Status', 'tmu-theme'); ?>
                            </label>
                            <select id="status-filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value=""><?php _e('All Status', 'tmu-theme'); ?></option>
                                <option value="airing" <?php selected($current_status, 'airing'); ?>><?php _e('Currently Airing', 'tmu-theme'); ?></option>
                                <option value="ended" <?php selected($current_status, 'ended'); ?>><?php _e('Ended', 'tmu-theme'); ?></option>
                                <option value="cancelled" <?php selected($current_status, 'cancelled'); ?>><?php _e('Cancelled', 'tmu-theme'); ?></option>
                            </select>
                        </div>
                        
                        <!-- Sort Filter -->
                        <div class="mb-6">
                            <label for="sort-filter" class="block text-sm font-medium text-gray-700 mb-2">
                                <?php _e('Sort By', 'tmu-theme'); ?>
                            </label>
                            <select id="sort-filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="date" <?php selected($current_sort, 'date'); ?>><?php _e('Latest Added', 'tmu-theme'); ?></option>
                                <option value="title" <?php selected($current_sort, 'title'); ?>><?php _e('Title A-Z', 'tmu-theme'); ?></option>
                                <option value="first_air_date" <?php selected($current_sort, 'first_air_date'); ?>><?php _e('Air Date', 'tmu-theme'); ?></option>
                                <option value="rating" <?php selected($current_sort, 'rating'); ?>><?php _e('Highest Rated', 'tmu-theme'); ?></option>
                                <option value="popularity" <?php selected($current_sort, 'popularity'); ?>><?php _e('Most Popular', 'tmu-theme'); ?></option>
                            </select>
                        </div>
                        
                        <!-- Clear Filters -->
                        <button id="clear-filters" class="w-full px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                            <?php _e('Clear All Filters', 'tmu-theme'); ?>
                        </button>
                    </div>
                </aside>
                
                <!-- TV Shows Grid -->
                <main class="lg:w-3/4">
                    <!-- View Toggle -->
                    <div class="flex justify-between items-center mb-6">
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600"><?php _e('View:', 'tmu-theme'); ?></span>
                            <button id="grid-view" class="p-2 rounded border border-gray-300 bg-blue-600 text-white">
                                ⊞
                            </button>
                            <button id="list-view" class="p-2 rounded border border-gray-300 text-gray-600 hover:bg-gray-50">
                                ☰
                            </button>
                        </div>
                        
                        <div class="text-sm text-gray-600">
                            <?php
                            printf(
                                __('Showing %d-%d of %d TV shows', 'tmu-theme'),
                                (($paged - 1) * get_option('posts_per_page')) + 1,
                                min($paged * get_option('posts_per_page'), $wp_query->found_posts),
                                $wp_query->found_posts
                            );
                            ?>
                        </div>
                    </div>
                    
                    <?php if (have_posts()): ?>
                        <!-- Grid View -->
                        <div id="tv-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
                            <?php while (have_posts()): the_post(); ?>
                                <?php
                                get_template_part('templates/components/movie-card', null, [
                                    'movie_data' => tmu_get_tv_data(get_the_ID()),
                                    'post_id' => get_the_ID(),
                                    'size' => 'medium'
                                ]);
                                ?>
                            <?php endwhile; ?>
                        </div>
                        
                        <!-- List View (Hidden by default) -->
                        <div id="tv-list" class="space-y-6 mb-8 hidden">
                            <?php 
                            // Reset query for list view
                            rewind_posts();
                            while (have_posts()): the_post();
                                $tv_data = tmu_get_tv_data(get_the_ID());
                                ?>
                                <div class="bg-white rounded-lg shadow-sm p-6 flex gap-6">
                                    <!-- Poster -->
                                    <div class="flex-shrink-0 w-24">
                                        <?php if (has_post_thumbnail()): ?>
                                            <a href="<?php echo esc_url(get_permalink()); ?>">
                                                <?php the_post_thumbnail('thumbnail', [
                                                    'class' => 'w-full h-auto rounded',
                                                    'alt' => get_the_title()
                                                ]); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Content -->
                                    <div class="flex-1">
                                        <h3 class="text-xl font-bold mb-2">
                                            <a href="<?php echo esc_url(get_permalink()); ?>" class="text-gray-900 hover:text-blue-600">
                                                <?php the_title(); ?>
                                            </a>
                                        </h3>
                                        
                                        <div class="flex items-center space-x-4 text-sm text-gray-600 mb-3">
                                            <?php if (!empty($tv_data['first_air_date'])): ?>
                                                <span><?php echo date('Y', strtotime($tv_data['first_air_date'])); ?></span>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($tv_data['number_of_seasons'])): ?>
                                                <span><?php printf(_n('%d Season', '%d Seasons', (int) $tv_data['number_of_seasons'], 'tmu-theme'), (int) $tv_data['number_of_seasons']); ?></span>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($tv_data['vote_average'])): ?>
                                                <div class="flex items-center">
                                                    <?php echo tmu_render_rating((float) $tv_data['vote_average']); ?>
                                                    <span class="ml-1"><?php echo number_format((float) $tv_data['vote_average'], 1); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($tv_data['overview'])): ?>
                                            <p class="text-gray-700 mb-4">
                                                <?php echo esc_html(tmu_truncate_text($tv_data['overview'], 200)); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="flex justify-between items-center">
                                            <?php $genres = tmu_get_genre_links(get_the_ID()); ?>
                                            <?php if (!empty($genres)): ?>
                                                <div class="flex space-x-2">
                                                    <?php foreach (array_slice($genres, 0, 3) as $genre): ?>
                                                        <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">
                                                            <?php echo esc_html($genre['name']); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <a href="<?php echo esc_url(get_permalink()); ?>" 
                                               class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                                                <?php _e('View Details', 'tmu-theme'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            endwhile;
                            ?>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="flex justify-center">
                            <?php
                            echo paginate_links([
                                'total' => $wp_query->max_num_pages,
                                'current' => $paged,
                                'format' => '?paged=%#%',
                                'prev_text' => '← ' . __('Previous', 'tmu-theme'),
                                'next_text' => __('Next', 'tmu-theme') . ' →',
                                'class' => 'pagination'
                            ]);
                            ?>
                        </div>
                        
                    <?php else: ?>
                        <!-- No Results -->
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">📺</div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-4"><?php _e('No TV shows found', 'tmu-theme'); ?></h2>
                            <p class="text-gray-600 mb-8"><?php _e('Try adjusting your search criteria or browse all TV shows.', 'tmu-theme'); ?></p>
                            <a href="<?php echo esc_url(get_post_type_archive_link('tv')); ?>" 
                               class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <?php _e('View All TV Shows', 'tmu-theme'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </main>
            </div>
        </div>
    </section>
</div>

<!-- JavaScript for filters and view toggle -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const genreFilter = document.getElementById('genre-filter');
    const yearFilter = document.getElementById('year-filter');
    const statusFilter = document.getElementById('status-filter');
    const sortFilter = document.getElementById('sort-filter');
    const clearFilters = document.getElementById('clear-filters');
    const gridView = document.getElementById('grid-view');
    const listView = document.getElementById('list-view');
    const tvGrid = document.getElementById('tv-grid');
    const tvList = document.getElementById('tv-list');
    
    // Filter change handler
    function applyFilters() {
        const genre = genreFilter.value;
        const year = yearFilter.value;
        const status = statusFilter.value;
        const sort = sortFilter.value;
        
        const params = new URLSearchParams(window.location.search);
        
        if (genre) params.set('genre_filter', genre);
        else params.delete('genre_filter');
        
        if (year) params.set('year_filter', year);
        else params.delete('year_filter');
        
        if (status) params.set('status_filter', status);
        else params.delete('status_filter');
        
        if (sort && sort !== 'date') params.set('sort_by', sort);
        else params.delete('sort_by');
        
        // Remove page parameter when filtering
        params.delete('paged');
        
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.location.href = newUrl;
    }
    
    // Add event listeners
    genreFilter.addEventListener('change', applyFilters);
    yearFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    sortFilter.addEventListener('change', applyFilters);
    
    // Clear filters
    clearFilters.addEventListener('click', function() {
        window.location.href = window.location.pathname;
    });
    
    // View toggle
    gridView.addEventListener('click', function() {
        tvGrid.classList.remove('hidden');
        tvList.classList.add('hidden');
        gridView.classList.add('bg-blue-600', 'text-white');
        gridView.classList.remove('text-gray-600');
        listView.classList.remove('bg-blue-600', 'text-white');
        listView.classList.add('text-gray-600');
    });
    
    listView.addEventListener('click', function() {
        tvList.classList.remove('hidden');
        tvGrid.classList.add('hidden');
        listView.classList.add('bg-blue-600', 'text-white');
        listView.classList.remove('text-gray-600');
        gridView.classList.remove('bg-blue-600', 'text-white');
        gridView.classList.add('text-gray-600');
    });
});
</script>

<style>
.tmu-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin: 2rem 0;
}

.pagination a,
.pagination span {
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db;
    color: #374151;
    text-decoration: none;
    border-radius: 0.375rem;
    transition: background-color 0.2s;
}

.pagination a:hover {
    background-color: #f9fafb;
}

.pagination .current {
    background-color: #2563eb;
    color: white;
    border-color: #2563eb;
}
</style>

<?php
wp_reset_postdata();
get_footer();
?>