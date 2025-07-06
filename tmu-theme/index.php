<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
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
    <main id="main" class="site-main" role="main">
        
        <?php if (have_posts()) : ?>
            
            <header class="page-header">
                <?php
                the_archive_title('<h1 class="page-title">', '</h1>');
                the_archive_description('<div class="archive-description">', '</div>');
                ?>
            </header><!-- .page-header -->
            
            <div class="posts-container">
                <?php
                // Start the Loop
                while (have_posts()) :
                    the_post();
                    
                    /*
                     * Include the Post-Type-specific template for the content.
                     * If you want to override this in a child theme, then include a file
                     * called content-___.php (where ___ is the Post Type name) and that will be used instead.
                     */
                    get_template_part('template-parts/content', get_post_type());
                    
                endwhile;
                
                // Previous/next page navigation
                the_posts_navigation();
                ?>
            </div><!-- .posts-container -->
            
        <?php else : ?>
            
            <?php get_template_part('template-parts/content', 'none'); ?>
            
        <?php endif; ?>
        
    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_sidebar();
get_footer();