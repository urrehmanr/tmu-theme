<?php
/**
 * TMU Block Registry
 *
 * @package TMU
 * @version 1.0.0
 */

namespace TMU\Blocks;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Block Registry Class
 * 
 * Manages registration and configuration of Gutenberg blocks
 */
class BlockRegistry {
    
    /**
     * Registry instance
     *
     * @var BlockRegistry
     */
    private static ?BlockRegistry $instance = null;
    
    /**
     * Registered blocks
     *
     * @var array
     */
    private array $blocks = [];
    
    /**
     * Get registry instance
     *
     * @return BlockRegistry
     */
    public static function getInstance(): BlockRegistry {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        // Will be expanded later
    }
    
    /**
     * Initialize block registry
     */
    public function init(): void {
        add_action('init', [$this, 'registerBlocks']);
        add_filter('block_categories_all', [$this, 'addBlockCategories'], 10, 2);
    }
    
    /**
     * Register blocks
     */
    public function registerBlocks(): void {
        // Block registration will be implemented later
        tmu_log("Block registry initialized");
    }
    
    /**
     * Add custom block categories
     */
    public function addBlockCategories($categories, $post): array {
        $tmu_categories = tmu_config('block_categories') ?? [];
        
        foreach ($tmu_categories as $category) {
            $categories[] = [
                'slug' => $category['slug'],
                'title' => $category['title'],
                'icon' => $category['icon'] ?? null
            ];
        }
        
        return $categories;
    }
}