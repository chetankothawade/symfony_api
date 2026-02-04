<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\CommonService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserServiceTest extends TestCase
{
    private UserService $userService;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private CommonService $commonService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->commonService = $this->createMock(CommonService::class);

        $this->userService = new UserService(
            $this->userRepository,
            $this->entityManager,
            $this->passwordHasher,
            $this->commonService
        );
    }

    public function testCreateUserSuccess(): void
    {
        $this->userRepository
            ->method('findOneByEmail')
            ->with('newuser@example.com')
            ->willReturn(null);

        $this->commonService
            ->method('generateUuidV4')
            ->willReturn('550e8400-e29b-41d4-a716-446655440000');

        $this->commonService
            ->method('transactional')
            ->willReturnCallback(function ($callback) {
                return $callback();
            });

        $this->passwordHasher
            ->method('hashPassword')
            ->willReturn('hashed-password');

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'Password@123',
            'phone' => '1234567890',
            'role' => 'editor',
        ];

        $result = $this->userService->createUser($userData, '192.168.1.1');

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('newuser@example.com', $result->getEmail());
        $this->assertEquals('New User', $result->getName());
        $this->assertEquals('editor', $result->getRole());
    }

    public function testCreateUserWithDuplicateEmail(): void
    {
        $existingUser = $this->createTestUser('active');

        $this->userRepository
            ->method('findOneByEmail')
            ->with('existing@example.com')
            ->willReturn($existingUser);

        $userData = [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'Password@123',
        ];

        $result = $this->userService->createUser($userData);

        $this->assertFalse($result);
    }

    public function testUpdateUserSuccess(): void
    {
        $user = $this->createTestUser('active');

        $this->commonService
            ->method('transactional')
            ->willReturnCallback(function ($callback) {
                return $callback();
            });

        $this->entityManager->expects($this->once())->method('flush');

        $userData = [
            'name' => 'Updated Name',
            'phone' => '9999999999',
            'role' => 'editor',
        ];

        $result = $this->userService->updateUser($user, $userData);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('Updated Name', $result->getName());
        $this->assertEquals('9999999999', $result->getPhone());
        $this->assertEquals('editor', $result->getRole());
    }

    public function testUpdateUserWithPassword(): void
    {
        $user = $this->createTestUser('active');

        $this->commonService
            ->method('transactional')
            ->willReturnCallback(function ($callback) {
                return $callback();
            });

        $this->passwordHasher
            ->method('hashPassword')
            ->willReturn('new-hashed-password');

        $this->entityManager->expects($this->once())->method('flush');

        $userData = [
            'password' => 'NewPassword@123',
        ];

        $result = $this->userService->updateUser($user, $userData);

        $this->assertInstanceOf(User::class, $result);
    }

    public function testDeleteUser(): void
    {
        $user = $this->createTestUser('active');

        $this->commonService
            ->method('transactional')
            ->willReturnCallback(function ($callback) {
                return $callback();
            });

        $this->entityManager->expects($this->once())->method('flush');

        $this->userService->deleteUser($user);

        $this->assertEquals('deleted', $user->getStatus());
        $this->assertNotNull($user->getDeletedAt());
    }

    public function testToggleStatus(): void
    {
        $user = $this->createTestUser('active');

        $this->commonService
            ->method('transactional')
            ->willReturnCallback(function ($callback) {
                return $callback();
            });

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->userService->toggleStatus($user);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('inactive', $result->getStatus());
    }

    public function testToggleStatusInactiveToActive(): void
    {
        $user = $this->createTestUser('inactive');

        $this->commonService
            ->method('transactional')
            ->willReturnCallback(function ($callback) {
                return $callback();
            });

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->userService->toggleStatus($user);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('active', $result->getStatus());
    }

    public function testGetByUuid(): void
    {
        $user = $this->createTestUser('active');

        $this->userRepository
            ->method('findActiveByUuid')
            ->with('550e8400-e29b-41d4-a716-446655440000')
            ->willReturn($user);

        $result = $this->userService->getByUuid('550e8400-e29b-41d4-a716-446655440000');

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('test@example.com', $result->getEmail());
    }

    public function testGetByUuidNotFound(): void
    {
        $this->userRepository
            ->method('findActiveByUuid')
            ->with('550e8400-e29b-41d4-a716-446655440000')
            ->willReturn(null);

        $result = $this->userService->getByUuid('550e8400-e29b-41d4-a716-446655440000');

        $this->assertNull($result);
    }

    private function createTestUser(string $status): User
    {
        $user = new User();
        $user->setUuid('550e8400-e29b-41d4-a716-446655440000');
        $user->setName('Test User');
        $user->setEmail('test@example.com');
        $user->setPhone('1234567890');
        $user->setStatus($status);
        $user->setRole('admin');
        $user->setPassword('hashed-password');
        $user->setCreatedAt(new \DateTimeImmutable('now'));
        $user->setUpdatedAt(new \DateTimeImmutable('now'));

        return $user;
    }
}
