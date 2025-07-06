<?php
/**
 * Template for displaying a single movie
 *
 * @package TMU
 * @version 1.0.0
 */

get_header(); ?>

<main id="primary" class="tmu-container py-8">
    <?php while (have_posts()) : the_post(); ?>
        
        <?php
        $movie_data = tmu_get_movie_data(get_the_ID());
        $rating_data = tmu_get_rating_data(get_the_ID());
        ?>
        
        <article id="post-<?php the_ID(); ?>" <?php post_class('movie-single max-w-6xl mx-auto'); ?>>
            
            <!-- Movie Header -->
            <header class="movie-header mb-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Movie Poster -->
                    <div class="movie-poster">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="aspect-poster rounded-lg overflow-hidden shadow-large">
                                <?php the_post_thumbnail('tmu-poster', [
                                    'class' => 'w-full h-full object-cover',
                                    'loading' => 'eager'
                                ]); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Movie Information -->
                    <div class="movie-info lg:col-span-2">
                        <h1 class="movie-title text-4xl font-bold text-gray-900 mb-4"><?php the_title(); ?></h1>
                        
                        <?php if (!empty($movie_data['original_title']) && $movie_data['original_title'] !== get_the_title()) : ?>
                            <p class="original-title text-xl text-gray-600 mb-4 italic">
                                <?php echo esc_html($movie_data['original_title']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie_data['tagline'])) : ?>
                            <p class="tagline text-lg text-gray-700 mb-6 font-medium">
                                "<?php echo esc_html($movie_data['tagline']); ?>"
                            </p>
                        <?php endif; ?>
                        
                        <!-- Movie Meta -->
                        <div class="movie-meta grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            
                            <?php if (!empty($movie_data['release_date'])) : ?>
                                <div class="meta-item">
                                    <span class="meta-label font-semibold text-gray-700"><?php _e('Release Date:', 'tmu'); ?></span>
                                    <span class="meta-value text-gray-600"><?php echo esc_html($movie_data['release_date']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($movie_data['runtime'])) : ?>
                                <div class="meta-item">
                                    <span class="meta-label font-semibold text-gray-700"><?php _e('Runtime:', 'tmu'); ?></span>
                                    <span class="meta-value text-gray-600"><?php echo tmu_format_runtime($movie_data['runtime']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php $genres = tmu_get_linked_terms(get_the_ID(), 'genre'); ?>
                            <?php if (!empty($genres)) : ?>
                                <div class="meta-item">
                                    <span class="meta-label font-semibold text-gray-700"><?php _e('Genres:', 'tmu'); ?></span>
                                    <span class="meta-value"><?php echo $genres; ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($movie_data['certification'])) : ?>
                                <div class="meta-item">
                                    <span class="meta-label font-semibold text-gray-700"><?php _e('Rating:', 'tmu'); ?></span>
                                    <span class="meta-value">
                                        <span class="inline-block px-2 py-1 bg-gray-100 text-gray-800 text-sm font-medium rounded">
                                            <?php echo esc_html($movie_data['certification']); ?>
                                        </span>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <?php $countries = tmu_get_linked_terms(get_the_ID(), 'country'); ?>
                            <?php if (!empty($countries)) : ?>
                                <div class="meta-item">
                                    <span class="meta-label font-semibold text-gray-700"><?php _e('Country:', 'tmu'); ?></span>
                                    <span class="meta-value"><?php echo $countries; ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php $languages = tmu_get_linked_terms(get_the_ID(), 'language'); ?>
                            <?php if (!empty($languages)) : ?>
                                <div class="meta-item">
                                    <span class="meta-label font-semibold text-gray-700"><?php _e('Language:', 'tmu'); ?></span>
                                    <span class="meta-value"><?php echo $languages; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Rating -->
                        <?php if ($rating_data['average'] > 0) : ?>
                            <div class="movie-rating mb-6">
                                <div class="flex items-center space-x-4">
                                    <div class="rating-display">
                                        <?php echo tmu_render_star_rating($rating_data['average']); ?>
                                    </div>
                                    <div class="rating-info text-sm text-gray-600">
                                        <?php
                                        printf(
                                            __('%1$s/10 based on %2$s votes', 'tmu'),
                                            number_format($rating_data['average'], 1),
                                            number_format($rating_data['count'])
                                        );
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Actions -->
                        <div class="movie-actions flex flex-wrap gap-3">
                            <button class="tmu-btn tmu-btn-primary">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                                <?php _e('Add to Favorites', 'tmu'); ?>
                            </button>
                            
                            <button class="tmu-btn tmu-btn-secondary">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                                </svg>
                                <?php _e('Watchlist', 'tmu'); ?>
                            </button>
                            
                            <button class="tmu-btn tmu-btn-secondary">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                                </svg>
                                <?php _e('Share', 'tmu'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Movie Content -->
            <div class="movie-content grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Main Content -->
                <div class="main-content lg:col-span-2">
                    
                    <!-- Overview -->
                    <?php if (get_the_content()) : ?>
                        <section class="movie-overview mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 mb-4"><?php _e('Overview', 'tmu'); ?></h2>
                            <div class="prose max-w-none">
                                <?php the_content(); ?>
                            </div>
                        </section>
                    <?php endif; ?>
                    
                    <!-- Cast -->
                    <?php if (!empty($movie_data['star_cast'])) : ?>
                        <section class="movie-cast mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 mb-4"><?php _e('Cast', 'tmu'); ?></h2>
                            <div class="cast-grid grid grid-cols-2 md:grid-cols-4 gap-4">
                                <?php foreach (array_slice($movie_data['star_cast'], 0, 8) as $cast_member) : ?>
                                    <div class="cast-member text-center">
                                        <div class="cast-photo aspect-square bg-gray-200 rounded-lg mb-2 overflow-hidden">
                                            <?php if (!empty($cast_member['profile_path'])) : ?>
                                                <img src="<?php echo tmu_get_tmdb_image_url($cast_member['profile_path'], 'w185', 'profile'); ?>" 
                                                     alt="<?php echo esc_attr($cast_member['name']); ?>"
                                                     class="w-full h-full object-cover"
                                                     loading="lazy">
                                            <?php endif; ?>
                                        </div>
                                        <p class="cast-name font-medium text-sm"><?php echo esc_html($cast_member['name']); ?></p>
                                        <p class="cast-character text-xs text-gray-600"><?php echo esc_html($cast_member['character']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                    
                    <!-- Comments -->
                    <?php if (comments_open() || get_comments_number()) : ?>
                        <section class="movie-comments">
                            <?php comments_template(); ?>
                        </section>
                    <?php endif; ?>
                </div>
                
                <!-- Sidebar -->
                <div class="movie-sidebar">
                    
                    <!-- Additional Info -->
                    <div class="tmu-card p-6 mb-6">
                        <h3 class="text-lg font-semibold mb-4"><?php _e('Details', 'tmu'); ?></h3>
                        <dl class="space-y-3">
                            
                            <?php if (!empty($movie_data['budget']) && $movie_data['budget'] > 0) : ?>
                                <div>
                                    <dt class="text-sm font-medium text-gray-700"><?php _e('Budget', 'tmu'); ?></dt>
                                    <dd class="text-sm text-gray-600">$<?php echo number_format($movie_data['budget']); ?></dd>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($movie_data['revenue']) && $movie_data['revenue'] > 0) : ?>
                                <div>
                                    <dt class="text-sm font-medium text-gray-700"><?php _e('Box Office', 'tmu'); ?></dt>
                                    <dd class="text-sm text-gray-600">$<?php echo number_format($movie_data['revenue']); ?></dd>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($movie_data['production_house'])) : ?>
                                <div>
                                    <dt class="text-sm font-medium text-gray-700"><?php _e('Production', 'tmu'); ?></dt>
                                    <dd class="text-sm text-gray-600"><?php echo esc_html($movie_data['production_house']); ?></dd>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($movie_data['tmdb_id'])) : ?>
                                <div>
                                    <dt class="text-sm font-medium text-gray-700"><?php _e('TMDB ID', 'tmu'); ?></dt>
                                    <dd class="text-sm text-gray-600">
                                        <a href="https://www.themoviedb.org/movie/<?php echo $movie_data['tmdb_id']; ?>" 
                                           target="_blank" 
                                           rel="noopener"
                                           class="text-tmu-primary-600 hover:text-tmu-primary-700">
                                            <?php echo $movie_data['tmdb_id']; ?>
                                        </a>
                                    </dd>
                                </div>
                            <?php endif; ?>
                        </dl>
                    </div>
                    
                    <!-- Social Sharing -->
                    <div class="tmu-card p-6">
                        <h3 class="text-lg font-semibold mb-4"><?php _e('Share', 'tmu'); ?></h3>
                        <?php echo tmu_generate_social_sharing(get_permalink(), get_the_title()); ?>
                    </div>
                </div>
            </div>
        </article>
        
    <?php endwhile; ?>
</main>

<?php get_footer(); ?>