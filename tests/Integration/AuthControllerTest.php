<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\ApiTestCase;

class AuthControllerTest extends ApiTestCase
{
    public function testLoginSuccess(): void
    {
        $client = static::createClient();
        $this->createUser('admin@example.com', 'Password@123', 'admin', 'active');

        $client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => 'admin@example.com',
            'password' => 'Password@123',
        ], JSON_THROW_ON_ERROR));

        $this->assertResponseIsSuccessful();
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($payload['status']);
        $this->assertNotEmpty($payload['data']['token']);
    }

    public function testLoginInvalidPassword(): void
    {
        $client = static::createClient();
        $this->createUser('admin@example.com', 'Password@123', 'admin', 'active');

        $client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => 'admin@example.com',
            'password' => 'WrongPassword',
        ], JSON_THROW_ON_ERROR));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginInactiveUser(): void
    {
        $client = static::createClient();
        $this->createUser('inactive@example.com', 'Password@123', 'admin', 'inactive');

        $client->request('POST', '/api/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => 'inactive@example.com',
            'password' => 'Password@123',
        ], JSON_THROW_ON_ERROR));

        $this->assertResponseStatusCodeSame(403);
    }
}
