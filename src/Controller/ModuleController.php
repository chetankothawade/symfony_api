<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\ModuleStoreRequest;
use App\Dto\ModuleUpdateRequest;
use App\Entity\Module;
use App\Service\ModuleService;
use App\Traits\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class ModuleController extends AbstractController
{
    use ApiResponse;

    #[Route('/api/module', name: 'api_modules_index', methods: ['GET'])]
    public function index(Request $request, ModuleService $moduleService): JsonResponse
    {
        $filters = [
            'search' => $request->query->get('search'),
            'status' => $request->query->get('status'),
            'sortedField' => $request->query->get('sortedField', 'id'),
            'sortedBy' => $request->query->get('sortedBy', 'desc'),
            'perPage' => $request->query->getInt('perPage', 10),
            'page' => $request->query->getInt('page', 1),
        ];

        $result = $moduleService->getPaginatedModules($filters);

        return new JsonResponse([
            'status' => true,
            'message' => 'Module list fetched successfully.',
            'data' => array_map(fn(Module $module) => $this->serializeModule($module), $result['items']),
            'pagination' => $result['pagination'],
        ]);
    }

    #[Route('/api/module', name: 'api_modules_store', methods: ['POST'])]
    public function store(
        #[MapRequestPayload] ModuleStoreRequest $moduleStoreRequest,
        ModuleService $moduleService
    ): JsonResponse {
        try {
            $module = $moduleService->createModule([
                'uuid' => $moduleStoreRequest->uuid,
                'parent_id' => $moduleStoreRequest->parent_id,
                'name' => $moduleStoreRequest->name,
                'url' => $moduleStoreRequest->url,
                'icon' => $moduleStoreRequest->icon,
                'seq_no' => $moduleStoreRequest->seq_no,
                'is_sub_module' => $moduleStoreRequest->is_sub_module,
                'status' => $moduleStoreRequest->status,
                'is_permission' => $moduleStoreRequest->is_permission,
            ]);
        } catch (\Throwable $e) {
            return $this->error('Module could not be created.', ['exception' => [$e->getMessage()]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success('Module created successfully.', [
            'module' => $this->serializeModule($module),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/module/getList', name: 'api_modules_get_list', methods: ['GET'])]
    public function getModuleList(ModuleService $moduleService): JsonResponse
    {
        $modules = $moduleService->getAllParent();

        return $this->success('Module list fetched successfully.', [
            'modules' => array_map(fn(Module $module) => $this->serializeModule($module), $modules),
        ]);
    }

    #[Route('/api/module/{uuid}', name: 'api_modules_show', methods: ['GET'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    public function show(string $uuid, ModuleService $moduleService): JsonResponse
    {
        $module = $moduleService->getByUuid($uuid);
        if (!$module) {
            return $this->error('Module not found.', [], Response::HTTP_NOT_FOUND);
        }

        return $this->success('Module details fetched successfully.', [
            'module' => $this->serializeModule($module),
        ]);
    }

    #[Route('/api/module/{uuid}/edit', name: 'api_modules_edit', methods: ['GET'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    public function edit(string $uuid, ModuleService $moduleService): JsonResponse
    {
        return $this->show($uuid, $moduleService);
    }

    #[Route('/api/module/{uuid}', name: 'api_modules_update', methods: ['PUT', 'PATCH'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    public function update(
        #[MapRequestPayload] ModuleUpdateRequest $moduleUpdateRequest,
        string $uuid,
        ModuleService $moduleService
    ): JsonResponse {
        $module = $moduleService->getByUuid($uuid);
        if (!$module) {
            return $this->error('Module not found.', [], Response::HTTP_NOT_FOUND);
        }

        try {
            $module = $moduleService->updateModule($module, [
                'uuid' => $moduleUpdateRequest->uuid,
                'parent_id' => $moduleUpdateRequest->parent_id,
                'name' => $moduleUpdateRequest->name,
                'url' => $moduleUpdateRequest->url,
                'icon' => $moduleUpdateRequest->icon,
                'seq_no' => $moduleUpdateRequest->seq_no,
                'is_sub_module' => $moduleUpdateRequest->is_sub_module,
                'status' => $moduleUpdateRequest->status,
                'is_permission' => $moduleUpdateRequest->is_permission,
            ]);
        } catch (\Throwable $e) {
            return $this->error('Module could not be updated.', ['exception' => [$e->getMessage()]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success('Module updated successfully.', [
            'module' => $this->serializeModule($module),
        ]);
    }

    #[Route('/api/module/{uuid}', name: 'api_modules_destroy', methods: ['DELETE'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    public function destroy(string $uuid, ModuleService $moduleService): JsonResponse
    {
        $module = $moduleService->getByUuid($uuid);
        if (!$module) {
            return $this->error('Module not found.', [], Response::HTTP_NOT_FOUND);
        }

        $moduleService->deleteModule($module);

        return $this->success('Module deleted successfully.', []);
    }

    #[Route('/api/module/{uuid}/active', name: 'api_modules_active', methods: ['PATCH'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    public function active(string $uuid, ModuleService $moduleService): JsonResponse
    {
        $module = $moduleService->getByUuid($uuid);
        if (!$module) {
            return $this->error('Module not found.', [], Response::HTTP_NOT_FOUND);
        }

        $module = $moduleService->toggleStatus($module);

        return $this->success('Module status updated successfully.', [
            'status' => $module->getStatus(),
        ]);
    }

    private function serializeModule(Module $module): array
    {
        return [
            'id' => $module->getId(),
            'uuid' => $module->getUuid(),
            'parent_id' => $module->getParent()?->getId(),
            'name' => $module->getName(),
            'url' => $module->getUrl(),
            'icon' => $module->getIcon(),
            'seq_no' => $module->getSeqNo(),
            'is_sub_module' => $module->getIsSubModule(),
            'status' => $module->getStatus(),
            'is_permission' => $module->getIsPermission(),
            'created_at' => $module->getCreatedAt()?->format(DATE_ATOM),
            'updated_at' => $module->getUpdatedAt()?->format(DATE_ATOM),
        ];
    }
}

