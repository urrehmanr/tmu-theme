<?php
/**
 * Movie Details Tab Template
 * 
 * Displays detailed information about a movie
 * 
 * @package TMU
 * @since 1.0.0
 */

$movie_data = $args['movie_data'] ?? [];
$post_id = get_the_ID();
$countries = tmu_get_country_links($post_id);
?>

<div class="tmu-movie-details">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Left Column: Main Details -->
        <div class="space-y-6">
            <!-- Production Details -->
            <div class="bg-white rounded-lg p-6 shadow-sm">
                <h3 class="text-xl font-semibold mb-4 text-gray-900"><?php _e('Production Details', 'tmu-theme'); ?></h3>
                
                <div class="space-y-3">
                    <?php if (!empty($movie_data['original_title']) && $movie_data['original_title'] !== get_the_title()): ?>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="font-medium text-gray-700"><?php _e('Original Title:', 'tmu-theme'); ?></span>
                            <span class="text-gray-900"><?php echo esc_html($movie_data['original_title']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($movie_data['release_date'])): ?>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="font-medium text-gray-700"><?php _e('Release Date:', 'tmu-theme'); ?></span>
                            <span class="text-gray-900"><?php echo date('F j, Y', strtotime($movie_data['release_date'])); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($movie_data['runtime'])): ?>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="font-medium text-gray-700"><?php _e('Runtime:', 'tmu-theme'); ?></span>
                            <span class="text-gray-900"><?php echo tmu_format_runtime((int) $movie_data['runtime']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($movie_data['status'])): ?>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="font-medium text-gray-700"><?php _e('Status:', 'tmu-theme'); ?></span>
                            <span><?php echo tmu_get_status_badge($movie_data['status']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($movie_data['original_language'])): ?>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="font-medium text-gray-700"><?php _e('Language:', 'tmu-theme'); ?></span>
                            <span class="text-gray-900"><?php echo esc_html(strtoupper($movie_data['original_language'])); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($countries)): ?>
                        <div class="flex justify-between items-start py-2">
                            <span class="font-medium text-gray-700"><?php _e('Countries:', 'tmu-theme'); ?></span>
                            <div class="text-right">
                                <?php foreach ($countries as $index => $country): ?>
                                    <a href="<?php echo esc_url($country['url']); ?>" 
                                       class="text-blue-600 hover:text-blue-800 transition-colors">
                                        <?php echo esc_html($country['name']); ?>
                                    </a>
                                    <?php if ($index < count($countries) - 1): ?>
                                        <span class="text-gray-400">, </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Financial Information -->
            <?php if (!empty($movie_data['budget']) || !empty($movie_data['revenue'])): ?>
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h3 class="text-xl font-semibold mb-4 text-gray-900"><?php _e('Financial Information', 'tmu-theme'); ?></h3>
                    
                    <div class="space-y-3">
                        <?php if (!empty($movie_data['budget'])): ?>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="font-medium text-gray-700"><?php _e('Budget:', 'tmu-theme'); ?></span>
                                <span class="text-green-600 font-semibold"><?php echo tmu_format_currency((int) $movie_data['budget']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie_data['revenue'])): ?>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="font-medium text-gray-700"><?php _e('Revenue:', 'tmu-theme'); ?></span>
                                <span class="text-green-600 font-semibold"><?php echo tmu_format_currency((int) $movie_data['revenue']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie_data['budget']) && !empty($movie_data['revenue']) && $movie_data['budget'] > 0): ?>
                            <?php $profit = (int) $movie_data['revenue'] - (int) $movie_data['budget']; ?>
                            <div class="flex justify-between items-center py-2">
                                <span class="font-medium text-gray-700"><?php _e('Profit:', 'tmu-theme'); ?></span>
                                <span class="<?php echo $profit >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-semibold">
                                    <?php echo tmu_format_currency(abs($profit)); ?>
                                    <?php if ($profit < 0): ?> <?php _e('(Loss)', 'tmu-theme'); ?><?php endif; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Right Column: Ratings & Popularity -->
        <div class="space-y-6">
            <!-- Ratings & Reviews -->
            <div class="bg-white rounded-lg p-6 shadow-sm">
                <h3 class="text-xl font-semibold mb-4 text-gray-900"><?php _e('Ratings & Reviews', 'tmu-theme'); ?></h3>
                
                <div class="space-y-4">
                    <?php if (!empty($movie_data['vote_average'])): ?>
                        <div class="text-center">
                            <div class="text-4xl font-bold text-yellow-500 mb-2">
                                <?php echo number_format((float) $movie_data['vote_average'], 1); ?>/10
                            </div>
                            
                            <div class="flex justify-center mb-2">
                                <?php 
                                get_template_part('templates/components/rating-stars', null, [
                                    'rating' => (float) $movie_data['vote_average'],
                                    'size' => 'large',
                                    'show_text' => false
                                ]);
                                ?>
                            </div>
                            
                            <?php if (!empty($movie_data['vote_count'])): ?>
                                <div class="text-sm text-gray-600">
                                    <?php printf(
                                        _n('Based on %s vote', 'Based on %s votes', (int) $movie_data['vote_count'], 'tmu-theme'),
                                        number_format((int) $movie_data['vote_count'])
                                    ); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($movie_data['popularity'])): ?>
                        <div class="text-center pt-4 border-t border-gray-100">
                            <div class="text-2xl font-bold text-purple-500 mb-1">
                                <?php echo number_format((float) $movie_data['popularity'], 1); ?>
                            </div>
                            <div class="text-sm text-gray-600"><?php _e('Popularity Score', 'tmu-theme'); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Keywords -->
            <?php 
            $keywords = get_the_terms($post_id, 'keyword');
            if ($keywords && !is_wp_error($keywords)): 
            ?>
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h3 class="text-xl font-semibold mb-4 text-gray-900"><?php _e('Keywords', 'tmu-theme'); ?></h3>
                    
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($keywords as $keyword): ?>
                            <a href="<?php echo esc_url(get_term_link($keyword)); ?>" 
                               class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-full transition-colors">
                                <?php echo esc_html($keyword->name); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Additional Information -->
            <div class="bg-white rounded-lg p-6 shadow-sm">
                <h3 class="text-xl font-semibold mb-4 text-gray-900"><?php _e('Additional Information', 'tmu-theme'); ?></h3>
                
                <div class="space-y-3">
                    <?php if (!empty($movie_data['homepage'])): ?>
                        <div>
                            <span class="font-medium text-gray-700"><?php _e('Official Website:', 'tmu-theme'); ?></span>
                            <a href="<?php echo esc_url($movie_data['homepage']); ?>" 
                               target="_blank" 
                               class="text-blue-600 hover:text-blue-800 ml-2 transition-colors">
                                <?php _e('Visit Site', 'tmu-theme'); ?>
                                <span class="ml-1">↗</span>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($movie_data['imdb_id'])): ?>
                        <div>
                            <span class="font-medium text-gray-700"><?php _e('IMDb:', 'tmu-theme'); ?></span>
                            <a href="https://www.imdb.com/title/<?php echo esc_attr($movie_data['imdb_id']); ?>" 
                               target="_blank" 
                               class="text-blue-600 hover:text-blue-800 ml-2 transition-colors">
                                <?php _e('View on IMDb', 'tmu-theme'); ?>
                                <span class="ml-1">↗</span>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <span class="font-medium text-gray-700"><?php _e('Age Rating:', 'tmu-theme'); ?></span>
                        <span class="ml-2 px-2 py-1 bg-gray-200 text-gray-800 text-xs font-semibold rounded">
                            <?php echo tmu_get_age_rating($post_id); ?>
                        </span>
                    </div>
                    
                    <?php if (tmu_is_featured($post_id)): ?>
                        <div>
                            <span class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded">
                                ⭐ <?php _e('Featured Content', 'tmu-theme'); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>