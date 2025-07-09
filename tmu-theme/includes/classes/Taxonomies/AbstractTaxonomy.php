<?php
/**
 * Abstract Taxonomy Base Class
 *
 * @package TMU\Taxonomies
 * @version 1.0.0
 */

namespace TMU\Taxonomies;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract Taxonomy Class
 * 
 * Base class for all taxonomy implementations
 */
abstract class AbstractTaxonomy {
    
    /**
     * Taxonomy slug
     *
     * @var string
     */
    protected $taxonomy = '';
    
    /**
     * Object types this taxonomy applies to
     *
     * @var array
     */
    protected $object_types = [];
    
    /**
     * Meta fields for this taxonomy
     *
     * @var array
     */
    protected $meta_fields = [];
    
    /**
     * Whether this taxonomy is hierarchical
     *
     * @var bool
     */
    protected $hierarchical = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->addHooks();
    }
    
    /**
     * Register the taxonomy
     */
    public function register(): void {
        tmu_log("Registering taxonomy: {$this->taxonomy}", 'debug');
        
        // Force registration for debugging - always register taxonomies
        // if (!$this->shouldRegister()) {
        //     tmu_log("Taxonomy {$this->taxonomy} should not register, but forcing for debug", 'debug');
        //     // return;
        // }
        
        // Check if taxonomy already exists to avoid duplicate registration
        if (!taxonomy_exists($this->taxonomy)) {
            tmu_log("Taxonomy {$this->taxonomy} does not exist, registering now", 'debug');
            
            // Get taxonomy arguments
            $args = $this->getArgs();
            tmu_log("Taxonomy {$this->taxonomy} args: " . wp_json_encode($args), 'debug');
            
            // Register taxonomy
            $result = register_taxonomy(
                $this->taxonomy,
                $this->object_types,
                $args
            );
            
            // Check if registration was successful
            if (taxonomy_exists($this->taxonomy)) {
                tmu_log("Taxonomy {$this->taxonomy} registered successfully", 'info');
                
                // Register meta fields
                $this->registerMetaFields();
                
                // Add admin customizations
                $this->addAdminHooks();
                
                // Log successful registration
                if (function_exists('tmu_log')) {
                    tmu_log("Registered taxonomy: {$this->taxonomy}", 'info');
                }
            } else {
                tmu_log("Failed to register taxonomy: {$this->taxonomy}", 'error');
                
                if (is_wp_error($result)) {
                    tmu_log("Taxonomy registration error: " . $result->get_error_message(), 'error');
                }
            }
        } else {
            tmu_log("Taxonomy {$this->taxonomy} already exists, adding to post types", 'debug');
            
            // If taxonomy exists, just register it for our post types
            foreach ($this->object_types as $object_type) {
                $success = register_taxonomy_for_object_type($this->taxonomy, $object_type);
                if ($success) {
                    tmu_log("Added taxonomy {$this->taxonomy} to post type {$object_type}", 'debug');
                } else {
                    tmu_log("Failed to add taxonomy {$this->taxonomy} to post type {$object_type}", 'error');
                }
            }
            
            if (function_exists('tmu_log')) {
                tmu_log("Added existing taxonomy {$this->taxonomy} to post types", 'info');
            }
        }
    }
    
    /**
     * Get taxonomy labels
     *
     * @return array
     */
    abstract protected function getLabels(): array;
    
    /**
     * Get taxonomy arguments
     *
     * @return array
     */
    abstract protected function getArgs(): array;
    
    /**
     * Check if taxonomy should be registered
     *
     * @return bool
     */
    abstract protected function shouldRegister(): bool;
    
    /**
     * Get default taxonomy arguments
     *
     * @return array
     */
    protected function getDefaultArgs(): array {
        return [
            'labels' => $this->getLabels(),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'show_tagcloud' => true,
            'show_in_quick_edit' => true,
            'show_admin_column' => true,
            'hierarchical' => $this->hierarchical,
            'query_var' => true,
            'capabilities' => [
                'manage_terms' => 'manage_categories',
                'edit_terms' => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_posts',
            ],
        ];
    }
    
    /**
     * Register meta fields
     */
    protected function registerMetaFields(): void {
        foreach ($this->meta_fields as $field_name => $field_config) {
            register_term_meta($this->taxonomy, $field_name, [
                'type' => $field_config['type'],
                'description' => $field_config['description'],
                'single' => $field_config['single'] ?? true,
                'show_in_rest' => $field_config['show_in_rest'] ?? true,
                'sanitize_callback' => $field_config['sanitize_callback'] ?? null,
            ]);
        }
    }
    
    /**
     * Add WordPress hooks
     */
    protected function addHooks(): void {
        add_action('init', [$this, 'register'], 10);
    }
    
    /**
     * Add admin hooks
     */
    protected function addAdminHooks(): void {
        // Add form fields
        add_action("{$this->taxonomy}_add_form_fields", [$this, 'addFormFields']);
        add_action("{$this->taxonomy}_edit_form_fields", [$this, 'editFormFields']);
        
        // Save meta fields
        add_action("created_{$this->taxonomy}", [$this, 'saveMetaFields']);
        add_action("edited_{$this->taxonomy}", [$this, 'saveMetaFields']);
        
        // Add admin columns
        add_filter("manage_edit-{$this->taxonomy}_columns", [$this, 'addAdminColumns']);
        add_filter("manage_{$this->taxonomy}_custom_column", [$this, 'displayAdminColumnContent'], 10, 3);
        
        // Add sortable columns
        add_filter("manage_edit-{$this->taxonomy}_sortable_columns", [$this, 'makeSortableColumns']);
    }
    
    /**
     * Add form fields to add term form
     *
     * @param string $taxonomy Taxonomy slug
     */
    public function addFormFields(string $taxonomy): void {
        if (empty($this->meta_fields)) {
            return;
        }
        
        foreach ($this->meta_fields as $field_name => $field_config) {
            echo '<div class="form-field">';
            echo '<label for="' . esc_attr($field_name) . '">' . esc_html($field_config['label']) . '</label>';
            
            $this->renderFormField($field_name, $field_config, '');
            
            if (!empty($field_config['description'])) {
                echo '<p class="description">' . esc_html($field_config['description']) . '</p>';
            }
            
            echo '</div>';
        }
    }
    
    /**
     * Add form fields to edit term form
     *
     * @param \WP_Term $term Term object
     */
    public function editFormFields(\WP_Term $term): void {
        if (empty($this->meta_fields)) {
            return;
        }
        
        foreach ($this->meta_fields as $field_name => $field_config) {
            $value = get_term_meta($term->term_id, $field_name, true);
            
            echo '<tr class="form-field">';
            echo '<th scope="row"><label for="' . esc_attr($field_name) . '">' . esc_html($field_config['label']) . '</label></th>';
            echo '<td>';
            
            $this->renderFormField($field_name, $field_config, $value);
            
            if (!empty($field_config['description'])) {
                echo '<p class="description">' . esc_html($field_config['description']) . '</p>';
            }
            
            echo '</td>';
            echo '</tr>';
        }
    }
    
    /**
     * Render form field
     *
     * @param string $field_name Field name
     * @param array $field_config Field configuration
     * @param mixed $value Field value
     */
    protected function renderFormField(string $field_name, array $field_config, $value): void {
        $field_type = $field_config['input_type'] ?? 'text';
        
        switch ($field_type) {
            case 'textarea':
                echo '<textarea id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" rows="5" cols="50">' . esc_textarea($value) . '</textarea>';
                break;
                
            case 'select':
                echo '<select id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '">';
                foreach ($field_config['options'] as $option_value => $option_label) {
                    echo '<option value="' . esc_attr($option_value) . '"' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
                }
                echo '</select>';
                break;
                
            case 'checkbox':
                echo '<input type="checkbox" id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" value="1"' . checked($value, 1, false) . ' />';
                break;
                
            case 'url':
                echo '<input type="url" id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" value="' . esc_url($value) . '" size="50" />';
                break;
                
            case 'email':
                echo '<input type="email" id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" size="50" />';
                break;
                
            case 'number':
                echo '<input type="number" id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" size="20" />';
                break;
                
            case 'color':
                echo '<input type="color" id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" />';
                break;
                
            default:
                echo '<input type="text" id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" size="50" />';
                break;
        }
    }
    
    /**
     * Save meta fields
     *
     * @param int $term_id Term ID
     */
    public function saveMetaFields(int $term_id): void {
        foreach ($this->meta_fields as $field_name => $field_config) {
            if (!isset($_POST[$field_name])) {
                continue;
            }
            
            $value = $_POST[$field_name];
            
            // Sanitize value
            if (isset($field_config['sanitize_callback']) && is_callable($field_config['sanitize_callback'])) {
                $value = call_user_func($field_config['sanitize_callback'], $value);
            } else {
                $value = sanitize_text_field($value);
            }
            
            update_term_meta($term_id, $field_name, $value);
        }
    }
    
    /**
     * Add custom admin columns
     *
     * @param array $columns Existing columns
     * @return array
     */
    public function addAdminColumns(array $columns): array {
        return $columns;
    }
    
    /**
     * Display custom column content
     *
     * @param string $content Column content
     * @param string $column_name Column name
     * @param int $term_id Term ID
     * @return string
     */
    public function displayAdminColumnContent(string $content, string $column_name, int $term_id): string {
        return $content;
    }
    
    /**
     * Make columns sortable
     *
     * @param array $columns Sortable columns
     * @return array
     */
    public function makeSortableColumns(array $columns): array {
        return $columns;
    }
    
    /**
     * Get taxonomy
     *
     * @return string
     */
    public function getTaxonomy(): string {
        return $this->taxonomy;
    }
    
    /**
     * Get object types
     *
     * @return array
     */
    public function getObjectTypes(): array {
        return $this->object_types;
    }
}