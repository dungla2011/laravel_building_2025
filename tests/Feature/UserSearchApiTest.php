<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSearchApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users for search testing
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com'
        ]);

        User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com'
        ]);

        User::factory()->create([
            'name' => 'Bob Johnson',
            'email' => 'bob.johnson@test.com'
        ]);

        User::factory()->create([
            'name' => 'Alice Brown',
            'email' => 'alice.brown@test.com'
        ]);
    }

    /**
     * Test searching users by name (like operator)
     */
    public function test_can_search_users_by_name_like(): void
    {
        $searchData = [
            'filters' => [
                [
                    'field' => 'name',
                    'operator' => 'like',
                    'value' => '%John%'
                ]
            ]
        ];

        $response = $this->postJson('/api/users/search', $searchData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'email_verified_at',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);

        // Should find both "John Doe" and "Bob Johnson"
        $responseData = $response->json('data');
        $this->assertCount(2, $responseData);
        
        $names = collect($responseData)->pluck('name')->toArray();
        $this->assertContains('John Doe', $names);
        $this->assertContains('Bob Johnson', $names);
    }

    /**
     * Test searching users by email domain
     */
    public function test_can_search_users_by_email_domain(): void
    {
        $searchData = [
            'filters' => [
                [
                    'field' => 'email',
                    'operator' => 'like',
                    'value' => '%example.com%'
                ]
            ]
        ];

        $response = $this->postJson('/api/users/search', $searchData);

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $this->assertCount(2, $responseData);

        $emails = collect($responseData)->pluck('email')->toArray();
        $this->assertContains('john.doe@example.com', $emails);
        $this->assertContains('jane.smith@example.com', $emails);
    }

    /**
     * Test exact match search
     */
    public function test_can_search_users_exact_match(): void
    {
        $searchData = [
            'filters' => [
                [
                    'field' => 'name',
                    'operator' => '=',
                    'value' => 'John Doe'
                ]
            ]
        ];

        $response = $this->postJson('/api/users/search', $searchData);

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertEquals('John Doe', $responseData[0]['name']);
    }

    /**
     * Test search with multiple filters
     */
    public function test_can_search_users_with_multiple_filters(): void
    {
        $searchData = [
            'filters' => [
                [
                    'field' => 'name',
                    'operator' => 'like',
                    'value' => '%John%'
                ],
                [
                    'field' => 'email',
                    'operator' => 'like',
                    'value' => '%example.com%'
                ]
            ]
        ];

        $response = $this->postJson('/api/users/search', $searchData);

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertEquals('John Doe', $responseData[0]['name']);
        $this->assertEquals('john.doe@example.com', $responseData[0]['email']);
    }

    /**
     * Test search with sorting
     */
    public function test_can_search_users_with_sorting(): void
    {
        $searchData = [
            'filters' => [
                [
                    'field' => 'email',
                    'operator' => 'like',
                    'value' => '%example.com%'
                ]
            ],
            'sort' => [
                [
                    'field' => 'name',
                    'direction' => 'desc'
                ]
            ]
        ];

        $response = $this->postJson('/api/users/search', $searchData);

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $this->assertCount(2, $responseData);

        // Should be sorted by name descending: John Doe, Jane Smith
        $this->assertEquals('John Doe', $responseData[0]['name']);
        $this->assertEquals('Jane Smith', $responseData[1]['name']);
    }

    /**
     * Test search with pagination
     */
    public function test_can_search_users_with_pagination(): void
    {
        $searchData = [
            'filters' => [
                [
                    'field' => 'email',
                    'operator' => 'like',
                    'value' => '%test.com%'
                ]
            ]
        ];

        $response = $this->postJson('/api/users/search', $searchData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total'
                ]
            ]);

        $meta = $response->json('meta');
        $this->assertEquals(1, $meta['current_page']);
        $this->assertEquals(15, $meta['per_page']); // Default Laravel pagination per_page
        $this->assertEquals(2, $meta['total']); // Should have 2 users with test.com domain
    }

    /**
     * Test search with no results
     */
    public function test_can_search_users_with_no_results(): void
    {
        $searchData = [
            'filters' => [
                [
                    'field' => 'name',
                    'operator' => 'like',
                    'value' => '%NonExistent%'
                ]
            ]
        ];

        $response = $this->postJson('/api/users/search', $searchData);

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $this->assertCount(0, $responseData);
    }

    /**
     * Test search with invalid field (should return validation error)
     */
    public function test_search_with_invalid_filter_field(): void
    {
        $searchData = [
            'filters' => [
                [
                    'field' => 'invalid_field',
                    'operator' => '=',
                    'value' => 'test'
                ]
            ]
        ];

        $response = $this->postJson('/api/users/search', $searchData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['filters.0.field']);
    }

    /**
     * Test search with invalid operator
     */
    public function test_search_with_invalid_operator(): void
    {
        $searchData = [
            'filters' => [
                [
                    'field' => 'name',
                    'operator' => 'invalid_operator',
                    'value' => 'test'
                ]
            ]
        ];

        $response = $this->postJson('/api/users/search', $searchData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['filters.0.operator']);
    }

    /**
     * Test empty search request
     */
    public function test_empty_search_request(): void
    {
        $response = $this->postJson('/api/users/search', []);

        $response->assertStatus(200);

        // Should return all users when no filters are provided
        $responseData = $response->json('data');
        $this->assertCount(4, $responseData);
    }
}