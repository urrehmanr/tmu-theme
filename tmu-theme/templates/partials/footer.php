<?php
/**
 * Footer partial template
 * 
 * @package TMU
 */
?>

<footer class="bg-gray-900 text-white mt-auto" role="contentinfo">
    <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            
            <!-- About Section -->
            <div class="col-span-1 lg:col-span-2">
                <h3 class="text-xl font-bold mb-4"><?php bloginfo('name'); ?></h3>
                <p class="text-gray-300 mb-4 leading-relaxed">
                    <?php
                    $description = get_bloginfo('description');
                    if ($description) {
                        echo esc_html($description);
                    } else {
                        echo 'Your ultimate destination for movie and TV show information. Discover new content, read reviews, and keep track of your watchlist.';
                    }
                    ?>
                </p>
                
                <!-- Social Media Links -->
                <div class="flex space-x-4">
                    <?php if (get_theme_mod('tmu_social_facebook')): ?>
                        <a href="<?php echo esc_url(get_theme_mod('tmu_social_facebook')); ?>" 
                           class="text-gray-400 hover:text-white transition-colors duration-200"
                           target="_blank" 
                           rel="noopener noreferrer"
                           aria-label="Follow us on Facebook">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (get_theme_mod('tmu_social_twitter')): ?>
                        <a href="<?php echo esc_url(get_theme_mod('tmu_social_twitter')); ?>" 
                           class="text-gray-400 hover:text-white transition-colors duration-200"
                           target="_blank" 
                           rel="noopener noreferrer"
                           aria-label="Follow us on Twitter">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (get_theme_mod('tmu_social_instagram')): ?>
                        <a href="<?php echo esc_url(get_theme_mod('tmu_social_instagram')); ?>" 
                           class="text-gray-400 hover:text-white transition-colors duration-200"
                           target="_blank" 
                           rel="noopener noreferrer"
                           aria-label="Follow us on Instagram">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.624 5.367 11.99 11.988 11.99s11.982-5.366 11.982-11.99C23.999 5.367 18.641.001 12.017.001zm5.568 16.729c-.814.813-1.982 1.316-3.255 1.316H9.672c-1.273 0-2.441-.503-3.255-1.316C5.604 15.916 5.1 14.748 5.1 13.475V10.51c0-1.273.504-2.441 1.317-3.255.814-.813 1.982-1.316 3.255-1.316h4.658c1.273 0 2.441.503 3.255 1.316.813.814 1.316 1.982 1.316 3.255v2.965c0 1.273-.503 2.441-1.316 3.254z"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (get_theme_mod('tmu_social_youtube')): ?>
                        <a href="<?php echo esc_url(get_theme_mod('tmu_social_youtube')); ?>" 
                           class="text-gray-400 hover:text-white transition-colors duration-200"
                           target="_blank" 
                           rel="noopener noreferrer"
                           aria-label="Subscribe to our YouTube channel">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div>
                <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                <ul class="space-y-2">
                    <li>
                        <a href="<?php echo get_post_type_archive_link('movie'); ?>" 
                           class="text-gray-300 hover:text-white transition-colors duration-200">
                            Movies
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo get_post_type_archive_link('tv'); ?>" 
                           class="text-gray-300 hover:text-white transition-colors duration-200">
                            TV Shows
                        </a>
                    </li>
                    <?php if (get_option('tmu_dramas') === 'on'): ?>
                        <li>
                            <a href="<?php echo get_post_type_archive_link('drama'); ?>" 
                               class="text-gray-300 hover:text-white transition-colors duration-200">
                                Dramas
                            </a>
                        </li>
                    <?php endif; ?>
                    <li>
                        <a href="<?php echo get_post_type_archive_link('people'); ?>" 
                           class="text-gray-300 hover:text-white transition-colors duration-200">
                            People
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Categories -->
            <div>
                <h4 class="text-lg font-semibold mb-4">Categories</h4>
                <ul class="space-y-2">
                    <?php
                    $genres = get_terms([
                        'taxonomy' => 'genre',
                        'number' => 6,
                        'hide_empty' => true,
                        'orderby' => 'count',
                        'order' => 'DESC'
                    ]);
                    
                    if ($genres && !is_wp_error($genres)):
                        foreach ($genres as $genre):
                            ?>
                            <li>
                                <a href="<?php echo get_term_link($genre); ?>" 
                                   class="text-gray-300 hover:text-white transition-colors duration-200">
                                    <?php echo esc_html($genre->name); ?>
                                </a>
                            </li>
                            <?php
                        endforeach;
                    endif;
                    ?>
                </ul>
            </div>
        </div>
        
        <!-- Bottom Bar -->
        <div class="border-t border-gray-800 mt-8 pt-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="text-gray-400 text-sm mb-4 md:mb-0">
                    <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.</p>
                </div>
                
                <!-- Footer Menu -->
                <nav class="flex flex-wrap justify-center md:justify-end space-x-6 text-sm">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'footer',
                        'menu_class' => 'flex flex-wrap space-x-6',
                        'container' => false,
                        'link_class' => 'text-gray-400 hover:text-white transition-colors duration-200',
                        'fallback_cb' => false
                    ]);
                    ?>
                    
                    <?php if (!wp_nav_menu(['theme_location' => 'footer', 'echo' => false])): ?>
                        <a href="<?php echo home_url('/privacy-policy'); ?>" 
                           class="text-gray-400 hover:text-white transition-colors duration-200">
                            Privacy Policy
                        </a>
                        <a href="<?php echo home_url('/terms-of-service'); ?>" 
                           class="text-gray-400 hover:text-white transition-colors duration-200">
                            Terms of Service
                        </a>
                        <a href="<?php echo home_url('/contact'); ?>" 
                           class="text-gray-400 hover:text-white transition-colors duration-200">
                            Contact
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
            
            <!-- TMDB Attribution -->
            <div class="mt-4 text-center">
                <p class="text-xs text-gray-500">
                    Movie and TV show data provided by 
                    <a href="https://www.themoviedb.org" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="text-blue-400 hover:text-blue-300 transition-colors duration-200">
                        The Movie Database (TMDB)
                    </a>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Back to Top Button -->
    <button id="back-to-top" 
            class="fixed bottom-8 right-8 bg-yellow-500 hover:bg-yellow-600 text-white p-3 rounded-full shadow-lg transition-all duration-200 opacity-0 invisible z-50"
            aria-label="Back to top">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
        </svg>
    </button>
</footer>

<script>
// Back to top functionality
document.addEventListener('DOMContentLoaded', function() {
    const backToTopButton = document.getElementById('back-to-top');
    
    // Show/hide button based on scroll position
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopButton.classList.remove('opacity-0', 'invisible');
            backToTopButton.classList.add('opacity-100', 'visible');
        } else {
            backToTopButton.classList.remove('opacity-100', 'visible');
            backToTopButton.classList.add('opacity-0', 'invisible');
        }
    });
    
    // Smooth scroll to top
    backToTopButton.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});
</script>