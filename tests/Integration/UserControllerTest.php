<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\ApiTestCase;

class UserControllerTest extends ApiTestCase
{
    public function testListUsers(): void
    {
        $client = static::createClient();
        $this->createUser('admin@example.com', 'Password@123', 'admin', 'active');
        $this->createUser('editor1@example.com', 'Password@123', 'editor', 'active');
        $this->createUser('editor2@example.com', 'Password@123', 'editor', 'inactive');

        $token = $this->getLoginToken($client, 'admin@example.com', 'Password@123');

        $client->request('GET', '/api/users?page=1&perPage=10', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($payload['status']);
        $this->assertIsArray($payload['data']);
        $this->assertArrayHasKey('pagination', $payload);
    }

    public function testCreateShowUpdateDeleteToggle(): void
    {
        $client = static::createClient();
        $this->createUser('admin@example.com', 'Password@123', 'admin', 'active');
        $token = $this->getLoginToken($client, 'admin@example.com', 'Password@123');

        $client->request('POST', '/api/users', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ], content: json_encode([
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'Password@123',
            'phone' => '9999999999',
            'role' => 'editor',
        ], JSON_THROW_ON_ERROR));

        $this->assertResponseStatusCodeSame(201);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $uuid = $payload['data']['user']['uuid'] ?? null;
        $this->assertNotEmpty($uuid);

        $client->request('GET', '/api/users/' . $uuid, server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $this->assertResponseIsSuccessful();

        $client->request('PATCH', '/api/users/' . $uuid, server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ], content: json_encode([
            'name' => 'Updated User',
            'role' => 'admin'
        ], JSON_THROW_ON_ERROR));
        $this->assertResponseIsSuccessful();

        $client->request('PATCH', '/api/users/' . $uuid . '/toggle-status', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $this->assertResponseIsSuccessful();

        $client->request('DELETE', '/api/users/' . $uuid, server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $this->assertResponseIsSuccessful();
    }
}
