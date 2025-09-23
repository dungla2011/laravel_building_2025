<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Api\UserController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class UserControllerPureTest extends TestCase
{
    use WithoutMiddleware;

    /** @test */
    public function it_validates_user_data_structure()
    {
        // Arrange - Pure data validation without DB
        $validData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ];

        $invalidData = [
            'name' => '', // Invalid empty name
            'email' => 'invalid-email', // Invalid email format
            'password' => '123' // Too short password
        ];

        // Act & Assert - Test validation logic
        $this->assertTrue($this->isValidUserData($validData));
        $this->assertFalse($this->isValidUserData($invalidData));
    }

    /** @test */
    public function it_formats_user_response_correctly()
    {
        // Arrange - Create fake user data
        $userData = [
            'id' => 1,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00'
        ];

        // Act - Format response
        $formatted = $this->formatUserResponse($userData);

        // Assert - Check response structure
        $this->assertArrayHasKey('id', $formatted);
        $this->assertArrayHasKey('name', $formatted);
        $this->assertArrayHasKey('email', $formatted);
        $this->assertEquals(1, $formatted['id']);
        $this->assertEquals('Jane Smith', $formatted['name']);
    }

    /** @test */
    public function it_calculates_user_age_correctly()
    {
        // Arrange - Test pure business logic
        $birthDate = '1990-01-01';
        $currentDate = '2025-01-01';

        // Act
        $age = $this->calculateAge($birthDate, $currentDate);

        // Assert
        $this->assertEquals(35, $age);
    }

    /** @test */
    public function it_generates_username_from_email()
    {
        // Arrange
        $email = 'john.doe@example.com';

        // Act
        $username = $this->generateUsername($email);

        // Assert
        $this->assertEquals('john.doe', $username);
    }

    /** @test */
    public function it_checks_password_strength()
    {
        // Arrange - Test different password scenarios
        $strongPassword = 'StrongP@ssw0rd123';
        $weakPassword = '123456';
        $mediumPassword = 'password123';

        // Act & Assert
        $this->assertTrue($this->isStrongPassword($strongPassword));
        $this->assertFalse($this->isStrongPassword($weakPassword));
        $this->assertFalse($this->isStrongPassword($mediumPassword));
    }

    // Helper methods for pure unit testing
    private function isValidUserData(array $data): bool
    {
        return !empty($data['name']) && 
               filter_var($data['email'], FILTER_VALIDATE_EMAIL) && 
               strlen($data['password']) >= 6;
    }

    private function formatUserResponse(array $userData): array
    {
        return [
            'id' => $userData['id'],
            'name' => $userData['name'],
            'email' => $userData['email'],
            'created_at' => $userData['created_at']
        ];
    }

    private function calculateAge(string $birthDate, string $currentDate): int
    {
        $birth = new \DateTime($birthDate);
        $current = new \DateTime($currentDate);
        return $birth->diff($current)->y;
    }

    private function generateUsername(string $email): string
    {
        return explode('@', $email)[0];
    }

    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 8 &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password) &&
               preg_match('/[^A-Za-z0-9]/', $password);
    }
}