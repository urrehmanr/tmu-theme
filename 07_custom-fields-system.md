# Step 07: Custom Fields System

## Purpose
Implement a comprehensive custom fields system that replaces Meta Box plugin functionality with native WordPress meta boxes, preserving all existing field types, data storage, and admin interface while adding modern OOP architecture.

## Overview
This step converts the plugin's Meta Box-based custom fields to native WordPress implementation:
1. Preserve existing custom table storage
2. Replicate all field types and functionality
3. Maintain data compatibility
4. Implement modern field management system
5. Add validation and sanitization

## Analysis from Plugin Fields

### Field Storage Strategy
- **Custom Tables**: Movie, TV, Drama, People data stored in separate tables
- **Meta Box Configuration**: Complex field definitions with groups, cloning, conditional logic
- **Field Types**: Text, select, date, post relationship, group, image, URL, etc.

### Key Field Patterns Identified
- **Group Fields**: Nested field collections (credits, cast, crew)
- **Cloneable Fields**: Repeatable field groups (social media, photos)
- **Conditional Fields**: Fields shown based on other field values
- **Post Relationships**: Connect people to movies/shows
- **Complex Validations**: TMDB integration, department-specific jobs

## Architecture Implementation

### Directory Structure
```
includes/classes/Fields/
├── FieldManager.php          # Main field manager
├── AbstractField.php         # Base field class
├── FieldRenderer.php         # Field rendering engine
├── FieldValidator.php        # Validation system
├── DataManager.php           # Custom table operations
├── Types/                    # Individual field types
│   ├── TextField.php         # Text input fields
│   ├── SelectField.php       # Select dropdowns
│   ├── DateField.php         # Date picker fields
│   ├── GroupField.php        # Grouped fields
│   ├── PostField.php         # Post relationship
│   └── ImageField.php        # Image upload
└── Storage/
    ├── CustomTableStorage.php # Custom table handler
    └── MetaStorage.php        # WordPress meta fallback
```

## Core Implementation

### 1. Field Manager (`FieldManager.php`)
```php
<?php
namespace TMU\Fields;

use TMU\Config\ThemeConfig;

class FieldManager {
    private static $instance = null;
    private $fields = [];
    private $storage;
    
    public static function getInstance(): FieldManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->storage = new Storage\CustomTableStorage();
        $this->initHooks();
        $this->registerFields();
    }
    
    private function initHooks(): void {
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post', [$this, 'saveFields']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }
    
    public function addMetaBoxes(): void {
        $post_type = get_current_screen()->post_type;
        $config = $this->getFieldsForPostType($post_type);
        
        foreach ($config as $meta_box) {
            add_meta_box(
                $meta_box['id'],
                $meta_box['title'],
                [$this, 'renderMetaBox'],
                $post_type,
                'normal',
                'high',
                $meta_box
            );
        }
    }
    
    public function renderMetaBox(\WP_Post $post, array $args): void {
        $fields = $args['args']['fields'];
        $renderer = new FieldRenderer($this->storage);
        
        wp_nonce_field('tmu_fields_nonce', 'tmu_fields_nonce');
        
        echo '<div class="tmu-fields-container">';
        foreach ($fields as $field) {
            $renderer->render($field, $post->ID);
        }
        echo '</div>';
    }
    
    public function saveFields(int $post_id): void {
        if (!$this->shouldSave($post_id)) {
            return;
        }
        
        $post_type = get_post_type($post_id);
        $fields = $this->getFieldsForPostType($post_type);
        
        foreach ($fields as $meta_box) {
            foreach ($meta_box['fields'] as $field) {
                $this->saveField($field, $post_id);
            }
        }
    }
    
    private function saveField(array $field, int $post_id): void {
        $value = $_POST[$field['id']] ?? '';
        $sanitized = FieldValidator::sanitize($value, $field['type']);
        
        if (FieldValidator::validate($sanitized, $field)) {
            $this->storage->save($post_id, $field['id'], $sanitized);
        }
    }
    
    private function shouldSave(int $post_id): bool {
        return wp_verify_nonce($_POST['tmu_fields_nonce'] ?? '', 'tmu_fields_nonce') &&
               current_user_can('edit_post', $post_id) &&
               !wp_is_post_autosave($post_id) &&
               !wp_is_post_revision($post_id);
    }
}
```

