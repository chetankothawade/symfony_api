<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use App\Service\AuthService;
use App\Service\CommonService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private UserRepository $userRepository;
    private PasswordResetTokenRepository $passwordResetTokenRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private JWTTokenManagerInterface $jwtTokenManager;
    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;
    private CommonService $commonService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordResetTokenRepository = $this->createMock(PasswordResetTokenRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->jwtTokenManager = $this->createMock(JWTTokenManagerInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->commonService = $this->createMock(CommonService::class);

        $this->authService = new AuthService(
            $this->userRepository,
            $this->passwordResetTokenRepository,
            $this->passwordHasher,
            $this->jwtTokenManager,
            $this->entityManager,
            $this->mailer,
            $this->commonService,
            'http://localhost/reset',
            'no-reply@example.com'
        );
    }

    public function testLoginSuccess(): void
    {
        $user = $this->createTestUser('active');
        
        $this->userRepository
            ->method('findOneByEmail')
            ->with('test@example.com')
            ->willReturn($user);

        $this->passwordHasher
            ->method('isPasswordValid')
            ->with($user, 'Password@123')
            ->willReturn(true);

        $this->jwtTokenManager
            ->method('create')
            ->with($user)
            ->willReturn('test-token-123');

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->authService->login('test@example.com', 'Password@123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals('test-token-123', $result['token']);
        $this->assertInstanceOf(User::class, $result['user']);
    }

    public function testLoginWithInvalidPassword(): void
    {
        $user = $this->createTestUser('active');
        
        $this->userRepository
            ->method('findOneByEmail')
            ->with('test@example.com')
            ->willReturn($user);

        $this->passwordHasher
            ->method('isPasswordValid')
            ->with($user, 'WrongPassword')
            ->willReturn(false);

        $result = $this->authService->login('test@example.com', 'WrongPassword');

        $this->assertFalse($result);
    }

    public function testLoginWithInactiveUser(): void
    {
        $user = $this->createTestUser('inactive');
        
        $this->userRepository
            ->method('findOneByEmail')
            ->with('inactive@example.com')
            ->willReturn($user);

        $this->passwordHasher
            ->method('isPasswordValid')
            ->with($user, 'Password@123')
            ->willReturn(true);

        $result = $this->authService->login('inactive@example.com', 'Password@123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('inactive', $result);
        $this->assertTrue($result['inactive']);
    }

    public function testLoginWithNonExistentUser(): void
    {
        $this->userRepository
            ->method('findOneByEmail')
            ->with('nonexistent@example.com')
            ->willReturn(null);

        $result = $this->authService->login('nonexistent@example.com', 'Password@123');

        $this->assertFalse($result);
    }

    public function testRegisterSuccess(): void
    {
        $this->userRepository
            ->method('findOneByEmail')
            ->with('newuser@example.com')
            ->willReturn(null);

        $this->commonService
            ->method('generateUuidV4')
            ->willReturn('550e8400-e29b-41d4-a716-446655440000');

        $this->passwordHasher
            ->method('hashPassword')
            ->willReturn('hashed-password');

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->authService->register('Test User', 'newuser@example.com', 'Password@123', '1234567890');

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('newuser@example.com', $result->getEmail());
    }

    public function testRegisterWithExistingEmail(): void
    {
        $existingUser = $this->createTestUser('active');
        
        $this->userRepository
            ->method('findOneByEmail')
            ->with('existing@example.com')
            ->willReturn($existingUser);

        $result = $this->authService->register('New User', 'existing@example.com', 'Password@123');

        $this->assertFalse($result);
    }

    public function testResetPasswordSuccess(): void
    {
        $user = $this->createTestUser('active');
        $plainToken = 'plain-token-123';
        $hashedToken = password_hash($plainToken, PASSWORD_BCRYPT);

        $resetToken = $this->createMock(\App\Entity\PasswordResetToken::class);
        $resetToken->method('getToken')->willReturn($hashedToken);
        $resetToken->method('getCreatedAt')->willReturn(new \DateTimeImmutable('-30 minutes'));

        $this->passwordResetTokenRepository
            ->method('findOneByEmail')
            ->with('test@example.com')
            ->willReturn($resetToken);

        $this->userRepository
            ->method('findOneByEmail')
            ->with('test@example.com')
            ->willReturn($user);

        $this->passwordHasher
            ->method('hashPassword')
            ->willReturn('new-hashed-password');

        $this->entityManager->expects($this->once())->method('remove');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->authService->resetPassword('test@example.com', $plainToken, 'NewPassword@123');

        $this->assertTrue($result);
    }

    public function testResetPasswordWithInvalidToken(): void
    {
        $resetToken = $this->createMock(\App\Entity\PasswordResetToken::class);
        $resetToken->method('getToken')->willReturn(password_hash('different-token', PASSWORD_BCRYPT));

        $this->passwordResetTokenRepository
            ->method('findOneByEmail')
            ->with('test@example.com')
            ->willReturn($resetToken);

        $result = $this->authService->resetPassword('test@example.com', 'invalid-token', 'NewPassword@123');

        $this->assertFalse($result);
    }

    public function testResetPasswordWithExpiredToken(): void
    {
        $plainToken = 'plain-token-123';
        $hashedToken = password_hash($plainToken, PASSWORD_BCRYPT);

        $resetToken = $this->createMock(\App\Entity\PasswordResetToken::class);
        $resetToken->method('getToken')->willReturn($hashedToken);
        $resetToken->method('getCreatedAt')->willReturn(new \DateTimeImmutable('-120 minutes'));

        $this->passwordResetTokenRepository
            ->method('findOneByEmail')
            ->with('test@example.com')
            ->willReturn($resetToken);

        $result = $this->authService->resetPassword('test@example.com', $plainToken, 'NewPassword@123', 60);

        $this->assertFalse($result);
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
