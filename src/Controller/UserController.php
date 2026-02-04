<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\UserStoreRequest;
use App\Dto\UserUpdateRequest;
use App\Entity\User;
use App\Service\UserService;
use App\Traits\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    use ApiResponse;

    #[Route('/api/users', name: 'api_users_index', methods: ['GET'])]
    public function index(Request $request, UserService $userService): JsonResponse
    {
        $filters = [
            'search' => $request->query->get('search'),
            'sortedField' => $request->query->get('sortedField', 'id'),
            'sortedBy' => $request->query->get('sortedBy', 'asc'),
            'perPage' => $request->query->getInt('perPage', 10),
            'page' => $request->query->getInt('page', 1),
        ];

        $currentUserId = null;
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() !== null) {
            $currentUserId = (int) $currentUser->getId();
        }

        $result = $userService->getPaginatedUsers($filters, $currentUserId);
        $data = array_map(fn(User $user) => $this->serializeUser($user), $result['items']);

        return new JsonResponse([
            'status' => true,
            'message' => 'Success.',
            'data' => $data,
            'pagination' => $result['pagination'],
        ]);
    }

    #[Route('/api/users', name: 'api_users_store', methods: ['POST'])]
    public function store(
        #[MapRequestPayload] UserStoreRequest $userStoreRequest,
        UserService $userService,
        Request $request
    ): JsonResponse {
        $user = $userService->createUser([
            'name' => $userStoreRequest->name,
            'email' => $userStoreRequest->email,
            'password' => $userStoreRequest->password,
            'phone' => $userStoreRequest->phone,
            'role' => $userStoreRequest->role,
        ], $request->getClientIp());

        if (!$user) {
            return $this->error('Validation failed.', [
                'email' => ['The email has already been taken.'],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success('User created successfully.', [
            'user' => $this->serializeUser($user),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/users/{uuid}', name: 'api_users_show', methods: ['GET'])]
    public function show(string $uuid, UserService $userService): JsonResponse
    {
        $user = $userService->getByUuid($uuid);
        if (!$user) {
            return $this->error('User not found.', [], Response::HTTP_NOT_FOUND);
        }

        return $this->success('Success.', [
            'user' => $this->serializeUser($user),
        ]);
    }

    #[Route('/api/users/{uuid}', name: 'api_users_update', methods: ['PUT', 'PATCH'])]
    public function update(
        #[MapRequestPayload] UserUpdateRequest $userUpdateRequest,
        string $uuid,
        UserService $userService
    ): JsonResponse {
        $user = $userService->getByUuid($uuid);
        if (!$user) {
            return $this->error('User not found.', [], Response::HTTP_NOT_FOUND);
        }

        $updated = $userService->updateUser($user, [
            'name' => $userUpdateRequest->name,
            'phone' => $userUpdateRequest->phone,
            'role' => $userUpdateRequest->role,
            'password' => $userUpdateRequest->password,
        ]);

        return $this->success('User updated successfully.', [
            'user' => $this->serializeUser($updated),
        ]);
    }

    #[Route('/api/users/{uuid}', name: 'api_users_delete', methods: ['DELETE'])]
    public function destroy(string $uuid, UserService $userService): JsonResponse
    {
        $user = $userService->getByUuid($uuid);
        if (!$user) {
            return $this->error('User not found.', [], Response::HTTP_NOT_FOUND);
        }

        $userService->deleteUser($user);

        return $this->success('User deleted successfully.', []);
    }

    #[Route('/api/users/{uuid}/toggle-status', name: 'api_users_toggle_status', methods: ['PATCH'])]
    public function toggleStatus(string $uuid, UserService $userService): JsonResponse
    {
        $user = $userService->getByUuid($uuid);
        if (!$user) {
            return $this->error('User not found.', [], Response::HTTP_NOT_FOUND);
        }

        $user = $userService->toggleStatus($user);

        return $this->success('User status updated successfully.', [
            'status' => $user->getStatus(),
        ]);
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'uuid' => $user->getUuid(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
            'status' => $user->getStatus(),
            'role' => $user->getRole(),
            'last_login_at' => $user->getLastLoginAt()?->format(DATE_ATOM),
            'last_login_ip' => $user->getLastLoginIp(),
            'last_login_ua' => $user->getLastLoginUa(),
            'created_at' => $user->getCreatedAt()?->format(DATE_ATOM),
            'updated_at' => $user->getUpdatedAt()?->format(DATE_ATOM),
            'deleted_at' => $user->getDeletedAt()?->format(DATE_ATOM),
        ];
    }
}
