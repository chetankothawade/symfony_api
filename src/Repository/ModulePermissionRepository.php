<?php

namespace App\Repository;

use App\Entity\ModulePermission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModulePermission>
 */
class ModulePermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModulePermission::class);
    }

    public function hasAnyForModuleId(int $moduleId): bool
    {
        $count = (int) $this->createQueryBuilder('mp')
            ->select('COUNT(mp.id)')
            ->join('mp.module', 'm')
            ->andWhere('m.id = :moduleId')
            ->setParameter('moduleId', $moduleId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
