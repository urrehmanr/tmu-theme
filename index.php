<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 *
 * @package TMU
 * @version 1.0.0
 */

get_header(); ?>

<main id="primary" class="tmu-container py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php if (have_posts()) : ?>
            
            <?php if (is_home() && !is_front_page()) : ?>
                <header class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900"><?php single_post_title(); ?></h1>
                </header>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while (have_posts()) : the_post(); ?>
                    
                    <article id="post-<?php the_ID(); ?>" <?php post_class('tmu-card hover:shadow-medium transition-shadow'); ?>>
                        
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="aspect-poster">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('tmu-poster', [
                                        'class' => 'w-full h-full object-cover',
                                        'loading' => 'lazy'
                                    ]); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="p-4">
                            <h2 class="text-lg font-semibold mb-2">
                                <a href="<?php the_permalink(); ?>" class="text-gray-900 hover:text-tmu-primary-600 transition-colors">
                                    <?php the_title(); ?>
                                </a>
                            </h2>
                            
                            <?php if (has_excerpt()) : ?>
                                <p class="text-gray-600 text-sm mb-3"><?php the_excerpt(); ?></p>
                            <?php endif; ?>
                            
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <time datetime="<?php echo get_the_date('c'); ?>">
                                    <?php echo get_the_date(); ?>
                                </time>
                                
                                <?php if (tmu_is_tmu_page()) : ?>
                                    <?php
                                    $rating_data = tmu_get_rating_data(get_the_ID());
                                    if ($rating_data['average'] > 0) :
                                    ?>
                                        <div class="flex items-center">
                                            <svg class="w-3 h-3 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                            <span><?php echo number_format($rating_data['average'], 1); ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                    
                <?php endwhile; ?>
            </div>
            
            <?php the_posts_navigation([
                'prev_text' => __('Previous', 'tmu'),
                'next_text' => __('Next', 'tmu'),
                'class' => 'mt-8'
            ]); ?>
            
        <?php else : ?>
            
            <div class="text-center py-12">
                <h1 class="text-2xl font-bold text-gray-900 mb-4"><?php _e('Nothing here', 'tmu'); ?></h1>
                <p class="text-gray-600 mb-6"><?php _e('It looks like nothing was found at this location. Maybe try a search?', 'tmu'); ?></p>
                <?php get_search_form(); ?>
            </div>
            
        <?php endif; ?>
        
    </div>
</main>

<?php get_footer(); ?>