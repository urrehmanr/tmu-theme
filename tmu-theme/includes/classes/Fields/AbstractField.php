<?php
/**
 * Abstract Field Base Class
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
 * Abstract Field Class
 * 
 * Base class for all custom field implementations
 */
abstract class AbstractField {
    
    /**
     * Field ID
     *
     * @var string
     */
    protected $id = '';
    
    /**
     * Field title
     *
     * @var string
     */
    protected $title = '';
    
    /**
     * Field type
     *
     * @var string
     */
    protected $type = 'text';
    
    /**
     * Field description
     *
     * @var string
     */
    protected $description = '';
    
    /**
     * Field default value
     *
     * @var mixed
     */
    protected $default = '';
    
    /**
     * Field validation rules
     *
     * @var array
     */
    protected $validation = [];
    
    /**
     * Field sanitization callback
     *
     * @var callable
     */
    protected $sanitize_callback = null;
    
    /**
     * Field CSS classes
     *
     * @var array
     */
    protected $css_classes = [];
    
    /**
     * Field attributes
     *
     * @var array
     */
    protected $attributes = [];
    
    /**
     * Field dependency rules
     *
     * @var array
     */
    protected $dependency = [];
    
    /**
     * Whether field is required
     *
     * @var bool
     */
    protected $required = false;
    
    /**
     * Constructor
     *
     * @param string $id Field ID
     * @param array $args Field arguments
     */
    public function __construct(string $id, array $args = []) {
        $this->id = $id;
        $this->parseArgs($args);
        $this->init();
    }
    
