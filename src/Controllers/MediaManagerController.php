<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
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
            $manager->upload($files);
        } catch (\Exception) {
        }

        return Redirect::back();
    }

    public function delete(MoonShineRequest $request)
    {
        $files = $request->get('files');

        $manager = new MediaManager();

        try {
            $manager->delete($files);
        } catch (\Exception) {
        }

        return Redirect::back();
    }

    public function move(MoonShineRequest $request)
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

    public function newFolder(MoonShineRequest $request)
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
