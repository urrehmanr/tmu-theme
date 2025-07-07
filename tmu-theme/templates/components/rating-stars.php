<?php
/**
 * Rating Stars Component
 * 
 * Displays rating stars for movies, TV shows, and other content
 * 
 * @package TMU
 * @since 1.0.0
 */

$rating = $args['rating'] ?? 0;
$max_stars = $args['max_stars'] ?? 5;
$show_text = $args['show_text'] ?? true;
$size = $args['size'] ?? 'medium';
$color = $args['color'] ?? 'yellow';

$rating_normalized = $rating / 2; // Convert 10-point to 5-point scale
$full_stars = floor($rating_normalized);
$half_star = ($rating_normalized - $full_stars) >= 0.5;
$empty_stars = $max_stars - $full_stars - ($half_star ? 1 : 0);

$size_classes = [
    'small' => 'text-sm',
    'medium' => 'text-base',
    'large' => 'text-lg',
    'xl' => 'text-xl'
];

$color_classes = [
    'yellow' => 'text-yellow-400',
    'orange' => 'text-orange-400',
    'red' => 'text-red-400',
    'blue' => 'text-blue-400'
];

$star_class = $size_classes[$size] ?? $size_classes['medium'];
$color_class = $color_classes[$color] ?? $color_classes['yellow'];
?>

<div class="tmu-rating-stars flex items-center space-x-1 <?php echo esc_attr($star_class); ?>">
    <!-- Rating Stars Display -->
    <div class="flex items-center <?php echo esc_attr($color_class); ?>" role="img" aria-label="<?php echo esc_attr(sprintf(__('Rating: %.1f out of %d stars', 'tmu-theme'), $rating_normalized, $max_stars)); ?>">
        <!-- Full Stars -->
        <?php for ($i = 0; $i < $full_stars; $i++): ?>
            <span class="star star-full" aria-hidden="true">★</span>
        <?php endfor; ?>
        
        <!-- Half Star -->
        <?php if ($half_star): ?>
            <span class="star star-half relative" aria-hidden="true">
                <span class="absolute inset-0 overflow-hidden w-1/2">★</span>
                <span class="text-gray-300">★</span>
            </span>
        <?php endif; ?>
        
        <!-- Empty Stars -->
        <?php for ($i = 0; $i < $empty_stars; $i++): ?>
            <span class="star star-empty text-gray-300" aria-hidden="true">☆</span>
        <?php endfor; ?>
    </div>
    
    <!-- Rating Text -->
    <?php if ($show_text && $rating > 0): ?>
        <span class="rating-text text-gray-600 ml-2 text-sm font-medium">
            <?php echo number_format($rating, 1); ?>/10
        </span>
    <?php endif; ?>
</div>