<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private UserRepository $users,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private CommonService $common
    ) {}

    /**
     * @return array{items: User[], pagination: array{total:int, perPage:int, currentPage:int, lastPage:int, from:int, to:int}}
     */
    public function getPaginatedUsers(array $filters, ?int $currentUserId = null): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = (int) ($filters['perPage'] ?? 10);
        if ($perPage < 1) {
            $perPage = 10;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $search = isset($filters['search']) ? trim((string) $filters['search']) : null;
        $sortedField = isset($filters['sortedField']) ? (string) $filters['sortedField'] : 'id';
        $sortedBy = strtolower((string) ($filters['sortedBy'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';

        $qb = $this->users->createUsersQueryBuilder($search, $sortedField, $sortedBy, $currentUserId);
        $qb->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $paginator = new Paginator($qb, true);
        $total = count($paginator);
        $lastPage = (int) max(1, (int) ceil($total / $perPage));
        $from = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
        $to = $total === 0 ? 0 : min($page * $perPage, $total);

        return [
            'items' => iterator_to_array($paginator->getIterator()),
            'pagination' => [
                'total' => $total,
                'perPage' => $perPage,
                'currentPage' => $page,
                'lastPage' => $lastPage,
                'from' => $from,
                'to' => $to,
            ],
        ];
    }

    public function createUser(array $data, ?string $ipAddress = null): User|false
    {
        if ($this->users->findOneByEmail($data['email'])) {
            return false;
        }

        return $this->common->transactional(function () use ($data, $ipAddress) {
            $now = new \DateTimeImmutable('now');
            $user = new User();
            $user->setUuid($this->common->generateUuidV4());
            $user->setName($data['name']);
            $user->setEmail($data['email']);
            $user->setPhone($data['phone'] ?? null);
            $user->setStatus('active');
            $user->setRole($data['role'] ?? 'admin');
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
            $user->setCreatedAt($now);
            $user->setUpdatedAt($now);
            if ($ipAddress) {
                $user->setLastLoginIp($ipAddress);
            }

            $this->em->persist($user);
            $this->em->flush();

            return $user;
        });
    }

    public function updateUser(User $user, array $data): User
    {
        return $this->common->transactional(function () use ($user, $data) {
            $now = new \DateTimeImmutable('now');
            if (array_key_exists('name', $data) && $data['name'] !== null) {
                $user->setName($data['name']);
            }
            if (array_key_exists('phone', $data)) {
                $user->setPhone($data['phone']);
            }
            if (array_key_exists('role', $data) && $data['role'] !== null) {
                $user->setRole($data['role']);
            }
            if (!empty($data['password'])) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
            }
            $user->setUpdatedAt($now);

            $this->em->flush();

            return $user;
        });
    }

    public function deleteUser(User $user): void
    {
        $this->common->transactional(function () use ($user) {
            $now = new \DateTimeImmutable('now');
            $user->setStatus('deleted');
            $user->setDeletedAt($now);
            $user->setUpdatedAt($now);
            $this->em->flush();
        });
    }

    public function toggleStatus(User $user): User
    {
        return $this->common->transactional(function () use ($user) {
            $now = new \DateTimeImmutable('now');
            $newStatus = $user->getStatus() === 'active' ? 'inactive' : 'active';
            $user->setStatus($newStatus);
            $user->setUpdatedAt($now);
            $this->em->flush();

            return $user;
        });
    }

    public function getByUuid(string $uuid): ?User
    {
        return $this->users->findActiveByUuid($uuid);
    }
}
