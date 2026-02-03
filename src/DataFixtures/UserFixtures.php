<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher) {}

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('now');

        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@yopmail.com',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@yopmail.com',
                'role' => 'admin',
            ],
            [
                'name' => 'Editor',
                'email' => 'editor@yopmail.com',
                'role' => 'editor',
            ],
        ];

        foreach ($users as $data) {
            $user = new User();
            $user->setUuid($this->generateUuidV4());
            $user->setName($data['name']);
            $user->setEmail($data['email']);
            $user->setPhone(null);
            $user->setStatus('active');
            $user->setRole($data['role']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'Password@123'));
            $user->setLastLoginAt(null);
            $user->setLastLogoutAt(null);
            $user->setLastLoginIp(null);
            $user->setLastLoginUa(null);
            $user->setCreatedAt($now);
            $user->setUpdatedAt($now);
            $user->setDeletedAt(null);

            $manager->persist($user);
        }

        $manager->flush();
    }

    private function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
