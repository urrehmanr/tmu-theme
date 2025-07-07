<?php
/**
 * Input Validator
 * 
 * Comprehensive input validation and sanitization for all user inputs.
 * Protects against injection attacks and ensures data integrity.
 * 
 * @package TMU\Security
 * @since 1.0.0
 */

namespace TMU\Security;

/**
 * InputValidator class
 * 
 * Handles validation and sanitization of all user inputs
 */
class InputValidator {
    
    /**
     * Validation rules
     * @var array
     */
    private $validation_rules = [];
    
    /**
     * Sanitization filters
     * @var array
     */
    private $sanitization_filters = [];
    
    /**
     * Error messages
     * @var array
     */
    private $error_messages = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_validation_rules();
        $this->init_sanitization_filters();
        $this->init_error_messages();
        $this->init_hooks();
    }
    
    /**
     * Initialize validation rules
     */
    private function init_validation_rules(): void {
        $this->validation_rules = [
            'post_id' => [
                'type' => 'integer',
                'min' => 1,
                'required' => true
            ],
            'tmdb_id' => [
                'type' => 'integer',
                'min' => 1,
                'max' => 999999999,
                'required' => false
            ],
            'title' => [
                'type' => 'string',
                'min_length' => 1,
                'max_length' => 255,
                'required' => true,
                'pattern' => '/^[a-zA-Z0-9\s\-\.\,\:\;\!\?\(\)\[\]\'\"]+$/'
            ],
            'original_title' => [
                'type' => 'string',
                'min_length' => 0,
                'max_length' => 255,
                'required' => false,
                'pattern' => '/^[a-zA-Z0-9\s\-\.\,\:\;\!\?\(\)\[\]\'\"]+$/u'
            ],
            'overview' => [
                'type' => 'text',
                'min_length' => 0,
                'max_length' => 5000,
                'required' => false
            ],
            'tagline' => [
                'type' => 'string',
                'min_length' => 0,
                'max_length' => 500,
                'required' => false
            ],
            'homepage' => [
                'type' => 'url',
                'required' => false
            ],
            'release_date' => [
                'type' => 'date',
                'format' => 'Y-m-d',
                'required' => false
            ],
            'runtime' => [
                'type' => 'integer',
                'min' => 0,
                'max' => 1000,
                'required' => false
            ],
            'budget' => [
                'type' => 'integer',
                'min' => 0,
                'max' => 999999999999,
                'required' => false
            ],
            'revenue' => [
                'type' => 'integer',
                'min' => 0,
                'max' => 999999999999,
                'required' => false
            ],
            'vote_average' => [
                'type' => 'float',
                'min' => 0,
                'max' => 10,
                'required' => false
            ],
            'vote_count' => [
                'type' => 'integer',
                'min' => 0,
                'max' => 999999999,
                'required' => false
            ],
            'popularity' => [
                'type' => 'float',
                'min' => 0,
                'required' => false
            ],
            'status' => [
                'type' => 'enum',
                'values' => ['Released', 'In Production', 'Post Production', 'Planned', 'Canceled'],
                'required' => false
            ],
            'email' => [
                'type' => 'email',
                'required' => false
            ],
            'username' => [
                'type' => 'string',
                'min_length' => 3,
                'max_length' => 60,
                'pattern' => '/^[a-zA-Z0-9_\-]+$/',
                'required' => false
            ],
            'search_term' => [
                'type' => 'string',
                'min_length' => 1,
                'max_length' => 100,
                'pattern' => '/^[a-zA-Z0-9\s\-\.\,]+$/',
                'required' => true
            ],
            'orderby' => [
                'type' => 'enum',
                'values' => ['date', 'title', 'release_date', 'rating', 'popularity', 'vote_count'],
                'required' => false
            ],
            'order' => [
                'type' => 'enum',
                'values' => ['ASC', 'DESC'],
                'required' => false
            ]
        ];
        
        // Allow customization via filters
        $this->validation_rules = apply_filters('tmu_validation_rules', $this->validation_rules);
    }
    
    /**
     * Initialize sanitization filters
     */
    private function init_sanitization_filters(): void {
        $this->sanitization_filters = [
            'integer' => 'intval',
            'float' => 'floatval',
            'string' => 'sanitize_text_field',
            'text' => 'sanitize_textarea_field',
            'email' => 'sanitize_email',
            'url' => 'esc_url_raw',
            'date' => [$this, 'sanitize_date'],
            'enum' => 'sanitize_text_field',
            'html' => 'wp_kses_post',
            'slug' => 'sanitize_title'
        ];
        
        // Allow customization via filters
        $this->sanitization_filters = apply_filters('tmu_sanitization_filters', $this->sanitization_filters);
    }
    
    /**
     * Initialize error messages
     */
    private function init_error_messages(): void {
        $this->error_messages = [
            'required' => __('This field is required.', 'tmu-theme'),
            'invalid_type' => __('Invalid data type provided.', 'tmu-theme'),
            'min_length' => __('Value is too short. Minimum length is %d characters.', 'tmu-theme'),
            'max_length' => __('Value is too long. Maximum length is %d characters.', 'tmu-theme'),
            'min_value' => __('Value is too small. Minimum value is %d.', 'tmu-theme'),
            'max_value' => __('Value is too large. Maximum value is %d.', 'tmu-theme'),
            'invalid_pattern' => __('Value contains invalid characters.', 'tmu-theme'),
            'invalid_date' => __('Invalid date format. Expected format: Y-m-d.', 'tmu-theme'),
            'invalid_email' => __('Invalid email address.', 'tmu-theme'),
            'invalid_url' => __('Invalid URL format.', 'tmu-theme'),
            'invalid_enum' => __('Invalid value. Allowed values: %s.', 'tmu-theme')
        ];
        
        // Allow customization via filters
        $this->error_messages = apply_filters('tmu_validation_error_messages', $this->error_messages);
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        add_action('init', [$this, 'init_security_filters']);
        add_filter('pre_get_posts', [$this, 'sanitize_query_vars']);
        add_action('wp_ajax_tmu_save_data', [$this, 'validate_ajax_request']);
        add_action('wp_ajax_nopriv_tmu_save_data', [$this, 'validate_ajax_request']);
        add_action('wp_ajax_tmu_search', [$this, 'validate_search_request']);
        add_action('wp_ajax_nopriv_tmu_search', [$this, 'validate_search_request']);
        
        // Validate form submissions
        add_action('wp_loaded', [$this, 'validate_form_submissions']);
        
        // Validate REST API requests
        add_filter('rest_pre_dispatch', [$this, 'validate_rest_request'], 10, 3);
    }
    
    /**
     * Initialize security filters
     */
    public function init_security_filters(): void {
        // Sanitize superglobals
        $this->sanitize_superglobals();
        
        // Set up input filtering
        add_filter('tmu_validate_input', [$this, 'validate_input'], 10, 2);
        add_filter('tmu_sanitize_input', [$this, 'sanitize_input'], 10, 2);
    }
    
    /**
     * Sanitize query variables
     */
    public function sanitize_query_vars($query): void {
        if (!is_admin() && $query->is_main_query()) {
            $allowed_orderby = ['date', 'title', 'release_date', 'rating', 'popularity'];
            $orderby = $query->get('orderby');
            
            if ($orderby && !in_array($orderby, $allowed_orderby)) {
                $query->set('orderby', 'date');
            }
            
            $order = $query->get('order');
            if ($order && !in_array(strtoupper($order), ['ASC', 'DESC'])) {
                $query->set('order', 'DESC');
            }
            
            // Sanitize search query
            $search = $query->get('s');
            if ($search) {
                $query->set('s', $this->sanitize_search_query($search));
            }
            
            // Sanitize meta queries
            $meta_query = $query->get('meta_query');
            if ($meta_query) {
                $query->set('meta_query', $this->sanitize_meta_query($meta_query));
            }
        }
    }
    
    /**
     * Validate AJAX request
     */
    public function validate_ajax_request(): void {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'tmu_ajax_nonce')) {
            wp_die(__('Security check failed', 'tmu-theme'));
        }
        
        // Validate user permissions
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'tmu-theme'));
        }
        
        // Sanitize and validate input data
        $data = $this->sanitize_input_data($_POST);
        $validation_result = $this->validate_data($data);
        
        if (!$validation_result['valid']) {
            wp_die(wp_json_encode([
                'success' => false,
                'errors' => $validation_result['errors']
            ]));
        }
        
        // Process validated data
        $this->process_validated_data($data);
    }
    
    /**
     * Validate search request
     */
    public function validate_search_request(): void {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'tmu_search_nonce')) {
            wp_die(__('Security check failed', 'tmu-theme'));
        }
        
        // Validate search parameters
        $search_data = [
            'term' => $_POST['term'] ?? '',
            'post_types' => $_POST['post_types'] ?? [],
            'limit' => $_POST['limit'] ?? 10
        ];
        
        $sanitized_data = $this->sanitize_search_data($search_data);
        $validation_result = $this->validate_search_data($sanitized_data);
        
        if (!$validation_result['valid']) {
            wp_die(wp_json_encode([
                'success' => false,
                'errors' => $validation_result['errors']
            ]));
        }
        
        // Process search
        $this->process_search_request($sanitized_data);
    }
    
    /**
     * Validate form submissions
     */
    public function validate_form_submissions(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
            // Check for TMU forms
            if (isset($_POST['tmu_form_type'])) {
                $form_type = sanitize_text_field($_POST['tmu_form_type']);
                $this->validate_form_submission($form_type, $_POST);
            }
        }
    }
    
    /**
     * Validate REST API request
     */
    public function validate_rest_request($result, $server, $request) {
        $route = $request->get_route();
        
        // Only validate TMU API endpoints
        if (strpos($route, '/tmu/') === 0) {
            $params = $request->get_params();
            $validation_result = $this->validate_api_request($params, $route);
            
            if (!$validation_result['valid']) {
                return new \WP_Error(
                    'invalid_request',
                    __('Invalid request parameters', 'tmu-theme'),
                    ['status' => 400, 'errors' => $validation_result['errors']]
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Sanitize input data
     */
    private function sanitize_input_data($data): array {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (isset($this->validation_rules[$key])) {
                $rule = $this->validation_rules[$key];
                $sanitized[$key] = $this->sanitize_value($value, $rule['type']);
            } else {
                // Default sanitization for unknown fields
                $sanitized[$key] = $this->sanitize_value($value, 'string');
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Validate data against rules
     */
    public function validate_data($data): array {
        $errors = [];
        $valid = true;
        
        foreach ($this->validation_rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $field_errors = $this->validate_field($field, $value, $rule);
            
            if (!empty($field_errors)) {
                $errors[$field] = $field_errors;
                $valid = false;
            }
        }
        
        return ['valid' => $valid, 'errors' => $errors];
    }
    
    /**
     * Validate individual field
     */
    private function validate_field($field, $value, $rule): array {
        $errors = [];
        
        // Check if required
        if ($rule['required'] && ($value === null || $value === '')) {
            $errors[] = $this->error_messages['required'];
            return $errors; // No point in further validation if required field is empty
        }
        
        // Skip validation if field is not required and empty
        if (!$rule['required'] && ($value === null || $value === '')) {
            return $errors;
        }
        
        // Type validation
        if (!$this->validate_type($value, $rule['type'])) {
            $errors[] = $this->error_messages['invalid_type'];
            return $errors;
        }
        
        // Length validation for strings
        if (in_array($rule['type'], ['string', 'text'])) {
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[] = sprintf($this->error_messages['min_length'], $rule['min_length']);
            }
            
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[] = sprintf($this->error_messages['max_length'], $rule['max_length']);
            }
        }
        
        // Value validation for numbers
        if (in_array($rule['type'], ['integer', 'float'])) {
            if (isset($rule['min']) && $value < $rule['min']) {
                $errors[] = sprintf($this->error_messages['min_value'], $rule['min']);
            }
            
            if (isset($rule['max']) && $value > $rule['max']) {
                $errors[] = sprintf($this->error_messages['max_value'], $rule['max']);
            }
        }
        
        // Pattern validation
        if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
            $errors[] = $this->error_messages['invalid_pattern'];
        }
        
        // Enum validation
        if ($rule['type'] === 'enum' && !in_array($value, $rule['values'])) {
            $errors[] = sprintf($this->error_messages['invalid_enum'], implode(', ', $rule['values']));
        }
        
        // Date validation
        if ($rule['type'] === 'date' && !$this->validate_date($value, $rule['format'] ?? 'Y-m-d')) {
            $errors[] = $this->error_messages['invalid_date'];
        }
        
        // URL validation
        if ($rule['type'] === 'url' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $errors[] = $this->error_messages['invalid_url'];
        }
        
        // Email validation
        if ($rule['type'] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = $this->error_messages['invalid_email'];
        }
        
        return $errors;
    }
    
    /**
     * Validate type
     */
    private function validate_type($value, $type): bool {
        switch ($type) {
            case 'integer':
                return is_numeric($value) && is_int($value + 0);
            case 'float':
                return is_numeric($value);
            case 'string':
            case 'text':
            case 'enum':
                return is_string($value);
            case 'email':
                return is_string($value);
            case 'url':
                return is_string($value);
            case 'date':
                return is_string($value);
            default:
                return true;
        }
    }
    
    /**
     * Validate date
     */
    private function validate_date($date, $format): bool {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Sanitize value
     */
    private function sanitize_value($value, $type) {
        if (isset($this->sanitization_filters[$type])) {
            $filter = $this->sanitization_filters[$type];
            
            if (is_callable($filter)) {
                return call_user_func($filter, $value);
            }
        }
        
        // Default sanitization
        return sanitize_text_field($value);
    }
    
    /**
     * Sanitize date
     */
    public function sanitize_date($date): string {
        $date = sanitize_text_field($date);
        
        // Validate and format date
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        
        return '';
    }
    
    /**
     * Sanitize superglobals
     */
    private function sanitize_superglobals(): void {
        // Note: This is for demonstration. In production, be very careful about modifying superglobals
        if (!empty($_GET)) {
            foreach ($_GET as $key => $value) {
                $_GET[$key] = $this->deep_sanitize($value);
            }
        }
    }
    
    /**
     * Deep sanitize array or string
     */
    private function deep_sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'deep_sanitize'], $data);
        }
        
        return sanitize_text_field($data);
    }
    
    /**
     * Sanitize search query
     */
    private function sanitize_search_query($query): string {
        // Remove special characters that could be used for injection
        $query = preg_replace('/[<>\"\'%;()&+]/', '', $query);
        
        // Limit length
        $query = substr($query, 0, 100);
        
        return sanitize_text_field($query);
    }
    
    /**
     * Sanitize meta query
     */
    private function sanitize_meta_query($meta_query): array {
        if (!is_array($meta_query)) {
            return [];
        }
        
        $sanitized = [];
        
        foreach ($meta_query as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_meta_query($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize search data
     */
    private function sanitize_search_data($data): array {
        return [
            'term' => $this->sanitize_search_query($data['term']),
            'post_types' => array_map('sanitize_text_field', (array) $data['post_types']),
            'limit' => intval($data['limit'])
        ];
    }
    
    /**
     * Validate search data
     */
    private function validate_search_data($data): array {
        $errors = [];
        $valid = true;
        
        if (empty($data['term']) || strlen($data['term']) < 1) {
            $errors['term'] = __('Search term is required', 'tmu-theme');
            $valid = false;
        }
        
        if ($data['limit'] < 1 || $data['limit'] > 100) {
            $errors['limit'] = __('Limit must be between 1 and 100', 'tmu-theme');
            $valid = false;
        }
        
        $allowed_post_types = ['movie', 'tv', 'drama', 'people'];
        foreach ($data['post_types'] as $post_type) {
            if (!in_array($post_type, $allowed_post_types)) {
                $errors['post_types'] = __('Invalid post type specified', 'tmu-theme');
                $valid = false;
                break;
            }
        }
        
        return ['valid' => $valid, 'errors' => $errors];
    }
    
    /**
     * Validate form submission
     */
    private function validate_form_submission($form_type, $data): void {
        // Implement form-specific validation
        $validation_result = $this->validate_data($data);
        
        if (!$validation_result['valid']) {
            // Handle validation errors
            wp_redirect(add_query_arg(['errors' => urlencode(wp_json_encode($validation_result['errors']))], wp_get_referer()));
            exit;
        }
    }
    
    /**
     * Validate API request
     */
    private function validate_api_request($params, $route): array {
        // Implement route-specific validation
        return ['valid' => true, 'errors' => []];
    }
    
    /**
     * Process validated data
     */
    private function process_validated_data($data): void {
        // Process the validated and sanitized data
        do_action('tmu_process_validated_data', $data);
    }
    
    /**
     * Process search request
     */
    private function process_search_request($data): void {
        // Process the search request
        do_action('tmu_process_search_request', $data);
    }
    
    /**
     * Add validation rule
     */
    public function add_validation_rule($field, $rule): void {
        $this->validation_rules[$field] = $rule;
    }
    
    /**
     * Get validation rules
     */
    public function get_validation_rules(): array {
        return $this->validation_rules;
    }
    
    /**
     * Validate input (public method for external use)
     */
    public function validate_input($data, $rules = null): array {
        if ($rules) {
            $original_rules = $this->validation_rules;
            $this->validation_rules = $rules;
            $result = $this->validate_data($data);
            $this->validation_rules = $original_rules;
            return $result;
        }
        
        return $this->validate_data($data);
    }
    
    /**
     * Sanitize input (public method for external use)
     */
    public function sanitize_input($data, $context = 'default'): array {
        return $this->sanitize_input_data($data);
    }
}