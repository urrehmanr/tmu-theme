<?php
/**
 * File Upload Security
 * 
 * Comprehensive file upload security and validation.
 * Protects against malicious file uploads and ensures safe file handling.
 * 
 * @package TMU\Security
 * @since 1.0.0
 */

namespace TMU\Security;

/**
 * FileUploadSecurity class
 * 
 * Handles secure file upload validation and processing
 */
class FileUploadSecurity {
    
    /**
     * Allowed file types
     * @var array
     */
    private $allowed_types = [];
    
    /**
     * Maximum file size (in bytes)
     * @var int
     */
    private $max_file_size = 5242880; // 5MB
    
    /**
     * Dangerous file extensions
     * @var array
     */
    private $dangerous_extensions = [];
    
    /**
     * Allowed image types
     * @var array
     */
    private $allowed_image_types = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_allowed_types();
        $this->init_dangerous_extensions();
        $this->init_allowed_image_types();
        $this->init_hooks();
    }
    
    /**
     * Initialize allowed file types
     */
    private function init_allowed_types(): void {
        $this->allowed_types = [
            // Images
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'image/webp' => ['webp'],
            'image/bmp' => ['bmp'],
            'image/tiff' => ['tiff', 'tif'],
            
            // Documents (if needed)
            'application/pdf' => ['pdf'],
            'text/plain' => ['txt'],
            'text/csv' => ['csv'],
            
            // Archives (if needed)
            'application/zip' => ['zip'],
            'application/x-rar-compressed' => ['rar'],
        ];
        
        // Allow customization via filters
        $this->allowed_types = apply_filters('tmu_allowed_file_types', $this->allowed_types);
    }
    
    /**
     * Initialize dangerous extensions
     */
    private function init_dangerous_extensions(): void {
        $this->dangerous_extensions = [
            // Executable files
            'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'pht',
            'exe', 'com', 'bat', 'cmd', 'scr', 'pif',
            'msi', 'msp', 'msc',
            
            // Script files
            'js', 'vbs', 'vbe', 'ws', 'wsf', 'wsc', 'wsh',
            'ps1', 'ps1xml', 'ps2', 'ps2xml', 'psc1', 'psc2',
            'asp', 'aspx', 'ascx', 'ashx', 'asmx', 'cer',
            'jsp', 'jspx', 'jsw', 'jsv', 'jspf',
            'pl', 'py', 'rb', 'sh', 'cgi',
            
            // Server files
            'htaccess', 'htpasswd', 'ini', 'conf', 'config',
            'sql', 'sqlite', 'db',
            
            // Archive with executables
            'jar', 'war', 'ear',
            
            // Other dangerous files
            'svg', 'xml', 'xsl', 'xslt',
            'swf', 'fla',
            'dmg', 'iso', 'img',
        ];
        
        // Allow customization via filters
        $this->dangerous_extensions = apply_filters('tmu_dangerous_extensions', $this->dangerous_extensions);
    }
    
    /**
     * Initialize allowed image types
     */
    private function init_allowed_image_types(): void {
        $this->allowed_image_types = [
            IMAGETYPE_JPEG,
            IMAGETYPE_PNG,
            IMAGETYPE_GIF,
            IMAGETYPE_WEBP,
            IMAGETYPE_BMP,
            IMAGETYPE_TIFF_II,
            IMAGETYPE_TIFF_MM
        ];
        
        // Allow customization via filters
        $this->allowed_image_types = apply_filters('tmu_allowed_image_types', $this->allowed_image_types);
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        add_filter('wp_handle_upload_prefilter', [$this, 'validate_file_upload']);
        add_filter('upload_mimes', [$this, 'restrict_upload_mimes']);
        add_filter('wp_check_filetype_and_ext', [$this, 'check_filetype_and_ext'], 10, 4);
        
        // Additional upload security
        add_action('wp_handle_upload', [$this, 'post_upload_security_check']);
        add_filter('wp_handle_sideload_prefilter', [$this, 'validate_file_upload']);
        
        // Media library security
        add_filter('wp_generate_attachment_metadata', [$this, 'validate_uploaded_image'], 10, 2);
        
        // AJAX upload security
        add_action('wp_ajax_upload-attachment', [$this, 'validate_ajax_upload'], 1);
        add_action('wp_ajax_media-form', [$this, 'validate_ajax_upload'], 1);
        
        // Remove dangerous file types from media library
        add_action('admin_init', [$this, 'remove_dangerous_uploads']);
        
        // File URL security
        add_filter('wp_get_attachment_url', [$this, 'secure_attachment_url']);
        add_filter('attachment_link', [$this, 'secure_attachment_link']);
        
        // Upload directory security
        add_action('wp_loaded', [$this, 'secure_upload_directory']);
    }
    
    /**
     * Validate file upload
     */
    public function validate_file_upload($file): array {
        // Check if file is provided
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $file['error'] = __('Invalid file upload.', 'tmu-theme');
            return $file;
        }
        
        // Check file size
        if ($file['size'] > $this->max_file_size) {
            $file['error'] = sprintf(
                __('File size exceeds maximum allowed size of %s.', 'tmu-theme'),
                size_format($this->max_file_size)
            );
            return $file;
        }
        
        // Check if file is empty
        if ($file['size'] <= 0) {
            $file['error'] = __('File is empty.', 'tmu-theme');
            return $file;
        }
        
        // Validate file extension
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($file_ext, $this->dangerous_extensions)) {
            $file['error'] = __('File type not allowed for security reasons.', 'tmu-theme');
            $this->log_security_event('dangerous_file_upload_attempt', [
                'filename' => $file['name'],
                'extension' => $file_ext,
                'size' => $file['size'],
                'type' => $file['type']
            ]);
            return $file;
        }
        
        // Validate MIME type
        if (!$this->is_allowed_mime_type($file['type'])) {
            $file['error'] = __('File type not allowed.', 'tmu-theme');
            return $file;
        }
        
        // Validate file extension against MIME type
        if (!$this->validate_extension_mime_match($file_ext, $file['type'])) {
            $file['error'] = __('File extension does not match file type.', 'tmu-theme');
            return $file;
        }
        
        // Validate file content
        if (!$this->validate_file_content($file['tmp_name'], $file['type'])) {
            $file['error'] = __('File content validation failed.', 'tmu-theme');
            return $file;
        }
        
        // Scan for malicious content
        if (!$this->scan_file_content($file['tmp_name'])) {
            $file['error'] = __('File contains potentially malicious content.', 'tmu-theme');
            $this->log_security_event('malicious_file_upload_attempt', [
                'filename' => $file['name'],
                'size' => $file['size'],
                'type' => $file['type']
            ]);
            return $file;
        }
        
        // Rename file for security
        $file['name'] = $this->sanitize_filename($file['name']);
        
        return $file;
    }
    
    /**
     * Restrict upload MIME types
     */
    public function restrict_upload_mimes($mimes): array {
        // Remove potentially dangerous file types
        $dangerous_mimes = [
            'exe' => 'application/x-msdownload',
            'php' => 'application/x-httpd-php',
            'php3' => 'application/x-httpd-php',
            'php4' => 'application/x-httpd-php',
            'php5' => 'application/x-httpd-php',
            'phtml' => 'application/x-httpd-php',
            'js' => 'application/javascript',
            'swf' => 'application/x-shockwave-flash',
            'class' => 'application/java',
            'jar' => 'application/java-archive',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml'
        ];
        
        foreach ($dangerous_mimes as $ext => $mime) {
            unset($mimes[$ext]);
        }
        
        // Only allow specific types based on configuration
        $allowed_mimes = [];
        foreach ($this->allowed_types as $mime_type => $extensions) {
            foreach ($extensions as $ext) {
                $allowed_mimes[$ext] = $mime_type;
            }
        }
        
        return array_intersect($mimes, $allowed_mimes);
    }
    
    /**
     * Check filetype and extension
     */
    public function check_filetype_and_ext($data, $file, $filename, $mimes): array {
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Additional security check
        if (in_array($file_ext, $this->dangerous_extensions)) {
            $data['ext'] = false;
            $data['type'] = false;
        }
        
        // Validate image files
        if (strpos($data['type'], 'image/') === 0 && !$this->is_valid_image($file)) {
            $data['ext'] = false;
            $data['type'] = false;
        }
        
        return $data;
    }
    
    /**
     * Post upload security check
     */
    public function post_upload_security_check($upload): array {
        if (isset($upload['file']) && file_exists($upload['file'])) {
            // Additional content validation after upload
            if (!$this->validate_uploaded_file($upload['file'])) {
                unlink($upload['file']);
                $upload['error'] = __('File failed post-upload security validation.', 'tmu-theme');
            }
        }
        
        return $upload;
    }
    
    /**
     * Validate uploaded image
     */
    public function validate_uploaded_image($metadata, $attachment_id): array {
        $file_path = get_attached_file($attachment_id);
        
        if ($file_path && file_exists($file_path)) {
            // Check if it's really an image
            if (!$this->is_valid_image($file_path)) {
                wp_delete_attachment($attachment_id, true);
                $this->log_security_event('invalid_image_upload', [
                    'attachment_id' => $attachment_id,
                    'file_path' => $file_path
                ]);
            }
        }
        
        return $metadata;
    }
    
    /**
     * Validate AJAX upload
     */
    public function validate_ajax_upload(): void {
        if (!current_user_can('upload_files')) {
            wp_die(__('You do not have permission to upload files.', 'tmu-theme'));
        }
        
        // Additional AJAX-specific validation can be added here
    }
    
    /**
     * Remove dangerous uploads from admin
     */
    public function remove_dangerous_uploads(): void {
        if (is_admin()) {
            // Check for and remove any dangerous files that might have been uploaded
            $uploads = wp_upload_dir();
            $this->scan_upload_directory($uploads['basedir']);
        }
    }
    
    /**
     * Secure attachment URL
     */
    public function secure_attachment_url($url): string {
        // Add additional security parameters or checks
        if ($this->is_sensitive_file($url)) {
            // Return protected URL or deny access
            return '';
        }
        
        return $url;
    }
    
    /**
     * Secure attachment link
     */
    public function secure_attachment_link($link): string {
        // Additional link security
        return $link;
    }
    
    /**
     * Secure upload directory
     */
    public function secure_upload_directory(): void {
        $uploads = wp_upload_dir();
        
        // Create .htaccess file in uploads directory
        $htaccess_file = $uploads['basedir'] . '/.htaccess';
        
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "# TMU Security - Disable PHP execution\n";
            $htaccess_content .= "<Files *.php>\n";
            $htaccess_content .= "deny from all\n";
            $htaccess_content .= "</Files>\n";
            $htaccess_content .= "<Files *.phtml>\n";
            $htaccess_content .= "deny from all\n";
            $htaccess_content .= "</Files>\n";
            $htaccess_content .= "<Files *.php3>\n";
            $htaccess_content .= "deny from all\n";
            $htaccess_content .= "</Files>\n";
            $htaccess_content .= "<Files *.php4>\n";
            $htaccess_content .= "deny from all\n";
            $htaccess_content .= "</Files>\n";
            $htaccess_content .= "<Files *.php5>\n";
            $htaccess_content .= "deny from all\n";
            $htaccess_content .= "</Files>\n";
            
            file_put_contents($htaccess_file, $htaccess_content);
        }
        
        // Create index.html to prevent directory listing
        $index_file = $uploads['basedir'] . '/index.html';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<!-- Silence is golden -->');
        }
    }
    
    /**
     * Check if MIME type is allowed
     */
    private function is_allowed_mime_type($mime_type): bool {
        return array_key_exists($mime_type, $this->allowed_types);
    }
    
    /**
     * Validate extension matches MIME type
     */
    private function validate_extension_mime_match($extension, $mime_type): bool {
        if (!isset($this->allowed_types[$mime_type])) {
            return false;
        }
        
        return in_array($extension, $this->allowed_types[$mime_type]);
    }
    
    /**
     * Validate file content
     */
    private function validate_file_content($file_path, $mime_type): bool {
        // For images, validate using getimagesize
        if (strpos($mime_type, 'image/') === 0) {
            return $this->is_valid_image($file_path);
        }
        
        // For other files, perform basic validation
        return $this->validate_generic_file($file_path);
    }
    
    /**
     * Check if file is a valid image
     */
    private function is_valid_image($file_path): bool {
        $image_info = getimagesize($file_path);
        
        if ($image_info === false) {
            return false;
        }
        
        // Check if image type is allowed
        if (!in_array($image_info[2], $this->allowed_image_types)) {
            return false;
        }
        
        // Additional validation for image dimensions
        if ($image_info[0] <= 0 || $image_info[1] <= 0) {
            return false;
        }
        
        // Check for unreasonably large images
        $max_width = apply_filters('tmu_max_image_width', 10000);
        $max_height = apply_filters('tmu_max_image_height', 10000);
        
        if ($image_info[0] > $max_width || $image_info[1] > $max_height) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate generic file
     */
    private function validate_generic_file($file_path): bool {
        // Read first few bytes to check for malicious content
        $handle = fopen($file_path, 'rb');
        if (!$handle) {
            return false;
        }
        
        $first_bytes = fread($handle, 1024);
        fclose($handle);
        
        // Check for PHP tags
        if (strpos($first_bytes, '<?php') !== false || 
            strpos($first_bytes, '<?=') !== false ||
            strpos($first_bytes, '<script') !== false) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Scan file for malicious content
     */
    private function scan_file_content($file_path): bool {
        $content = file_get_contents($file_path, false, null, 0, 8192); // Read first 8KB
        
        if ($content === false) {
            return false;
        }
        
        // Check for malicious patterns
        $malicious_patterns = [
            '/\<\?php/i',
            '/\<\?=/i',
            '/\<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec\s*\(/i',
            '/passthru\s*\(/i',
            '/file_get_contents\s*\(/i',
            '/fopen\s*\(/i',
            '/fwrite\s*\(/i',
            '/base64_decode\s*\(/i'
        ];
        
        foreach ($malicious_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Sanitize filename
     */
    private function sanitize_filename($filename): string {
        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', $filename);
        
        // Ensure filename is not empty
        if (empty($filename)) {
            $filename = 'file_' . time();
        }
        
        // Add random suffix to prevent conflicts and enhance security
        $pathinfo = pathinfo($filename);
        $name = $pathinfo['filename'] . '_' . wp_generate_password(8, false, false);
        $extension = isset($pathinfo['extension']) ? '.' . $pathinfo['extension'] : '';
        
        return $name . $extension;
    }
    
    /**
     * Validate uploaded file after upload
     */
    private function validate_uploaded_file($file_path): bool {
        // Additional post-upload validation
        if (!file_exists($file_path)) {
            return false;
        }
        
        // Check file permissions
        if (!is_readable($file_path)) {
            return false;
        }
        
        // Re-validate content
        return $this->scan_file_content($file_path);
    }
    
    /**
     * Scan upload directory for dangerous files
     */
    private function scan_upload_directory($directory): void {
        if (!is_dir($directory)) {
            return;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                
                if (in_array($extension, $this->dangerous_extensions)) {
                    // Log and potentially remove dangerous file
                    $this->log_security_event('dangerous_file_found', [
                        'file_path' => $file->getPathname(),
                        'extension' => $extension
                    ]);
                    
                    // Optionally remove the file
                    if (apply_filters('tmu_auto_remove_dangerous_files', false)) {
                        unlink($file->getPathname());
                    }
                }
            }
        }
    }
    
    /**
     * Check if file is sensitive
     */
    private function is_sensitive_file($url): bool {
        $sensitive_patterns = [
            '/\.php$/i',
            '/\.phtml$/i',
            '/\.php3$/i',
            '/\.php4$/i',
            '/\.php5$/i',
            '/config/i',
            '/\.htaccess$/i',
            '/\.htpasswd$/i'
        ];
        
        foreach ($sensitive_patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log security event
     */
    private function log_security_event($type, $data): void {
        do_action('tmu_security_event', $type, array_merge($data, [
            'ip' => $this->get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'user_id' => get_current_user_id()
        ]), 'high');
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip(): string {
        $ip_headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get allowed file types
     */
    public function get_allowed_types(): array {
        return $this->allowed_types;
    }
    
    /**
     * Set maximum file size
     */
    public function set_max_file_size($size): void {
        $this->max_file_size = max(1024, intval($size)); // Minimum 1KB
    }
    
    /**
     * Get maximum file size
     */
    public function get_max_file_size(): int {
        return $this->max_file_size;
    }
    
    /**
     * Add allowed file type
     */
    public function add_allowed_type($mime_type, $extensions): void {
        $this->allowed_types[$mime_type] = (array) $extensions;
    }
    
    /**
     * Remove allowed file type
     */
    public function remove_allowed_type($mime_type): void {
        unset($this->allowed_types[$mime_type]);
    }
    
    /**
     * Check if extension is dangerous
     */
    public function is_dangerous_extension($extension): bool {
        return in_array(strtolower($extension), $this->dangerous_extensions);
    }
    
    /**
     * Add dangerous extension
     */
    public function add_dangerous_extension($extension): void {
        $this->dangerous_extensions[] = strtolower($extension);
        $this->dangerous_extensions = array_unique($this->dangerous_extensions);
    }
    
    /**
     * Remove dangerous extension
     */
    public function remove_dangerous_extension($extension): void {
        $key = array_search(strtolower($extension), $this->dangerous_extensions);
        if ($key !== false) {
            unset($this->dangerous_extensions[$key]);
        }
    }
}