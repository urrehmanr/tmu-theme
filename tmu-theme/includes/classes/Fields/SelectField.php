<?php
/**
 * Select Field
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
 * Select Field Class
 */
class SelectField extends AbstractField {
    
    /**
     * Field options
     *
     * @var array
     */
    protected $options = [];
    
    /**
     * Allow multiple selections
     *
     * @var bool
     */
    protected $multiple = false;
    
    /**
     * AJAX options callback
     *
     * @var callable
     */
    protected $ajax_callback = null;
    
    /**
     * Parse field arguments
     *
     * @param array $args Field arguments
     */
    protected function parseArgs(array $args): void {
        parent::parseArgs($args);
        
        $this->options = $args['options'] ?? [];
        $this->multiple = $args['multiple'] ?? false;
        $this->ajax_callback = $args['ajax_callback'] ?? null;
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
        
        if ($this->multiple) {
            $attributes['multiple'] = true;
            $attributes['name'] = $name . '[]';
            
            if (!is_array($value)) {
                $value = $value ? [$value] : [];
            }
        }
        
        $html = $this->renderWrapperStart();
        $html .= $this->renderLabel();
        $html .= sprintf('<select%s>', $this->renderAttributes($attributes));
        
        // Empty option
        if (!$this->multiple) {
            $html .= '<option value="">' . __('Select an option...', 'tmu') . '</option>';
        }
        
        // Render options
        foreach ($this->getOptions() as $option_value => $option_label) {
            $selected = $this->multiple 
                ? (in_array($option_value, $value) ? 'selected' : '')
                : selected($value, $option_value, false);
            
            $html .= sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($option_value),
                $selected,
                esc_html($option_label)
            );
        }
        
        $html .= '</select>';
        $html .= $this->renderDescription();
        $html .= $this->renderWrapperEnd();
        
        return $html;
    }
    
    /**
     * Get field options
     *
     * @return array
     */
    protected function getOptions(): array {
        if ($this->ajax_callback && is_callable($this->ajax_callback)) {
            return call_user_func($this->ajax_callback);
        }
        
        return $this->options;
    }
    
    /**
     * Get default sanitization for field type
     *
     * @param mixed $value Field value
     * @return mixed
     */
    protected function getDefaultSanitization($value) {
        if ($this->multiple) {
            return is_array($value) ? array_map('sanitize_text_field', $value) : [];
        }
        
        return sanitize_text_field($value);
    }
}