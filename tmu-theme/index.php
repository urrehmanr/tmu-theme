<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * 
 * This is a placeholder template for Step 01. Full templates will be created in Step 10.
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        
        <div class="container mx-auto px-4 py-8">
            <div class="text-center">
                <h1 class="text-4xl font-bold text-tmu-dark mb-4">
                    <?php _e('TMU Theme', 'tmu'); ?>
                </h1>
                <p class="text-lg text-gray-600 mb-8">
                    <?php _e('Welcome to the TMU WordPress Theme - A modern movie and TV database system.', 'tmu'); ?>
                </p>
                
                <?php if (have_posts()) : ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php while (have_posts()) : the_post(); ?>
                            <article id="post-<?php the_ID(); ?>" <?php post_class('card'); ?>>
                                <div class="card-header">
                                    <h2 class="text-xl font-semibold">
                                        <a href="<?php the_permalink(); ?>" class="text-tmu-primary hover:text-tmu-secondary">
                                            <?php the_title(); ?>
                                        </a>
                                    </h2>
                                </div>
                                <div class="card-body">
                                    <?php
                                    if (has_post_thumbnail()) {
                                        the_post_thumbnail('medium', ['class' => 'w-full h-48 object-cover rounded mb-4']);
                                    }
                                    ?>
                                    <?php the_excerpt(); ?>
                                    <a href="<?php the_permalink(); ?>" class="btn btn-primary mt-4">
                                        <?php _e('Read More', 'tmu'); ?>
                                    </a>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>
                    
                    <?php
                    // Pagination
                    the_posts_pagination([
                        'mid_size' => 2,
                        'prev_text' => __('Previous', 'tmu'),
                        'next_text' => __('Next', 'tmu'),
                        'class' => 'pagination'
                    ]);
                    ?>
                    
                <?php else : ?>
                    <div class="text-center py-12">
                        <h2 class="text-2xl font-semibold text-gray-600 mb-4">
                            <?php _e('No content found', 'tmu'); ?>
                        </h2>
                        <p class="text-gray-500">
                            <?php _e('It looks like nothing was found at this location. Maybe try a search?', 'tmu'); ?>
                        </p>
                        <?php get_search_form(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_sidebar();
get_footer();