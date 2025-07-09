<?php
/**
 * TMU Theme Bootstrap
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load the comprehensive bootstrap system
require_once get_template_directory() . '/includes/bootstrap.php';

/**
 * Register widget areas
 */
function tmu_widgets_init() {
    register_sidebar(
        array(
            'name'          => __('Sidebar', 'tmu'),
            'id'            => 'sidebar-1',
            'description'   => __('Add widgets here to appear in your sidebar.', 'tmu'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        )
    );
}
add_action('widgets_init', 'tmu_widgets_init');