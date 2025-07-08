    </main><!-- #main -->

    <footer id="colophon" class="site-footer bg-tmu-dark text-white">
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Site Info -->
                <div class="footer-info">
                    <h3 class="text-xl font-semibold mb-4"><?php bloginfo('name'); ?></h3>
                    <?php
                    $description = get_bloginfo('description', 'display');
                    if ($description) :
                    ?>
                        <p class="text-gray-300 mb-4"><?php echo $description; ?></p>
                    <?php endif; ?>
                    
                    <p class="text-sm text-gray-400">
                        <?php printf(
                            esc_html__('© %1$s %2$s. All rights reserved.', 'tmu'),
                            date('Y'),
                            get_bloginfo('name')
                        ); ?>
                    </p>
                </div>

                <!-- Navigation Links -->
                <div class="footer-navigation">
                    <h4 class="text-lg font-medium mb-4"><?php esc_html_e('Quick Links', 'tmu'); ?></h4>
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'footer',
                        'menu_id'        => 'footer-menu',
                        'container'      => false,
                        'menu_class'     => 'space-y-2 text-sm',
                        'fallback_cb'    => false,
                    ]);
                    ?>
                </div>

                <!-- Contact/Social -->
                <div class="footer-contact">
                    <h4 class="text-lg font-medium mb-4"><?php esc_html_e('Connect', 'tmu'); ?></h4>
                    <div class="text-sm text-gray-300 space-y-2">
                        <?php if (get_theme_mod('contact_email')) : ?>
                            <p>
                                <strong><?php esc_html_e('Email:', 'tmu'); ?></strong>
                                <a href="mailto:<?php echo esc_attr(get_theme_mod('contact_email')); ?>" 
                                   class="text-tmu-yellow hover:text-white transition-colors">
                                    <?php echo esc_html(get_theme_mod('contact_email')); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        
                        <div class="social-links flex space-x-4 mt-4">
                            <?php if (get_theme_mod('social_facebook')) : ?>
                                <a href="<?php echo esc_url(get_theme_mod('social_facebook')); ?>" 
                                   class="text-gray-400 hover:text-white transition-colors"
                                   target="_blank" rel="noopener">
                                    <span class="sr-only"><?php esc_html_e('Facebook', 'tmu'); ?></span>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (get_theme_mod('social_twitter')) : ?>
                                <a href="<?php echo esc_url(get_theme_mod('social_twitter')); ?>" 
                                   class="text-gray-400 hover:text-white transition-colors"
                                   target="_blank" rel="noopener">
                                    <span class="sr-only"><?php esc_html_e('Twitter', 'tmu'); ?></span>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (get_theme_mod('social_instagram')) : ?>
                                <a href="<?php echo esc_url(get_theme_mod('social_instagram')); ?>" 
                                   class="text-gray-400 hover:text-white transition-colors"
                                   target="_blank" rel="noopener">
                                    <span class="sr-only"><?php esc_html_e('Instagram', 'tmu'); ?></span>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987 6.62 0 11.987-5.367 11.987-11.987C24.014 5.367 18.637.001 12.017.001zM8.449 16.988c-1.297 0-2.348-1.051-2.348-2.348s1.051-2.348 2.348-2.348 2.348 1.051 2.348 2.348-1.051 2.348-2.348 2.348zm7.718 0c-1.297 0-2.348-1.051-2.348-2.348s1.051-2.348 2.348-2.348 2.348 1.051 2.348 2.348-1.051 2.348-2.348 2.348z"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bottom Bar -->
            <div class="border-t border-gray-700 mt-8 pt-6 text-center">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center text-sm text-gray-400">
                    <p>
                        <?php printf(
                            esc_html__('Powered by %1$s and %2$s theme.', 'tmu'),
                            '<a href="https://wordpress.org/" class="text-tmu-yellow hover:text-white transition-colors">WordPress</a>',
                            '<a href="#" class="text-tmu-yellow hover:text-white transition-colors">TMU</a>'
                        ); ?>
                    </p>
                    
                    <div class="mt-2 md:mt-0">
                        <a href="<?php echo esc_url(get_privacy_policy_url()); ?>" 
                           class="text-gray-400 hover:text-white transition-colors mr-4">
                            <?php esc_html_e('Privacy Policy', 'tmu'); ?>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <?php esc_html_e('Terms of Service', 'tmu'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer><!-- #colophon -->

</div><!-- #page -->

<script>
// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !isExpanded);
            mobileMenu.classList.toggle('hidden');
        });
    }
});
</script>

<?php wp_footer(); ?>

</body>
</html>