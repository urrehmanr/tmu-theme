<?php
/**
 * Textarea Field
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
 * Textarea Field Class
 */
class TextareaField extends AbstractField {
    
    /**
     * Number of rows
     *
     * @var int
     */
    protected $rows = 5;
    
    /**
     * Number of columns
     *
     * @var int
     */
    protected $cols = 50;
    
    /**
     * Placeholder text
     *
     * @var string
     */
    protected $placeholder = '';
    
    /**
     * Maximum length
     *
     * @var int
     */
    protected $maxlength = 0;
    
    /**
     * Parse field arguments
     *
     * @param array $args Field arguments
     */
    protected function parseArgs(array $args): void {
        parent::parseArgs($args);
        
        $this->rows = intval($args['rows'] ?? 5);
        $this->cols = intval($args['cols'] ?? 50);
        $this->placeholder = $args['placeholder'] ?? '';
        $this->maxlength = intval($args['maxlength'] ?? 0);
    }
    
    /**
     * Render field HTML
     *
     * @param mixed $value Field value
     * @param string $name Field name
     * @return string
     */
    public function render($value, string $name): string {
        $attributes = $this->getInputAttributes($name);
        
        $attributes['rows'] = $this->rows;
        $attributes['cols'] = $this->cols;
        
        if ($this->placeholder) {
            $attributes['placeholder'] = $this->placeholder;
        }
        
        if ($this->maxlength > 0) {
            $attributes['maxlength'] = $this->maxlength;
        }
        
        $html = $this->renderWrapperStart();
        $html .= $this->renderLabel();
        $html .= sprintf('<textarea%s>%s</textarea>', $this->renderAttributes($attributes), esc_textarea($value));
        
        // Character counter
        if ($this->maxlength > 0) {
            $html .= sprintf(
                '<div class="tmu-char-counter"><span class="current">%d</span>/<span class="max">%d</span></div>',
                strlen($value),
                $this->maxlength
            );
        }
        
        $html .= $this->renderDescription();
        $html .= $this->renderWrapperEnd();
        
        return $html;
    }
    
    /**
     * Get default sanitization for field type
     *
     * @param mixed $value Field value
     * @return mixed
     */
    protected function getDefaultSanitization($value) {
        return sanitize_textarea_field($value);
    }
}