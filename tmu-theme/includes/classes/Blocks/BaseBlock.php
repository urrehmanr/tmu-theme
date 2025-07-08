<?php
/**
 * Abstract Base Block Class
 * 
 * Provides shared functionality for all TMU Gutenberg blocks.
 * This class establishes the common interface and properties
 * that all block implementations must follow.
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

namespace TMU\Blocks;

/**
 * Abstract BaseBlock class
 * 
 * All TMU blocks extend this class to inherit common functionality
 * and maintain consistency across the block system.
 */
abstract class BaseBlock {
    
    /**
     * Block name (without namespace)
     * @var string
     */
    protected $name;
    
    /**
     * Block display title
     * @var string
     */
    protected $title;
    
    /**
     * Block description
     * @var string
     */
    protected $description;
    
    /**
     * Block category
     * @var string
     */
    protected $category = 'tmu-blocks';
    
    /**
     * Block icon (Dashicon name or SVG)
     * @var string
     */
    protected $icon = 'admin-post';
    
    /**
     * Block supports configuration
     * @var array
     */
    protected $supports = [
        'html' => false,
        'multiple' => false,
        'reusable' => false,
    ];
    
    /**
     * Block keywords for search
     * @var array
     */
    protected $keywords = [];
    
    /**
     * Post types where this block is allowed
     * @var array
     */
    protected $post_types = [];
    
    /**
     * Whether block is only for specific post types
     * @var bool
     */
    protected $post_type_restricted = true;
    
    /**
     * Abstract method to get block attributes
     * Must be implemented by each block
     * 
     * @return array Block attributes configuration
     */
    abstract public static function get_attributes(): array;
    
    /**
     * Abstract method to render block content
     * Must be implemented by each block
     * 
     * @param array $attributes Block attributes
     * @param string $content Block content
     * @return string Rendered HTML
     */
    abstract public static function render($attributes, $content): string;
    
    /**
     * Get complete block configuration
     * 
     * @return array Block configuration array
     */
    public function get_block_config(): array {
        return [
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'icon' => $this->icon,
            'supports' => $this->supports,
            'keywords' => $this->keywords,
            'post_types' => $this->post_types,
            'attributes' => static::get_attributes(),
        ];
    }
    
    /**
     * Get block name with namespace
     * 
     * @return string Namespaced block name
     */
    public function get_block_name(): string {
        return 'tmu/' . $this->name;
    }
    
    /**
     * Check if block is allowed on current post type
     * 
     * @param string $post_type Current post type
     * @return bool Whether block is allowed
     */
    public function is_allowed_post_type($post_type): bool {
        if (!$this->post_type_restricted) {
            return true;
        }
        
        return in_array($post_type, $this->post_types);
    }
    
    /**
     * Validate block attributes
     * 
     * @param array $attributes Attributes to validate
     * @return array Validated attributes
     */
    public static function validate_attributes($attributes): array {
        $schema = static::get_attributes();
        $validated = [];
        
        foreach ($schema as $key => $config) {
            if (isset($attributes[$key])) {
                $validated[$key] = static::sanitize_attribute($attributes[$key], $config);
            } else {
                $validated[$key] = $config['default'] ?? null;
            }
        }
        
        return $validated;
    }
    
    /**
     * Sanitize individual attribute based on type
     * 
     * @param mixed $value Attribute value
     * @param array $config Attribute configuration
     * @return mixed Sanitized value
     */
    protected static function sanitize_attribute($value, $config) {
        $type = $config['type'] ?? 'string';
        
        switch ($type) {
            case 'string':
                return sanitize_text_field($value);
            case 'number':
                return is_numeric($value) ? (float) $value : 0;
            case 'integer':
                return (int) $value;
            case 'boolean':
                return (bool) $value;
            case 'array':
                return is_array($value) ? $value : [];
            case 'object':
                return is_array($value) || is_object($value) ? (array) $value : [];
            default:
                return $value;
        }
    }
    
    /**
     * Get default attribute values
     * 
     * @return array Default attributes
     */
    public static function get_default_attributes(): array {
        $attributes = static::get_attributes();
        $defaults = [];
        
        foreach ($attributes as $key => $config) {
            $defaults[$key] = $config['default'] ?? null;
        }
        
        return $defaults;
    }
    
    /**
     * Common utility to format date attributes
     * 
     * @param string $date Date string
     * @param string $format Output format
     * @return string Formatted date
     */
    protected static function format_date($date, $format = 'Y-m-d'): string {
        if (empty($date)) {
            return '';
        }
        
        $timestamp = strtotime($date);
        return $timestamp ? date($format, $timestamp) : '';
    }
    
    /**
     * Common utility to format runtime
     * 
     * @param int $minutes Runtime in minutes
     * @return string Formatted runtime (e.g., "2h 30m")
     */
    protected static function format_runtime($minutes): string {
        if (!$minutes || !is_numeric($minutes)) {
            return '';
        }
        
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($hours > 0) {
            return $hours . 'h' . ($mins > 0 ? ' ' . $mins . 'm' : '');
        }
        
        return $mins . 'm';
    }
    
    /**
     * Common utility to format currency
     * 
     * @param int $amount Amount in dollars
     * @return string Formatted currency
     */
    protected static function format_currency($amount): string {
        if (!$amount || !is_numeric($amount)) {
            return '';
        }
        
        return '$' . number_format($amount);
    }
    
    /**
     * Common utility to get TMDB image URL
     * 
     * @param string $path TMDB image path
     * @param string $size Image size (w200, w500, original, etc.)
     * @return string Complete image URL
     */
    protected static function get_tmdb_image_url($path, $size = 'w500'): string {
        if (empty($path)) {
            return '';
        }
        
        return 'https://image.tmdb.org/t/p/' . $size . $path;
    }
    
    /**
     * Common utility to truncate text
     * 
     * @param string $text Text to truncate
     * @param int $length Maximum length
     * @param string $suffix Suffix to append
     * @return string Truncated text
     */
    protected static function truncate_text($text, $length = 150, $suffix = '...'): string {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . $suffix;
    }
    
    /**
     * Get block editor script handle
     * 
     * @return string Script handle
     */
    public function get_editor_script_handle(): string {
        return 'tmu-blocks-editor';
    }
    
    /**
     * Get block frontend script handle
     * 
     * @return string Script handle
     */
    public function get_script_handle(): string {
        return 'tmu-blocks';
    }
    
    /**
     * Get block editor style handle
     * 
     * @return string Style handle
     */
    public function get_editor_style_handle(): string {
        return 'tmu-blocks-editor';
    }
    
    /**
     * Get block frontend style handle
     * 
     * @return string Style handle
     */
    public function get_style_handle(): string {
        return 'tmu-blocks';
    }

    /**
     * Register the block with WordPress
     */
    public function register(): void {
        if (!function_exists('register_block_type')) {
            return;
        }
        
        // Check if block is enabled in theme settings
        if (!$this->is_block_enabled()) {
            return;
        }
        
        register_block_type($this->get_block_name(), [
            'editor_script' => $this->get_editor_script_handle(),
            'editor_style' => $this->get_editor_style_handle(),
            'style' => $this->get_style_handle(),
            'render_callback' => [static::class, 'render'],
            'attributes' => static::get_attributes(),
            'supports' => $this->supports,
        ]);
    }
    
    /**
     * Check if block is enabled in theme settings
     * 
     * @return bool Whether block is enabled
     */
    protected function is_block_enabled(): bool {
        $option_name = 'tmu_block_' . str_replace('-', '_', $this->name) . '_enabled';
        return get_option($option_name, true);
    }
}