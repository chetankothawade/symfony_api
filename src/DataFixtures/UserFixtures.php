<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\CommonService;
use Faker\Factory;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private CommonService $common,
        private UserRepository $users
    ) {}

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('now');
        $faker = Factory::create();

        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@yopmail.com',
                'role' => 'super_admin',
                'status' => 'active',
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@yopmail.com',
                'role' => 'admin',
                'status' => 'active',
            ],
            [
                'name' => 'Editor',
                'email' => 'editor@yopmail.com',
                'role' => 'editor',
                'status' => 'active',
            ],
        ];

        foreach ($users as $data) {
            $this->createUser($manager, $now, $data['name'], $data['email'], $data['role'], null, $data['status']);
        }

        for ($i = 1; $i <= 20; $i++) {
            $name = $faker->name();
            $email = $faker->unique()->safeEmail();
            $role = $faker->randomElement(['admin', 'editor']);
            $status = $faker->randomElement(['active', 'inactive']);
            $this->createUser($manager, $now, $name, $email, $role, $faker->phoneNumber(), $status);
        }

        $manager->flush();
    }

    private function createUser(
        ObjectManager $manager,
        \DateTimeImmutable $now,
        string $name,
        string $email,
        string $role,
        ?string $phone = null,
        string $status = 'active'
    ): void {
        if ($this->users->findOneByEmail($email)) {
            return;
        }

        $user = new User();
        $user->setUuid($this->common->generateUuidV4());
        $user->setName($name);
        $user->setEmail($email);
        $user->setPhone($phone);
        $user->setStatus($status);
        $user->setRole($role);
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
}