    /**
     * Parse field arguments
     *
     * @param array $args Field arguments
     */
    protected function parseArgs(array $args): void {
        $defaults = [
            'title' => '',
            'type' => 'text',
            'description' => '',
            'default' => '',
            'validation' => [],
            'sanitize_callback' => null,
            'css_classes' => [],
            'attributes' => [],
            'dependency' => [],
            'required' => false,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        foreach ($args as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * Initialize field
     */
    protected function init(): void {
        // Override in child classes for specific initialization
    }
    
    /**
     * Render field HTML
     *
     * @param mixed $value Field value
     * @param string $name Field name
     * @return string
     */
    abstract public function render($value, string $name): string;
    
    /**
     * Validate field value
     *
     * @param mixed $value Field value
     * @return bool|WP_Error
     */
    public function validate($value) {
        // Check required
        if ($this->required && empty($value)) {
            return new \WP_Error('required', sprintf(__('Field "%s" is required.', 'tmu'), $this->title));
        }
        
        // Apply validation rules
        foreach ($this->validation as $rule => $params) {
            $result = $this->applyValidationRule($rule, $value, $params);
            if (is_wp_error($result)) {
                return $result;
            }
        }
        
        return true;
    }
    
    /**
     * Apply validation rule
     *
     * @param string $rule Validation rule
     * @param mixed $value Field value
     * @param mixed $params Rule parameters
     * @return bool|WP_Error
     */
    protected function applyValidationRule(string $rule, $value, $params) {
        switch ($rule) {
            case 'minlength':
                if (strlen($value) < $params) {
                    return new \WP_Error('minlength', sprintf(__('Field "%s" must be at least %d characters long.', 'tmu'), $this->title, $params));
                }
                break;
                
            case 'maxlength':
                if (strlen($value) > $params) {
                    return new \WP_Error('maxlength', sprintf(__('Field "%s" must not exceed %d characters.', 'tmu'), $this->title, $params));
                }
                break;
                
            case 'min':
                if (is_numeric($value) && $value < $params) {
                    return new \WP_Error('min', sprintf(__('Field "%s" must be at least %s.', 'tmu'), $this->title, $params));
                }
                break;
                
            case 'max':
                if (is_numeric($value) && $value > $params) {
                    return new \WP_Error('max', sprintf(__('Field "%s" must not exceed %s.', 'tmu'), $this->title, $params));
                }
                break;
                
            case 'email':
                if (!is_email($value)) {
                    return new \WP_Error('email', sprintf(__('Field "%s" must be a valid email address.', 'tmu'), $this->title));
                }
                break;
                
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    return new \WP_Error('url', sprintf(__('Field "%s" must be a valid URL.', 'tmu'), $this->title));
                }
                break;
                
            case 'numeric':
                if (!is_numeric($value)) {
                    return new \WP_Error('numeric', sprintf(__('Field "%s" must be a number.', 'tmu'), $this->title));
                }
                break;
                
            case 'regex':
                if (!preg_match($params, $value)) {
                    return new \WP_Error('regex', sprintf(__('Field "%s" format is invalid.', 'tmu'), $this->title));
                }
                break;
        }
        
        return true;
    }
    
    /**
     * Sanitize field value
     *
     * @param mixed $value Field value
     * @return mixed
     */
    public function sanitize($value) {
        if ($this->sanitize_callback && is_callable($this->sanitize_callback)) {
            return call_user_func($this->sanitize_callback, $value);
        }
        
        return $this->getDefaultSanitization($value);
    }
    
    /**
     * Get default sanitization for field type
     *
     * @param mixed $value Field value
     * @return mixed
     */
    protected function getDefaultSanitization($value) {
        switch ($this->type) {
            case 'text':
            case 'textarea':
                return sanitize_text_field($value);
                
            case 'email':
                return sanitize_email($value);
                
            case 'url':
                return esc_url_raw($value);
                
            case 'number':
                return intval($value);
                
            case 'float':
                return floatval($value);
                
            case 'html':
                return wp_kses_post($value);
                
            case 'boolean':
                return (bool) $value;
                
            case 'json':
                return json_encode($value);
                
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Get field wrapper attributes
     *
     * @return array
     */
    protected function getWrapperAttributes(): array {
        $attributes = [
            'class' => implode(' ', array_merge(['tmu-field-wrapper', 'tmu-field-' . $this->type], $this->css_classes)),
            'data-field-id' => $this->id,
            'data-field-type' => $this->type,
        ];
        
        // Add dependency attributes
        if (!empty($this->dependency)) {
            $attributes['data-dependency'] = wp_json_encode($this->dependency);
        }
        
        return $attributes;
    }
    
    /**
     * Get field input attributes
     *
     * @param string $name Field name
     * @return array
     */
    protected function getInputAttributes(string $name): array {
        $attributes = array_merge([
            'name' => $name,
            'id' => $this->id,
            'class' => 'tmu-field-input',
        ], $this->attributes);
        
        if ($this->required) {
            $attributes['required'] = 'required';
        }
        
        return $attributes;
    }
    
    /**
     * Render field wrapper start
     *
     * @return string
     */
    protected function renderWrapperStart(): string {
        $attributes = $this->getWrapperAttributes();
        $attr_string = '';
        
        foreach ($attributes as $attr => $value) {
            $attr_string .= sprintf(' %s="%s"', $attr, esc_attr($value));
        }
        
        return "<div{$attr_string}>";
    }
    
    /**
     * Render field wrapper end
     *
     * @return string
     */
    protected function renderWrapperEnd(): string {
        return '</div>';
    }
    
    /**
     * Render field label
     *
     * @return string
     */
    protected function renderLabel(): string {
        if (empty($this->title)) {
            return '';
        }
        
        $required_mark = $this->required ? ' <span class="required">*</span>' : '';
        
        return sprintf(
            '<label for="%s" class="tmu-field-label">%s%s</label>',
            esc_attr($this->id),
            esc_html($this->title),
            $required_mark
        );
    }
    
    /**
     * Render field description
     *
     * @return string
     */
    protected function renderDescription(): string {
        if (empty($this->description)) {
            return '';
        }
        
        return sprintf(
            '<p class="tmu-field-description">%s</p>',
            wp_kses_post($this->description)
        );
    }
    
    /**
     * Render attributes array as string
     *
     * @param array $attributes Attributes array
     * @return string
     */
    protected function renderAttributes(array $attributes): string {
        $attr_string = '';
        
        foreach ($attributes as $attr => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $attr_string .= ' ' . $attr;
                }
            } else {
                $attr_string .= sprintf(' %s="%s"', $attr, esc_attr($value));
            }
        }
        
        return $attr_string;
    }
    
    /**
     * Get field ID
     *
     * @return string
     */
    public function getId(): string {
        return $this->id;
    }
    
    /**
     * Get field type
     *
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }
    
    /**
     * Get field title
     *
     * @return string
     */
    public function getTitle(): string {
        return $this->title;
    }
    
    /**
     * Get field default value
     *
     * @return mixed
     */
    public function getDefault() {
        return $this->default;
    }
    
    /**
     * Check if field is required
     *
     * @return bool
     */
    public function isRequired(): bool {
        return $this->required;
    }
    
    /**
     * Get field dependency rules
     *
     * @return array
     */
    public function getDependency(): array {
        return $this->dependency;
    }
}