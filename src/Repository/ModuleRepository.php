<?php

namespace App\Repository;

use App\Entity\Module;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Module>
 */
class ModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Module::class);
    }

    public function findOneByUuid(string $uuid): ?Module
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function createModulesQueryBuilder(?string $search, ?string $status, string $sortedField, string $sortedBy): QueryBuilder
    {
        $qb = $this->createQueryBuilder('m');

        if ($search !== null && $search !== '') {
            $qb->andWhere('m.name LIKE :search OR m.url LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('m.status = :status')
                ->setParameter('status', $status);
        }

        $fieldMap = [
            'id' => 'm.id',
            'name' => 'm.name',
            'url' => 'm.url',
            'status' => 'm.status',
            'seq_no' => 'm.seqNo',
            'seqNo' => 'm.seqNo',
            'created_at' => 'm.createdAt',
            'createdAt' => 'm.createdAt',
            'updated_at' => 'm.updatedAt',
            'updatedAt' => 'm.updatedAt',
        ];

        $sortField = $fieldMap[$sortedField] ?? 'm.id';
        $sortDirection = strtoupper($sortedBy) === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy($sortField, $sortDirection);

        return $qb;
    }

    /**
     * @return Module[]
     */
    public function findParentModulesList(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.parent IS NULL')
            ->andWhere('m.isSubModule = :isSubModule')
            ->setParameter('isSubModule', 'Y')
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
