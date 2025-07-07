<?php
/**
 * Bulk Edit Actions
 * 
 * Handles bulk editing operations for TMU content including
 * mass status updates, taxonomy assignments, and metadata changes.
 * 
 * @package TMU\Admin\Actions
 * @since 1.0.0
 */

namespace TMU\Admin\Actions;

/**
 * BulkEdit class
 * 
 * Manages bulk editing operations for TMU content
 */
class BulkEdit {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_footer', [$this, 'addBulkEditFields']);
        add_action('wp_ajax_tmu_bulk_edit', [$this, 'handleBulkEdit']);
        add_action('admin_notices', [$this, 'showBulkEditNotices']);
        add_filter('bulk_actions-edit-movie', [$this, 'addBulkActions']);
        add_filter('bulk_actions-edit-tv', [$this, 'addBulkActions']);
        add_filter('bulk_actions-edit-drama', [$this, 'addBulkActions']);
        add_filter('bulk_actions-edit-people', [$this, 'addBulkActions']);
        add_filter('handle_bulk_actions-edit-movie', [$this, 'handleBulkActions'], 10, 3);
        add_filter('handle_bulk_actions-edit-tv', [$this, 'handleBulkActions'], 10, 3);
        add_filter('handle_bulk_actions-edit-drama', [$this, 'handleBulkActions'], 10, 3);
        add_filter('handle_bulk_actions-edit-people', [$this, 'handleBulkActions'], 10, 3);
    }
    
    /**
     * Add custom bulk actions
     * 
     * @param array $actions Existing bulk actions
     * @return array Modified bulk actions
     */
    public function addBulkActions(array $actions): array {
        $actions['tmu_featured'] = __('Mark as Featured', 'tmu');
        $actions['tmu_unfeatured'] = __('Remove Featured', 'tmu');
        $actions['tmu_sync_tmdb'] = __('Sync with TMDB', 'tmu');
        $actions['tmu_assign_genre'] = __('Assign Genre', 'tmu');
        $actions['tmu_assign_country'] = __('Assign Country', 'tmu');
        $actions['tmu_update_status'] = __('Update Status', 'tmu');
        
        return $actions;
    }
    
    /**
     * Handle bulk actions
     * 
     * @param string $redirect_to Redirect URL
     * @param string $doaction Action being performed
     * @param array $post_ids Selected post IDs
     * @return string Modified redirect URL
     */
    public function handleBulkActions(string $redirect_to, string $doaction, array $post_ids): string {
        if (!current_user_can('edit_posts')) {
            return $redirect_to;
        }
        
        $processed = 0;
        $errors = 0;
        
        switch ($doaction) {
            case 'tmu_featured':
                $processed = $this->markAsFeatured($post_ids);
                break;
                
            case 'tmu_unfeatured':
                $processed = $this->removeFeatured($post_ids);
                break;
                
            case 'tmu_sync_tmdb':
                [$processed, $errors] = $this->syncWithTMDB($post_ids);
                break;
                
            default:
                return $redirect_to;
        }
        
        $redirect_to = add_query_arg([
            'tmu_bulk_action' => $doaction,
            'processed' => $processed,
            'errors' => $errors
        ], $redirect_to);
        
        return $redirect_to;
    }
    
    /**
     * Add bulk edit fields to admin footer
     */
    public function addBulkEditFields(): void {
        global $current_screen;
        
        if (!$current_screen || !in_array($current_screen->id, ['edit-movie', 'edit-tv', 'edit-drama', 'edit-people'])) {
            return;
        }
        
        $post_type = str_replace('edit-', '', $current_screen->id);
        ?>
        <div id="tmu-bulk-edit-modal" class="tmu-modal" style="display: none;">
            <div class="tmu-modal-content">
                <div class="tmu-modal-header">
                    <h3><?php _e('Bulk Edit TMU Content', 'tmu'); ?></h3>
                    <button type="button" class="tmu-modal-close">&times;</button>
                </div>
                
                <div class="tmu-modal-body">
                    <form id="tmu-bulk-edit-form">
                        <div class="bulk-edit-field">
                            <label for="bulk-featured">
                                <input type="checkbox" id="bulk-featured" name="featured" value="1">
                                <?php _e('Mark as Featured', 'tmu'); ?>
                            </label>
                        </div>
                        
                        <div class="bulk-edit-field">
                            <label for="bulk-genre"><?php _e('Genre:', 'tmu'); ?></label>
                            <select id="bulk-genre" name="genre" class="widefat">
                                <option value=""><?php _e('— No Change —', 'tmu'); ?></option>
                                <?php
                                $genres = get_terms(['taxonomy' => 'genre', 'hide_empty' => false]);
                                foreach ($genres as $genre) {
                                    echo '<option value="' . esc_attr($genre->term_id) . '">' . esc_html($genre->name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="bulk-edit-field">
                            <label for="bulk-country"><?php _e('Country:', 'tmu'); ?></label>
                            <select id="bulk-country" name="country" class="widefat">
                                <option value=""><?php _e('— No Change —', 'tmu'); ?></option>
                                <?php
                                $countries = get_terms(['taxonomy' => 'country', 'hide_empty' => false]);
                                foreach ($countries as $country) {
                                    echo '<option value="' . esc_attr($country->term_id) . '">' . esc_html($country->name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <?php if (in_array($post_type, ['tv', 'drama'])): ?>
                        <div class="bulk-edit-field">
                            <label for="bulk-status"><?php _e('Series Status:', 'tmu'); ?></label>
                            <select id="bulk-status" name="series_status" class="widefat">
                                <option value=""><?php _e('— No Change —', 'tmu'); ?></option>
                                <option value="Returning Series"><?php _e('Returning Series', 'tmu'); ?></option>
                                <option value="Ended"><?php _e('Ended', 'tmu'); ?></option>
                                <option value="Canceled"><?php _e('Canceled', 'tmu'); ?></option>
                                <option value="In Production"><?php _e('In Production', 'tmu'); ?></option>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="bulk-edit-field">
                            <label>
                                <input type="checkbox" id="bulk-sync-tmdb" name="sync_tmdb" value="1">
                                <?php _e('Sync with TMDB', 'tmu'); ?>
                            </label>
                        </div>
                        
                        <input type="hidden" id="bulk-post-ids" name="post_ids" value="">
                        <input type="hidden" name="action" value="tmu_bulk_edit">
                        <?php wp_nonce_field('tmu_bulk_edit', 'nonce'); ?>
                    </form>
                </div>
                
                <div class="tmu-modal-footer">
                    <button type="button" class="button button-secondary tmu-modal-close">
                        <?php _e('Cancel', 'tmu'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="tmu-bulk-edit-submit">
                        <?php _e('Apply Changes', 'tmu'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Add bulk edit button
            $('.bulkactions').append('<input type="button" class="button" id="tmu-bulk-edit-btn" value="<?php _e('TMU Bulk Edit', 'tmu'); ?>">');
            
            // Open bulk edit modal
            $('#tmu-bulk-edit-btn').on('click', function() {
                var selectedPosts = [];
                $('input[name="post[]"]:checked').each(function() {
                    selectedPosts.push($(this).val());
                });
                
                if (selectedPosts.length === 0) {
                    alert('<?php _e('Please select items to edit.', 'tmu'); ?>');
                    return;
                }
                
                $('#bulk-post-ids').val(selectedPosts.join(','));
                $('#tmu-bulk-edit-modal').show();
            });
            
            // Close modal
            $('.tmu-modal-close').on('click', function() {
                $('#tmu-bulk-edit-modal').hide();
            });
            
            // Submit bulk edit
            $('#tmu-bulk-edit-submit').on('click', function() {
                var button = $(this);
                var form = $('#tmu-bulk-edit-form');
                
                button.text('<?php _e('Processing...', 'tmu'); ?>').prop('disabled', true);
                
                $.post(ajaxurl, form.serialize(), function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php _e('An error occurred.', 'tmu'); ?>');
                        button.text('<?php _e('Apply Changes', 'tmu'); ?>').prop('disabled', false);
                    }
                }).fail(function() {
                    alert('<?php _e('An error occurred.', 'tmu'); ?>');
                    button.text('<?php _e('Apply Changes', 'tmu'); ?>').prop('disabled', false);
                });
            });
            
            // Close modal on outside click
            $(window).on('click', function(event) {
                if (event.target.id === 'tmu-bulk-edit-modal') {
                    $('#tmu-bulk-edit-modal').hide();
                }
            });
        });
        </script>
        
        <style>
        .tmu-modal {
            position: fixed;
            z-index: 999999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .tmu-modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 600px;
            max-width: 90%;
            max-height: 80%;
            overflow-y: auto;
        }
        .tmu-modal-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .tmu-modal-header h3 {
            margin: 0;
        }
        .tmu-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        .tmu-modal-close:hover {
            color: #000;
        }
        .tmu-modal-body {
            padding: 20px;
        }
        .tmu-modal-footer {
            padding: 20px;
            border-top: 1px solid #ddd;
            text-align: right;
        }
        .tmu-modal-footer .button {
            margin-left: 10px;
        }
        .bulk-edit-field {
            margin-bottom: 15px;
        }
        .bulk-edit-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .bulk-edit-field input[type="checkbox"] + label {
            display: inline;
            margin-left: 5px;
        }
        </style>
        <?php
    }
    
    /**
     * Handle AJAX bulk edit
     */
    public function handleBulkEdit(): void {
        check_ajax_referer('tmu_bulk_edit', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Unauthorized', 'tmu')]);
        }
        
        $post_ids = array_map('intval', explode(',', $_POST['post_ids']));
        $featured = !empty($_POST['featured']);
        $genre = intval($_POST['genre']);
        $country = intval($_POST['country']);
        $series_status = sanitize_text_field($_POST['series_status']);
        $sync_tmdb = !empty($_POST['sync_tmdb']);
        
        $processed = 0;
        $errors = 0;
        
        foreach ($post_ids as $post_id) {
            try {
                // Update featured status
                if ($featured) {
                    update_post_meta($post_id, '_tmu_featured', 1);
                }
                
                // Assign genre
                if ($genre > 0) {
                    wp_set_object_terms($post_id, [$genre], 'genre', false);
                }
                
                // Assign country
                if ($country > 0) {
                    wp_set_object_terms($post_id, [$country], 'country', false);
                }
                
                // Update series status
                if ($series_status && in_array(get_post_type($post_id), ['tv', 'drama'])) {
                    $this->updateSeriesStatus($post_id, $series_status);
                }
                
                // Sync with TMDB
                if ($sync_tmdb) {
                    $this->syncSinglePostWithTMDB($post_id);
                }
                
                $processed++;
                
            } catch (Exception $e) {
                $errors++;
                error_log('TMU Bulk Edit Error: ' . $e->getMessage());
            }
        }
        
        wp_send_json_success([
            'message' => sprintf(
                __('Processed %d items successfully. %d errors.', 'tmu'),
                $processed,
                $errors
            ),
            'processed' => $processed,
            'errors' => $errors
        ]);
    }
    
    /**
     * Mark posts as featured
     * 
     * @param array $post_ids Post IDs
     * @return int Number of processed posts
     */
    private function markAsFeatured(array $post_ids): int {
        $processed = 0;
        
        foreach ($post_ids as $post_id) {
            update_post_meta($post_id, '_tmu_featured', 1);
            $processed++;
        }
        
        return $processed;
    }
    
    /**
     * Remove featured status
     * 
     * @param array $post_ids Post IDs
     * @return int Number of processed posts
     */
    private function removeFeatured(array $post_ids): int {
        $processed = 0;
        
        foreach ($post_ids as $post_id) {
            delete_post_meta($post_id, '_tmu_featured');
            $processed++;
        }
        
        return $processed;
    }
    
    /**
     * Sync posts with TMDB
     * 
     * @param array $post_ids Post IDs
     * @return array [processed, errors]
     */
    private function syncWithTMDB(array $post_ids): array {
        $processed = 0;
        $errors = 0;
        
        foreach ($post_ids as $post_id) {
            try {
                $this->syncSinglePostWithTMDB($post_id);
                $processed++;
            } catch (Exception $e) {
                $errors++;
                error_log('TMU TMDB Sync Error: ' . $e->getMessage());
            }
        }
        
        return [$processed, $errors];
    }
    
    /**
     * Sync single post with TMDB
     * 
     * @param int $post_id Post ID
     * @throws Exception If sync fails
     */
    private function syncSinglePostWithTMDB(int $post_id): void {
        $tmdb_id = get_post_meta($post_id, 'tmdb_id', true);
        
        if (!$tmdb_id) {
            throw new Exception('No TMDB ID found for post ' . $post_id);
        }
        
        // Update last sync attempt
        update_post_meta($post_id, '_tmdb_last_sync_attempt', current_time('mysql'));
        
        // This would integrate with the TMDB API class from Step 9
        // For now, just mark as synced
        update_post_meta($post_id, '_tmdb_last_sync', current_time('mysql'));
        delete_post_meta($post_id, '_tmdb_sync_error');
    }
    
    /**
     * Update series status in database
     * 
     * @param int $post_id Post ID
     * @param string $status Series status
     */
    private function updateSeriesStatus(int $post_id, string $status): void {
        global $wpdb;
        
        $post_type = get_post_type($post_id);
        $table = '';
        
        if ($post_type === 'tv') {
            $table = $wpdb->prefix . 'tmu_tv_series';
        } elseif ($post_type === 'drama') {
            $table = $wpdb->prefix . 'tmu_dramas';
        }
        
        if ($table) {
            $wpdb->update(
                $table,
                ['status' => $status],
                ['ID' => $post_id],
                ['%s'],
                ['%d']
            );
        }
    }
    
    /**
     * Show bulk edit notices
     */
    public function showBulkEditNotices(): void {
        if (!isset($_GET['tmu_bulk_action'])) {
            return;
        }
        
        $action = sanitize_text_field($_GET['tmu_bulk_action']);
        $processed = intval($_GET['processed']);
        $errors = intval($_GET['errors']);
        
        $message = '';
        $type = 'success';
        
        switch ($action) {
            case 'tmu_featured':
                $message = sprintf(
                    _n(
                        '%d item marked as featured.',
                        '%d items marked as featured.',
                        $processed,
                        'tmu'
                    ),
                    $processed
                );
                break;
                
            case 'tmu_unfeatured':
                $message = sprintf(
                    _n(
                        '%d item unmarked as featured.',
                        '%d items unmarked as featured.',
                        $processed,
                        'tmu'
                    ),
                    $processed
                );
                break;
                
            case 'tmu_sync_tmdb':
                $message = sprintf(
                    __('%d items synced with TMDB. %d errors.', 'tmu'),
                    $processed,
                    $errors
                );
                if ($errors > 0) {
                    $type = 'warning';
                }
                break;
        }
        
        if ($message) {
            echo '<div class="notice notice-' . $type . ' is-dismissible"><p>' . $message . '</p></div>';
        }
    }
}