<?php
/**
 * Person Card Component
 * 
 * @package TMU
 */

$person_data = $args['person_data'] ?? [];
$post_id = $args['post_id'] ?? get_the_ID();
$role = $args['role'] ?? '';
$size = $args['size'] ?? 'medium';
$show_bio = $args['show_bio'] ?? false;

if (!$post_id) return;

$person_post = get_post($post_id);
if (!$person_post) return;

// Get person data from helper function or passed data
if (empty($person_data) && function_exists('tmu_get_person_data')) {
    $person_data = tmu_get_person_data($post_id);
}
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-200 group">
    <!-- Person Image -->
    <div class="relative <?php echo $size === 'large' ? 'h-64' : 'h-48'; ?> bg-gray-200 overflow-hidden">
        <a href="<?php echo esc_url(get_permalink($post_id)); ?>" 
           class="block w-full h-full">
            <?php if (has_post_thumbnail($post_id)): ?>
                <?php echo get_the_post_thumbnail($post_id, 'medium', [
                    'class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-200',
                    'alt' => get_the_title($post_id)
                ]); ?>
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300">
                    <svg class="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            <?php endif; ?>
        </a>
        
        <!-- Quick Actions Overlay -->
        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 flex items-center justify-center opacity-0 group-hover:opacity-100">
            <div class="flex space-x-2">
                <button class="bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-800 p-2 rounded-full transition-all duration-200"
                        data-action="quick-view"
                        data-post-id="<?php echo esc_attr($post_id); ?>"
                        title="Quick View">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </button>
                
                <button class="bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-800 p-2 rounded-full transition-all duration-200"
                        data-action="add-favorite"
                        data-post-id="<?php echo esc_attr($post_id); ?>"
                        title="Add to Favorites">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Known For Badge -->
        <?php if (!empty($person_data['known_for_department'])): ?>
            <div class="absolute top-2 left-2">
                <span class="bg-blue-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                    <?php echo esc_html($person_data['known_for_department']); ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Person Info -->
    <div class="p-4">
        <!-- Name -->
        <h3 class="text-lg font-semibold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors duration-200">
            <a href="<?php echo esc_url(get_permalink($post_id)); ?>" 
               class="line-clamp-2">
                <?php echo esc_html(get_the_title($post_id)); ?>
            </a>
        </h3>
        
        <!-- Role (if provided) -->
        <?php if ($role): ?>
            <p class="text-sm text-gray-600 mb-2 font-medium">
                <?php echo esc_html($role); ?>
            </p>
        <?php endif; ?>
        
        <!-- Person Details -->
        <div class="space-y-1 mb-3">
            <?php if (!empty($person_data['birthday'])): ?>
                <div class="flex items-center text-sm text-gray-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span><?php echo esc_html(date('M j, Y', strtotime($person_data['birthday']))); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($person_data['place_of_birth'])): ?>
                <div class="flex items-center text-sm text-gray-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span class="truncate"><?php echo esc_html($person_data['place_of_birth']); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Biography (if enabled) -->
        <?php if ($show_bio && !empty($person_data['biography'])): ?>
            <div class="mt-3">
                <p class="text-sm text-gray-600 line-clamp-3">
                    <?php echo esc_html(wp_trim_words($person_data['biography'], 25)); ?>
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Known For Preview -->
        <?php if (!empty($person_data['known_for']) && is_array($person_data['known_for'])): ?>
            <div class="mt-3">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Known For:</h4>
                <div class="flex flex-wrap gap-1">
                    <?php foreach (array_slice($person_data['known_for'], 0, 3) as $work): ?>
                        <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full">
                            <?php echo esc_html($work['title'] ?? $work['name'] ?? 'Unknown'); ?>
                        </span>
                    <?php endforeach; ?>
                    
                    <?php if (count($person_data['known_for']) > 3): ?>
                        <span class="inline-block bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded-full">
                            +<?php echo count($person_data['known_for']) - 3; ?> more
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div class="mt-4 flex justify-between items-center">
            <a href="<?php echo esc_url(get_permalink($post_id)); ?>" 
               class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors duration-200">
                View Profile
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
            
            <!-- Popularity Score -->
            <?php if (!empty($person_data['popularity'])): ?>
                <div class="flex items-center text-sm text-gray-500">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                    <span><?php echo number_format($person_data['popularity'], 1); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>