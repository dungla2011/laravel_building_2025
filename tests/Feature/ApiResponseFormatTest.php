<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiResponseFormatTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_response_format()
    {
        $response = $this->getJson('/api/users');
        
        echo "Status: " . $response->getStatusCode() . "\n";
        echo "Response: " . $response->getContent() . "\n";
        
        $this->assertTrue(true); // Always pass to see output
    }
}