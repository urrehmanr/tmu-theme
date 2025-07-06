<?php
/**
 * Image Field
 *
 * @package TMU\Fields
 * @version 1.0.0
 */

namespace TMU\Fields;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Image Field Class
 */
class ImageField extends AbstractField {
    
    /**
     * Return attachment ID instead of URL
     *
     * @var bool
     */
    protected $return_id = false;
    
    /**
     * Preview size
     *
     * @var string
     */
    protected $preview_size = 'medium';
    
    /**
     * Allow URL input
     *
     * @var bool
     */
    protected $allow_url = true;
    
    /**
     * Parse field arguments
     *
     * @param array $args Field arguments
     */
    protected function parseArgs(array $args): void {
        parent::parseArgs($args);
        
        $this->return_id = $args['return_id'] ?? false;
        $this->preview_size = $args['preview_size'] ?? 'medium';
        $this->allow_url = $args['allow_url'] ?? true;
    }
    
    /**
     * Render field HTML
     *
     * @param mixed $value Field value
     * @param string $name Field name
     * @return string
     */
    public function render($value, string $name): string {
        $html = $this->renderWrapperStart();
        $html .= $this->renderLabel();
        
        // Image container
        $html .= '<div class="tmu-image-field-container">';
        
        // Preview area
        $html .= '<div class="tmu-image-preview">';
        if ($value) {
            $image_url = $this->getImageUrl($value);
            if ($image_url) {
                $html .= sprintf(
                    '<img src="%s" alt="" style="max-width: 200px; max-height: 200px;" />',
                    esc_url($image_url)
                );
            }
        }
        $html .= '</div>';
        
        // Hidden input for value
        $html .= sprintf(
            '<input type="hidden" name="%s" id="%s" value="%s" />',
            esc_attr($name),
            esc_attr($this->id),
            esc_attr($value)
        );
        
        // Buttons
        $html .= '<div class="tmu-image-actions">';
        
        // Upload/Select button
        $html .= sprintf(
            '<button type="button" class="button tmu-image-upload" data-field-id="%s" data-return-id="%s">%s</button>',
            esc_attr($this->id),
            $this->return_id ? '1' : '0',
            $value ? __('Change Image', 'tmu') : __('Select Image', 'tmu')
        );
        
        // Remove button
        if ($value) {
            $html .= sprintf(
                '<button type="button" class="button tmu-image-remove" data-field-id="%s">%s</button>',
                esc_attr($this->id),
                __('Remove', 'tmu')
            );
        }
        
        $html .= '</div>';
        
        // URL input (if allowed)
        if ($this->allow_url) {
            $html .= '<div class="tmu-image-url-input">';
            $html .= '<label for="' . esc_attr($this->id) . '_url">' . __('Or enter URL:', 'tmu') . '</label>';
            $html .= sprintf(
                '<input type="url" id="%s_url" class="regular-text tmu-image-url" data-field-id="%s" placeholder="%s" />',
                esc_attr($this->id),
                esc_attr($this->id),
                __('https://example.com/image.jpg', 'tmu')
            );
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        $html .= $this->renderDescription();
        $html .= $this->renderWrapperEnd();
        
        return $html;
    }
    
    /**
     * Get image URL from value
     *
     * @param mixed $value Field value
     * @return string
     */
    private function getImageUrl($value): string {
        if (empty($value)) {
            return '';
        }
        
        // If it's already a URL
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        
        // If it's an attachment ID
        if (is_numeric($value)) {
            $image_url = wp_get_attachment_image_url($value, $this->preview_size);
            return $image_url ?: '';
        }
        
        return '';
    }
    
    /**
     * Get default sanitization for field type
     *
     * @param mixed $value Field value
     * @return mixed
     */
    protected function getDefaultSanitization($value) {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return esc_url_raw($value);
        }
        
        return intval($value);
    }
}