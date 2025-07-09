<?php
/**
 * Blocks Debug Admin Page
 * 
 * Debug page to check if blocks are properly loaded and registered
 * 
 * @package TMU\Admin
 * @since 1.0.0
 */

namespace TMU\Admin;

/**
 * BlocksDebug class
 */
class BlocksDebug {
    
    /**
     * Initialize debug page
     */
    public static function init(): void {
        add_action('admin_menu', [self::class, 'add_debug_page']);
    }
    
    /**
     * Add debug page to admin menu
     */
    public static function add_debug_page(): void {
        add_submenu_page(
            'tools.php',
            'TMU Blocks Debug',
            'TMU Blocks Debug',
            'manage_options',
            'tmu-blocks-debug',
            [self::class, 'render_debug_page']
        );
    }
    
    /**
     * Render debug page
     */
    public static function render_debug_page(): void {
        ?>
        <div class="wrap">
            <h1>TMU Blocks Debug</h1>
            
            <div class="card">
                <h2>Block Registration Status</h2>
                <?php self::check_block_registration(); ?>
            </div>
            
            <div class="card">
                <h2>Block Files Status</h2>
                <?php self::check_block_files(); ?>
            </div>
            
            <div class="card">
                <h2>Block Assets Status</h2>
                <?php self::check_block_assets(); ?>
            </div>
            
            <div class="card">
                <h2>WordPress Block Registry</h2>
                <?php self::check_wp_blocks(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Check if blocks are registered with TMU BlockRegistry
     */
    private static function check_block_registration(): void {
        try {
            $registry = \TMU\Blocks\BlockRegistry::getInstance();
            $blocks = $registry->get_blocks();
            
            echo '<p><strong>TMU BlockRegistry Status:</strong> ✅ Loaded</p>';
            echo '<p><strong>Registered Blocks:</strong></p>';
            echo '<ul>';
            foreach ($blocks as $name => $class) {
                $status = class_exists($class) ? '✅' : '❌';
                echo "<li>{$status} {$name} ({$class})</li>";
            }
            echo '</ul>';
            
        } catch (Exception $e) {
            echo '<p><strong>❌ BlockRegistry Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
        }
    }
    
    /**
     * Check if block files exist
     */
    private static function check_block_files(): void {
        $block_files = [
            'BaseBlock.php',
            'BlockRegistry.php',
            'MovieMetadataBlock.php',
            'TvSeriesMetadataBlock.php',
            'DramaMetadataBlock.php',
            'PeopleMetadataBlock.php',
            'TvEpisodeMetadataBlock.php',
            'DramaEpisodeMetadataBlock.php',
            'SeasonMetadataBlock.php',
            'VideoMetadataBlock.php',
            'TaxonomyImageBlock.php',
            'TaxonomyFaqsBlock.php',
            'BlogPostsListBlock.php',
            'TrendingContentBlock.php',
            'TmdbSyncBlock.php',
        ];
        
        $blocks_dir = TMU_INCLUDES_DIR . '/classes/Blocks/';
        
        echo '<ul>';
        foreach ($block_files as $file) {
            $file_path = $blocks_dir . $file;
            $status = file_exists($file_path) ? '✅' : '❌';
            echo "<li>{$status} {$file}</li>";
        }
        echo '</ul>';
    }
    
    /**
     * Check if block assets exist
     */
    private static function check_block_assets(): void {
        $asset_files = [
            'js/blocks-editor.js',
            'css/blocks-editor.css',
            'css/blocks.css',
            'js/blocks.js',
        ];
        
        $build_dir = TMU_THEME_DIR . '/assets/build/';
        
        echo '<ul>';
        foreach ($asset_files as $file) {
            $file_path = $build_dir . $file;
            $status = file_exists($file_path) ? '✅' : '❌';
            $url = TMU_THEME_URL . '/assets/build/' . $file;
            echo "<li>{$status} {$file} (<a href='{$url}' target='_blank'>View</a>)</li>";
        }
        echo '</ul>';
    }
    
    /**
     * Check WordPress block registry
     */
    private static function check_wp_blocks(): void {
        $registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
        $tmu_blocks = array_filter($registered_blocks, function($block_name) {
            return strpos($block_name, 'tmu/') === 0;
        }, ARRAY_FILTER_USE_KEY);
        
        echo '<p><strong>WordPress Registered TMU Blocks:</strong></p>';
        if (empty($tmu_blocks)) {
            echo '<p>❌ No TMU blocks found in WordPress registry</p>';
        } else {
            echo '<ul>';
            foreach ($tmu_blocks as $block_name => $block_type) {
                echo "<li>✅ {$block_name}</li>";
            }
            echo '</ul>';
        }
        
        echo '<p><strong>Block Categories:</strong></p>';
        $categories = get_default_block_categories();
        $tmu_category_found = false;
        foreach ($categories as $category) {
            if ($category['slug'] === 'tmu-blocks') {
                $tmu_category_found = true;
                break;
            }
        }
        echo $tmu_category_found ? '<p>✅ TMU Blocks category found</p>' : '<p>❌ TMU Blocks category not found</p>';
    }
}