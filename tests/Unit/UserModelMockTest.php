<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Collection;
use Mockery;

class UserModelTest extends TestCase
{
    use WithoutMiddleware;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_mock_user_creation()
    {
        // Mock User model static methods
        $userMock = Mockery::mock('alias:App\Models\User');
        
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'hashed_password'
        ];

        $expectedUser = new User($userData);
        $expectedUser->id = 1;

        $userMock->shouldReceive('create')
            ->once()
            ->with($userData)
            ->andReturn($expectedUser);

        // Act
        $result = User::create($userData);

        // Assert
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Test User', $result->name);
    }

    /** @test */
    public function it_can_mock_user_find()
    {
        // Mock User::find()
        $userMock = Mockery::mock('alias:App\Models\User');
        
        $mockUser = new User([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        $mockUser->id = 1;

        $userMock->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($mockUser);

        // Act
        $result = User::find(1);

        // Assert
        $this->assertEquals(1, $result->id);
        $this->assertEquals('John Doe', $result->name);
    }

    /** @test */
    public function it_can_mock_user_collection()
    {
        // Mock User::all()
        $userMock = Mockery::mock('alias:App\Models\User');
        
        $mockUsers = collect([
            new User(['id' => 1, 'name' => 'User 1', 'email' => 'user1@example.com']),
            new User(['id' => 2, 'name' => 'User 2', 'email' => 'user2@example.com'])
        ]);

        $userMock->shouldReceive('all')
            ->once()
            ->andReturn($mockUsers);

        // Act
        $result = User::all();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals('User 1', $result->first()->name);
    }
}