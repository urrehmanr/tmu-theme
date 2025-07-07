<?php
/**
 * Movie Cast & Crew Tab Template
 * 
 * Displays cast and crew information for a movie
 * 
 * @package TMU
 * @since 1.0.0
 */

$cast_crew = $args['cast_crew'] ?? [];
$cast = $cast_crew['cast'] ?? [];
$crew = $cast_crew['crew'] ?? [];

// Group crew by department
$crew_by_department = [];
foreach ($crew as $crew_member) {
    $department = $crew_member['department'] ?? 'Unknown';
    if (!isset($crew_by_department[$department])) {
        $crew_by_department[$department] = [];
    }
    $crew_by_department[$department][] = $crew_member;
}

// Sort departments with important ones first
$department_order = ['Directing', 'Writing', 'Production', 'Art', 'Sound', 'Camera', 'Editing'];
uksort($crew_by_department, function($a, $b) use ($department_order) {
    $a_index = array_search($a, $department_order);
    $b_index = array_search($b, $department_order);
    
    if ($a_index === false && $b_index === false) {
        return strcmp($a, $b);
    }
    if ($a_index === false) return 1;
    if ($b_index === false) return -1;
    
    return $a_index - $b_index;
});
?>

<div class="tmu-movie-cast-crew">
    <?php if (!empty($cast)): ?>
        <!-- Cast Section -->
        <div class="mb-12">
            <h3 class="text-2xl font-semibold mb-6 text-gray-900 flex items-center">
                <span class="mr-2">🎭</span>
                <?php _e('Cast', 'tmu-theme'); ?>
                <span class="ml-2 text-sm text-gray-500 font-normal">(<?php echo count($cast); ?>)</span>
            </h3>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                <?php foreach (array_slice($cast, 0, 18) as $actor): ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                        <!-- Actor Photo -->
                        <div class="aspect-[3/4] bg-gray-200 relative overflow-hidden">
                            <?php if (!empty($actor['profile_path'])): ?>
                                <img src="<?php echo esc_url(tmu_get_image_url($actor['profile_path'], 'w185')); ?>" 
                                     alt="<?php echo esc_attr($actor['name'] ?? ''); ?>"
                                     class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <span class="text-4xl">👤</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Actor Info -->
                        <div class="p-3">
                            <h4 class="font-semibold text-sm text-gray-900 mb-1 line-clamp-2">
                                <?php if (!empty($actor['person_id'])): ?>
                                    <a href="<?php echo esc_url(get_permalink($actor['person_id'])); ?>" 
                                       class="hover:text-blue-600 transition-colors">
                                        <?php echo esc_html($actor['name'] ?? ''); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo esc_html($actor['name'] ?? ''); ?>
                                <?php endif; ?>
                            </h4>
                            
                            <?php if (!empty($actor['character'])): ?>
                                <p class="text-xs text-gray-600 line-clamp-2">
                                    <?php echo esc_html($actor['character']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($cast) > 18): ?>
                <div class="mt-6 text-center">
                    <button class="tmu-show-more-cast bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        <?php printf(__('Show All %d Cast Members', 'tmu-theme'), count($cast)); ?>
                    </button>
                </div>
                
                <!-- Hidden Cast Members -->
                <div class="tmu-hidden-cast hidden mt-6">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                        <?php foreach (array_slice($cast, 18) as $actor): ?>
                            <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                                <div class="aspect-[3/4] bg-gray-200 relative overflow-hidden">
                                    <?php if (!empty($actor['profile_path'])): ?>
                                        <img src="<?php echo esc_url(tmu_get_image_url($actor['profile_path'], 'w185')); ?>" 
                                             alt="<?php echo esc_attr($actor['name'] ?? ''); ?>"
                                             class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                                            <span class="text-4xl">👤</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="p-3">
                                    <h4 class="font-semibold text-sm text-gray-900 mb-1 line-clamp-2">
                                        <?php if (!empty($actor['person_id'])): ?>
                                            <a href="<?php echo esc_url(get_permalink($actor['person_id'])); ?>" 
                                               class="hover:text-blue-600 transition-colors">
                                                <?php echo esc_html($actor['name'] ?? ''); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo esc_html($actor['name'] ?? ''); ?>
                                        <?php endif; ?>
                                    </h4>
                                    
                                    <?php if (!empty($actor['character'])): ?>
                                        <p class="text-xs text-gray-600 line-clamp-2">
                                            <?php echo esc_html($actor['character']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($crew_by_department)): ?>
        <!-- Crew Section -->
        <div>
            <h3 class="text-2xl font-semibold mb-6 text-gray-900 flex items-center">
                <span class="mr-2">🎬</span>
                <?php _e('Crew', 'tmu-theme'); ?>
                <span class="ml-2 text-sm text-gray-500 font-normal">(<?php echo count($crew); ?>)</span>
            </h3>
            
            <div class="space-y-8">
                <?php foreach ($crew_by_department as $department => $department_crew): ?>
                    <div class="bg-white rounded-lg p-6 shadow-sm">
                        <h4 class="text-lg font-semibold mb-4 text-gray-900 border-b border-gray-200 pb-2">
                            <?php echo esc_html($department); ?>
                            <span class="text-sm text-gray-500 font-normal ml-2">(<?php echo count($department_crew); ?>)</span>
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($department_crew as $crew_member): ?>
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="flex-shrink-0">
                                        <?php if (!empty($crew_member['profile_path'])): ?>
                                            <img src="<?php echo esc_url(tmu_get_image_url($crew_member['profile_path'], 'w92')); ?>" 
                                                 alt="<?php echo esc_attr($crew_member['name'] ?? ''); ?>"
                                                 class="w-12 h-12 rounded-full object-cover">
                                        <?php else: ?>
                                            <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center">
                                                <span class="text-gray-500 text-lg">👤</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <h5 class="font-medium text-gray-900 text-sm truncate">
                                            <?php if (!empty($crew_member['person_id'])): ?>
                                                <a href="<?php echo esc_url(get_permalink($crew_member['person_id'])); ?>" 
                                                   class="hover:text-blue-600 transition-colors">
                                                    <?php echo esc_html($crew_member['name'] ?? ''); ?>
                                                </a>
                                            <?php else: ?>
                                                <?php echo esc_html($crew_member['name'] ?? ''); ?>
                                            <?php endif; ?>
                                        </h5>
                                        
                                        <?php if (!empty($crew_member['job'])): ?>
                                            <p class="text-xs text-gray-600 truncate">
                                                <?php echo esc_html($crew_member['job']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (empty($cast) && empty($crew)): ?>
        <!-- No Cast/Crew Available -->
        <div class="text-center py-12 bg-gray-50 rounded-lg">
            <div class="text-gray-400 text-6xl mb-4">🎭</div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php _e('No Cast & Crew Information Available', 'tmu-theme'); ?></h3>
            <p class="text-gray-600"><?php _e('Cast and crew details will be added when available.', 'tmu-theme'); ?></p>
        </div>
    <?php endif; ?>
</div>

<script>
// Show more cast functionality
document.addEventListener('DOMContentLoaded', function() {
    const showMoreButton = document.querySelector('.tmu-show-more-cast');
    const hiddenCast = document.querySelector('.tmu-hidden-cast');
    
    if (showMoreButton && hiddenCast) {
        showMoreButton.addEventListener('click', function() {
            hiddenCast.classList.remove('hidden');
            showMoreButton.style.display = 'none';
        });
    }
});
</script>