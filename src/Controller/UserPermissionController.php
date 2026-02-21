<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\UserPermissionToggleRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserPermissionService;
use App\Traits\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class UserPermissionController extends AbstractController
{
    use ApiResponse;

    public function __construct(
        private UserPermissionService $service,
        private UserRepository $users
    ) {}

    #[Route('/api/user-permissions/toggle', name: 'api_user_permissions_toggle', methods: ['POST'])]
    public function toggle(#[MapRequestPayload] UserPermissionToggleRequest $request): JsonResponse
    {
        $user = $this->users->findOneBy(['uuid' => $request->userUuid]);
        if (!$user instanceof User) {
            return $this->error('User not found.', [], Response::HTTP_NOT_FOUND);
        }

        $result = $this->service->toggle(
            (int) $user->getId(),
            $request->modulePermissionId,
            $request->isChecked
        );

        return $this->success('User permission updated successfully.', [
            'action' => $request->isChecked ? 'assigned' : 'revoked',
            'result' => $result,
        ]);
    }

    #[Route('/api/user-permissions/getAll/{uuid}', name: 'api_user_permissions_matrix', methods: ['GET'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    public function getUsersModulesPermission(string $uuid): JsonResponse
    {
        $user = $this->users->findOneBy(['uuid' => $uuid]);
        if (!$user instanceof User) {
            return $this->error('User not found.', [], Response::HTTP_NOT_FOUND);
        }

        return $this->success('User permission matrix fetched successfully.', $this->service->getModulePermissionMatrix(
            (int) $user->getId(),
            'Y'
        ));
    }

    #[Route('/api/user-permissions/access/{uuid}', name: 'api_user_permissions_access', methods: ['GET'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    public function userModuleAccess(string $uuid): JsonResponse
    {
        $user = $this->users->findOneBy(['uuid' => $uuid]);
        if (!$user instanceof User) {
            return $this->error('User not found.', [], Response::HTTP_NOT_FOUND);
        }

        return $this->success('User module access fetched successfully.', $this->service->getUserModuleAccess(
            (int) $user->getId()
        ));
    }

    #[Route('/api/user-permissions/sidebar-menu', name: 'api_user_permissions_sidebar', methods: ['GET'])]
    public function sidebarMenu(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getId() === null) {
            return $this->error('Unauthorized.', [], Response::HTTP_UNAUTHORIZED);
        }

        return $this->success('Sidebar loaded successfully.', [
            'items' => $this->service->buildSidebarMenu((int) $user->getId()),
        ]);
    }
}

