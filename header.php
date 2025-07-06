<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <?php wp_head(); ?>
    
    <script>
        document.documentElement.classList.remove('no-js');
        document.documentElement.classList.add('js');
    </script>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site min-h-screen flex flex-col">
    <a class="skip-link screen-reader-text sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:bg-white focus:p-2 focus:z-50" href="#primary">
        <?php _e('Skip to content', 'tmu'); ?>
    </a>

    <header id="masthead" class="site-header bg-white shadow-sm sticky top-0 z-40" x-data="{ mobileMenuOpen: false }">
        <div class="tmu-container">
            <div class="flex items-center justify-between h-16">
                
                <!-- Site Branding -->
                <div class="site-branding flex-shrink-0">
                    <?php if (has_custom_logo()) : ?>
                        <div class="custom-logo-link">
                            <?php the_custom_logo(); ?>
                        </div>
                    <?php else : ?>
                        <h1 class="site-title text-xl font-bold">
                            <a href="<?php echo esc_url(home_url('/')); ?>" class="text-tmu-primary-600 hover:text-tmu-primary-700 transition-colors">
                                <?php bloginfo('name'); ?>
                            </a>
                        </h1>
                        <?php 
                        $description = get_bloginfo('description', 'display');
                        if ($description || is_customize_preview()) : ?>
                            <p class="site-description text-sm text-gray-600"><?php echo $description; ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Primary Navigation -->
                <nav id="site-navigation" class="main-navigation hidden md:flex items-center space-x-8">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'primary',
                        'menu_id'        => 'primary-menu',
                        'container'      => false,
                        'menu_class'     => 'flex items-center space-x-6',
                        'link_before'    => '<span class="text-gray-700 hover:text-tmu-primary-600 transition-colors">',
                        'link_after'     => '</span>',
                        'fallback_cb'    => false,
                    ]);
                    ?>
                    
                    <!-- Search Form -->
                    <div class="search-form-wrapper">
                        <form role="search" method="get" class="search-form relative" action="<?php echo esc_url(home_url('/')); ?>">
                            <label class="sr-only" for="search-field-header"><?php _e('Search for:', 'tmu'); ?></label>
                            <input type="search" 
                                   id="search-field-header"
                                   class="search-field w-48 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-tmu-primary-500 focus:border-transparent" 
                                   placeholder="<?php _e('Search movies, TV shows...', 'tmu'); ?>" 
                                   value="<?php echo get_search_query(); ?>" 
                                   name="s" />
                            <button type="submit" class="search-submit absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-tmu-primary-600">
                                <span class="sr-only"><?php _e('Search', 'tmu'); ?></span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </nav>

                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button type="button" 
                            class="mobile-menu-button p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-tmu-primary-500"
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            :aria-expanded="mobileMenuOpen">
                        <span class="sr-only"><?php _e('Open main menu', 'tmu'); ?></span>
                        <svg class="w-6 h-6" x-show="!mobileMenuOpen" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg class="w-6 h-6" x-show="mobileMenuOpen" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div class="md:hidden" x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95">
            <div class="px-2 pt-2 pb-3 space-y-1 bg-white border-t border-gray-200">
                <?php
                wp_nav_menu([
                    'theme_location' => 'mobile',
                    'menu_id'        => 'mobile-menu',
                    'container'      => false,
                    'menu_class'     => 'mobile-nav-menu',
                    'link_before'    => '<span class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-tmu-primary-600 hover:bg-gray-50 rounded-md transition-colors">',
                    'link_after'     => '</span>',
                    'fallback_cb'    => 'wp_page_menu',
                ]);
                ?>
                
                <!-- Mobile Search -->
                <div class="px-3 py-2">
                    <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
                        <div class="relative">
                            <input type="search" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-tmu-primary-500 focus:border-transparent" 
                                   placeholder="<?php _e('Search...', 'tmu'); ?>" 
                                   value="<?php echo get_search_query(); ?>" 
                                   name="s" />
                            <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <?php
    // Display breadcrumbs on TMU pages
    if (tmu_is_tmu_page() && !is_home() && !is_front_page()) :
        $breadcrumbs = tmu_get_breadcrumbs();
        if (!empty($breadcrumbs)) :
    ?>
        <nav class="breadcrumbs bg-gray-50 py-3" aria-label="<?php _e('Breadcrumb', 'tmu'); ?>">
            <div class="tmu-container">
                <ol class="flex items-center space-x-2 text-sm">
                    <?php foreach ($breadcrumbs as $index => $breadcrumb) : ?>
                        <li class="flex items-center">
                            <?php if ($index > 0) : ?>
                                <svg class="w-4 h-4 text-gray-400 mx-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            <?php endif; ?>
                            
                            <?php if ($breadcrumb['current']) : ?>
                                <span class="text-gray-500" aria-current="page"><?php echo esc_html($breadcrumb['title']); ?></span>
                            <?php else : ?>
                                <a href="<?php echo esc_url($breadcrumb['url']); ?>" class="text-tmu-primary-600 hover:text-tmu-primary-700 transition-colors">
                                    <?php echo esc_html($breadcrumb['title']); ?>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </nav>
    <?php endif; endif; ?>
</div>

<?php wp_footer(); ?>
</body>
</html>