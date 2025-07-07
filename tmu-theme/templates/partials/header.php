<?php
/**
 * Header partial template
 * 
 * @package TMU
 */
?>

<header class="bg-gradient-to-r from-gray-900 to-black text-white shadow-lg" role="banner">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <!-- Logo / Site Title -->
            <div class="flex-shrink-0">
                <?php if (has_custom_logo()): ?>
                    <div class="h-8 w-auto">
                        <?php the_custom_logo(); ?>
                    </div>
                <?php else: ?>
                    <h1 class="text-2xl font-bold">
                        <a href="<?php echo esc_url(home_url('/')); ?>" 
                           class="text-white hover:text-yellow-400 transition-colors duration-200" 
                           rel="home">
                            <?php bloginfo('name'); ?>
                        </a>
                    </h1>
                    <?php if (get_bloginfo('description')): ?>
                        <p class="text-sm text-gray-300 mt-1">
                            <?php bloginfo('description'); ?>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Main Navigation -->
            <nav class="hidden md:block" role="navigation" aria-label="Main navigation">
                <?php
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'menu_class' => 'flex space-x-6',
                    'container' => false,
                    'link_class' => 'text-white hover:text-yellow-400 transition-colors duration-200 font-medium',
                    'fallback_cb' => 'tmu_fallback_menu'
                ]);
                ?>
            </nav>
            
            <!-- Search and Actions -->
            <div class="flex items-center space-x-4">
                <!-- Search Form -->
                <div class="hidden lg:block">
                    <?php get_template_part('templates/partials/search-form'); ?>
                </div>
                
                <!-- User Menu (if logged in) -->
                <?php if (is_user_logged_in()): ?>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="flex items-center text-white hover:text-yellow-400 transition-colors duration-200">
                            <span class="text-sm font-medium mr-2">
                                <?php echo wp_get_current_user()->display_name; ?>
                            </span>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                        
                        <div x-show="open" 
                             @click.away="open = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            
                            <a href="<?php echo admin_url(); ?>" 
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Dashboard
                            </a>
                            <a href="<?php echo wp_logout_url(home_url()); ?>" 
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo wp_login_url(); ?>" 
                       class="text-white hover:text-yellow-400 transition-colors duration-200 text-sm font-medium">
                        Login
                    </a>
                <?php endif; ?>
                
                <!-- Mobile Menu Toggle -->
                <button class="md:hidden text-white hover:text-yellow-400 transition-colors duration-200" 
                        aria-label="Toggle mobile menu"
                        x-data 
                        @click="$store.mobileMenu.toggle()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Mobile Navigation -->
        <div class="md:hidden" 
             x-data 
             x-show="$store.mobileMenu.isOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-1"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-1">
            
            <div class="px-2 pt-2 pb-3 space-y-1 border-t border-gray-700">
                <?php
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'menu_class' => 'space-y-1',
                    'container' => false,
                    'link_class' => 'block px-3 py-2 text-white hover:text-yellow-400 hover:bg-gray-800 rounded-md transition-colors duration-200',
                    'fallback_cb' => 'tmu_fallback_menu'
                ]);
                ?>
                
                <!-- Mobile Search -->
                <div class="px-3 py-2">
                    <?php get_template_part('templates/partials/search-form'); ?>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Breadcrumbs -->
<?php if (!is_front_page()): ?>
    <nav class="bg-gray-100 border-b" aria-label="Breadcrumb">
        <div class="container mx-auto px-4 py-3">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="<?php echo home_url(); ?>" 
                       class="text-gray-600 hover:text-gray-900 transition-colors duration-200">
                        Home
                    </a>
                </li>
                
                <?php if (is_singular()): ?>
                    <?php $post_type_object = get_post_type_object(get_post_type()); ?>
                    <?php if ($post_type_object && $post_type_object->has_archive): ?>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <a href="<?php echo get_post_type_archive_link(get_post_type()); ?>" 
                               class="text-gray-600 hover:text-gray-900 transition-colors duration-200">
                                <?php echo $post_type_object->labels->name; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-900 font-medium">
                            <?php the_title(); ?>
                        </span>
                    </li>
                    
                <?php elseif (is_post_type_archive()): ?>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-900 font-medium">
                            <?php post_type_archive_title(); ?>
                        </span>
                    </li>
                    
                <?php elseif (is_tax()): ?>
                    <?php $term = get_queried_object(); ?>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-900 font-medium">
                            <?php echo $term->name; ?>
                        </span>
                    </li>
                    
                <?php elseif (is_search()): ?>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-900 font-medium">
                            Search Results for "<?php echo get_search_query(); ?>"
                        </span>
                    </li>
                <?php endif; ?>
            </ol>
        </div>
    </nav>
<?php endif; ?>

<script>
// Initialize mobile menu store
document.addEventListener('alpine:init', () => {
    Alpine.store('mobileMenu', {
        isOpen: false,
        toggle() {
            this.isOpen = !this.isOpen;
        },
        close() {
            this.isOpen = false;
        }
    });
});
</script>