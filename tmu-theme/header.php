<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site min-h-screen flex flex-col bg-gray-50">
    <a class="skip-link screen-reader-text sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:px-4 focus:py-2 focus:bg-tmu-primary focus:text-white focus:rounded" href="#main">
        <?php esc_html_e('Skip to content', 'tmu'); ?>
    </a>

    <header id="masthead" class="site-header bg-white shadow-md sticky top-0 z-40">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Site Branding -->
                <div class="site-branding flex items-center">
                    <?php if (has_custom_logo()) : ?>
                        <div class="custom-logo">
                            <?php the_custom_logo(); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (is_front_page() && is_home()) : ?>
                        <h1 class="site-title text-2xl font-bold text-tmu-dark ml-3">
                            <a href="<?php echo esc_url(home_url('/')); ?>" rel="home" class="text-tmu-primary hover:text-tmu-secondary">
                                <?php bloginfo('name'); ?>
                            </a>
                        </h1>
                    <?php else : ?>
                        <p class="site-title text-xl font-semibold text-tmu-dark ml-3">
                            <a href="<?php echo esc_url(home_url('/')); ?>" rel="home" class="text-tmu-primary hover:text-tmu-secondary">
                                <?php bloginfo('name'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                    
                    <?php
                    $description = get_bloginfo('description', 'display');
                    if ($description || is_customize_preview()) :
                    ?>
                        <p class="site-description text-sm text-gray-600 ml-2 hidden md:block">
                            <?php echo $description; ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Navigation -->
                <nav id="site-navigation" class="main-navigation hidden lg:block">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'primary',
                        'menu_id'        => 'primary-menu',
                        'container'      => false,
                        'menu_class'     => 'flex space-x-6',
                        'fallback_cb'    => false,
                    ]);
                    ?>
                </nav>

                <!-- Search & Mobile Menu -->
                <div class="header-actions flex items-center space-x-4">
                    <!-- Search Form -->
                    <div class="search-form hidden md:block">
                        <form role="search" method="get" class="search-form flex" action="<?php echo esc_url(home_url('/')); ?>">
                            <label class="sr-only" for="search-field"><?php esc_html_e('Search', 'tmu'); ?></label>
                            <input type="search" 
                                   id="search-field" 
                                   class="search-field px-3 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-tmu-primary focus:border-transparent" 
                                   placeholder="<?php esc_attr_e('Search movies, shows...', 'tmu'); ?>" 
                                   value="<?php echo get_search_query(); ?>" 
                                   name="s" />
                            <button type="submit" 
                                    class="search-submit px-4 py-2 bg-tmu-primary text-white rounded-r-lg hover:bg-tmu-secondary transition-colors">
                                <span class="sr-only"><?php esc_html_e('Search', 'tmu'); ?></span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button>
                        </form>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button id="mobile-menu-toggle" 
                            class="mobile-menu-toggle lg:hidden p-2 text-gray-600 hover:text-tmu-primary focus:outline-none focus:ring-2 focus:ring-tmu-primary"
                            aria-controls="mobile-menu" 
                            aria-expanded="false">
                        <span class="sr-only"><?php esc_html_e('Toggle navigation', 'tmu'); ?></span>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <div id="mobile-menu" class="mobile-menu lg:hidden hidden">
                <div class="border-t border-gray-200 pt-4 pb-4">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'mobile',
                        'menu_id'        => 'mobile-menu',
                        'container'      => false,
                        'menu_class'     => 'space-y-2',
                        'fallback_cb'    => false,
                    ]);
                    ?>
                    
                    <!-- Mobile Search -->
                    <div class="mt-4 md:hidden">
                        <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
                            <div class="flex">
                                <input type="search" 
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-tmu-primary" 
                                       placeholder="<?php esc_attr_e('Search...', 'tmu'); ?>" 
                                       value="<?php echo get_search_query(); ?>" 
                                       name="s" />
                                <button type="submit" 
                                        class="px-4 py-2 bg-tmu-primary text-white rounded-r-lg hover:bg-tmu-secondary">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main id="main" class="site-main flex-1">