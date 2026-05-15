<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract as MoonShineRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;
use YuriZoom\MoonShineMediaManager\MediaManager;

class MediaManagerController extends Controller
{
    public function index(MoonShineRequest $request): JsonResponse
    {
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
        $file = $request->get('file');

        $manager = new MediaManager($file);

        try {
            return $manager->download();
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function upload(MoonShineRequest $request): JsonResponse
    {
        $files = $request->file('files');
        $dir = $request->get('dir', '/');

        $manager = new MediaManager($dir);

        try {
            $result = $manager->upload($files);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
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
        $files = $request->get('files');

        $manager = new MediaManager;

        try {
            $manager->delete($files);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => __('moonshine-media-manager::media-manager.deleted_successfully'),
        ]);
    }

    public function move(MoonShineRequest $request): JsonResponse
    {
        $path = $request->get('path');
        $new = $request->get('new');

        $manager = new MediaManager($path);

        try {
            $manager->move($new);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => __('moonshine-media-manager::media-manager.moved_successfully'),
        ]);
    }

    public function newFolder(MoonShineRequest $request): JsonResponse
    {
        $dir = $request->get('dir');
        $name = $request->get('name');

        $manager = new MediaManager($dir);

        try {
            $manager->newFolder($name);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => __('moonshine-media-manager::media-manager.folder_created_successfully'),
        ]);
    }
}
