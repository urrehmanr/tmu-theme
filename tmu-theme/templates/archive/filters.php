<?php
/**
 * Archive Filters Template
 * 
 * Advanced filtering system for archive pages
 * 
 * @package TMU
 * @since 1.0.0
 */

$post_type = $args['post_type'] ?? get_queried_object()->name ?? 'movie';
$current_filters = $args['current_filters'] ?? [];
$results_count = $args['results_count'] ?? 0;
$total_count = $args['total_count'] ?? 0;

// Get filter options based on post type
$genres = get_terms(['taxonomy' => 'genre', 'hide_empty' => true]);
$countries = get_terms(['taxonomy' => 'country', 'hide_empty' => true]);
$years = get_terms(['taxonomy' => 'by-year', 'hide_empty' => true]);

// Sort years in descending order
if ($years && !is_wp_error($years)) {
    usort($years, function($a, $b) {
        return (int)$b->name - (int)$a->name;
    });
}

// Get language terms for TV shows and dramas
$languages = [];
if (in_array($post_type, ['tv', 'drama'])) {
    $languages = get_terms(['taxonomy' => 'language', 'hide_empty' => true]);
}

// Get network/channel terms for TV content
$networks = [];
if ($post_type === 'tv') {
    $networks = get_terms(['taxonomy' => 'network', 'hide_empty' => true]);
} elseif ($post_type === 'drama') {
    $networks = get_terms(['taxonomy' => 'channel', 'hide_empty' => true]);
}
?>

