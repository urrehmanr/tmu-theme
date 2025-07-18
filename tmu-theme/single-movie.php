<?php
/**
 * Movie Single Template
 * 
 * Template for displaying single movie posts
 * 
 * @package TMU
 * @since 1.0.0
 */

get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        $movie_data = tmu_get_movie_data(get_the_ID());
        $cast_crew = tmu_get_cast_crew(get_the_ID());
        $related_movies = tmu_get_related_posts(get_the_ID(), 'movie', 6);
        $trailer_url = tmu_get_trailer_url(get_the_ID());
        ?>
        
        <div class="tmu-movie-single">
            <!-- Breadcrumb Navigation -->
            <div class="tmu-container">
                <?php echo tmu_render_breadcrumbs(get_the_ID()); ?>
            </div>
            
            <!-- Movie Hero Section -->
            <section class="tmu-movie-hero relative overflow-hidden">
                <?php if (!empty($movie_data['backdrop_path'])): ?>
                    <div class="tmu-hero-backdrop absolute inset-0">
                        <img src="<?php echo esc_url(tmu_get_image_url($movie_data['backdrop_path'], 'w1280')); ?>" 
                             alt="<?php echo esc_attr(get_the_title()); ?>" 
                             class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/50 to-transparent"></div>
                    </div>
                <?php endif; ?>
                
                <div class="tmu-container relative z-10">
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 py-12">
                        <!-- Movie Poster -->
                        <div class="lg:col-span-1">
                            <div class="tmu-movie-poster max-w-sm mx-auto lg:mx-0">
                                <?php if (has_post_thumbnail()): ?>
                                    <?php the_post_thumbnail('large', [
                                        'class' => 'w-full h-auto rounded-lg shadow-2xl',
                                        'alt' => get_the_title()
                                    ]); ?>
                                <?php else: ?>
                                    <div class="w-full aspect-[2/3] bg-gray-300 rounded-lg flex items-center justify-center">
                                        <span class="text-gray-500 text-4xl">🎬</span>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Action Buttons -->
                                <div class="mt-6 space-y-3">
                                    <?php if ($trailer_url): ?>
                                        <a href="<?php echo esc_url($trailer_url); ?>" 
                                           target="_blank"
                                           class="w-full bg-red-600 hover:bg-red-700 text-white py-3 px-6 rounded-lg font-semibold text-center flex items-center justify-center transition-colors">
                                            <span class="mr-2">▶</span>
                                            <?php _e('Watch Trailer', 'tmu-theme'); ?>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button class="w-full bg-gray-800 hover:bg-gray-700 text-white py-3 px-6 rounded-lg font-semibold transition-colors">
                                        <span class="mr-2">+</span>
                                        <?php _e('Add to Watchlist', 'tmu-theme'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Movie Info -->
                        <div class="lg:col-span-3 text-white">
                            <h1 class="text-4xl lg:text-5xl font-bold mb-4"><?php the_title(); ?></h1>
                            
                            <?php if (!empty($movie_data['tagline'])): ?>
                                <p class="text-xl text-gray-300 mb-6 italic"><?php echo esc_html($movie_data['tagline']); ?></p>
                            <?php endif; ?>
                            
                            <!-- Movie Meta Info -->
                            <div class="flex flex-wrap items-center gap-6 mb-6 text-sm">
                                <?php if (!empty($movie_data['vote_average'])): ?>
                                    <div class="flex items-center space-x-2">
                                        <div class="flex items-center">
                                            <?php echo tmu_render_rating((float) $movie_data['vote_average']); ?>
                                        </div>
                                        <span class="text-yellow-400 font-semibold">
                                            <?php echo number_format((float) $movie_data['vote_average'], 1); ?>/10
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($movie_data['runtime'])): ?>
                                    <div class="flex items-center space-x-1">
                                        <span class="text-gray-400">⏱</span>
                                        <span><?php echo tmu_format_runtime((int) $movie_data['runtime']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($movie_data['release_date'])): ?>
                                    <div class="flex items-center space-x-1">
                                        <span class="text-gray-400">📅</span>
                                        <span><?php echo date('Y', strtotime($movie_data['release_date'])); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($movie_data['status'])): ?>
                                    <div>
                                        <?php echo tmu_get_status_badge($movie_data['status']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div>
                                    <span class="px-2 py-1 bg-gray-700 rounded text-xs font-medium">
                                        <?php echo tmu_get_age_rating(get_the_ID()); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Genres -->
                            <?php $genres = tmu_get_genre_links(get_the_ID()); ?>
                            <?php if (!empty($genres)): ?>
                                <div class="mb-6">
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($genres as $genre): ?>
                                            <a href="<?php echo esc_url($genre['url']); ?>" 
                                               class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded-full text-sm font-medium transition-colors">
                                                <?php echo esc_html($genre['name']); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Overview -->
                            <?php if (!empty($movie_data['overview'])): ?>
                                <div class="mb-8">
                                    <h3 class="text-xl font-semibold mb-3"><?php _e('Overview', 'tmu-theme'); ?></h3>
                                    <p class="text-gray-300 leading-relaxed"><?php echo esc_html($movie_data['overview']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Quick Stats -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                                <?php if (!empty($movie_data['budget'])): ?>
                                    <div>
                                        <div class="text-2xl font-bold text-green-400"><?php echo tmu_format_currency((int) $movie_data['budget']); ?></div>
                                        <div class="text-sm text-gray-400"><?php _e('Budget', 'tmu-theme'); ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($movie_data['revenue'])): ?>
                                    <div>
                                        <div class="text-2xl font-bold text-green-400"><?php echo tmu_format_currency((int) $movie_data['revenue']); ?></div>
                                        <div class="text-sm text-gray-400"><?php _e('Revenue', 'tmu-theme'); ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($movie_data['vote_count'])): ?>
                                    <div>
                                        <div class="text-2xl font-bold text-blue-400"><?php echo number_format((int) $movie_data['vote_count']); ?></div>
                                        <div class="text-sm text-gray-400"><?php _e('Votes', 'tmu-theme'); ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($movie_data['popularity'])): ?>
                                    <div>
                                        <div class="text-2xl font-bold text-purple-400"><?php echo number_format((float) $movie_data['popularity'], 1); ?></div>
                                        <div class="text-sm text-gray-400"><?php _e('Popularity', 'tmu-theme'); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Movie Content Tabs -->
            <section class="tmu-movie-content bg-gray-100 py-12">
                <div class="tmu-container">
                    <!-- Tab Navigation -->
                    <div class="border-b border-gray-300 mb-8">
                        <nav class="flex space-x-8">
                            <button class="tmu-tab-button active pb-4 px-1 border-b-2 border-blue-600 text-blue-600 font-medium transition-colors" 
                                    data-tab="details">
                                <?php _e('Details', 'tmu-theme'); ?>
                            </button>
                            <button class="tmu-tab-button pb-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium transition-colors" 
                                    data-tab="cast">
                                <?php _e('Cast & Crew', 'tmu-theme'); ?>
                            </button>
                            <?php if (!empty($related_movies)): ?>
                                <button class="tmu-tab-button pb-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium transition-colors" 
                                        data-tab="similar">
                                    <?php _e('Similar Movies', 'tmu-theme'); ?>
                                </button>
                            <?php endif; ?>
                        </nav>
                    </div>
                    
                    <!-- Tab Content -->
                    <div class="tmu-tab-content">
                        <!-- Details Tab -->
                        <div class="tmu-tab-pane active" id="details">
                            <?php get_template_part('templates/movie/details', null, ['movie_data' => $movie_data]); ?>
                        </div>
                        
                        <!-- Cast & Crew Tab -->
                        <div class="tmu-tab-pane hidden" id="cast">
                            <?php get_template_part('templates/movie/cast', null, ['cast_crew' => $cast_crew]); ?>
                        </div>
                        
                        <!-- Similar Movies Tab -->
                        <?php if (!empty($related_movies)): ?>
                            <div class="tmu-tab-pane hidden" id="similar">
                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                                    <?php foreach ($related_movies as $related_movie): ?>
                                        <?php 
                                        get_template_part('templates/components/movie-card', null, [
                                            'movie_data' => tmu_get_movie_data($related_movie->ID),
                                            'post_id' => $related_movie->ID,
                                            'size' => 'small'
                                        ]);
                                        ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
        
        <?php
    endwhile;
endif;

get_footer();
?>