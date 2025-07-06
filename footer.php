    <footer id="colophon" class="site-footer bg-gray-900 text-white mt-auto">
        <div class="footer-widgets py-12 border-b border-gray-800">
            <div class="tmu-container">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    
                    <!-- About Section -->
                    <div class="footer-widget">
                        <h3 class="text-lg font-semibold mb-4"><?php _e('About TMU', 'tmu'); ?></h3>
                        <p class="text-gray-300 text-sm leading-relaxed mb-4">
                            <?php _e('Your ultimate destination for movies, TV shows, and entertainment content. Discover, rate, and explore the world of cinema.', 'tmu'); ?>
                        </p>
                        
                        <!-- Social Media Links -->
                        <div class="social-links flex space-x-3">
                            <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="<?php _e('Facebook', 'tmu'); ?>">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </a>
                            <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="<?php _e('Twitter', 'tmu'); ?>">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                </svg>
                            </a>
                            <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="<?php _e('Instagram', 'tmu'); ?>">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.618 5.367 11.986 11.988 11.986 6.618 0 11.986-5.368 11.986-11.986C24.003 5.367 18.635.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.611-3.197-1.559-.374-.473-.594-1.07-.594-1.718 0-1.297.61-2.448 1.559-3.197.473-.374 1.07-.594 1.718-.594 1.297 0 2.448.61 3.197 1.559.374.473.594 1.07.594 1.718 0 1.297-.61 2.448-1.559 3.197-.473.374-1.07.594-1.718.594zm7.138 0c-1.297 0-2.448-.611-3.197-1.559-.374-.473-.594-1.07-.594-1.718 0-1.297.61-2.448 1.559-3.197.473-.374 1.07-.594 1.718-.594 1.297 0 2.448.61 3.197 1.559.374.473.594 1.07.594 1.718 0 1.297-.61 2.448-1.559 3.197-.473.374-1.07.594-1.718.594z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="footer-widget">
                        <h3 class="text-lg font-semibold mb-4"><?php _e('Quick Links', 'tmu'); ?></h3>
                        <?php
                        wp_nav_menu([
                            'theme_location' => 'footer',
                            'menu_id'        => 'footer-menu',
                            'container'      => false,
                            'menu_class'     => 'footer-nav space-y-2',
                            'link_before'    => '<span class="text-gray-300 hover:text-white transition-colors text-sm">',
                            'link_after'     => '</span>',
                            'fallback_cb'    => false,
                        ]);
                        ?>
                    </div>
                    
                    <!-- Categories -->
                    <div class="footer-widget">
                        <h3 class="text-lg font-semibold mb-4"><?php _e('Categories', 'tmu'); ?></h3>
                        <ul class="space-y-2">
                            <?php if (get_option('tmu_movies') === 'on') : ?>
                                <li><a href="<?php echo get_post_type_archive_link('movie'); ?>" class="text-gray-300 hover:text-white transition-colors text-sm"><?php _e('Movies', 'tmu'); ?></a></li>
                            <?php endif; ?>
                            <?php if (get_option('tmu_tv_series') === 'on') : ?>
                                <li><a href="<?php echo get_post_type_archive_link('tv'); ?>" class="text-gray-300 hover:text-white transition-colors text-sm"><?php _e('TV Shows', 'tmu'); ?></a></li>
                            <?php endif; ?>
                            <?php if (get_option('tmu_dramas') === 'on') : ?>
                                <li><a href="<?php echo get_post_type_archive_link('drama'); ?>" class="text-gray-300 hover:text-white transition-colors text-sm"><?php _e('Dramas', 'tmu'); ?></a></li>
                            <?php endif; ?>
                            <?php if (get_option('tmu_people') === 'on') : ?>
                                <li><a href="<?php echo get_post_type_archive_link('people'); ?>" class="text-gray-300 hover:text-white transition-colors text-sm"><?php _e('People', 'tmu'); ?></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <!-- Newsletter -->
                    <div class="footer-widget">
                        <h3 class="text-lg font-semibold mb-4"><?php _e('Stay Updated', 'tmu'); ?></h3>
                        <p class="text-gray-300 text-sm mb-4">
                            <?php _e('Subscribe to get the latest updates on new releases and features.', 'tmu'); ?>
                        </p>
                        <form class="newsletter-form" action="#" method="post">
                            <div class="flex">
                                <input type="email" 
                                       placeholder="<?php _e('Your email address', 'tmu'); ?>" 
                                       class="flex-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-l-md text-white text-sm focus:outline-none focus:ring-2 focus:ring-tmu-primary-500 focus:border-transparent"
                                       required>
                                <button type="submit" 
                                        class="px-4 py-2 bg-tmu-primary-600 hover:bg-tmu-primary-700 rounded-r-md text-white text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-tmu-primary-500 focus:ring-offset-2 focus:ring-offset-gray-900">
                                    <?php _e('Subscribe', 'tmu'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom py-6">
            <div class="tmu-container">
                <div class="flex flex-col md:flex-row items-center justify-between">
                    <div class="site-info text-gray-400 text-sm mb-4 md:mb-0">
                        <p>
                            &copy; <?php echo date('Y'); ?> 
                            <a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-white transition-colors">
                                <?php bloginfo('name'); ?>
                            </a>. 
                            <?php _e('All rights reserved.', 'tmu'); ?>
                        </p>
                    </div>
                    
                    <div class="footer-links flex items-center space-x-6 text-sm">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors"><?php _e('Privacy Policy', 'tmu'); ?></a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors"><?php _e('Terms of Service', 'tmu'); ?></a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors"><?php _e('Contact', 'tmu'); ?></a>
                    </div>
                </div>
                
                <!-- Theme Credits -->
                <div class="theme-credits text-center mt-4 pt-4 border-t border-gray-800">
                    <p class="text-gray-500 text-xs">
                        <?php
                        printf(
                            __('Powered by %1$s and %2$s', 'tmu'),
                            '<a href="https://wordpress.org/" class="hover:text-gray-400 transition-colors">WordPress</a>',
                            '<span class="text-tmu-primary-400">TMU Theme</span>'
                        );
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </footer>
</div><!-- #page -->

<!-- Back to Top Button -->
<button id="back-to-top" 
        class="fixed bottom-6 right-6 w-12 h-12 bg-tmu-primary-600 hover:bg-tmu-primary-700 text-white rounded-full shadow-lg transition-all duration-300 opacity-0 invisible z-50"
        aria-label="<?php _e('Back to top', 'tmu'); ?>"
        x-data="{ show: false }"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-2"
        @scroll.window="show = (window.pageYOffset > 100)"
        @click="window.scrollTo({ top: 0, behavior: 'smooth' })">
    <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
    </svg>
</button>

<?php wp_footer(); ?>
</body>
</html>