### 2. Custom Table Storage (`Storage/CustomTableStorage.php`)
```php
<?php
namespace TMU\Fields\Storage;

class CustomTableStorage {
    private $tables = [
        'movie' => 'tmu_movies',
        'tv' => 'tmu_tv_series', 
        'drama' => 'tmu_dramas',
        'people' => 'tmu_people'
    ];
    
    public function save(int $post_id, string $field_id, $value): bool {
        global $wpdb;
        
        $post_type = get_post_type($post_id);
        $table = $this->getTable($post_type);
        
        if (!$table) {
            return update_post_meta($post_id, $field_id, $value);
        }
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE post_id = %d",
            $post_id
        ));
        
        if ($existing > 0) {
            return $wpdb->update(
                $table,
                [$field_id => maybe_serialize($value)],
                ['post_id' => $post_id],
                ['%s'],
                ['%d']
            ) !== false;
        } else {
            return $wpdb->insert(
                $table,
                [
                    'post_id' => $post_id,
                    $field_id => maybe_serialize($value)
                ],
                ['%d', '%s']
            ) !== false;
        }
    }
    
    public function get(int $post_id, string $field_id, $default = '') {
        global $wpdb;
        
        $post_type = get_post_type($post_id);
        $table = $this->getTable($post_type);
        
        if (!$table) {
            return get_post_meta($post_id, $field_id, true) ?: $default;
        }
        
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT {$field_id} FROM {$table} WHERE post_id = %d",
            $post_id
        ));
        
        return $value ? maybe_unserialize($value) : $default;
    }
    
    private function getTable(string $post_type): ?string {
        global $wpdb;
        return isset($this->tables[$post_type]) ? 
               $wpdb->prefix . $this->tables[$post_type] : null;
    }
}
```

### 3. Field Renderer (`FieldRenderer.php`)
```php
<?php
namespace TMU\Fields;

class FieldRenderer {
    private $storage;
    
    public function __construct($storage) {
        $this->storage = $storage;
    }
    
    public function render(array $field, int $post_id): void {
        $value = $this->storage->get($post_id, $field['id'], $field['default'] ?? '');
        $field_class = $this->getFieldClass($field['type']);
        
        if ($field_class && class_exists($field_class)) {
            $field_instance = new $field_class($field);
            $field_instance->render($value, $post_id);
        } else {
            $this->renderFallback($field, $value);
        }
    }
    
    private function getFieldClass(string $type): ?string {
        $class_map = [
            'text' => 'TMU\\Fields\\Types\\TextField',
            'select' => 'TMU\\Fields\\Types\\SelectField',
            'date' => 'TMU\\Fields\\Types\\DateField',
            'group' => 'TMU\\Fields\\Types\\GroupField',
            'post' => 'TMU\\Fields\\Types\\PostField',
        ];
        
        return $class_map[$type] ?? null;
    }
    
    private function renderFallback(array $field, $value): void {
        echo '<div class="tmu-field-fallback">';
        echo '<label>' . esc_html($field['name']) . '</label>';
        echo '<input type="text" name="' . esc_attr($field['id']) . '" value="' . esc_attr($value) . '">';
        echo '</div>';
    }
}
```

### 4. Field Types - Text Field (`Types/TextField.php`)
```php
<?php
namespace TMU\Fields\Types;

class TextField extends AbstractField {
    public function render($value, int $post_id): void {
        $columns = $this->field['columns'] ?? 12;
        $placeholder = $this->field['placeholder'] ?? '';
        
        echo '<div class="tmu-field tmu-text-field" data-columns="' . $columns . '">';
        echo '<label for="' . $this->field['id'] . '">' . $this->field['name'] . '</label>';
        echo '<input type="text" 
                id="' . $this->field['id'] . '" 
                name="' . $this->field['id'] . '" 
                value="' . esc_attr($value) . '"
                placeholder="' . esc_attr($placeholder) . '"
                class="regular-text">';
        echo '</div>';
    }
}
```

### 5. Complex Field - Group Field (`Types/GroupField.php`)
```php
<?php
namespace TMU\Fields\Types;

class GroupField extends AbstractField {
    public function render($value, int $post_id): void {
        $is_cloneable = $this->field['clone'] ?? false;
        $max_clone = $this->field['max_clone'] ?? 10;
        
        echo '<div class="tmu-group-field" data-field-id="' . $this->field['id'] . '">';
        echo '<label class="tmu-group-label">' . $this->field['name'] . '</label>';
        
        if ($is_cloneable) {
            $this->renderCloneableGroup($value, $post_id, $max_clone);
        } else {
            $this->renderSingleGroup($value, $post_id);
        }
        
        echo '</div>';
    }
    
    private function renderCloneableGroup($values, int $post_id, int $max_clone): void {
        $values = is_array($values) ? $values : [[]];
        
        echo '<div class="tmu-clone-container">';
        
        foreach ($values as $index => $group_value) {
            $this->renderGroupInstance($group_value, $post_id, $index);
        }
        
        if (count($values) < $max_clone) {
            echo '<button type="button" class="tmu-add-clone button">Add ' . $this->field['name'] . '</button>';
        }
        
        echo '</div>';
        
        // Template for new clones
        echo '<template class="tmu-clone-template">';
        $this->renderGroupInstance([], $post_id, '{{index}}');
        echo '</template>';
    }
    
    private function renderGroupInstance($group_value, int $post_id, $index): void {
        echo '<div class="tmu-group-instance" data-index="' . $index . '">';
        
        if (is_numeric($index)) {
            echo '<button type="button" class="tmu-remove-clone">Remove</button>';
        }
        
        foreach ($this->field['fields'] as $sub_field) {
            $sub_field['id'] = $this->field['id'] . '[' . $index . '][' . $sub_field['id'] . ']';
            $sub_value = $group_value[$sub_field['id']] ?? '';
            
            $renderer = new FieldRenderer(new \TMU\Fields\Storage\CustomTableStorage());
            $renderer->render($sub_field, $post_id);
        }
        
        echo '</div>';
    }
}
```

