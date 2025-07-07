<?php
/**
 * 404 Error Page Template
 * 
 * Template for displaying 404 error pages
 * 
 * @package TMU
 * @since 1.0.0
 */

get_header();
?>

<div class="tmu-404-page bg-gray-50 min-h-screen">
    <div class="tmu-container py-20">
        <div class="max-w-2xl mx-auto text-center">
            <!-- Error Icon -->
            <div class="mb-8">
                <div class="text-8xl mb-4">🎬</div>
                <h1 class="text-6xl font-bold text-gray-900 mb-4">404</h1>
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">
                    <?php _e('Page Not Found', 'tmu-theme'); ?>
                </h2>
                <p class="text-lg text-gray-600 mb-8">
                    <?php _e('Sorry, the page you are looking for doesn\'t exist or has been moved.', 'tmu-theme'); ?>
                </p>
            </div>
            
            <!-- Search Form -->
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-4"><?php _e('Search for Movies or TV Shows', 'tmu-theme'); ?></h3>
                <?php get_template_part('templates/partials/search-form'); ?>
            </div>
            
            <!-- Quick Links -->
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-4"><?php _e('Popular Pages', 'tmu-theme'); ?></h3>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="<?php echo esc_url(home_url('/')); ?>" 
                       class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <?php _e('Home', 'tmu-theme'); ?>
                    </a>
                    <a href="<?php echo esc_url(get_post_type_archive_link('movie')); ?>" 
                       class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        <?php _e('Movies', 'tmu-theme'); ?>
                    </a>
                    <?php if (post_type_exists('tv')): ?>
                        <a href="<?php echo esc_url(get_post_type_archive_link('tv')); ?>" 
                           class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            <?php _e('TV Shows', 'tmu-theme'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if (post_type_exists('drama') && get_option('tmu_dramas') === 'on'): ?>
                        <a href="<?php echo esc_url(get_post_type_archive_link('drama')); ?>" 
                           class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            <?php _e('Dramas', 'tmu-theme'); ?>
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(get_post_type_archive_link('people')); ?>" 
                       class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        <?php _e('People', 'tmu-theme'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Recent Content -->
            <div class="grid md:grid-cols-2 gap-8 mt-12">
                <!-- Recent Movies -->
                <div>
                    <h3 class="text-xl font-semibold mb-4"><?php _e('Recent Movies', 'tmu-theme'); ?></h3>
                    <?php
                    $recent_movies = new WP_Query([
                        'post_type' => 'movie',
                        'posts_per_page' => 4,
                        'post_status' => 'publish'
                    ]);
                    
                    if ($recent_movies->have_posts()):
                        ?>
                        <div class="space-y-3">
                            <?php while ($recent_movies->have_posts()): $recent_movies->the_post(); ?>
                                <div class="flex items-center space-x-3 p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                    <div class="w-12 h-16 flex-shrink-0">
                                        <?php if (has_post_thumbnail()): ?>
                                            <?php the_post_thumbnail('thumbnail', [
                                                'class' => 'w-full h-full object-cover rounded',
                                                'alt' => get_the_title()
                                            ]); ?>
                                        <?php else: ?>
                                            <div class="w-full h-full bg-gray-300 rounded flex items-center justify-center">
                                                <span class="text-gray-500 text-xs">🎬</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-medium text-gray-900 truncate">
                                            <a href="<?php echo esc_url(get_permalink()); ?>" class="hover:text-blue-600">
                                                <?php the_title(); ?>
                                            </a>
                                        </h4>
                                        <p class="text-sm text-gray-500">
                                            <?php 
                                            $movie_data = tmu_get_movie_data(get_the_ID());
                                            if (!empty($movie_data['release_date'])) {
                                                echo date('Y', strtotime($movie_data['release_date']));
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <?php
                        wp_reset_postdata();
                    endif;
                    ?>
                </div>
                
                <!-- Popular Genres -->
                <div>
                    <h3 class="text-xl font-semibold mb-4"><?php _e('Popular Genres', 'tmu-theme'); ?></h3>
                    <?php
                    $genres = get_terms([
                        'taxonomy' => 'genre',
                        'hide_empty' => true,
                        'number' => 8,
                        'orderby' => 'count',
                        'order' => 'DESC'
                    ]);
                    
                    if (!empty($genres) && !is_wp_error($genres)):
                        ?>
                        <div class="grid grid-cols-2 gap-2">
                            <?php foreach ($genres as $genre): ?>
                                <a href="<?php echo esc_url(get_term_link($genre)); ?>" 
                                   class="p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow text-center">
                                    <div class="font-medium text-gray-900"><?php echo esc_html($genre->name); ?></div>
                                    <div class="text-sm text-gray-500">
                                        <?php printf(_n('%d movie', '%d movies', $genre->count, 'tmu-theme'), $genre->count); ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.tmu-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}
</style>

<?php get_footer(); ?>