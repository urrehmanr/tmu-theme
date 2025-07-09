<?php
/**
 * TMU Theme Autoloader
 *
 * @package TMU
 * @version 1.0.0
 */

namespace TMU;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PSR-4 Compliant Autoloader
 */
class Autoloader {
    
    /**
     * Namespace prefix for this autoloader
     *
     * @var string
     */
    private $prefix = 'TMU\\';
    
    /**
     * Base directory for the namespace prefix
     *
     * @var string
     */
    private $base_dir;
    
    /**
     * Registered namespaces and their paths
     *
     * @var array
     */
    private $namespaces = [];
    
    /**
     * Class aliases for backward compatibility
     *
     * @var array
     */
    private $aliases = [];
    
    /**
     * Constructor
     *
     * @param string $base_dir Base directory for the namespace prefix
     */
    public function __construct(string $base_dir = null) {
        $this->base_dir = $base_dir ?: TMU_INCLUDES_DIR . '/classes/';
        $this->setupDefaultNamespaces();
        $this->setupAliases();
    }
    
    /**
     * Register autoloader with SPL
     *
     * @param bool $prepend Whether to prepend the autoloader
     */
    public function register(bool $prepend = false): void {
        spl_autoload_register([$this, 'loadClass'], true, $prepend);
    }
    
    /**
     * Unregister autoloader
     */
    public function unregister(): void {
        spl_autoload_unregister([$this, 'loadClass']);
    }
    
    /**
     * Setup default namespaces
     */
    private function setupDefaultNamespaces(): void {
        $this->namespaces = [
            'TMU\\Core\\' => $this->base_dir . 'Core/',
            'TMU\\Admin\\' => $this->base_dir . 'Admin/',
            'TMU\\API\\' => $this->base_dir . 'API/',
            'TMU\\Config\\' => $this->base_dir . 'Config/',
            'TMU\\Database\\' => $this->base_dir . 'Database/',
            'TMU\\Fields\\' => $this->base_dir . 'Fields/',
            'TMU\\Frontend\\' => $this->base_dir . 'Frontend/',
            'TMU\\PostTypes\\' => $this->base_dir . 'PostTypes/',
            'TMU\\Taxonomies\\' => $this->base_dir . 'Taxonomies/',
            'TMU\\Utils\\' => $this->base_dir . 'Utils/',
            'TMU\\Migration\\' => $this->base_dir . 'Migration/',
            'TMU\\Testing\\' => $this->base_dir . 'Testing/',
            'TMU\\Performance\\' => $this->base_dir . 'Performance/',
            'TMU\\SEO\\' => $this->base_dir . 'SEO/',
            'TMU\\Search\\' => $this->base_dir . 'Search/',
            'TMU\\Blocks\\' => $this->base_dir . 'Blocks/',
            'TMU\\Backup\\' => $this->base_dir . 'Backup/',
            'TMU\\Maintenance\\' => $this->base_dir . 'Maintenance/',
            'TMU\\Updates\\' => $this->base_dir . 'Updates/',
            'TMU\\' => $this->base_dir,
        ];
    }
    
    /**
     * Setup class aliases for backward compatibility
     */
    private function setupAliases(): void {
        $this->aliases = [
            // Legacy plugin class mappings if needed
            'TMU_Settings' => 'TMU\\Admin\\Settings',
            'TMU_PostTypes' => 'TMU\\PostTypes\\PostTypeManager',
            'TMU_Taxonomies' => 'TMU\\Taxonomies\\TaxonomyManager',
        ];
    }
    
    /**
     * Add a namespace
     *
     * @param string $prefix The namespace prefix
     * @param string $base_dir Base directory for the namespace
     * @param bool $prepend Whether to prepend the namespace
     */
    public function addNamespace(string $prefix, string $base_dir, bool $prepend = false): void {
        // Normalize namespace prefix
        $prefix = trim($prefix, '\\') . '\\';
        
        // Normalize base directory
        $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . '/';
        
        if ($prepend) {
            $this->namespaces = [$prefix => $base_dir] + $this->namespaces;
        } else {
            $this->namespaces[$prefix] = $base_dir;
        }
    }
    
    /**
     * Load a class file for the given class name
     *
     * @param string $class The fully-qualified class name
     * @return mixed The mapped file name on success, or boolean false on failure
     */
    public function loadClass(string $class) {
        // Check for aliases first
        if (isset($this->aliases[$class])) {
            return class_alias($this->aliases[$class], $class);
        }
        
        // Try to load from registered namespaces
        foreach ($this->namespaces as $prefix => $base_dir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                continue;
            }
            
            // Get the relative class name
            $relative_class = substr($class, $len);
            
            // Replace namespace separators with directory separators
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            
            // If the file exists, require it
            if ($this->requireFile($file)) {
                return $file;
            }
        }
        
        // No file found
        return false;
    }
    
    /**
     * Require a file if it exists
     *
     * @param string $file The file to require
     * @return bool True if the file exists and was included, false otherwise
     */
    private function requireFile(string $file): bool {
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
        return false;
    }
    
    /**
     * Get all registered namespaces
     *
     * @return array
     */
    public function getNamespaces(): array {
        return $this->namespaces;
    }
    
    /**
     * Get all registered aliases
     *
     * @return array
     */
    public function getAliases(): array {
        return $this->aliases;
    }
    
    /**
     * Add a class alias
     *
     * @param string $alias The alias name
     * @param string $original The original class name
     */
    public function addAlias(string $alias, string $original): void {
        $this->aliases[$alias] = $original;
    }
    
    /**
     * Check if a class can be loaded
     *
     * @param string $class The class name to check
     * @return bool
     */
    public function canLoadClass(string $class): bool {
        return $this->loadClass($class) !== false;
    }
}