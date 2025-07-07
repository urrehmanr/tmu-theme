<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1a1a1a">
    
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    
    <div class="tmu-site-wrapper min-h-screen flex flex-col">
        <?php get_template_part('templates/partials/header'); ?>
        
        <main class="tmu-main-content flex-1" role="main">
            <?php echo $content; ?>
        </main>
        
        <?php get_template_part('templates/partials/footer'); ?>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>