<?php
/**
 * Field Manager
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
 * Field Manager Class
 * 
 * Manages all custom field registrations and meta box operations
 */
class FieldManager {
    
    /**
     * Singleton instance
     *
     * @var FieldManager
     */
    private static $instance = null;
    
    /**
     * Registered fields
     *
     * @var array
     */
    private $fields = [];
    
    /**
     * Registered meta boxes
     *
     * @var array
     */
    private $meta_boxes = [];
    
    /**
     * Field groups
     *
     * @var array
     */
    private $groups = [];
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->addHooks();
        $this->registerDefaultFields();
    }
    
    /**
     * Get singleton instance
     *
     * @return FieldManager
     */
    public static function getInstance(): FieldManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Add WordPress hooks
     */
    private function addHooks(): void {
        add_action('add_meta_boxes', [$this, 'registerMetaBoxes']);
        add_action('save_post', [$this, 'saveMetaFields'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_tmu_field_dependency', [$this, 'handleFieldDependency']);
    }
    
    /**
     * Register a custom field
     *
     * @param string $id Field ID
     * @param string $type Field type
     * @param array $args Field arguments
     * @return AbstractField
     */
    public function registerField(string $id, string $type, array $args = []): AbstractField {
        $field_class = $this->getFieldClass($type);
        
        if (!class_exists($field_class)) {
            throw new \Exception("Field type '{$type}' is not registered.");
        }
        
        $field = new $field_class($id, $args);
        $this->fields[$id] = $field;
        
        return $field;
    }
    
    /**
     * Register a meta box
     *
     * @param string $id Meta box ID
     * @param array $args Meta box arguments
     */
    public function registerMetaBox(string $id, array $args): void {
        $defaults = [
            'title' => '',
            'post_types' => [],
            'context' => 'normal',
            'priority' => 'default',
            'fields' => [],
            'callback' => null,
        ];
        
        $args = wp_parse_args($args, $defaults);
        $this->meta_boxes[$id] = $args;
    }
    
    /**
     * Register a field group
     *
     * @param string $id Group ID
     * @param array $args Group arguments
     */
    public function registerGroup(string $id, array $args): void {
        $defaults = [
            'title' => '',
            'fields' => [],
            'post_types' => [],
            'context' => 'normal',
            'priority' => 'default',
            'conditional_logic' => [],
        ];
        
        $args = wp_parse_args($args, $defaults);
        $this->groups[$id] = $args;
        
        // Auto-register meta box for group
        $this->registerMetaBox($id, [
            'title' => $args['title'],
            'post_types' => $args['post_types'],
            'context' => $args['context'],
            'priority' => $args['priority'],
            'callback' => [$this, 'renderGroupMetaBox'],
            'group_id' => $id,
        ]);
    }
    
    /**
     * Register WordPress meta boxes
     */
    public function registerMetaBoxes(): void {
        global $post;
        
        foreach ($this->meta_boxes as $id => $meta_box) {
            foreach ($meta_box['post_types'] as $post_type) {
                add_meta_box(
                    $id,
                    $meta_box['title'],
                    $meta_box['callback'] ?: [$this, 'renderMetaBox'],
                    $post_type,
                    $meta_box['context'],
                    $meta_box['priority'],
                    $meta_box
                );
            }
        }
    }
    
    /**
     * Render meta box
     *
     * @param WP_Post $post Post object
     * @param array $metabox Meta box data
     */
    public function renderMetaBox(\WP_Post $post, array $metabox): void {
        $meta_box_id = $metabox['id'];
        $meta_box = $this->meta_boxes[$meta_box_id] ?? null;
        
        if (!$meta_box) {
            return;
        }
        
        // Add nonce field
        wp_nonce_field("tmu_meta_box_{$meta_box_id}", "tmu_meta_box_{$meta_box_id}_nonce");
        
        echo '<div class="tmu-meta-box-content">';
        
        foreach ($meta_box['fields'] as $field_id) {
            if (isset($this->fields[$field_id])) {
                $this->renderField($this->fields[$field_id], $post);
            }
        }
        
        echo '</div>';
    }
    
    /**
     * Render group meta box
     *
     * @param WP_Post $post Post object
     * @param array $metabox Meta box data
     */
    public function renderGroupMetaBox(\WP_Post $post, array $metabox): void {
        $group_id = $metabox['args']['group_id'] ?? '';
        $group = $this->groups[$group_id] ?? null;
        
        if (!$group) {
            return;
        }
        
        // Add nonce field
        wp_nonce_field("tmu_group_{$group_id}", "tmu_group_{$group_id}_nonce");
        
        echo '<div class="tmu-field-group" data-group-id="' . esc_attr($group_id) . '">';
        
        foreach ($group['fields'] as $field_config) {
            $field = $this->createFieldFromConfig($field_config);
            if ($field) {
                $this->renderField($field, $post);
            }
        }
        
        echo '</div>';
    }
    
    /**
     * Render individual field
     *
     * @param AbstractField $field Field object
     * @param WP_Post $post Post object
     */
    private function renderField(AbstractField $field, \WP_Post $post): void {
        $field_id = $field->getId();
        $value = get_post_meta($post->ID, $field_id, true);
        
        if (empty($value)) {
            $value = $field->getDefault();
        }
        
        echo $field->render($value, $field_id);
    }
    
    /**
     * Save meta fields
     *
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     */
    public function saveMetaFields(int $post_id, \WP_Post $post): void {
        // Skip for autosaves and revisions
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save meta box fields
        foreach ($this->meta_boxes as $meta_box_id => $meta_box) {
            if (!in_array($post->post_type, $meta_box['post_types'])) {
                continue;
            }
            
            // Verify nonce
            $nonce_field = "tmu_meta_box_{$meta_box_id}_nonce";
            if (!isset($_POST[$nonce_field]) || !wp_verify_nonce($_POST[$nonce_field], "tmu_meta_box_{$meta_box_id}")) {
                continue;
            }
            
            // Save fields
            foreach ($meta_box['fields'] as $field_id) {
                if (isset($this->fields[$field_id])) {
                    $this->saveField($this->fields[$field_id], $post_id);
                }
            }
        }
        
        // Save group fields
        foreach ($this->groups as $group_id => $group) {
            if (!in_array($post->post_type, $group['post_types'])) {
                continue;
            }
            
            // Verify nonce
            $nonce_field = "tmu_group_{$group_id}_nonce";
            if (!isset($_POST[$nonce_field]) || !wp_verify_nonce($_POST[$nonce_field], "tmu_group_{$group_id}")) {
                continue;
            }
            
            // Save group fields
            foreach ($group['fields'] as $field_config) {
                $field = $this->createFieldFromConfig($field_config);
                if ($field) {
                    $this->saveField($field, $post_id);
                }
            }
        }
    }
    
    /**
     * Save individual field
     *
     * @param AbstractField $field Field object
     * @param int $post_id Post ID
     */
    private function saveField(AbstractField $field, int $post_id): void {
        $field_id = $field->getId();
        
        if (!isset($_POST[$field_id])) {
            return;
        }
        
        $value = $_POST[$field_id];
        
        // Validate field
        $validation_result = $field->validate($value);
        if (is_wp_error($validation_result)) {
            add_post_meta($post_id, "_tmu_field_error_{$field_id}", $validation_result->get_error_message());
            return;
        }
        
        // Sanitize field
        $value = $field->sanitize($value);
        
        // Save field
        update_post_meta($post_id, $field_id, $value);
        
        // Clear any previous errors
        delete_post_meta($post_id, "_tmu_field_error_{$field_id}");
    }
    
    /**
     * Create field from configuration
     *
     * @param array $config Field configuration
     * @return AbstractField|null
     */
    private function createFieldFromConfig(array $config): ?AbstractField {
        if (!isset($config['id']) || !isset($config['type'])) {
            return null;
        }
        
        $field_class = $this->getFieldClass($config['type']);
        
        if (!class_exists($field_class)) {
            return null;
        }
        
        return new $field_class($config['id'], $config);
    }
    
    /**
     * Get field class name from type
     *
     * @param string $type Field type
     * @return string
     */
    private function getFieldClass(string $type): string {
        $type = str_replace('_', '', ucwords($type, '_'));
        return "TMU\\Fields\\{$type}Field";
    }
    
    /**
     * Register default field types
     */
    private function registerDefaultFields(): void {
        // Default field types will be loaded automatically
        // Text, Textarea, Number, Email, URL, Select, etc.
    }
    
    /**
     * Enqueue field assets
     */
    public function enqueueAssets(): void {
        global $post;
        
        if (!$post) {
            return;
        }
        
        // Check if current post type has custom fields
        $has_fields = false;
        foreach ($this->meta_boxes as $meta_box) {
            if (in_array($post->post_type, $meta_box['post_types'])) {
                $has_fields = true;
                break;
            }
        }
        
        foreach ($this->groups as $group) {
            if (in_array($post->post_type, $group['post_types'])) {
                $has_fields = true;
                break;
            }
        }
        
        if (!$has_fields) {
            return;
        }
        
        // Enqueue field styles
        wp_enqueue_style(
            'tmu-fields',
            TMU_ASSETS_BUILD_URL . '/css/fields.css',
            [],
            TMU_VERSION
        );
        
        // Enqueue field scripts
        wp_enqueue_script(
            'tmu-fields',
            TMU_ASSETS_BUILD_URL . '/js/fields.js',
            ['jquery', 'wp-util'],
            TMU_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('tmu-fields', 'tmu_fields', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmu_fields_nonce'),
            'strings' => [
                'loading' => __('Loading...', 'tmu'),
                'error' => __('An error occurred. Please try again.', 'tmu'),
                'required_field' => __('This field is required.', 'tmu'),
                'invalid_format' => __('Invalid format.', 'tmu'),
            ],
        ]);
    }
    
    /**
     * Handle field dependency AJAX
     */
    public function handleFieldDependency(): void {
        check_ajax_referer('tmu_fields_nonce', 'nonce');
        
        $field_id = sanitize_text_field($_POST['field_id'] ?? '');
        $value = sanitize_text_field($_POST['value'] ?? '');
        $post_id = intval($_POST['post_id'] ?? 0);
        
        // Process dependency logic here
        $response = [
            'success' => true,
            'data' => [],
        ];
        
        wp_send_json($response);
    }
    
    /**
     * Get registered fields
     *
     * @return array
     */
    public function getFields(): array {
        return $this->fields;
    }
    
    /**
     * Get registered meta boxes
     *
     * @return array
     */
    public function getMetaBoxes(): array {
        return $this->meta_boxes;
    }
    
    /**
     * Get registered groups
     *
     * @return array
     */
    public function getGroups(): array {
        return $this->groups;
    }
    
    /**
     * Get field by ID
     *
     * @param string $id Field ID
     * @return AbstractField|null
     */
    public function getField(string $id): ?AbstractField {
        return $this->fields[$id] ?? null;
    }
    
    /**
     * Check if field exists
     *
     * @param string $id Field ID
     * @return bool
     */
    public function hasField(string $id): bool {
        return isset($this->fields[$id]);
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    private function __wakeup() {}
}