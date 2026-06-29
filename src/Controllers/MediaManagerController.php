<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract as MoonShineRequest;
use MoonShine\Laravel\Http\Controllers\MoonShineController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use YuriZoom\MoonShineMediaManager\Exceptions\MediaManagerException;
use YuriZoom\MoonShineMediaManager\MediaManager;

final class MediaManagerController extends MoonShineController
{
    private function authorizeAction(): void
    {
        $ability = config('moonshine.media_manager.ability');

        if ($ability) {
            Gate::authorize($ability);
        }
    }

    public function index(MoonShineRequest $request): JsonResponse
    {
        $this->authorizeAction();
        $path = $request->get('path', '/');
        $view = $request->get('view', config('moonshine.media_manager.default_view', 'table'));
        $types = $request->get('types', []);
        $extensions = $request->get('extensions', []);

        $manager = new MediaManager($path);

        $files = $manager->ls();

        if (! empty($types) || ! empty($extensions)) {
            $files = array_filter($files, function (array $file) use ($types, $extensions) {
                if ($file['isDir']) {
                    return true;
                }

                if (! empty($types) && ! in_array($file['type'] ?? '', (array) $types, true)) {
                    return false;
                }

                if (! empty($extensions)) {
                    $ext = pathinfo($file['path'], PATHINFO_EXTENSION);
                    if (! in_array(strtolower($ext), array_map('strtolower', (array) $extensions), true)) {
                        return false;
                    }
                }

                return true;
            });
            $files = array_values($files);
        }

        return response()->json([
            'status' => true,
            'files' => $files,
            'navigation' => $manager->navigation(),
            'urls' => $manager->urls(),
            'path' => $path,
            'view' => $view,
        ]);
    }

    public function download(MoonShineRequest $request): JsonResponse|StreamedResponse
    {
        $this->authorizeAction();
        try {
            return (new MediaManager((string) $request->get('file', '/')))->download();
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function upload(MoonShineRequest $request): JsonResponse
    {
        $this->authorizeAction();
        $manager = new MediaManager((string) $request->get('dir', '/'));

        try {
            $result = $manager->upload($request->file('files', []));
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }

        if (! $result) {
            return response()->json([
                'status' => false,
                'message' => __('moonshine-media-manager::media-manager.error.file_extension_not_allowed', ['ext' => '']),
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => __('moonshine-media-manager::media-manager.uploaded_successfully'),
        ]);
    }

    public function delete(MoonShineRequest $request): JsonResponse
    {
        $this->authorizeAction();
        try {
            (new MediaManager)->delete($request->get('files'));
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }

        return response()->json([
            'status' => true,
            'message' => __('moonshine-media-manager::media-manager.deleted_successfully'),
        ]);
    }

    public function move(MoonShineRequest $request): JsonResponse
    {
        $this->authorizeAction();
        try {
            (new MediaManager((string) $request->get('path', '/')))->move((string) $request->get('new', ''));
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }

        return response()->json([
            'status' => true,
            'message' => __('moonshine-media-manager::media-manager.moved_successfully'),
        ]);
    }

    public function newFolder(MoonShineRequest $request): JsonResponse
    {
        $this->authorizeAction();
        try {
            (new MediaManager((string) $request->get('dir', '/')))->newFolder((string) $request->get('name', ''));
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }

        return response()->json([
            'status' => true,
            'message' => __('moonshine-media-manager::media-manager.folder_created_successfully'),
        ]);
    }

    public function replace(MoonShineRequest $request): JsonResponse
    {
        $this->authorizeAction();
        $path = (string) $request->get('path', '/');
        $file = $request->file('file');

        try {
            if (! $file) {
                throw new \YuriZoom\MoonShineMediaManager\Exceptions\MediaManagerException(
                    __('moonshine-media-manager::media-manager.error.select_file')
                );
            }
            (new MediaManager('/'))->replace($path, $file);
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }

        return response()->json([
            'status' => true,
            'message' => __('moonshine-media-manager::media-manager.replaced_successfully'),
        ]);
    }

    private function errorResponse(Throwable $e, int $status = 400): JsonResponse
    {
        if (! $e instanceof MediaManagerException) {
            report($e);
        }

        return response()->json([
            'status' => false,
            'message' => app()->isLocal()
                ? $e->getMessage()
                : __('moonshine-media-manager::media-manager.error.operation_failed'),
        ], $status);
    }
}