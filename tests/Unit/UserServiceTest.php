<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Services\UserService;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery;

class UserServiceTest extends TestCase
{
    use WithoutMiddleware;

    protected $userRepository;
    protected $userService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock UserRepository
        $this->userRepository = Mockery::mock(UserRepository::class);
        $this->userService = new UserService($this->userRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_user_without_database()
    {
        // Arrange - Setup mock data
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ];

        $expectedUser = new User($userData);
        $expectedUser->id = 1;

        // Mock repository method
        $this->userRepository
            ->shouldReceive('create')
            ->once()
            ->with($userData)
            ->andReturn($expectedUser);

        // Act - Call the service method
        $result = $this->userService->createUser($userData);

        // Assert - Verify results
        $this->assertEquals(1, $result->id);
        $this->assertEquals('John Doe', $result->name);
        $this->assertEquals('john@example.com', $result->email);
    }

    /** @test */
    public function it_can_find_user_by_id_without_database()
    {
        // Arrange
        $userId = 1;
        $mockUser = new User([
            'id' => $userId,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com'
        ]);

        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with($userId)
            ->andReturn($mockUser);

        // Act
        $result = $this->userService->getUserById($userId);

        // Assert
        $this->assertEquals($userId, $result->id);
        $this->assertEquals('Jane Doe', $result->name);
    }

    /** @test */
    public function it_returns_null_when_user_not_found()
    {
        // Arrange
        $userId = 999;

        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with($userId)
            ->andReturn(null);

        // Act
        $result = $this->userService->getUserById($userId);

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_can_update_user_without_database()
    {
        // Arrange
        $userId = 1;
        $updateData = ['name' => 'Updated Name'];
        
        $mockUser = new User([
            'id' => $userId,
            'name' => 'Updated Name',
            'email' => 'user@example.com'
        ]);

        $this->userRepository
            ->shouldReceive('update')
            ->once()
            ->with($userId, $updateData)
            ->andReturn($mockUser);

        // Act
        $result = $this->userService->updateUser($userId, $updateData);

        // Assert
        $this->assertEquals('Updated Name', $result->name);
    }
}