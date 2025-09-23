<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }

    /**
     * Test basic environment configuration
     */
    public function test_environment_configuration(): void
    {
        // Test that we can access basic PHP environment
        $this->assertIsString(PHP_VERSION, 'PHP version should be available');
        $this->assertGreaterThanOrEqual(8.2, (float)phpversion(), 'PHP version should be 8.2+');
        
        // Test basic constants
        $this->assertTrue(defined('PHP_OS'), 'PHP_OS should be defined');
        $this->assertNotEmpty(PHP_OS, 'PHP_OS should not be empty');
    }

    /**
     * Test basic PHP functionality
     */
    public function test_basic_php_functions(): void
    {
        // Test array functions
        $array = [1, 2, 3, 4, 5];
        $this->assertEquals(5, count($array));
        $this->assertEquals(15, array_sum($array));
        
        // Test string functions
        $string = 'Laravel Testing';
        $this->assertEquals('laravel testing', strtolower($string));
        $this->assertTrue(str_contains($string, 'Laravel'));
    }

    /**
     * Test JSON operations
     */
    public function test_json_operations(): void
    {
        $data = ['name' => 'Laravel', 'version' => '12.x', 'testing' => true];
        
        $json = json_encode($data);
        $this->assertJson($json);
        
        $decoded = json_decode($json, true);
        $this->assertEquals($data, $decoded);
        $this->assertTrue($decoded['testing']);
    }
}