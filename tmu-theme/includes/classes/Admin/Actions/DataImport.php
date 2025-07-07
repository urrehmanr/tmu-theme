<?php
/**
 * Data Import
 * 
 * Handles data import tools and utilities for TMU content management.
 * Provides bulk import functionality from various sources including CSV, JSON, and TMDB.
 * 
 * @package TMU\Admin\Actions
 * @since 1.0.0
 */

namespace TMU\Admin\Actions;

/**
 * DataImport class
 * 
 * Manages content import operations and tools
 */
class DataImport {
    
    /**
     * Supported import formats
     * @var array
     */
    private const SUPPORTED_FORMATS = ['csv', 'json', 'tmdb', 'xml'];
    
    /**
     * Maximum import batch size
     * @var int
     */
    private const BATCH_SIZE = 50;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->initHooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void {
        add_action('wp_ajax_tmu_import_data', [$this, 'handleImportRequest']);
        add_action('wp_ajax_tmu_import_progress', [$this, 'getImportProgress']);
        add_action('wp_ajax_tmu_validate_import', [$this, 'validateImportData']);
        add_action('wp_ajax_tmu_process_import_batch', [$this, 'processImportBatch']);
        
        // Add import pages to tools menu
        add_action('tmu_render_tools_page', [$this, 'renderImportTools']);
        add_action('tmu_render_import_tools', [$this, 'renderPostTypeImportTools'], 10, 1);
        
        // Handle file uploads
        add_action('wp_ajax_tmu_upload_import_file', [$this, 'handleFileUpload']);
        
        // Schedule batch processing
        add_action('tmu_process_import_batch', [$this, 'processBatchCallback'], 10, 3);
    }
    
    /**
     * Handle import request
     */
    public function handleImportRequest(): void {
        check_ajax_referer('tmu_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'tmu')]);
        }
        
        $import_type = sanitize_text_field($_POST['import_type'] ?? '');
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'movie');
        $source_data = $_POST['source_data'] ?? [];
        
        if (!in_array($import_type, self::SUPPORTED_FORMATS)) {
            wp_send_json_error(['message' => __('Unsupported import format', 'tmu')]);
        }
        
