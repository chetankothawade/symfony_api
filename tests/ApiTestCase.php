<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiTestCase extends WebTestCase
{
    protected EntityManagerInterface $em;
    protected UserPasswordHasherInterface $passwordHasher;
    private bool $databaseInitialized = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->databaseInitialized = false;
    }

    private function initializeDatabase(): void
    {
        if ($this->databaseInitialized) {
            return;
        }

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $this->resetDatabase();
        $this->databaseInitialized = true;
    }

    protected function resetDatabase(): void
    {
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        if ($metadata === []) {
            return;
        }

        $tool = new SchemaTool($this->em);
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);
    }

    protected function createUser(
        string $email = 'admin@example.com',
        string $password = 'Password@123',
        string $role = 'admin',
        string $status = 'active'
    ): User {
        $this->initializeDatabase();
        $now = new \DateTimeImmutable('now');
        $user = new User();
        $user->setUuid($this->generateUuidV4());
        $user->setName('Test User');
        $user->setEmail($email);
        $user->setPhone('9999999999');
        $user->setStatus($status);
        $user->setRole($role);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setCreatedAt($now);
        $user->setUpdatedAt($now);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function login(string $email = 'admin@example.com', string $password = 'Password@123'): string
    {
        // Create a temporary client just for login, or reuse the global one if exists
        $client = static::createClient();
        $client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => $email,
            'password' => $password,
        ], JSON_THROW_ON_ERROR));

        // Don't assert here - let the test handle assertions if needed
        $response = $client->getResponse();
        if ($response->getStatusCode() !== 200) {
            return '';
        }
        
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $payload['data']['token'] ?? '';
    }

    protected function getLoginToken($client, string $email = 'admin@example.com', string $password = 'Password@123'): string
    {
        $client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => $email,
            'password' => $password,
        ], JSON_THROW_ON_ERROR));

        $response = $client->getResponse();
        if ($response->getStatusCode() !== 200) {
            return '';
        }
        
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $payload['data']['token'] ?? '';
    }

    private function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
