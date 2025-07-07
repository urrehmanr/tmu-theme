<?php
/**
 * Search Filters Template
 *
 * Template for displaying search filters as shown in Step 12 documentation
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current facets data
$facets = $args['facets'] ?? [];
?>

<div class="tmu-search-filters bg-white rounded-lg shadow-md p-6">
    <div class="tmu-filters-header flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900"><?php _e('Filters', 'tmu'); ?></h3>
        <button class="tmu-clear-filters text-sm text-blue-600 hover:text-blue-800 transition-colors duration-200">
            <?php _e('Clear All', 'tmu'); ?>
        </button>
    </div>
    
    <!-- Content Type Filter -->
    <div class="tmu-filter-group mb-6">
        <h4 class="font-medium text-gray-900 mb-3"><?php _e('Content Type', 'tmu'); ?></h4>
        <div class="tmu-filter-options space-y-2">
            <label class="tmu-filter-option flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors duration-200">
                <input type="checkbox" class="tmu-filter-checkbox mr-3 text-blue-600 rounded focus:ring-blue-500" 
                       data-filter-type="post_type" value="movie">
                <span class="flex-1 text-sm text-gray-700"><?php _e('Movies', 'tmu'); ?></span>
                <span class="tmu-filter-count text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                    (<?php echo esc_html($facets['post_type']['movie'] ?? 0); ?>)
                </span>
            </label>
            
            <label class="tmu-filter-option flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors duration-200">
                <input type="checkbox" class="tmu-filter-checkbox mr-3 text-blue-600 rounded focus:ring-blue-500" 
                       data-filter-type="post_type" value="tv">
                <span class="flex-1 text-sm text-gray-700"><?php _e('TV Shows', 'tmu'); ?></span>
                <span class="tmu-filter-count text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                    (<?php echo esc_html($facets['post_type']['tv'] ?? 0); ?>)
                </span>
            </label>
            
            <?php if (get_option('tmu_dramas') === 'on'): ?>
            <label class="tmu-filter-option flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors duration-200">
                <input type="checkbox" class="tmu-filter-checkbox mr-3 text-blue-600 rounded focus:ring-blue-500" 
                       data-filter-type="post_type" value="drama">
                <span class="flex-1 text-sm text-gray-700"><?php _e('Dramas', 'tmu'); ?></span>
                <span class="tmu-filter-count text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                    (<?php echo esc_html($facets['post_type']['drama'] ?? 0); ?>)
                </span>
            </label>
            <?php endif; ?>
            
            <label class="tmu-filter-option flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors duration-200">
                <input type="checkbox" class="tmu-filter-checkbox mr-3 text-blue-600 rounded focus:ring-blue-500" 
                       data-filter-type="post_type" value="people">
                <span class="flex-1 text-sm text-gray-700"><?php _e('People', 'tmu'); ?></span>
                <span class="tmu-filter-count text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                    (<?php echo esc_html($facets['post_type']['people'] ?? 0); ?>)
                </span>
            </label>
        </div>
    </div>
    
    <!-- Genres Filter -->
    <div class="tmu-filter-group mb-6">
        <h4 class="font-medium text-gray-900 mb-3"><?php _e('Genres', 'tmu'); ?></h4>
        <div class="tmu-filter-options tmu-scrollable max-h-48 overflow-y-auto space-y-2">
            <?php foreach ($facets['genres'] ?? [] as $genre_slug => $count): ?>
                <?php $genre = get_term_by('slug', $genre_slug, 'genre'); ?>
                <?php if ($genre && $count > 0): ?>
                <label class="tmu-filter-option flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors duration-200">
                    <input type="checkbox" class="tmu-filter-checkbox mr-3 text-blue-600 rounded focus:ring-blue-500" 
                           data-filter-type="genre" value="<?php echo esc_attr($genre_slug); ?>">
                    <span class="flex-1 text-sm text-gray-700"><?php echo esc_html($genre->name); ?></span>
                    <span class="tmu-filter-count text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                        (<?php echo esc_html($count); ?>)
                    </span>
                </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Release Year Filter -->
    <div class="tmu-filter-group mb-6">
        <h4 class="font-medium text-gray-900 mb-3"><?php _e('Release Year', 'tmu'); ?></h4>
        <div class="tmu-year-range-slider">
            <div class="flex items-center space-x-4 mb-3">
                <input type="range" 
                       class="tmu-year-min flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer" 
                       min="1900" max="<?php echo date('Y'); ?>" value="1900"
                       data-filter-type="year-min">
                <input type="range" 
                       class="tmu-year-max flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer" 
                       min="1900" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>"
                       data-filter-type="year-max">
            </div>
            <div class="tmu-year-display flex items-center justify-between text-sm text-gray-600">
                <span class="tmu-year-min-display">1900</span>
                <span>-</span>
                <span class="tmu-year-max-display"><?php echo date('Y'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Rating Filter -->
    <div class="tmu-filter-group mb-6">
        <h4 class="font-medium text-gray-900 mb-3"><?php _e('Rating', 'tmu'); ?></h4>
        <div class="tmu-rating-filter">
            <div class="tmu-rating-range">
                <input type="range" 
                       class="tmu-rating-slider w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer" 
                       min="0" max="10" step="0.1" value="0"
                       data-filter-type="rating">
                <div class="tmu-rating-display flex items-center justify-center mt-2 text-sm text-gray-600">
                    <span class="tmu-rating-value">0.0</span>
                    <span class="ml-1">+ ⭐</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Countries Filter -->
    <div class="tmu-filter-group mb-6">
        <h4 class="font-medium text-gray-900 mb-3"><?php _e('Countries', 'tmu'); ?></h4>
        <div class="tmu-filter-search mb-3">
            <input type="text" 
                   class="tmu-country-search w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                   placeholder="<?php _e('Search countries...', 'tmu'); ?>">
        </div>
        <div class="tmu-filter-options tmu-scrollable max-h-48 overflow-y-auto space-y-2">
            <?php foreach ($facets['countries'] ?? [] as $country_slug => $count): ?>
                <?php $country = get_term_by('slug', $country_slug, 'country'); ?>
                <?php if ($country && $count > 0): ?>
                <label class="tmu-filter-option flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors duration-200">
                    <input type="checkbox" class="tmu-filter-checkbox mr-3 text-blue-600 rounded focus:ring-blue-500" 
                           data-filter-type="country" value="<?php echo esc_attr($country_slug); ?>">
                    <span class="flex-1 text-sm text-gray-700"><?php echo esc_html($country->name); ?></span>
                    <span class="tmu-filter-count text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                        (<?php echo esc_html($count); ?>)
                    </span>
                </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Languages Filter -->
    <div class="tmu-filter-group mb-6">
        <h4 class="font-medium text-gray-900 mb-3"><?php _e('Languages', 'tmu'); ?></h4>
        <div class="tmu-filter-options tmu-scrollable max-h-40 overflow-y-auto space-y-2">
            <?php foreach ($facets['languages'] ?? [] as $language_slug => $count): ?>
                <?php $language = get_term_by('slug', $language_slug, 'language'); ?>
                <?php if ($language && $count > 0): ?>
                <label class="tmu-filter-option flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors duration-200">
                    <input type="checkbox" class="tmu-filter-checkbox mr-3 text-blue-600 rounded focus:ring-blue-500" 
                           data-filter-type="language" value="<?php echo esc_attr($language_slug); ?>">
                    <span class="flex-1 text-sm text-gray-700"><?php echo esc_html($language->name); ?></span>
                    <span class="tmu-filter-count text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                        (<?php echo esc_html($count); ?>)
                    </span>
                </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Runtime Filter (Movies Only) -->
    <div class="tmu-filter-group mb-6" id="runtime-filter" style="display: none;">
        <h4 class="font-medium text-gray-900 mb-3"><?php _e('Runtime', 'tmu'); ?></h4>
        <div class="tmu-filter-options space-y-2">
            <label class="tmu-filter-option flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors duration-200">
                <input type="checkbox" class="tmu-filter-checkbox mr-3 text-blue-600 rounded focus:ring-blue-500" 
                       data-filter-type="runtime" value="short">
                <span class="flex-1 text-sm text-gray-700"><?php _e('Short (< 90 min)', 'tmu'); ?></span>
            </label>
            
            <label class="tmu-filter-option flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors duration-200">
                <input type="checkbox" class="tmu-filter-checkbox mr-3 text-blue-600 rounded focus:ring-blue-500" 
                       data-filter-type="runtime" value="standard">
                <span class="flex-1 text-sm text-gray-700"><?php _e('Standard (90-120 min)', 'tmu'); ?></span>
            </label>
            
            <label class="tmu-filter-option flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors duration-200">
                <input type="checkbox" class="tmu-filter-checkbox mr-3 text-blue-600 rounded focus:ring-blue-500" 
                       data-filter-type="runtime" value="long">
                <span class="flex-1 text-sm text-gray-700"><?php _e('Long (121-150 min)', 'tmu'); ?></span>
            </label>
            
            <label class="tmu-filter-option flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors duration-200">
                <input type="checkbox" class="tmu-filter-checkbox mr-3 text-blue-600 rounded focus:ring-blue-500" 
                       data-filter-type="runtime" value="very-long">
                <span class="flex-1 text-sm text-gray-700"><?php _e('Very Long (151-180 min)', 'tmu'); ?></span>
            </label>
            
            <label class="tmu-filter-option flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors duration-200">
                <input type="checkbox" class="tmu-filter-checkbox mr-3 text-blue-600 rounded focus:ring-blue-500" 
                       data-filter-type="runtime" value="epic">
                <span class="flex-1 text-sm text-gray-700"><?php _e('Epic (> 180 min)', 'tmu'); ?></span>
            </label>
        </div>
    </div>
    
    <!-- Network Filter (TV Shows) -->
    <?php if (get_option('tmu_tv_series') === 'on'): ?>
    <div class="tmu-filter-group mb-6" id="network-filter" style="display: none;">
        <h4 class="font-medium text-gray-900 mb-3"><?php _e('Networks', 'tmu'); ?></h4>
        <div class="tmu-filter-options tmu-scrollable max-h-40 overflow-y-auto space-y-2">
            <?php foreach ($facets['networks'] ?? [] as $network_slug => $count): ?>
                <?php $network = get_term_by('slug', $network_slug, 'network'); ?>
                <?php if ($network && $count > 0): ?>
                <label class="tmu-filter-option flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors duration-200">
                    <input type="checkbox" class="tmu-filter-checkbox mr-3 text-blue-600 rounded focus:ring-blue-500" 
                           data-filter-type="network" value="<?php echo esc_attr($network_slug); ?>">
                    <span class="flex-1 text-sm text-gray-700"><?php echo esc_html($network->name); ?></span>
                    <span class="tmu-filter-count text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                        (<?php echo esc_html($count); ?>)
                    </span>
                </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Channel Filter (Dramas) -->
    <?php if (get_option('tmu_dramas') === 'on'): ?>
    <div class="tmu-filter-group" id="channel-filter" style="display: none;">
        <h4 class="font-medium text-gray-900 mb-3"><?php _e('Channels', 'tmu'); ?></h4>
        <div class="tmu-filter-options tmu-scrollable max-h-40 overflow-y-auto space-y-2">
            <?php foreach ($facets['channels'] ?? [] as $channel_slug => $count): ?>
                <?php $channel = get_term_by('slug', $channel_slug, 'channel'); ?>
                <?php if ($channel && $count > 0): ?>
                <label class="tmu-filter-option flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors duration-200">
                    <input type="checkbox" class="tmu-filter-checkbox mr-3 text-blue-600 rounded focus:ring-blue-500" 
                           data-filter-type="channel" value="<?php echo esc_attr($channel_slug); ?>">
                    <span class="flex-1 text-sm text-gray-700"><?php echo esc_html($channel->name); ?></span>
                    <span class="tmu-filter-count text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                        (<?php echo esc_html($count); ?>)
                    </span>
                </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Show/hide conditional filters based on selected post types
document.addEventListener('DOMContentLoaded', function() {
    const postTypeCheckboxes = document.querySelectorAll('.tmu-filter-checkbox[data-filter-type="post_type"]');
    const runtimeFilter = document.getElementById('runtime-filter');
    const networkFilter = document.getElementById('network-filter');
    const channelFilter = document.getElementById('channel-filter');
    
    function updateConditionalFilters() {
        const selectedTypes = Array.from(postTypeCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        
        // Show runtime filter only for movies
        if (runtimeFilter) {
            runtimeFilter.style.display = selectedTypes.includes('movie') ? 'block' : 'none';
        }
        
        // Show network filter only for TV shows
        if (networkFilter) {
            networkFilter.style.display = selectedTypes.includes('tv') ? 'block' : 'none';
        }
        
        // Show channel filter only for dramas
        if (channelFilter) {
            channelFilter.style.display = selectedTypes.includes('drama') ? 'block' : 'none';
        }
    }
    
    postTypeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateConditionalFilters);
    });
    
    // Initial check
    updateConditionalFilters();
});
</script>