        try {
            $result = $this->processImport($import_type, $post_type, $source_data);
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Process import operation
     * 
     * @param string $import_type Import format type
     * @param string $post_type Target post type
     * @param array $source_data Source data for import
     * @return array Import result
     */
    private function processImport(string $import_type, string $post_type, array $source_data): array {
        $import_id = $this->generateImportId();
        
        // Store import job details
        $this->createImportJob($import_id, $import_type, $post_type, $source_data);
        
        // Process based on import type
        switch ($import_type) {
            case 'csv':
                return $this->processCsvImport($import_id, $post_type, $source_data);
            case 'json':
                return $this->processJsonImport($import_id, $post_type, $source_data);
            case 'tmdb':
                return $this->processTmdbImport($import_id, $post_type, $source_data);
            case 'xml':
                return $this->processXmlImport($import_id, $post_type, $source_data);
            default:
                throw new \Exception(__('Unsupported import type', 'tmu'));
        }
    }
    
    /**
     * Process CSV import
     * 
     * @param string $import_id Import job ID
     * @param string $post_type Target post type
     * @param array $source_data CSV data
     * @return array Import result
     */
    private function processCsvImport(string $import_id, string $post_type, array $source_data): array {
        if (!isset($source_data['file_path'])) {
            throw new \Exception(__('CSV file path not provided', 'tmu'));
        }
        
        $file_path = $source_data['file_path'];
        $mapping = $source_data['field_mapping'] ?? [];
        
        // Read and validate CSV
        $csv_data = $this->readCsvFile($file_path);
        $validated_data = $this->validateCsvData($csv_data, $mapping, $post_type);
        
        // Schedule batch processing
        $batches = array_chunk($validated_data, self::BATCH_SIZE);
        $total_batches = count($batches);
        
        foreach ($batches as $batch_index => $batch_data) {
            wp_schedule_single_event(
                time() + ($batch_index * 5), // Stagger by 5 seconds
                'tmu_process_import_batch',
                [$import_id, $batch_data, $post_type]
            );
        }
        
        return [
            'import_id' => $import_id,
            'total_items' => count($validated_data),
            'total_batches' => $total_batches,
            'message' => __('CSV import scheduled successfully', 'tmu')
        ];
    }
    
    /**
     * Process JSON import
     * 
     * @param string $import_id Import job ID
     * @param string $post_type Target post type
     * @param array $source_data JSON data
     * @return array Import result
     */
    private function processJsonImport(string $import_id, string $post_type, array $source_data): array {
        if (!isset($source_data['json_data'])) {
            throw new \Exception(__('JSON data not provided', 'tmu'));
        }
        
        $json_data = json_decode($source_data['json_data'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(__('Invalid JSON format', 'tmu'));
        }
        
        $validated_data = $this->validateJsonData($json_data, $post_type);
        
        // Schedule batch processing
        $batches = array_chunk($validated_data, self::BATCH_SIZE);
        $total_batches = count($batches);
        
        foreach ($batches as $batch_index => $batch_data) {
            wp_schedule_single_event(
                time() + ($batch_index * 5),
                'tmu_process_import_batch',
                [$import_id, $batch_data, $post_type]
            );
        }
        
        return [
            'import_id' => $import_id,
            'total_items' => count($validated_data),
            'total_batches' => $total_batches,
            'message' => __('JSON import scheduled successfully', 'tmu')
        ];
    }
    
    /**
     * Process TMDB import
     * 
     * @param string $import_id Import job ID
     * @param string $post_type Target post type
     * @param array $source_data TMDB parameters
     * @return array Import result
     */
    private function processTmdbImport(string $import_id, string $post_type, array $source_data): array {
        if (!isset($source_data['tmdb_ids']) && !isset($source_data['search_query'])) {
            throw new \Exception(__('TMDB IDs or search query required', 'tmu'));
        }
        
        $tmdb_ids = [];
        
        if (isset($source_data['tmdb_ids'])) {
            $tmdb_ids = array_map('intval', explode(',', $source_data['tmdb_ids']));
        } elseif (isset($source_data['search_query'])) {
            // Search TMDB for IDs
            $tmdb_ids = $this->searchTmdbIds($source_data['search_query'], $post_type);
        }
        
        // Validate TMDB IDs don't already exist
        $filtered_ids = $this->filterExistingTmdbIds($tmdb_ids, $post_type);
        
        // Schedule TMDB batch processing
        $batches = array_chunk($filtered_ids, 10); // Smaller batches for API calls
        $total_batches = count($batches);
        
        foreach ($batches as $batch_index => $batch_ids) {
            wp_schedule_single_event(
                time() + ($batch_index * 10), // Longer delay for API calls
                'tmu_process_import_batch',
                [$import_id, $batch_ids, $post_type]
            );
        }
        
        return [
            'import_id' => $import_id,
            'total_items' => count($filtered_ids),
            'total_batches' => $total_batches,
            'message' => __('TMDB import scheduled successfully', 'tmu')
        ];
    }
    
    /**
     * Process XML import
     * 
     * @param string $import_id Import job ID
     * @param string $post_type Target post type
     * @param array $source_data XML data
     * @return array Import result
     */
    private function processXmlImport(string $import_id, string $post_type, array $source_data): array {
        if (!isset($source_data['xml_data'])) {
            throw new \Exception(__('XML data not provided', 'tmu'));
        }
        
        $xml_data = simplexml_load_string($source_data['xml_data']);
        if ($xml_data === false) {
            throw new \Exception(__('Invalid XML format', 'tmu'));
        }
        
        $validated_data = $this->validateXmlData($xml_data, $post_type);
        
        // Schedule batch processing
        $batches = array_chunk($validated_data, self::BATCH_SIZE);
        $total_batches = count($batches);
        
        foreach ($batches as $batch_index => $batch_data) {
            wp_schedule_single_event(
                time() + ($batch_index * 5),
                'tmu_process_import_batch',
                [$import_id, $batch_data, $post_type]
            );
        }
        
        return [
            'import_id' => $import_id,
            'total_items' => count($validated_data),
            'total_batches' => $total_batches,
            'message' => __('XML import scheduled successfully', 'tmu')
        ];
    }
    
    /**
     * Process import batch callback
     * 
     * @param string $import_id Import job ID
     * @param array $batch_data Batch data to process
     * @param string $post_type Target post type
     */
    public function processBatchCallback(string $import_id, array $batch_data, string $post_type): void {
        $processed = 0;
        $errors = [];
        
        foreach ($batch_data as $item_data) {
            try {
                $this->createPostFromData($item_data, $post_type);
                $processed++;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        // Update import progress
        $this->updateImportProgress($import_id, $processed, $errors);
    }
    
    /**
     * Create post from import data
     * 
     * @param array $item_data Item data
     * @param string $post_type Post type
     * @return int Post ID
     */
    private function createPostFromData(array $item_data, string $post_type): int {
        $post_data = [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'post_title' => $item_data['title'] ?? __('Imported Content', 'tmu'),
            'post_content' => $item_data['content'] ?? '',
            'post_excerpt' => $item_data['excerpt'] ?? '',
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            throw new \Exception($post_id->get_error_message());
        }
        
        // Save meta data
        if (isset($item_data['meta'])) {
            foreach ($item_data['meta'] as $key => $value) {
                update_post_meta($post_id, $key, $value);
            }
        }
        
        // Set taxonomies
        if (isset($item_data['taxonomies'])) {
            foreach ($item_data['taxonomies'] as $taxonomy => $terms) {
                wp_set_object_terms($post_id, $terms, $taxonomy);
            }
        }
        
        return $post_id;
    }
    
    /**
     * Render import tools interface
     */
    public function renderImportTools(): void {
        ?>
        <div class="tmu-import-tools-section">
            <h2><?php _e('Data Import Tools', 'tmu'); ?></h2>
            <p><?php _e('Import content from various sources into your TMU database.', 'tmu'); ?></p>
            
            <div class="tmu-import-formats">
                <div class="import-format-card">
                    <h3><?php _e('CSV Import', 'tmu'); ?></h3>
                    <p><?php _e('Import content from CSV spreadsheet files.', 'tmu'); ?></p>
                    <button class="button" onclick="tmuOpenImportModal('csv')">
                        <?php _e('Import CSV', 'tmu'); ?>
                    </button>
                </div>
                
                <div class="import-format-card">
                    <h3><?php _e('JSON Import', 'tmu'); ?></h3>
                    <p><?php _e('Import content from JSON data files.', 'tmu'); ?></p>
                    <button class="button" onclick="tmuOpenImportModal('json')">
                        <?php _e('Import JSON', 'tmu'); ?>
                    </button>
                </div>
                
                <div class="import-format-card">
                    <h3><?php _e('TMDB Bulk Import', 'tmu'); ?></h3>
                    <p><?php _e('Import multiple items from TMDB database.', 'tmu'); ?></p>
                    <button class="button button-primary" onclick="tmuOpenImportModal('tmdb')">
                        <?php _e('Import from TMDB', 'tmu'); ?>
                    </button>
                </div>
                
                <div class="import-format-card">
                    <h3><?php _e('XML Import', 'tmu'); ?></h3>
                    <p><?php _e('Import content from XML data files.', 'tmu'); ?></p>
                    <button class="button" onclick="tmuOpenImportModal('xml')">
                        <?php _e('Import XML', 'tmu'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <style>
        .tmu-import-formats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .import-format-card {
            background: white;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
        }
        
        .import-format-card h3 {
            margin-top: 0;
            color: #1d2327;
        }
        
        .import-format-card p {
            color: #646970;
            margin-bottom: 15px;
        }
        </style>
        <?php
    }
    
    /**
     * Render post type specific import tools
     * 
     * @param string $post_type Post type
     */
    public function renderPostTypeImportTools(string $post_type): void {
        ?>
        <div class="tmu-post-type-import">
            <h3><?php printf(__('Import %s Content', 'tmu'), ucfirst($post_type)); ?></h3>
            
            <div class="import-options">
                <button class="button button-primary" onclick="tmuOpenPostTypeImport('<?php echo $post_type; ?>', 'tmdb')">
                    <?php printf(__('Import %s from TMDB', 'tmu'), ucfirst($post_type)); ?>
                </button>
                
                <button class="button" onclick="tmuOpenPostTypeImport('<?php echo $post_type; ?>', 'csv')">
                    <?php printf(__('Import %s from CSV', 'tmu'), ucfirst($post_type)); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get import progress
     */
    public function getImportProgress(): void {
        check_ajax_referer('tmu_admin_nonce', 'nonce');
        
        $import_id = sanitize_text_field($_GET['import_id'] ?? '');
        $progress = $this->getImportJobProgress($import_id);
        
        wp_send_json_success($progress);
    }
    
    /**
     * Validate import data
     */
    public function validateImportData(): void {
        check_ajax_referer('tmu_admin_nonce', 'nonce');
        
        $import_type = sanitize_text_field($_POST['import_type'] ?? '');
        $post_type = sanitize_text_field($_POST['post_type'] ?? '');
        $sample_data = $_POST['sample_data'] ?? [];
        
        try {
            $validation_result = $this->validateSampleData($import_type, $post_type, $sample_data);
            wp_send_json_success($validation_result);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle file upload
     */
    public function handleFileUpload(): void {
        check_ajax_referer('tmu_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'tmu')]);
        }
        
        if (!isset($_FILES['import_file'])) {
            wp_send_json_error(['message' => __('No file uploaded', 'tmu')]);
        }
        
        $file = $_FILES['import_file'];
        $upload = wp_handle_upload($file, ['test_form' => false]);
        
        if (isset($upload['error'])) {
            wp_send_json_error(['message' => $upload['error']]);
        }
        
        wp_send_json_success([
            'file_path' => $upload['file'],
            'file_url' => $upload['url'],
            'file_type' => $upload['type']
        ]);
    }
    
    /**
     * Helper methods for data processing
     */
    
    /**
     * Generate unique import ID
     * 
     * @return string Import ID
     */
    private function generateImportId(): string {
        return 'import_' . uniqid() . '_' . time();
    }
    
    /**
     * Create import job record
     * 
     * @param string $import_id Import ID
     * @param string $import_type Import type
     * @param string $post_type Post type
     * @param array $source_data Source data
     */
    private function createImportJob(string $import_id, string $import_type, string $post_type, array $source_data): void {
        $job_data = [
            'import_type' => $import_type,
            'post_type' => $post_type,
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'total_items' => 0,
            'processed_items' => 0,
            'errors' => []
        ];
        
        update_option("tmu_import_job_{$import_id}", $job_data);
    }
    
    /**
     * Update import progress
     * 
     * @param string $import_id Import ID
     * @param int $processed_count Processed items count
     * @param array $errors Error messages
     */
    private function updateImportProgress(string $import_id, int $processed_count, array $errors): void {
        $job_data = get_option("tmu_import_job_{$import_id}", []);
        
        if ($job_data) {
            $job_data['processed_items'] += $processed_count;
            $job_data['errors'] = array_merge($job_data['errors'], $errors);
            
            if ($job_data['processed_items'] >= $job_data['total_items']) {
                $job_data['status'] = 'completed';
                $job_data['completed_at'] = current_time('mysql');
            }
            
            update_option("tmu_import_job_{$import_id}", $job_data);
        }
    }
    
    /**
     * Get import job progress
     * 
     * @param string $import_id Import ID
     * @return array Progress data
     */
    private function getImportJobProgress(string $import_id): array {
        $job_data = get_option("tmu_import_job_{$import_id}", []);
        
        if (!$job_data) {
            return ['status' => 'not_found'];
        }
        
        $progress_percentage = $job_data['total_items'] > 0 
            ? round(($job_data['processed_items'] / $job_data['total_items']) * 100, 2)
            : 0;
        
        return [
            'status' => $job_data['status'],
            'total_items' => $job_data['total_items'],
            'processed_items' => $job_data['processed_items'],
            'progress_percentage' => $progress_percentage,
            'errors' => $job_data['errors'],
            'created_at' => $job_data['created_at']
        ];
    }
    
    /**
     * Read CSV file
     * 
     * @param string $file_path File path
     * @return array CSV data
     */
    private function readCsvFile(string $file_path): array {
        if (!file_exists($file_path)) {
            throw new \Exception(__('CSV file not found', 'tmu'));
        }
        
        $csv_data = [];
        $handle = fopen($file_path, 'r');
        
        if ($handle !== false) {
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $csv_data[] = array_combine($headers, $row);
            }
            fclose($handle);
        }
        
        return $csv_data;
    }
    
    /**
     * Validate CSV data
     * 
     * @param array $csv_data CSV data
     * @param array $mapping Field mapping
     * @param string $post_type Post type
     * @return array Validated data
     */
    private function validateCsvData(array $csv_data, array $mapping, string $post_type): array {
        $validated_data = [];
        
        foreach ($csv_data as $row) {
            $item_data = [];
            
            foreach ($mapping as $csv_field => $post_field) {
                if (isset($row[$csv_field])) {
                    $item_data[$post_field] = sanitize_text_field($row[$csv_field]);
                }
            }
            
            if (!empty($item_data)) {
                $validated_data[] = $item_data;
            }
        }
        
        return $validated_data;
    }
    
    /**
     * Validate JSON data
     * 
     * @param array $json_data JSON data
     * @param string $post_type Post type
     * @return array Validated data
     */
    private function validateJsonData(array $json_data, string $post_type): array {
        // Implement JSON validation logic
        return array_map(function($item) {
            return array_map('sanitize_text_field', $item);
        }, $json_data);
    }
    
    /**
     * Validate XML data
     * 
     * @param \SimpleXMLElement $xml_data XML data
     * @param string $post_type Post type
     * @return array Validated data
     */
    private function validateXmlData(\SimpleXMLElement $xml_data, string $post_type): array {
        // Implement XML validation logic
        $validated_data = [];
        
        foreach ($xml_data->children() as $item) {
            $item_data = [];
            foreach ($item as $key => $value) {
                $item_data[sanitize_key($key)] = sanitize_text_field((string)$value);
            }
            $validated_data[] = $item_data;
        }
        
        return $validated_data;
    }
    
    /**
     * Search TMDB IDs
     * 
     * @param string $query Search query
     * @param string $post_type Post type
     * @return array TMDB IDs
     */
    private function searchTmdbIds(string $query, string $post_type): array {
        // This would integrate with TMDB API
        // For now, return empty array
        return [];
    }
    
    /**
     * Filter existing TMDB IDs
     * 
     * @param array $tmdb_ids TMDB IDs
     * @param string $post_type Post type
     * @return array Filtered IDs
     */
    private function filterExistingTmdbIds(array $tmdb_ids, string $post_type): array {
        global $wpdb;
        
        $existing_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} pm 
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id 
             WHERE pm.meta_key = 'tmdb_id' 
             AND p.post_type = %s 
             AND pm.meta_value IN (" . implode(',', array_fill(0, count($tmdb_ids), '%d')) . ")",
            array_merge([$post_type], $tmdb_ids)
        ));
        
        return array_diff($tmdb_ids, array_map('intval', $existing_ids));
    }
    
    /**
     * Validate sample data
     * 
     * @param string $import_type Import type
     * @param string $post_type Post type
     * @param array $sample_data Sample data
     * @return array Validation result
     */
    private function validateSampleData(string $import_type, string $post_type, array $sample_data): array {
        $validation_result = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'field_mapping' => []
        ];
        
        // Implement validation logic based on import type
        switch ($import_type) {
            case 'csv':
                $validation_result = $this->validateCsvSample($sample_data, $post_type);
                break;
            case 'json':
                $validation_result = $this->validateJsonSample($sample_data, $post_type);
                break;
            case 'tmdb':
                $validation_result = $this->validateTmdbSample($sample_data, $post_type);
                break;
        }
        
        return $validation_result;
    }
    
    /**
     * Validate CSV sample
     * 
     * @param array $sample_data Sample data
     * @param string $post_type Post type
     * @return array Validation result
     */
    private function validateCsvSample(array $sample_data, string $post_type): array {
        // Implement CSV sample validation
        return [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'field_mapping' => []
        ];
    }
    
    /**
     * Validate JSON sample
     * 
     * @param array $sample_data Sample data
     * @param string $post_type Post type
     * @return array Validation result
     */
    private function validateJsonSample(array $sample_data, string $post_type): array {
        // Implement JSON sample validation
        return [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'field_mapping' => []
        ];
    }
    
    /**
     * Validate TMDB sample
     * 
     * @param array $sample_data Sample data
     * @param string $post_type Post type
     * @return array Validation result
     */
    private function validateTmdbSample(array $sample_data, string $post_type): array {
        // Implement TMDB sample validation
        return [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'field_mapping' => []
        ];
    }
}