<?php
/**
 * Number Field
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
 * Number Field Class
 */
class NumberField extends AbstractField {
    
    /**
     * Minimum value
     *
     * @var float
     */
    protected $min = null;
    
    /**
     * Maximum value
     *
     * @var float
     */
    protected $max = null;
    
    /**
     * Step value
     *
     * @var float
     */
    protected $step = 1;
    
    /**
     * Parse field arguments
     *
     * @param array $args Field arguments
     */
    protected function parseArgs(array $args): void {
        parent::parseArgs($args);
        
        $this->min = isset($args['min']) ? floatval($args['min']) : null;
        $this->max = isset($args['max']) ? floatval($args['max']) : null;
        $this->step = floatval($args['step'] ?? 1);
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
        
        $attributes['type'] = 'number';
        $attributes['value'] = esc_attr($value);
        $attributes['step'] = $this->step;
        
        if ($this->min !== null) {
            $attributes['min'] = $this->min;
        }
        
        if ($this->max !== null) {
            $attributes['max'] = $this->max;
        }
        
        $html = $this->renderWrapperStart();
        $html .= $this->renderLabel();
        $html .= sprintf('<input%s />', $this->renderAttributes($attributes));
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
        if ($this->step < 1) {
            return floatval($value);
        }
        
        return intval($value);
    }
}