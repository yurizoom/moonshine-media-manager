<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
use MoonShine\Laravel\MoonShineRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;
use YuriZoom\MoonShineMediaManager\MediaManager;

class MediaManagerController extends Controller
{
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

    public function upload(MoonShineRequest $request): RedirectResponse
    {
        $files = $request->file('files');
        $dir = $request->get('dir', '/');

        $manager = new MediaManager($dir);

        try {
            $manager->upload($files);
        } catch (\Exception) {
        }

        return Redirect::back();
    }

    public function delete(MoonShineRequest $request): RedirectResponse
    {
        $files = $request->get('files');

        $manager = new MediaManager();

        try {
            $manager->delete($files);
        } catch (\Exception) {
        }

        return Redirect::back();
    }

    public function move(MoonShineRequest $request): RedirectResponse
    {
        $path = $request->get('path');
        $new = $request->get('new');

        $manager = new MediaManager($path);

        try {
            $manager->move($new);
        } catch (\Exception) {
        }

        return Redirect::back();
    }

    public function newFolder(MoonShineRequest $request): RedirectResponse
    {
        $dir = $request->get('dir');
        $name = $request->get('name');

        $manager = new MediaManager($dir);

        try {
            $manager->newFolder($name);
        } catch (\Exception) {
        }

        return Redirect::back();
    }
}
