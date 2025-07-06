<?php
/**
 * TMDB Sync Field
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
 * TMDB Sync Field Class
 */
class TmdbSyncField extends AbstractField {
    
    /**
     * TMDB content type (movie, tv, person)
     *
     * @var string
     */
    protected $tmdb_type = 'movie';
    
    /**
     * Auto-sync on save
     *
     * @var bool
     */
    protected $auto_sync = false;
    
    /**
     * Parse field arguments
     *
     * @param array $args Field arguments
     */
    protected function parseArgs(array $args): void {
        parent::parseArgs($args);
        
        $this->tmdb_type = $args['tmdb_type'] ?? 'movie';
        $this->auto_sync = $args['auto_sync'] ?? false;
    }
    
    /**
     * Render field HTML
     *
     * @param mixed $value Field value
     * @param string $name Field name
     * @return string
     */
    public function render($value, string $name): string {
        global $post;
        
        $attributes = $this->getInputAttributes($name);
        $attributes['type'] = 'text';
        $attributes['value'] = esc_attr($value);
        $attributes['placeholder'] = __('Enter TMDB ID or search...', 'tmu');
        
        $html = $this->renderWrapperStart();
        $html .= $this->renderLabel();
        
        // Input field container
        $html .= '<div class="tmu-tmdb-field-container">';
        $html .= sprintf('<input%s />', $this->renderAttributes($attributes));
        
        // Search button
        $html .= sprintf(
            '<button type="button" class="button tmu-tmdb-search" data-field-id="%s" data-tmdb-type="%s">%s</button>',
            esc_attr($this->id),
            esc_attr($this->tmdb_type),
            __('Search TMDB', 'tmu')
        );
        
        // Sync button (if TMDB ID exists)
        if ($value) {
            $html .= sprintf(
                '<button type="button" class="button button-primary tmu-tmdb-sync" data-field-id="%s" data-tmdb-type="%s" data-tmdb-id="%s" data-post-id="%d">%s</button>',
                esc_attr($this->id),
                esc_attr($this->tmdb_type),
                esc_attr($value),
                $post->ID ?? 0,
                __('Sync Data', 'tmu')
            );
        }
        
        $html .= '</div>';
        
        // Search results container
        $html .= '<div class="tmu-tmdb-search-results" style="display: none;"></div>';
        
        // Sync status
        $sync_status = get_post_meta($post->ID ?? 0, '_tmdb_sync_status', true);
        if ($sync_status) {
            $html .= sprintf(
                '<div class="tmu-tmdb-sync-status notice notice-%s inline"><p>%s</p></div>',
                $sync_status['type'] ?? 'info',
                esc_html($sync_status['message'] ?? '')
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
        // TMDB ID should be numeric
        return intval($value);
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
        // Add TMDB-specific validation
        if ($rule === 'tmdb_exists') {
            if ($value && !$this->verifyTmdbId($value)) {
                return new \WP_Error('tmdb_exists', sprintf(__('TMDB ID %s does not exist.', 'tmu'), $value));
            }
        }
        
        return parent::applyValidationRule($rule, $value, $params);
    }
    
    /**
     * Verify TMDB ID exists
     *
     * @param int $tmdb_id TMDB ID
     * @return bool
     */
    private function verifyTmdbId(int $tmdb_id): bool {
        // This would integrate with TMDB API (to be implemented in API step)
        // For now, just return true
        return true;
    }
}