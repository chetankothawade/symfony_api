<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\PasswordResetTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private UserRepository $users,
        private PasswordResetTokenRepository $passwordResetTokens,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwt,
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        #[Autowire('%env(string:FRONTEND_RESET_URL)%')]
        private string $frontendResetUrl,
        #[Autowire('%env(string:MAILER_FROM)%')]
        private string $mailerFrom
    ) {}

    /**
     * @return array{token:string,user:User}|array{inactive:bool}|false
     */
    public function login(string $email, string $password, ?Request $request = null): array|false
    {
        $user = $this->users->findOneByEmail($email);
        if (!$user instanceof User || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return false;
        }

        if ($user->getStatus() !== 'active') {
            return ['inactive' => true];
        }

        $now = new \DateTimeImmutable('now');
        $user->setLastLoginAt($now);
        if ($request) {
            $user->setLastLoginIp($request->getClientIp());
            $user->setLastLoginUa($request->headers->get('User-Agent'));
        }
        $user->setUpdatedAt($now);
        $this->em->flush();

        return [
            'token' => $this->jwt->create($user),
            'user' => $user,
        ];
    }

    /**
     * @return User|false
     */
    public function register(string $name, string $email, string $password, ?string $phone = null): User|false
    {
        if ($this->users->findOneByEmail($email)) {
            return false;
        }

        $now = new \DateTimeImmutable('now');

        $user = new User();
        $user->setUuid($this->generateUuidV4());
        $user->setName($name);
        $user->setEmail($email);
        $user->setPhone($phone);
        $user->setStatus('active');
        $user->setRole('admin');
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setCreatedAt($now);
        $user->setUpdatedAt($now);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * @return string|false Returns the plain token to be emailed.
     */
    public function sendResetLink(string $email): string|false
    {
        $user = $this->users->findOneByEmail($email);
        if (!$user instanceof User) {
            return false;
        }

        $plainToken = bin2hex(random_bytes(32));
        $hashedToken = password_hash($plainToken, PASSWORD_BCRYPT);
        $now = new \DateTimeImmutable('now');

        $reset = $this->passwordResetTokens->findOneByEmail($email);
        if (!$reset) {
            $reset = new \App\Entity\PasswordResetToken();
            $reset->setEmail($email);
            $this->em->persist($reset);
        }

        $reset->setToken($hashedToken);
        $reset->setCreatedAt($now);
        $this->em->flush();

        $resetUrl = rtrim($this->frontendResetUrl, '/') . '?token=' . urlencode($plainToken) . '&email=' . urlencode($email);
        $emailMessage = (new Email())
            ->from($this->mailerFrom)
            ->to($email)
            ->subject('Reset your password')
            ->text("You requested a password reset.\n\nReset link: {$resetUrl}\n\nIf you did not request this, ignore this email.");

        $this->mailer->send($emailMessage);

        return $plainToken;
    }

    public function resetPassword(string $email, string $token, string $password, int $ttlMinutes = 60): bool
    {
        $reset = $this->passwordResetTokens->findOneByEmail($email);
        if (!$reset) {
            return false;
        }

        if (!password_verify($token, (string) $reset->getToken())) {
            return false;
        }

        $createdAt = $reset->getCreatedAt();
        if ($createdAt && $createdAt->modify(sprintf('+%d minutes', $ttlMinutes)) < new \DateTimeImmutable('now')) {
            return false;
        }

        $user = $this->users->findOneByEmail($email);
        if (!$user instanceof User) {
            return false;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setUpdatedAt(new \DateTimeImmutable('now'));

        $this->em->remove($reset);
        $this->em->flush();

        return true;
    }

    private function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
