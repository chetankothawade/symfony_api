<?php

namespace App\Repository;

use App\Entity\Permission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Permission>
 */
class PermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Permission::class);
    }

    /**
     * @return Permission[]
     */
    public function findByActions(array $actions): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.action IN (:actions)')
            ->setParameter('actions', $actions)
            ->getQuery()
            ->getResult();
    }
}
