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
     * Test basic Laravel configuration
     */
    public function test_environment_configuration(): void
    {
        // Test that we can access environment variables
        $appEnv = env('APP_ENV');
        $this->assertNotNull($appEnv, 'APP_ENV should be set');
        
        // Test that config values are accessible
        $appName = config('app.name');
        $this->assertNotNull($appName, 'App name should be configured');
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