## Field Configuration Files

### Movie Fields Configuration (`includes/config/fields/movie.php`)
```php
<?php
return [
    'id' => 'movies',
    'title' => __('Movie Details', 'tmu'),
    'post_types' => ['movie'],
    'storage_type' => 'custom_table',
    'table' => 'tmu_movies',
    'fields' => [
        [
            'id' => 'tmdb_id',
            'name' => __('TMDB ID', 'tmu'),
            'type' => 'text',
            'columns' => 2,
        ],
        [
            'id' => 'release_date',
            'name' => __('Release Date', 'tmu'),
            'type' => 'date',
            'columns' => 4,
        ],
        [
            'id' => 'star_cast',
            'name' => __('Star Cast', 'tmu'),
            'type' => 'group',
            'clone' => true,
            'max_clone' => 4,
            'fields' => [
                [
                    'id' => 'person',
                    'name' => __('Person', 'tmu'),
                    'type' => 'post',
                    'post_type' => ['people'],
                    'columns' => 6,
                ],
                [
                    'id' => 'character',
                    'name' => __('Character', 'tmu'),
                    'type' => 'text',
                    'columns' => 6,
                ],
            ],
        ],
        // Additional fields...
    ],
];
```

## Admin Assets

### Field Management JavaScript (`assets/js/admin-fields.js`)
```javascript
jQuery(document).ready(function($) {
    // Clone functionality
    $('.tmu-add-clone').on('click', function() {
        const container = $(this).siblings('.tmu-clone-container');
        const template = $(this).siblings('.tmu-clone-template');
        const index = container.children('.tmu-group-instance').length;
        
        let clone = template.html().replace(/\{\{index\}\}/g, index);
        container.append('<div class="tmu-group-instance" data-index="' + index + '">' + clone + '</div>');
    });
    
    // Remove clone
    $(document).on('click', '.tmu-remove-clone', function() {
        $(this).closest('.tmu-group-instance').remove();
    });
    
    // Conditional fields
    $('[data-conditional]').each(function() {
        const condition = $(this).data('conditional');
        const field = $(this);
        
        function checkCondition() {
            const dependentField = $('[name="' + condition.field + '"]');
            const value = dependentField.val();
            
            if (condition.value === value) {
                field.show();
            } else {
                field.hide();
            }
        }
        
        $('[name="' + condition.field + '"]').on('change', checkCondition);
        checkCondition();
    });
});
```

### Field Styling (`assets/css/admin-fields.css`)
```css
.tmu-fields-container {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 15px;
    margin: 20px 0;
}

.tmu-field[data-columns="2"] { grid-column: span 2; }
.tmu-field[data-columns="4"] { grid-column: span 4; }
.tmu-field[data-columns="6"] { grid-column: span 6; }
.tmu-field[data-columns="12"] { grid-column: span 12; }

.tmu-group-field {
    grid-column: span 12;
    border: 1px solid #ddd;
    padding: 15px;
    margin: 10px 0;
}

.tmu-group-instance {
    border: 1px solid #eee;
    padding: 10px;
    margin: 5px 0;
    position: relative;
}

.tmu-remove-clone {
    position: absolute;
    top: 5px;
    right: 5px;
    background: #dc3232;
    color: white;
    border: none;
    padding: 2px 8px;
    cursor: pointer;
}

.tmu-add-clone {
    margin-top: 10px;
    background: #0073aa;
    color: white;
}
```

## Integration and Testing

### Integration with Theme Core
```php
// In ThemeCore loadDependencies:
require_once TMU_INCLUDES_DIR . '/classes/Fields/FieldManager.php';

// In initTheme method:
Fields\FieldManager::getInstance();
```

### Data Migration Test
```php
public function testFieldDataMigration(): void {
    // Test that existing Meta Box data is accessible
    $post_id = $this->factory->post->create(['post_type' => 'movie']);
    
    // Simulate existing data in custom table
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'tmu_movies',
        ['post_id' => $post_id, 'tmdb_id' => '12345'],
        ['%d', '%s']
    );
    
    $storage = new CustomTableStorage();
    $value = $storage->get($post_id, 'tmdb_id');
    
    $this->assertEquals('12345', $value);
}
```

## Next Steps

1. **[Step 08: Admin UI and Meta Boxes](./08_admin-ui-and-meta-boxes.md)** - Enhanced admin interface
2. **[Step 09: TMDB API Integration](./09_tmdb-api-integration.md)** - API integration system
3. **[Step 10: Frontend Templates](./10_frontend-templates.md)** - Display templates

## Verification Checklist

- [ ] FieldManager operational
- [ ] Custom table storage working
- [ ] All field types implemented
- [ ] Group/clone fields functional
- [ ] Conditional fields working
- [ ] Data validation active
- [ ] Admin assets loaded
- [ ] Migration compatibility verified
- [ ] Testing framework ready

---

This custom fields system maintains 100% compatibility with existing data while providing a modern, maintainable codebase that doesn't depend on external plugins.