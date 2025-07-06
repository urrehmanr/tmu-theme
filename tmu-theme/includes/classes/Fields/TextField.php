<?php
/**
 * Text Field
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
 * Text Field Class
 */
class TextField extends AbstractField {
    
    /**
     * Input type
     *
     * @var string
     */
    protected $input_type = 'text';
    
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
        
        $this->input_type = $args['input_type'] ?? 'text';
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
        
        $attributes['type'] = $this->input_type;
        $attributes['value'] = esc_attr($value);
        
        if ($this->placeholder) {
            $attributes['placeholder'] = $this->placeholder;
        }
        
        if ($this->maxlength > 0) {
            $attributes['maxlength'] = $this->maxlength;
        }
        
        $html = $this->renderWrapperStart();
        $html .= $this->renderLabel();
        $html .= sprintf('<input%s />', $this->renderAttributes($attributes));
        $html .= $this->renderDescription();
        $html .= $this->renderWrapperEnd();
        
        return $html;
    }
}