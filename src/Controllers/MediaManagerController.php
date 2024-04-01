<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use MoonShine\MoonShineRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;
use YuriZoom\MoonShineMediaManager\MediaManager;

class MediaManagerController extends Controller
{
    public function index(MoonShineRequest $request)
    {
        dd('index');
        $path = $request->get('path', '/');
        $view = $request->get('view', 'table');

        $manager = new MediaManager($path);

        return view("moonshine-media-manager::{$view}", [
            'list' => $manager->ls(),
            'nav' => $manager->navigation(),
            'url' => $manager->urls(),
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

    public function upload(MoonShineRequest $request)
    {
        $files = $request->file('files');
        $dir = $request->get('dir', '/');

        $manager = new MediaManager($dir);

        try {
            if ($manager->upload($files)) {
                //admin_toastr(trans('admin.upload_succeeded'));
            }
        } catch (\Exception $e) {
            //admin_toastr($e->getMessage(), 'error');
        }

        return back();
    }

    public function delete(MoonShineRequest $request)
    {
        $files = $request->get('files');

        $manager = new MediaManager();

        try {
            if ($manager->delete($files)) {
                return response()->json([
                    'status' => true,
                    'message' => __('moonshine-media-manager::media-manager.delete_succeeded'),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => '',
        ]);
    }

    public function move(MoonShineRequest $request)
    {
        $path = $request->get('path');
        $new = $request->get('new');

        $manager = new MediaManager($path);

        try {
            if ($manager->move($new)) {
                return response()->json([
                    'status' => true,
                    'message' => __('moonshine-media-manager::media-manager.move_succeeded'),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => '',
        ]);
    }

    public function newFolder(MoonShineRequest $request)
    {
        $dir = $request->get('dir');
        $name = $request->get('name');

        $manager = new MediaManager($dir);

        try {
            if ($manager->newFolder($name)) {
                return response()->json([
                    'status' => true,
                    'message' => __('moonshine-media-manager::media-manager.move_succeeded'),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => '',
        ]);
    }
}
