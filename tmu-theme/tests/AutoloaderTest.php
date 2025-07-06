<?php
/**
 * Autoloader Tests
 *
 * @package TMU\Tests
 * @version 1.0.0
 */

namespace TMU\Tests;

use PHPUnit\Framework\TestCase;
use TMU\Autoloader;

/**
 * Test autoloader functionality
 */
class AutoloaderTest extends TestCase {
    
    /**
     * Autoloader instance
     *
     * @var Autoloader
     */
    private $autoloader;
    
    /**
     * Set up test
     */
    public function setUp(): void {
        $this->autoloader = new Autoloader();
    }
    
    /**
     * Clean up test
     */
    public function tearDown(): void {
        if ($this->autoloader) {
            $this->autoloader->unregister();
        }
    }
    
    /**
     * Test autoloader registration
     */
    public function testAutoloaderRegistration(): void {
        $this->autoloader->register();
        
        // Check if autoloader is registered
        $autoloaders = spl_autoload_functions();
        $registered = false;
        
        foreach ($autoloaders as $autoloader) {
            if (is_array($autoloader) && $autoloader[0] instanceof Autoloader) {
                $registered = true;
                break;
            }
        }
        
        $this->assertTrue($registered, 'Autoloader should be registered');
        
        $this->autoloader->unregister();
    }
    
    /**
     * Test namespace mapping
     */
    public function testNamespaceMapping(): void {
        $namespaces = $this->autoloader->getNamespaces();
        
        $this->assertIsArray($namespaces);
        $this->assertArrayHasKey('TMU\\Admin\\', $namespaces);
        $this->assertArrayHasKey('TMU\\Database\\', $namespaces);
        $this->assertArrayHasKey('TMU\\', $namespaces);
    }
    
    /**
     * Test class aliases
     */
    public function testClassAliases(): void {
        $aliases = $this->autoloader->getAliases();
        
        $this->assertIsArray($aliases);
        $this->assertArrayHasKey('TMU_Settings', $aliases);
        $this->assertEquals('TMU\\Admin\\Settings', $aliases['TMU_Settings']);
    }
    
    /**
     * Test can load class check
     */
    public function testCanLoadClassCheck(): void {
        // Test existing class
        $canLoad = $this->autoloader->canLoadClass('TMU\\Autoloader');
        $this->assertTrue($canLoad);
        
        // Test non-existing class
        $cannotLoad = $this->autoloader->canLoadClass('TMU\\NonExistentClass');
        $this->assertFalse($cannotLoad);
    }
    
    /**
     * Test autoloader statistics
     */
    public function testAutoloaderStats(): void {
        $stats = $this->autoloader->getStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('namespaces_count', $stats);
        $this->assertArrayHasKey('aliases_count', $stats);
        $this->assertArrayHasKey('base_directory', $stats);
        $this->assertArrayHasKey('prefix', $stats);
        
        $this->assertGreaterThan(0, $stats['namespaces_count']);
        $this->assertGreaterThan(0, $stats['aliases_count']);
        $this->assertEquals('TMU\\', $stats['prefix']);
    }
    
    /**
     * Test adding custom namespace
     */
    public function testAddCustomNamespace(): void {
        $this->autoloader->addNamespace('Custom\\Test\\', '/custom/path/');
        
        $namespaces = $this->autoloader->getNamespaces();
        $this->assertArrayHasKey('Custom\\Test\\', $namespaces);
        $this->assertEquals('/custom/path/', $namespaces['Custom\\Test\\']);
    }
    
    /**
     * Test adding custom alias
     */
    public function testAddCustomAlias(): void {
        $this->autoloader->addAlias('CustomAlias', 'TMU\\Custom\\Class');
        
        $aliases = $this->autoloader->getAliases();
        $this->assertArrayHasKey('CustomAlias', $aliases);
        $this->assertEquals('TMU\\Custom\\Class', $aliases['CustomAlias']);
    }
}