<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Module;
use App\Entity\ModulePermission;
use App\Entity\RoleModule;
use App\Repository\ModulePermissionRepository;
use App\Repository\ModuleRepository;
use App\Repository\PermissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

class ModuleService
{
    public function __construct(
        private ModuleRepository $modules,
        private PermissionRepository $permissions,
        private ModulePermissionRepository $modulePermissions,
        private EntityManagerInterface $em,
        private CommonService $common
    ) {}

    /**
     * @return array{items: Module[], pagination: array{total:int, perPage:int, currentPage:int, lastPage:int, from:int, to:int}}
     */
    public function getPaginatedModules(array $filters): array
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
        $status = isset($filters['status']) ? trim((string) $filters['status']) : null;
        $sortedField = isset($filters['sortedField']) ? (string) $filters['sortedField'] : 'id';
        $sortedBy = strtolower((string) ($filters['sortedBy'] ?? 'desc')) === 'desc' ? 'DESC' : 'ASC';

        $qb = $this->modules->createModulesQueryBuilder($search, $status, $sortedField, $sortedBy);
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

    public function createModule(array $data): Module
    {
        return $this->common->transactional(function () use ($data) {
            $module = new Module();
            $now = new \DateTimeImmutable('now');

            $module->setUuid(!empty($data['uuid']) ? (string) $data['uuid'] : $this->common->generateUuidV4());
            $module->setName((string) $data['name']);
            $module->setUrl($data['url'] ?? null);
            $module->setIcon($data['icon'] ?? null);
            $module->setSeqNo(isset($data['seq_no']) ? (int) $data['seq_no'] : null);
            $module->setIsSubModule((string) ($data['is_sub_module'] ?? 'N'));
            $module->setStatus((string) ($data['status'] ?? 'active'));
            $module->setIsPermission((string) ($data['is_permission'] ?? 'N'));
            $module->setCreatedAt($now);
            $module->setUpdatedAt($now);

            $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
            if ($parentId !== null && $parentId !== 0) {
                $parent = $this->em->find(Module::class, (string) $parentId);
                if ($parent instanceof Module) {
                    $module->setParent($parent);
                }
            }

            $this->em->persist($module);
            $this->em->flush();

            if ($module->getIsSubModule() === 'N') {
                $this->createDefaultPermissionMappings($module);
                $this->createSuperAdminRoleMapping($module);
            }

            $this->em->flush();

            return $module;
        });
    }

    public function updateModule(Module $module, array $data): Module
    {
        return $this->common->transactional(function () use ($module, $data) {
            $now = new \DateTimeImmutable('now');

            if (array_key_exists('uuid', $data) && $data['uuid'] !== null) {
                $module->setUuid((string) $data['uuid']);
            }
            if (array_key_exists('name', $data) && $data['name'] !== null) {
                $module->setName((string) $data['name']);
            }
            if (array_key_exists('url', $data)) {
                $module->setUrl($data['url']);
            }
            if (array_key_exists('icon', $data)) {
                $module->setIcon($data['icon']);
            }
            if (array_key_exists('seq_no', $data)) {
                $module->setSeqNo($data['seq_no'] !== null ? (int) $data['seq_no'] : null);
            }
            if (array_key_exists('is_sub_module', $data) && $data['is_sub_module'] !== null) {
                $module->setIsSubModule((string) $data['is_sub_module']);
            }
            if (array_key_exists('status', $data) && $data['status'] !== null) {
                $module->setStatus((string) $data['status']);
            }
            if (array_key_exists('is_permission', $data) && $data['is_permission'] !== null) {
                $module->setIsPermission((string) $data['is_permission']);
            }
            if (array_key_exists('parent_id', $data)) {
                $parentId = $data['parent_id'] !== null ? (int) $data['parent_id'] : null;
                if ($parentId === null || $parentId === 0) {
                    $module->setParent(null);
                } else {
                    $parent = $this->em->find(Module::class, (string) $parentId);
                    if ($parent instanceof Module) {
                        $module->setParent($parent);
                    }
                }
            }

            $module->setUpdatedAt($now);
            $this->em->flush();

            $moduleId = (int) ($module->getId() ?? 0);
            $hasMappings = $moduleId > 0 ? $this->modulePermissions->hasAnyForModuleId($moduleId) : false;
            $isPermission = (string) ($data['is_permission'] ?? $module->getIsPermission());

            if (!$hasMappings && $isPermission === 'Y') {
                $this->createDefaultPermissionMappings($module);
                $this->em->flush();
            }

            return $module;
        });
    }

    public function getByUuid(string $uuid): ?Module
    {
        return $this->modules->findOneByUuid($uuid);
    }

    public function deleteModule(Module $module): void
    {
        $this->common->transactional(function () use ($module) {
            $this->em->remove($module);
            $this->em->flush();
        });
    }

    public function toggleStatus(Module $module): Module
    {
        return $this->common->transactional(function () use ($module) {
            $module->setStatus($module->getStatus() === 'active' ? 'inactive' : 'active');
            $module->setUpdatedAt(new \DateTimeImmutable('now'));
            $this->em->flush();

            return $module;
        });
    }

    /**
     * @return Module[]
     */
    public function getAllParent(): array
    {
        return $this->modules->findParentModulesList();
    }

    private function createDefaultPermissionMappings(Module $module): void
    {
        $permissions = $this->permissions->findByActions(['view', 'create', 'edit', 'delete', 'status']);
        foreach ($permissions as $permission) {
            $mapping = new ModulePermission();
            $mapping->setModule($module);
            $mapping->setPermission($permission);
            $mapping->setCreatedAt(new \DateTimeImmutable('now'));
            $mapping->setUpdatedAt(new \DateTimeImmutable('now'));
            $this->em->persist($mapping);
        }
    }

    private function createSuperAdminRoleMapping(Module $module): void
    {
        $roleModule = new RoleModule();
        $roleModule->setModule($module);
        $roleModule->setRole('super_admin');
        $roleModule->setCreatedAt(new \DateTimeImmutable('now'));
        $roleModule->setUpdatedAt(new \DateTimeImmutable('now'));
        $this->em->persist($roleModule);
    }
}
