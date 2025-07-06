<?php
/**
 * Movie Card Component
 * 
 * Reusable component for displaying movie information
 * 
 * @package TMU
 * @since 1.0.0
 */

$movie_data = $args['movie_data'] ?? [];
$post_id = $args['post_id'] ?? get_the_ID();
$size = $args['size'] ?? 'medium';
$show_rating = $args['show_rating'] ?? true;
$show_year = $args['show_year'] ?? true;
$show_overview = $args['show_overview'] ?? false;

// Size classes for different card sizes
$size_classes = [
    'small' => 'w-full max-w-xs',
    'medium' => 'w-full max-w-sm', 
    'large' => 'w-full max-w-md'
];

$card_class = $size_classes[$size] ?? $size_classes['medium'];
?>

<article class="tmu-movie-card <?php echo esc_attr($card_class); ?> bg-white rounded-lg shadow-lg overflow-hidden transition-transform hover:scale-105 hover:shadow-xl">
    <!-- Movie Poster -->
    <div class="relative group">
        <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="block">
            <div class="aspect-[2/3] overflow-hidden">
                <?php if (has_post_thumbnail($post_id)): ?>
                    <?php echo get_the_post_thumbnail($post_id, 'medium', [
                        'class' => 'w-full h-full object-cover transition-transform group-hover:scale-110',
                        'alt' => get_the_title($post_id),
                        'loading' => 'lazy'
                    ]); ?>
                <?php else: ?>
                    <div class="w-full h-full bg-gray-300 flex items-center justify-center">
                        <span class="text-gray-500 text-4xl">🎬</span>
                    </div>
                <?php endif; ?>
            </div>
        </a>
        
        <!-- Overlay with actions -->
        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-70 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
            <div class="space-x-3">
                <button class="tmu-quick-view bg-white text-black p-2 rounded-full hover:bg-gray-200 transition-colors" 
                        data-post-id="<?php echo esc_attr($post_id); ?>"
                        title="<?php esc_attr_e('Quick View', 'tmu-theme'); ?>">
                    👁
                </button>
                <button class="tmu-add-watchlist bg-blue-600 text-white p-2 rounded-full hover:bg-blue-700 transition-colors" 
                        data-post-id="<?php echo esc_attr($post_id); ?>"
                        title="<?php esc_attr_e('Add to Watchlist', 'tmu-theme'); ?>">
                    +
                </button>
            </div>
        </div>
        
        <!-- Rating badge -->
        <?php if ($show_rating && !empty($movie_data['vote_average'])): ?>
            <div class="absolute top-2 right-2">
                <span class="bg-black bg-opacity-80 text-white text-xs font-bold px-2 py-1 rounded">
                    ⭐ <?php echo number_format((float) $movie_data['vote_average'], 1); ?>
                </span>
            </div>
        <?php endif; ?>
        
        <!-- Featured badge -->
        <?php if (tmu_is_featured($post_id)): ?>
            <div class="absolute top-2 left-2">
                <span class="bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">
                    <?php _e('Featured', 'tmu-theme'); ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Card Content -->
    <div class="p-4">
        <!-- Movie Title -->
        <h3 class="font-bold text-lg mb-2 line-clamp-2">
            <a href="<?php echo esc_url(get_permalink($post_id)); ?>" 
               class="text-gray-900 hover:text-blue-600 transition-colors">
                <?php echo esc_html(get_the_title($post_id)); ?>
            </a>
        </h3>
        
        <!-- Movie Year -->
        <?php if ($show_year && !empty($movie_data['release_date'])): ?>
            <div class="text-sm text-gray-600 mb-2">
                <?php echo date('Y', strtotime($movie_data['release_date'])); ?>
            </div>
        <?php endif; ?>
        
        <!-- Movie Genres -->
        <?php
        $genres = tmu_get_genre_links($post_id);
        if (!empty($genres)):
            $genre_names = array_slice(array_column($genres, 'name'), 0, 2);
            ?>
            <div class="text-sm text-gray-500 mb-3">
                <?php echo esc_html(implode(', ', $genre_names)); ?>
            </div>
        <?php endif; ?>
        
        <!-- Overview (for larger cards) -->
        <?php if ($show_overview && !empty($movie_data['overview'])): ?>
            <p class="text-sm text-gray-600 line-clamp-3 mb-3">
                <?php echo esc_html(tmu_truncate_text($movie_data['overview'], 120)); ?>
            </p>
        <?php endif; ?>
        
        <!-- Rating Stars -->
        <?php if ($show_rating && !empty($movie_data['vote_average'])): ?>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-1">
                    <?php echo tmu_render_rating((float) $movie_data['vote_average']); ?>
                </div>
                
                <!-- Runtime -->
                <?php if (!empty($movie_data['runtime'])): ?>
                    <div class="text-xs text-gray-500">
                        <?php echo tmu_format_runtime((int) $movie_data['runtime']); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Action buttons for larger cards -->
        <?php if ($size === 'large'): ?>
            <div class="mt-4 space-y-2">
                <a href="<?php echo esc_url(get_permalink($post_id)); ?>" 
                   class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded text-center font-medium transition-colors block">
                    <?php _e('View Details', 'tmu-theme'); ?>
                </a>
                
                <?php $trailer_url = tmu_get_trailer_url($post_id); ?>
                <?php if ($trailer_url): ?>
                    <a href="<?php echo esc_url($trailer_url); ?>" 
                       target="_blank"
                       class="w-full bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded text-center font-medium transition-colors block">
                        <?php _e('Watch Trailer', 'tmu-theme'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</article>

<!-- CSS for line clamping (if not using Tailwind's line-clamp) -->
<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.tmu-movie-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.tmu-movie-card:hover {
    transform: translateY(-4px);
}

.tmu-rating-stars .star {
    color: #fbbf24; /* Yellow color for stars */
    font-size: 0.875rem;
}

.tmu-rating-stars .star-empty {
    color: #d1d5db; /* Gray color for empty stars */
}
</style>