<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use MoonShine\Support\Enums\ToastType;
use Symfony\Component\HttpFoundation\StreamedResponse;
use YuriZoom\MoonShineMediaManager\Events\MediaManagerFileDeleted;
use YuriZoom\MoonShineMediaManager\Events\MediaManagerFileUploaded;
use YuriZoom\MoonShineMediaManager\Exceptions\MediaManagerException;
use YuriZoom\MoonShineMediaManager\Helpers\URLGenerator;
use YuriZoom\MoonShineMediaManager\Pages\MediaManagerPage;
use YuriZoom\MoonShineMediaManager\Support\MediaNavigator;
use YuriZoom\MoonShineMediaManager\Support\MediaSecurity;
use YuriZoom\MoonShineMediaManager\Support\MediaValidator;

class MediaManager
{
    protected string $path = '/';

    protected Filesystem $storage;

    protected MediaNavigator $navigator;

    public function __construct(string $path = '/')
    {
        $this->path = URLGenerator::sanitizePath($path);
        MediaSecurity::assertNotBlockedPath($this->path);

        $this->initStorage();
        $this->initNavigator();
    }

    private function initStorage(): void
    {
        $disk = config('moonshine.media_manager.disk');

        $this->storage = Storage::disk($disk);

        if (! $this->storage->getAdapter() instanceof LocalFilesystemAdapter) {
            throw new MediaManagerException(
                __('moonshine-media-manager::media-manager.error.only_local_storage')
            );
        }
    }

    private function initNavigator(): void
    {
        $view = request('view', config('moonshine.media_manager.default_view', 'table'));

        $this->navigator = new MediaNavigator(
            $this->path,
            $this->storage,
            $this->indexUrl([]),
            $view,
        );
    }

    public function ls(): array
    {
        if (! $this->exists()) {
            toast(
                __('moonshine-media-manager::media-manager.error.file_not_exists', ['path' => $this->path]),
                ToastType::ERROR
            );

            return [];
        }

        $files = $this->storage->files($this->path);
        $directories = $this->storage->directories($this->path);

        return $this->navigator->formatDirectories($directories, $this->routeCallback(...))
            ->merge($this->navigator->formatFiles($files, $this->routeCallback(...)))
            ->sort(function ($item) {
                return ($item['isDir'] ? '__' : '').$item['path'];
            })
            ->values()
            ->all();
    }

    public function download(): StreamedResponse
    {
        return $this->storage->download($this->path);
    }

    public function delete($path): bool
    {
        $paths = is_array($path) ? $path : func_get_args();
        $deleted = false;

        foreach ($paths as $rawPath) {
            $safePath = URLGenerator::sanitizePath($rawPath);
            MediaSecurity::assertNotBlockedPath($safePath);

            if ($this->storage->fileExists($safePath)) {
                $this->storage->delete($safePath);
                $deleted = true;
                if (class_exists(MediaManagerFileDeleted::class)) {
                    MediaManagerFileDeleted::dispatch($safePath, $this->getDisk());
                }
            } elseif ($this->storage->directoryExists($safePath)) {
                $this->storage->deleteDirectory($safePath);
                $deleted = true;
            }
        }

        return $deleted;
    }

    public function move(string $new): bool
    {
        $safeNew = URLGenerator::sanitizePath($new);
        MediaSecurity::assertNotBlockedPath($safeNew);

        return $this->storage->move($this->path, $safeNew);
    }

    public function upload(array $files = []): bool
    {
        if ($files === []) {
            return true;
        }

        $maxFileSize = config('moonshine.media_manager.max_file_size', 10 * 1024 * 1024);
        $allowed = ! empty(config('moonshine.media_manager.allowed_ext'))
            ? explode(',', config('moonshine.media_manager.allowed_ext'))
            : [];
        $renameDuplicates = (bool) config('moonshine.media_manager.rename_duplicates', true);

        $validator = new MediaValidator($allowed, $maxFileSize);

        foreach ($files as $file) {
            $validator->validateUploadedFile($file);

            $safeName = URLGenerator::sanitizeFileName($file->getClientOriginalName());

            if ($renameDuplicates) {
                $safeName = $this->uniqueName($safeName);
            }

            $path = rtrim($this->path, '/').'/'.$safeName;
            $this->storage->putFileAs($this->path, $file, $safeName);

            if (class_exists(MediaManagerFileUploaded::class)) {
                MediaManagerFileUploaded::dispatch($path, $this->getDisk());
            }
        }

        return true;
    }

    /**
     * Generate a unique filename inside the current path by appending a
     * numeric suffix ("-1", "-2", ...) when a file with the same name
     * already exists. Falls back to the original name after a sane limit.
     */
    private function uniqueName(string $name): string
    {
        $basePath = rtrim($this->path, '/');

        if (! $this->storage->fileExists($basePath.'/'.$name)) {
            return $name;
        }

        $basename = pathinfo($name, PATHINFO_FILENAME);
        $extension = pathinfo($name, PATHINFO_EXTENSION);

        for ($i = 1; $i <= 9999; $i++) {
            $candidate = $extension !== ''
                ? "{$basename}-{$i}.{$extension}"
                : "{$basename}-{$i}";

            if (! $this->storage->fileExists($basePath.'/'.$candidate)) {
                return $candidate;
            }
        }

        return $name;
    }

    public function newFolder(string $name): bool
    {
        $safeName = URLGenerator::sanitizeFileName($name);
        $path = rtrim($this->path, '/').'/'.$safeName;

        return $this->storage->makeDirectory($path);
    }

    public function exists(): bool
    {
        return $this->storage->exists($this->path);
    }

    public function getDisk(): string
    {
        return config('moonshine.media_manager.disk', 'public');
    }

    public function urls(): array
    {
        return [
            'path' => $this->path,
            'index' => route('moonshine.media.manager.index'),
            'page' => $this->indexUrl(),
            'move' => route('moonshine.media.manager.move'),
            'delete' => route('moonshine.media.manager.delete'),
            'upload' => route('moonshine.media.manager.upload'),
            'new-folder' => route('moonshine.media.manager.new.folder'),
        ];
    }

    public function navigation(): array
    {
        return $this->navigator->navigation();
    }

    protected function indexUrl(array $params = []): string
    {
        return app(MediaManagerPage::class)->getRoute($params);
    }

    private function routeCallback(string $action, array $params = []): string
    {
        $routeName = match ($action) {
            'download' => 'moonshine.media.manager.download',
            'index' => 'moonshine.media.manager.index',
            default => throw new \InvalidArgumentException("Unknown route action: {$action}"),
        };

        return route($routeName, $params);
    }
}