<div class="tmu-archive-filters bg-white rounded-lg shadow-sm p-6 mb-8">
    <!-- Filter Header -->
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <span class="mr-2">🔍</span>
            <?php _e('Filter Results', 'tmu-theme'); ?>
        </h3>
        
        <div class="flex items-center space-x-4">
            <!-- Results Count -->
            <span class="text-sm text-gray-600">
                <?php printf(
                    __('Showing %d of %d results', 'tmu-theme'),
                    $results_count,
                    $total_count
                ); ?>
            </span>
            
            <!-- Clear Filters -->
            <button id="clearFilters" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                <?php _e('Clear All Filters', 'tmu-theme'); ?>
            </button>
        </div>
    </div>
    
    <!-- Filter Form -->
    <form id="archiveFilters" class="space-y-4">
        <input type="hidden" name="post_type" value="<?php echo esc_attr($post_type); ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Genre Filter -->
            <?php if (!empty($genres) && !is_wp_error($genres)): ?>
                <div class="filter-group">
                    <label for="genre_filter" class="block text-sm font-medium text-gray-700 mb-2">
                        <?php _e('Genre', 'tmu-theme'); ?>
                    </label>
                    <select id="genre_filter" name="genre_filter" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value=""><?php _e('All Genres', 'tmu-theme'); ?></option>
                        <?php foreach ($genres as $genre): ?>
                            <option value="<?php echo esc_attr($genre->slug); ?>" 
                                    <?php selected($current_filters['genre_filter'] ?? '', $genre->slug); ?>>
                                <?php echo esc_html($genre->name); ?>
                                <span class="text-gray-500">(<?php echo $genre->count; ?>)</span>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <!-- Year Filter -->
            <?php if (!empty($years) && !is_wp_error($years)): ?>
                <div class="filter-group">
                    <label for="year_filter" class="block text-sm font-medium text-gray-700 mb-2">
                        <?php _e('Year', 'tmu-theme'); ?>
                    </label>
                    <select id="year_filter" name="year_filter" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value=""><?php _e('All Years', 'tmu-theme'); ?></option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo esc_attr($year->name); ?>" 
                                    <?php selected($current_filters['year_filter'] ?? '', $year->name); ?>>
                                <?php echo esc_html($year->name); ?>
                                <span class="text-gray-500">(<?php echo $year->count; ?>)</span>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <!-- Country Filter -->
            <?php if (!empty($countries) && !is_wp_error($countries)): ?>
                <div class="filter-group">
                    <label for="country_filter" class="block text-sm font-medium text-gray-700 mb-2">
                        <?php _e('Country', 'tmu-theme'); ?>
                    </label>
                    <select id="country_filter" name="country_filter" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value=""><?php _e('All Countries', 'tmu-theme'); ?></option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?php echo esc_attr($country->slug); ?>" 
                                    <?php selected($current_filters['country_filter'] ?? '', $country->slug); ?>>
                                <?php echo esc_html($country->name); ?>
                                <span class="text-gray-500">(<?php echo $country->count; ?>)</span>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <!-- Sort By -->
            <div class="filter-group">
                <label for="sort_by" class="block text-sm font-medium text-gray-700 mb-2">
                    <?php _e('Sort By', 'tmu-theme'); ?>
                </label>
                <select id="sort_by" name="sort_by" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="date" <?php selected($current_filters['sort_by'] ?? 'date', 'date'); ?>>
                        <?php _e('Latest Added', 'tmu-theme'); ?>
                    </option>
                    <option value="title" <?php selected($current_filters['sort_by'] ?? '', 'title'); ?>>
                        <?php _e('Title A-Z', 'tmu-theme'); ?>
                    </option>
                    <option value="title_desc" <?php selected($current_filters['sort_by'] ?? '', 'title_desc'); ?>>
                        <?php _e('Title Z-A', 'tmu-theme'); ?>
                    </option>
                    <option value="rating" <?php selected($current_filters['sort_by'] ?? '', 'rating'); ?>>
                        <?php _e('Highest Rated', 'tmu-theme'); ?>
                    </option>
                    <option value="popularity" <?php selected($current_filters['sort_by'] ?? '', 'popularity'); ?>>
                        <?php _e('Most Popular', 'tmu-theme'); ?>
                    </option>
                    <?php if ($post_type === 'movie'): ?>
                        <option value="release_date" <?php selected($current_filters['sort_by'] ?? '', 'release_date'); ?>>
                            <?php _e('Release Date', 'tmu-theme'); ?>
                        </option>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        
        <!-- Additional Filters Row -->
        <?php if (!empty($languages) || !empty($networks)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t border-gray-200">
                <!-- Language Filter -->
                <?php if (!empty($languages) && !is_wp_error($languages)): ?>
                    <div class="filter-group">
                        <label for="language_filter" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Language', 'tmu-theme'); ?>
                        </label>
                        <select id="language_filter" name="language_filter" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value=""><?php _e('All Languages', 'tmu-theme'); ?></option>
                            <?php foreach ($languages as $language): ?>
                                <option value="<?php echo esc_attr($language->slug); ?>" 
                                        <?php selected($current_filters['language_filter'] ?? '', $language->slug); ?>>
                                    <?php echo esc_html($language->name); ?>
                                    <span class="text-gray-500">(<?php echo $language->count; ?>)</span>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <!-- Network/Channel Filter -->
                <?php if (!empty($networks) && !is_wp_error($networks)): ?>
                    <div class="filter-group">
                        <label for="network_filter" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo $post_type === 'tv' ? __('Network', 'tmu-theme') : __('Channel', 'tmu-theme'); ?>
                        </label>
                        <select id="network_filter" name="network_filter" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">
                                <?php echo $post_type === 'tv' ? __('All Networks', 'tmu-theme') : __('All Channels', 'tmu-theme'); ?>
                            </option>
                            <?php foreach ($networks as $network): ?>
                                <option value="<?php echo esc_attr($network->slug); ?>" 
                                        <?php selected($current_filters['network_filter'] ?? '', $network->slug); ?>>
                                    <?php echo esc_html($network->name); ?>
                                    <span class="text-gray-500">(<?php echo $network->count; ?>)</span>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <!-- Rating Filter -->
                <div class="filter-group">
                    <label for="rating_filter" class="block text-sm font-medium text-gray-700 mb-2">
                        <?php _e('Minimum Rating', 'tmu-theme'); ?>
                    </label>
                    <select id="rating_filter" name="rating_filter" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value=""><?php _e('Any Rating', 'tmu-theme'); ?></option>
                        <option value="9" <?php selected($current_filters['rating_filter'] ?? '', '9'); ?>>9+ ⭐⭐⭐⭐⭐</option>
                        <option value="8" <?php selected($current_filters['rating_filter'] ?? '', '8'); ?>>8+ ⭐⭐⭐⭐</option>
                        <option value="7" <?php selected($current_filters['rating_filter'] ?? '', '7'); ?>>7+ ⭐⭐⭐⭐</option>
                        <option value="6" <?php selected($current_filters['rating_filter'] ?? '', '6'); ?>>6+ ⭐⭐⭐</option>
                        <option value="5" <?php selected($current_filters['rating_filter'] ?? '', '5'); ?>>5+ ⭐⭐⭐</option>
                    </select>
                </div>
                
                <!-- Status Filter (for TV/Drama) -->
                <?php if (in_array($post_type, ['tv', 'drama'])): ?>
                    <div class="filter-group">
                        <label for="status_filter" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Status', 'tmu-theme'); ?>
                        </label>
                        <select id="status_filter" name="status_filter" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value=""><?php _e('All Status', 'tmu-theme'); ?></option>
                            <option value="returning" <?php selected($current_filters['status_filter'] ?? '', 'returning'); ?>>
                                <?php _e('Currently Airing', 'tmu-theme'); ?>
                            </option>
                            <option value="ended" <?php selected($current_filters['status_filter'] ?? '', 'ended'); ?>>
                                <?php _e('Ended', 'tmu-theme'); ?>
                            </option>
                            <option value="canceled" <?php selected($current_filters['status_filter'] ?? '', 'canceled'); ?>>
                                <?php _e('Canceled', 'tmu-theme'); ?>
                            </option>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </form>
    
    <!-- Active Filters Display -->
    <div id="activeFilters" class="mt-6 hidden">
        <div class="flex flex-wrap gap-2">
            <span class="text-sm font-medium text-gray-700 mr-2"><?php _e('Active Filters:', 'tmu-theme'); ?></span>
            <div id="activeFiltersContainer" class="flex flex-wrap gap-2"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('archiveFilters');
    const clearButton = document.getElementById('clearFilters');
    const activeFiltersDiv = document.getElementById('activeFilters');
    const activeFiltersContainer = document.getElementById('activeFiltersContainer');
    
    // Handle filter changes
    form.addEventListener('change', function() {
        applyFilters();
        updateActiveFilters();
    });
    
    // Clear all filters
    clearButton.addEventListener('click', function() {
        form.reset();
        applyFilters();
        updateActiveFilters();
    });
    
    function applyFilters() {
        const formData = new FormData(form);
        const params = new URLSearchParams();
        
        for (let [key, value] of formData.entries()) {
            if (value && value !== '') {
                params.append(key, value);
            }
        }
        
        const url = new URL(window.location);
        url.search = params.toString();
        window.location.href = url.toString();
    }
    
    function updateActiveFilters() {
        const formData = new FormData(form);
        const activeFilters = [];
        
        for (let [key, value] of formData.entries()) {
            if (value && value !== '' && key !== 'post_type') {
                const select = form.querySelector(`[name="${key}"]`);
                const option = select.querySelector(`option[value="${value}"]`);
                if (option) {
                    activeFilters.push({
                        key: key,
                        value: value,
                        label: option.textContent.trim()
                    });
                }
            }
        }
        
        if (activeFilters.length > 0) {
            activeFiltersDiv.classList.remove('hidden');
            activeFiltersContainer.innerHTML = activeFilters.map(filter => 
                `<span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
                    ${filter.label}
                    <button type="button" class="ml-2 text-blue-600 hover:text-blue-800" onclick="removeFilter('${filter.key}')">
                        ×
                    </button>
                </span>`
            ).join('');
        } else {
            activeFiltersDiv.classList.add('hidden');
        }
    }
    
    // Initialize active filters display
    updateActiveFilters();
});

function removeFilter(filterKey) {
    const select = document.querySelector(`[name="${filterKey}"]`);
    if (select) {
        select.value = '';
        select.dispatchEvent(new Event('change'));
    }
}
</script>