<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class UserPermissionService
{
    public function __construct(
        private Connection $connection,
        private CommonService $common
    ) {}

    public function toggle(int $userId, int $modulePermissionId, bool $isChecked): array|int
    {
        return $this->common->transactional(function () use ($userId, $modulePermissionId, $isChecked) {
            if ($isChecked) {
                $existing = $this->connection->fetchAssociative(
                    'SELECT id, user_id, module_permission_id FROM user_permissions WHERE user_id = :userId AND module_permission_id = :modulePermissionId LIMIT 1',
                    [
                        'userId' => $userId,
                        'modulePermissionId' => $modulePermissionId,
                    ],
                    [
                        'userId' => Types::INTEGER,
                        'modulePermissionId' => Types::INTEGER,
                    ]
                );

                if ($existing !== false) {
                    return [
                        'id' => (int) $existing['id'],
                        'user_id' => (int) $existing['user_id'],
                        'module_permission_id' => (int) $existing['module_permission_id'],
                    ];
                }

                $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
                $this->connection->insert('user_permissions', [
                    'user_id' => $userId,
                    'module_permission_id' => $modulePermissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], [
                    'user_id' => Types::INTEGER,
                    'module_permission_id' => Types::INTEGER,
                    'created_at' => Types::STRING,
                    'updated_at' => Types::STRING,
                ]);

                return [
                    'id' => (int) $this->connection->lastInsertId(),
                    'user_id' => $userId,
                    'module_permission_id' => $modulePermissionId,
                ];
            }

            return $this->connection->executeStatement(
                'DELETE FROM user_permissions WHERE user_id = :userId AND module_permission_id = :modulePermissionId',
                [
                    'userId' => $userId,
                    'modulePermissionId' => $modulePermissionId,
                ],
                [
                    'userId' => Types::INTEGER,
                    'modulePermissionId' => Types::INTEGER,
                ]
            );
        });
    }

    public function getModulePermissionMatrix(int $userId, string $isPermission = 'N'): array
    {
        $user = $this->connection->fetchAssociative(
            'SELECT id, role FROM users WHERE id = :id LIMIT 1',
            ['id' => $userId],
            ['id' => Types::INTEGER]
        );

        if ($user === false) {
            return [
                'modules' => [],
                'userPermissions' => [],
            ];
        }

        $sql = <<<SQL
SELECT
    m.id AS module_id,
    m.name AS module_name,
    m.parent_id,
    m.url,
    m.icon,
    m.seq_no,
    m.is_sub_module,
    m.is_permission,
    p.id AS permission_id,
    p.action AS permission_action,
    mp.id AS module_permission_id,
    up.module_permission_id AS user_has_permission
FROM modules m
INNER JOIN role_modules rm
    ON rm.module_id = m.id
    AND rm.role = :role
CROSS JOIN permissions p
LEFT JOIN module_permissions mp
    ON mp.module_id = m.id
    AND mp.permission_id = p.id
LEFT JOIN user_permissions up
    ON up.module_permission_id = mp.id
    AND up.user_id = :userId
WHERE m.status = 'active'
  AND p.status = 'active'
SQL;

        if ($isPermission === 'Y') {
            $sql .= " AND m.is_permission = 'Y'";
        }

        $sql .= ' ORDER BY m.seq_no ASC, m.id ASC, p.id ASC';

        $rows = $this->connection->fetchAllAssociative(
            $sql,
            [
                'role' => (string) $user['role'],
                'userId' => $userId,
            ],
            [
                'role' => Types::STRING,
                'userId' => Types::INTEGER,
            ]
        );

        $parentNames = $this->connection->fetchAllKeyValue('SELECT id, name FROM modules');
        $modules = [];
        $userPermissions = [];

        foreach ($rows as $row) {
            $moduleId = (int) $row['module_id'];
            $parentId = $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
            if (!isset($modules[$moduleId])) {
                $displayName = $parentId === null
                    ? (string) $row['module_name']
                    : (($parentNames[(string) $parentId] ?? 'Parent') . ' > ' . (string) $row['module_name']);

                $modules[$moduleId] = [
                    'id' => $moduleId,
                    'name' => (string) $row['module_name'],
                    'url' => $row['url'],
                    'icon' => $row['icon'],
                    'seq_no' => $row['seq_no'] !== null ? (int) $row['seq_no'] : null,
                    'is_sub_module' => (string) $row['is_sub_module'],
                    'parent_id' => $parentId,
                    'is_permission' => (string) $row['is_permission'],
                    'displayName' => $displayName,
                    'permissions' => [],
                ];
            }

            $modules[$moduleId]['permissions'][] = [
                'id' => (int) $row['permission_id'],
                'action' => (string) $row['permission_action'],
                'modulePermissionId' => $row['module_permission_id'] !== null ? (int) $row['module_permission_id'] : null,
            ];

            if ($row['user_has_permission'] !== null) {
                $userPermissions[] = (int) $row['user_has_permission'];
            }
        }

        return [
            'modules' => array_values($modules),
            'userPermissions' => array_values(array_unique($userPermissions)),
        ];
    }

    public function getUserModuleAccess(int $userId): array
    {
        $user = $this->connection->fetchAssociative(
            'SELECT id, role FROM users WHERE id = :id LIMIT 1',
            ['id' => $userId],
            ['id' => Types::INTEGER]
        );

        if ($user === false) {
            return [
                'roleModules' => [],
                'permissions' => [],
            ];
        }

        $roleModuleIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT module_id FROM role_modules WHERE role = :role',
            ['role' => (string) $user['role']],
            ['role' => Types::STRING]
        ));

        if ($roleModuleIds === []) {
            return [
                'roleModules' => [],
                'permissions' => [],
            ];
        }

        $modules = $this->connection->fetchAllAssociative(
            'SELECT id, name FROM modules WHERE id IN (:ids) ORDER BY seq_no ASC, id ASC',
            ['ids' => $roleModuleIds],
            ['ids' => ArrayParameterType::INTEGER]
        );

        $userActionRows = $this->connection->fetchAllAssociative(
            'SELECT mp.module_id, p.action
             FROM user_permissions up
             INNER JOIN module_permissions mp ON mp.id = up.module_permission_id
             INNER JOIN permissions p ON p.id = mp.permission_id
             WHERE up.user_id = :userId AND mp.module_id IN (:ids)',
            [
                'userId' => $userId,
                'ids' => $roleModuleIds,
            ],
            [
                'userId' => Types::INTEGER,
                'ids' => ArrayParameterType::INTEGER,
            ]
        );

        $defaultActionRows = $this->connection->fetchAllAssociative(
            'SELECT mp.module_id, p.action
             FROM module_permissions mp
             INNER JOIN permissions p ON p.id = mp.permission_id
             WHERE mp.module_id IN (:ids)',
            ['ids' => $roleModuleIds],
            ['ids' => ArrayParameterType::INTEGER]
        );

        $userAccess = [];
        foreach ($userActionRows as $row) {
            $moduleId = (int) $row['module_id'];
            $userAccess[$moduleId][] = (string) $row['action'];
        }

        $defaultAccess = [];
        foreach ($defaultActionRows as $row) {
            $moduleId = (int) $row['module_id'];
            $defaultAccess[$moduleId][] = (string) $row['action'];
        }

        $permissionMap = [];
        $roleModules = [];
        foreach ($modules as $module) {
            $moduleId = (int) $module['id'];
            $moduleKey = (string) $module['name'];
            $roleModules[] = $moduleKey;

            if (!empty($userAccess[$moduleId])) {
                $permissionMap[$moduleKey] = array_values(array_unique($userAccess[$moduleId]));
            } else {
                $permissionMap[$moduleKey] = array_values(array_unique($defaultAccess[$moduleId] ?? []));
            }
        }

        return [
            'roleModules' => array_values(array_unique($roleModules)),
            'permissions' => $permissionMap,
        ];
    }

    public function buildSidebarMenu(int $userId): array
    {
        $user = $this->connection->fetchAssociative(
            'SELECT id, role FROM users WHERE id = :id LIMIT 1',
            ['id' => $userId],
            ['id' => Types::INTEGER]
        );

        if ($user === false) {
            return [];
        }

        $roleModuleIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT module_id FROM role_modules WHERE role = :role',
            ['role' => (string) $user['role']],
            ['role' => Types::STRING]
        ));

        $matrix = $this->getModulePermissionMatrix($userId, 'N');

        $modules = array_values(array_filter($matrix['modules'], static function (array $module) use ($roleModuleIds): bool {
            return in_array((int) $module['id'], $roleModuleIds, true);
        }));

        return $this->applyUserPermissionRules($modules, $matrix['userPermissions']);
    }

    private function applyUserPermissionRules(array $modules, array $userPermissions): array
    {
        $userPermissionSet = array_flip(array_map('intval', $userPermissions));
        $moduleMap = [];

        foreach ($modules as $module) {
            $permissions = $module['permissions'] ?? [];
            $configuredPermissionIds = [];
            $viewModulePermissionId = null;

            foreach ($permissions as $permission) {
                if (!empty($permission['modulePermissionId'])) {
                    $configuredPermissionIds[] = (int) $permission['modulePermissionId'];
                }
                if (($permission['action'] ?? '') === 'view') {
                    $viewModulePermissionId = $permission['modulePermissionId'] !== null ? (int) $permission['modulePermissionId'] : null;
                }
            }

            $hasAnyConfigured = count($configuredPermissionIds) > 0;
            $userHasAnyForModule = false;
            foreach ($configuredPermissionIds as $configuredPermissionId) {
                if (isset($userPermissionSet[$configuredPermissionId])) {
                    $userHasAnyForModule = true;
                    break;
                }
            }

            if (
                !$hasAnyConfigured
                || ($viewModulePermissionId !== null && isset($userPermissionSet[$viewModulePermissionId]))
                || ($hasAnyConfigured && !$userHasAnyForModule)
            ) {
                $moduleId = (int) $module['id'];
                $moduleMap[$moduleId] = [
                    'id' => $moduleId,
                    'name' => $module['name'],
                    'url' => $module['url'],
                    'icon' => $module['icon'],
                    'parent_id' => $module['parent_id'],
                    'children' => [],
                ];
            }
        }

        $tree = [];
        foreach ($moduleMap as $module) {
            $parentId = $module['parent_id'] !== null ? (int) $module['parent_id'] : null;
            if ($parentId === null) {
                $tree[$module['id']] = $module;
                continue;
            }

            if (isset($moduleMap[$parentId])) {
                $tree[$parentId]['children'][] = $module;
            }
        }

        return array_values($tree);
    }
}
