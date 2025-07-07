<?php
/**
 * Movie Similar/Related Template
 * 
 * Displays similar movies based on genres and ratings
 * 
 * @package TMU
 * @since 1.0.0
 */

$movie_data = $args['movie_data'] ?? [];
$post_id = get_the_ID();

// Get related movies based on genres
$related_movies = tmu_get_related_posts($post_id, 'movie', 12);

// If no related movies, try to get popular movies from same year
if (empty($related_movies) && !empty($movie_data['release_date'])) {
    $release_year = date('Y', strtotime($movie_data['release_date']));
    $related_movies = get_posts([
        'post_type' => 'movie',
        'posts_per_page' => 12,
        'post__not_in' => [$post_id],
        'tax_query' => [
            [
                'taxonomy' => 'by-year',
                'field' => 'name',
                'terms' => $release_year
            ]
        ],
        'meta_query' => [
            [
                'key' => 'vote_average',
                'value' => 5.0,
                'compare' => '>='
            ]
        ],
        'orderby' => 'meta_value_num',
        'meta_key' => 'vote_average',
        'order' => 'DESC'
    ]);
}

// Final fallback to highly rated movies
if (empty($related_movies)) {
    $related_movies = get_posts([
        'post_type' => 'movie',
        'posts_per_page' => 12,
        'post__not_in' => [$post_id],
        'meta_query' => [
            [
                'key' => 'vote_average',
                'value' => 7.0,
                'compare' => '>='
            ]
        ],
        'orderby' => 'meta_value_num',
        'meta_key' => 'vote_average',
        'order' => 'DESC'
    ]);
}
?>

<div class="tmu-similar-movies">
    <?php if (!empty($related_movies)): ?>
        <div class="tmu-similar-header mb-6">
            <h3 class="text-2xl font-semibold text-gray-900 mb-2">
                <?php _e('Similar Movies', 'tmu-theme'); ?>
            </h3>
            <p class="text-gray-600">
                <?php _e('You might also like these movies based on similar genres and ratings.', 'tmu-theme'); ?>
            </p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
            <?php foreach ($related_movies as $related_movie): ?>
                <?php 
                $related_movie_data = tmu_get_movie_data($related_movie->ID);
                get_template_part('templates/components/movie-card', null, [
                    'movie_data' => $related_movie_data,
                    'post_id' => $related_movie->ID,
                    'size' => 'small',
                    'show_rating' => true,
                    'show_year' => true
                ]);
                ?>
            <?php endforeach; ?>
        </div>
        
        <?php 
        // Get total count for "View All" link
        $genres = wp_get_post_terms($post_id, 'genre', ['fields' => 'ids']);
        if (!empty($genres)): 
            $genre_term = get_term($genres[0]);
            if ($genre_term && !is_wp_error($genre_term)):
        ?>
            <div class="mt-8 text-center">
                <a href="<?php echo esc_url(get_term_link($genre_term)); ?>" 
                   class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                    <?php printf(__('View All %s Movies', 'tmu-theme'), esc_html($genre_term->name)); ?>
                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        <?php 
            endif;
        endif; 
        ?>
        
    <?php else: ?>
        <!-- No Similar Movies Available -->
        <div class="text-center py-12 bg-gray-50 rounded-lg">
            <div class="text-gray-400 text-6xl mb-4">🎬</div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                <?php _e('No Similar Movies Found', 'tmu-theme'); ?>
            </h3>
            <p class="text-gray-600 mb-6">
                <?php _e('We couldn\'t find movies similar to this one, but you can explore our movie collection.', 'tmu-theme'); ?>
            </p>
            <a href="<?php echo esc_url(get_post_type_archive_link('movie')); ?>" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                <?php _e('Browse All Movies', 'tmu-theme'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
// Enhanced similar movies functionality
document.addEventListener('DOMContentLoaded', function() {
    const similarContainer = document.querySelector('.tmu-similar-movies');
    
    if (similarContainer) {
        // Add intersection observer for lazy loading
        const movieCards = similarContainer.querySelectorAll('.tmu-movie-card');
        
        if ('IntersectionObserver' in window) {
            const cardObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fadeIn');
                        cardObserver.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            });
            
            movieCards.forEach(card => {
                cardObserver.observe(card);
            });
        }
    }
});
</script>

<style>
/* Animation for similar movies cards */
.animate-fadeIn {
    animation: fadeIn 0.6s ease-out forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Hover effects for similar movies grid */
.tmu-similar-movies .tmu-movie-card {
    transition: all 0.3s ease;
}

.tmu-similar-movies .tmu-movie-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}
</style>