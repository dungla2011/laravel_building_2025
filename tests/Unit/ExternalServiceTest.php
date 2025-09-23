<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;

class ExternalServiceTest extends TestCase
{
    /** @test */
    public function it_can_mock_http_requests()
    {
        // Arrange - Mock HTTP responses
        Http::fake([
            'api.example.com/users/*' => Http::response([
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ], 200),
            'api.example.com/error' => Http::response([], 500)
        ]);

        // Act - Make HTTP request
        $response = Http::get('api.example.com/users/1');

        // Assert
        $this->assertEquals(200, $response->status());
        $this->assertEquals('John Doe', $response->json('name'));

        // Verify request was made
        Http::assertSent(function ($request) {
            return $request->url() === 'api.example.com/users/1';
        });
    }

    /** @test */
    public function it_can_mock_cache_operations()
    {
        // Arrange - Mock Cache facade
        Cache::shouldReceive('get')
            ->once()
            ->with('user_1')
            ->andReturn([
                'id' => 1,
                'name' => 'Cached User',
                'email' => 'cached@example.com'
            ]);

        Cache::shouldReceive('put')
            ->once()
            ->with('user_1', \Mockery::any(), 3600)
            ->andReturn(true);

        // Act
        $cachedUser = Cache::get('user_1');
        $cacheResult = Cache::put('user_1', $cachedUser, 3600);

        // Assert
        $this->assertEquals('Cached User', $cachedUser['name']);
        $this->assertTrue($cacheResult);
    }

    /** @test */
    public function it_can_mock_mail_sending()
    {
        // Arrange - Mock Mail facade
        Mail::fake();

        $user = [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ];

        // Act - Send email (this won't actually send)
        Mail::to($user['email'])->send(new WelcomeEmail($user));

        // Assert - Verify email was "sent"
        Mail::assertSent(WelcomeEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user['email']);
        });

        Mail::assertSent(WelcomeEmail::class, 1); // Exactly 1 email sent
    }

    /** @test */
    public function it_processes_batch_data_without_database()
    {
        // Arrange - Pure data processing
        $inputData = [
            ['name' => 'User 1', 'email' => 'user1@example.com', 'status' => 'active'],
            ['name' => 'User 2', 'email' => 'user2@example.com', 'status' => 'inactive'],
            ['name' => 'User 3', 'email' => 'user3@example.com', 'status' => 'active']
        ];

        // Act - Process data
        $activeUsers = $this->filterActiveUsers($inputData);
        $emailList = $this->extractEmails($inputData);

        // Assert
        $this->assertCount(2, $activeUsers);
        $this->assertCount(3, $emailList);
        $this->assertContains('user1@example.com', $emailList);
    }

    /** @test */
    public function it_validates_business_rules_without_database()
    {
        // Arrange - Test business logic
        $user = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 25,
            'subscription' => 'premium'
        ];

        // Act & Assert - Test various business rules
        $this->assertTrue($this->canAccessPremiumFeatures($user));
        $this->assertTrue($this->isAdultUser($user));
        $this->assertFalse($this->isEligibleForDiscount($user));
    }

    // Helper methods for testing business logic
    private function filterActiveUsers(array $users): array
    {
        return array_filter($users, function ($user) {
            return $user['status'] === 'active';
        });
    }

    private function extractEmails(array $users): array
    {
        return array_column($users, 'email');
    }

    private function canAccessPremiumFeatures(array $user): bool
    {
        return $user['subscription'] === 'premium';
    }

    private function isAdultUser(array $user): bool
    {
        return $user['age'] >= 18;
    }

    private function isEligibleForDiscount(array $user): bool
    {
        return $user['age'] >= 65; // Senior citizen discount
    }
}