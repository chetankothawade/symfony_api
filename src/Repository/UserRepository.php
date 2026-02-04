<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveByUuid(string $uuid): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.uuid = :uuid')
            ->andWhere('u.deletedAt IS NULL')
            ->andWhere('u.status != :deletedStatus')
            ->setParameter('uuid', $uuid)
            ->setParameter('deletedStatus', 'deleted')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function createUsersQueryBuilder(
        ?string $search,
        string $sortedField,
        string $sortedBy,
        ?int $currentUserId = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.deletedAt IS NULL')
            ->andWhere('u.status != :deletedStatus')
            ->andWhere('u.role != :superAdminRole')
            ->setParameter('deletedStatus', 'deleted')
            ->setParameter('superAdminRole', 'super_admin');

        if ($currentUserId !== null) {
            $qb->andWhere('u.id != :currentUserId')
                ->setParameter('currentUserId', $currentUserId);
        }

        if ($search !== null && $search !== '') {
            $qb->andWhere('u.name LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $fieldMap = [
            'id' => 'u.id',
            'name' => 'u.name',
            'email' => 'u.email',
            'phone' => 'u.phone',
            'status' => 'u.status',
            'role' => 'u.role',
            'created_at' => 'u.createdAt',
            'createdAt' => 'u.createdAt',
            'updated_at' => 'u.updatedAt',
            'updatedAt' => 'u.updatedAt',
        ];
        $sortField = $fieldMap[$sortedField] ?? 'u.id';
        $sortDirection = strtoupper($sortedBy) === 'DESC' ? 'DESC' : 'ASC';

        $qb->orderBy($sortField, $sortDirection);

        return $qb;
    }


    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

}
