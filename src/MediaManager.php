<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use YuriZoom\MoonShineMediaManager\Events\MediaManagerFileDeleted;
use YuriZoom\MoonShineMediaManager\Events\MediaManagerFileReplaced;
use YuriZoom\MoonShineMediaManager\Events\MediaManagerFileUploaded;
use YuriZoom\MoonShineMediaManager\Exceptions\MediaManagerException;
use YuriZoom\MoonShineMediaManager\Helpers\URLGenerator;
use YuriZoom\MoonShineMediaManager\Pages\MediaManagerPage;
use YuriZoom\MoonShineMediaManager\Support\MediaNavigator;
use YuriZoom\MoonShineMediaManager\Support\MediaSecurity;
use YuriZoom\MoonShineMediaManager\Support\MediaValidator;
use YuriZoom\MoonShineMediaManager\Support\SvgSanitizer;

/**
 * Domain core of the media manager: all file operations on the configured
 * local storage disk. Controllers talk to this class only; it never touches
 * HTTP requests or UI helpers.
 */
class MediaManager
{
    protected string $path = '/';

    protected Filesystem $storage;

    protected MediaNavigator $navigator;

    /**
     * @param  string  $path  initial directory (sanitized; traversal segments removed)
     * @param  string|null  $view  display view for navigation links; falls back to the configured default
     *
     * @throws MediaManagerException when the path is blocked or the disk is not local
     */
    public function __construct(string $path = '/', ?string $view = null)
    {
        $this->path = URLGenerator::sanitizePath($path);
        MediaSecurity::assertNotBlockedPath($this->path);

        $this->initStorage();
        $this->initNavigator($view ?? (string) config('moonshine.media_manager.default_view', 'table'));
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

    private function initNavigator(string $view): void
    {
        $this->navigator = new MediaNavigator(
            $this->path,
            $this->storage,
            $this->indexUrl([]),
            $view,
        );
    }

    /**
     * List files and directories of the current path, directories first.
     *
     * @return list<array<string, mixed>>
     *
     * @throws MediaManagerException when the current path does not exist
     */
    public function ls(): array
    {
        if (! $this->exists()) {
            throw new MediaManagerException(
                __('moonshine-media-manager::media-manager.error.file_not_exists', ['path' => $this->path])
            );
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

    /**
     * Stream the current path as a download response.
     */
    public function download(): StreamedResponse
    {
        return $this->storage->download($this->path);
    }

    /**
     * Delete files and/or directories by paths (bulk supported).
     *
     * @param  list<string>  $paths  sanitized before touching storage
     * @return bool true when at least one item was deleted
     *
     * @throws MediaManagerException when a path is blocked
     */
    public function delete(array $paths): bool
    {
        $deleted = false;

        foreach ($paths as $rawPath) {
            $safePath = URLGenerator::sanitizePath($rawPath);

            // The sanitized traversal path collapses to the disk root —
            // deleting it would wipe the entire storage.
            if ($safePath === '/' || $safePath === '') {
                throw new MediaManagerException(
                    __('moonshine-media-manager::media-manager.error.path_not_allowed')
                );
            }

            MediaSecurity::assertNotBlockedPath($safePath);

            if ($this->storage->fileExists($safePath)) {
                $this->storage->delete($safePath);
                $deleted = true;

                MediaManagerFileDeleted::dispatch($safePath, $this->getDisk());
            } elseif ($this->storage->directoryExists($safePath)) {
                // Collect inner files first: listeners (e.g. conversion
                // cleanups) expect a per-file event, so a folder delete must
                // emit one for every file it contained.
                $innerFiles = $this->storage->allFiles($safePath);

                $this->storage->deleteDirectory($safePath);
                $deleted = true;

                foreach ($innerFiles as $innerFile) {
                    // allFiles() yields root-relative paths; the event contract
                    // uses the absolute-style form like direct file deletes.
                    MediaManagerFileDeleted::dispatch('/'.ltrim($innerFile, '/'), $this->getDisk());
                }

                Log::debug('[MediaManager] folder deleted, per-file events dispatched', [
                    'dir' => $safePath,
                    'files' => count($innerFiles),
                ]);
            } else {
                Log::debug('[MediaManager] delete skipped missing path', ['path' => $safePath]);
            }
        }

        return $deleted;
    }

    /**
     * Move/rename the current path to a new location.
     *
     * @throws MediaManagerException on empty target, same path, blocked path or existing destination
     */
    public function move(string $new): bool
    {
        if (trim($new) === '' || $new === '/') {
            throw new MediaManagerException(
                __('moonshine-media-manager::media-manager.error.enter_name')
            );
        }

        $safeNew = URLGenerator::sanitizePath($new);
        MediaSecurity::assertNotBlockedPath($safeNew);

        if ($safeNew === $this->path) {
            throw new MediaManagerException(
                __('moonshine-media-manager::media-manager.error.same_path')
            );
        }

        $moved = false;

        $this->withLock('move:'.$this->path.'->'.$safeNew, function () use ($safeNew, &$moved): void {
            if ($this->storage->fileExists($safeNew) || $this->storage->directoryExists($safeNew)) {
                throw new MediaManagerException(
                    __('moonshine-media-manager::media-manager.error.already_exists', ['name' => $safeNew])
                );
            }

            $moved = $this->storage->move($this->path, $safeNew);
        });

        return $moved;
    }

    /**
     * Validate and persist uploaded files into the current directory.
     *
     * @param  list<UploadedFile>  $files
     * @param  list<string>  $allowedExtensions  picker-level extension filter (empty = no extra restriction)
     * @param  list<string>  $allowedTypes  picker-level type filter (empty = no extra restriction)
     *
     * @throws MediaManagerException when the list is empty or validation fails
     */
    public function upload(array $files = [], array $allowedExtensions = [], array $allowedTypes = []): bool
    {
        if ($files === []) {
            throw new MediaManagerException(
                __('moonshine-media-manager::media-manager.error.select_file')
            );
        }

        $renameDuplicates = (bool) config('moonshine.media_manager.rename_duplicates', true);
        $validator = $this->validator();

        foreach ($files as $file) {
            $safeName = URLGenerator::sanitizeFileName($file->getClientOriginalName());
            $extension = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));

            $this->assertWithinPickerFilters($extension, $allowedExtensions, $allowedTypes);

            $validator->validateUploadedFile($file, pathinfo($safeName, PATHINFO_EXTENSION));

            $this->atomicUpload($file, $safeName, $renameDuplicates);
        }

        return true;
    }

    /**
     * Enforce picker-level restrictions on uploads: the field's allowed
     * lists (when non-empty) narrow what may be stored on top of the
     * global whitelist.
     *
     * @param  list<string>  $allowedExtensions
     * @param  list<string>  $allowedTypes
     */
    private function assertWithinPickerFilters(string $extension, array $allowedExtensions, array $allowedTypes): void
    {
        if ($allowedExtensions === [] && $allowedTypes === []) {
            return;
        }

        Log::debug('[MediaManager] enforcing picker upload filters', [
            'extensions' => $allowedExtensions,
            'types' => $allowedTypes,
        ]);

        if ($allowedExtensions !== []) {
            $extensionsLower = array_map('strtolower', $allowedExtensions);

            if ($extension === '' || ! in_array($extension, $extensionsLower, true)) {
                throw new MediaManagerException(
                    __('moonshine-media-manager::media-manager.error.file_extension_not_allowed', ['ext' => $extension])
                );
            }
        }

        if ($allowedTypes !== []) {
            $typesLower = array_map('strtolower', $allowedTypes);
            $type = MediaNavigator::typeForExtension($extension);

            if (! in_array($type, $typesLower, true)) {
                throw new MediaManagerException(
                    __('moonshine-media-manager::media-manager.error.file_type_not_allowed', ['type' => $type])
                );
            }
        }
    }

    /**
     * Replace an existing file with a newly uploaded one (content changes, URL stays).
     *
     * @throws MediaManagerException when the target is missing/blocked or validation fails
     */
    public function replace(string $path, UploadedFile $file): bool
    {
        $safePath = URLGenerator::sanitizePath($path);
        MediaSecurity::assertNotBlockedPath($safePath);

        if (! $this->storage->fileExists($safePath)) {
            throw new MediaManagerException(
                __('moonshine-media-manager::media-manager.error.file_not_exists', ['path' => $safePath])
            );
        }

        $directory = trim(dirname($safePath), '/');
        $filename = basename($safePath);

        $this->validator()->validateUploadedFile($file, pathinfo($filename, PATHINFO_EXTENSION));

        $this->withLock('replace:'.$safePath, function () use ($file, $directory, $filename, $safePath): void {
            $this->storeUploadedFile($file, $directory === '' ? '/' : $directory, $filename, $safePath);

            MediaManagerFileReplaced::dispatch($safePath, $this->getDisk());
        });

        return true;
    }

    /**
     * Create a folder inside the current directory.
     *
     * @throws MediaManagerException on empty name or existing folder
     */
    public function newFolder(string $name): bool
    {
        if (trim($name, " \t\n\r\v.") === '') {
            throw new MediaManagerException(
                __('moonshine-media-manager::media-manager.error.enter_name')
            );
        }

        $safeName = URLGenerator::sanitizeFileName($name);
        $path = rtrim($this->path, '/').'/'.$safeName;

        $created = false;

        $this->withLock('folder:'.$path, function () use ($path, $safeName, &$created): void {
            if ($this->storage->directoryExists($path)) {
                throw new MediaManagerException(
                    __('moonshine-media-manager::media-manager.error.folder_already_exists', ['name' => $safeName])
                );
            }

            $created = $this->storage->makeDirectory($path);
        });

        return $created;
    }

    /**
     * Whether the current path exists on the disk.
     */
    public function exists(): bool
    {
        return $this->storage->exists($this->path);
    }

    /**
     * Name of the configured storage disk.
     */
    public function getDisk(): string
    {
        return (string) config('moonshine.media_manager.disk', 'public');
    }

    /**
     * Route map used by the frontend.
     *
     * @return array<string, string>
     */
    public function urls(): array
    {
        return [
            'path' => $this->path,
            'index' => route('moonshine.media.manager.index'),
            'page' => $this->indexUrl(),
            'move' => route('moonshine.media.manager.move'),
            'delete' => route('moonshine.media.manager.delete'),
            'upload' => route('moonshine.media.manager.upload'),
            'replace' => route('moonshine.media.manager.replace'),
            'new-folder' => route('moonshine.media.manager.new.folder'),
        ];
    }

    /**
     * Breadcrumb navigation for the current path.
     *
     * @return array<string, string>
     */
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

    /**
     * Build the upload validator from the package configuration.
     */
    private function validator(): MediaValidator
    {
        $allowed = array_filter(explode(',', (string) config('moonshine.media_manager.allowed_ext', '')));

        return MediaValidator::fromConfig(
            $allowed,
            (int) config('moonshine.media_manager.max_file_size', 10 * 1024 * 1024),
        );
    }

    /**
     * Persist an uploaded file, routing SVG documents through the sanitizer
     * so hostile markup never reaches the public disk.
     */
    private function storeUploadedFile(UploadedFile $file, string $directory, string $filename, string $fullPath): void
    {
        if (SvgSanitizer::appliesTo(pathinfo($filename, PATHINFO_EXTENSION))) {
            $this->storage->put($fullPath, SvgSanitizer::sanitize((string) $file->getContent()));

            return;
        }

        $this->storage->putFileAs($directory, $file, $filename);
    }

    /**
     * Upload under a per-path+filename lock so concurrent uploads of the same
     * name can't both pass uniqueName() and overwrite each other.
     */
    private function atomicUpload(UploadedFile $file, string $safeName, bool $renameDuplicates): void
    {
        $this->withLock('upload:'.$this->path.'/'.$safeName, function () use ($file, $safeName, $renameDuplicates): void {
            $finalName = $renameDuplicates ? $this->uniqueName($safeName) : $safeName;
            $path = rtrim($this->path, '/').'/'.$finalName;

            $this->storeUploadedFile($file, $this->path, $finalName, $path);

            MediaManagerFileUploaded::dispatch($path, $this->getDisk());
        });
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

    /**
     * Run an operation under an atomic cache lock, closing the TOCTOU window
     * between existence checks and mutations. Degrades gracefully when the
     * configured cache store does not support locks (array/null drivers) or
     * when the lock cannot be acquired in time — the operation then runs
     * unlocked with a warning in the log.
     *
     * @param  Closure(): void  $operation
     */
    private function withLock(string $key, Closure $operation): void
    {
        $lockKey = 'media-manager:'.md5($key);

        try {
            Cache::lock($lockKey, 10)->block(5, $operation);

            return;
        } catch (LockTimeoutException) {
            Log::warning('[MediaManager] lock timeout, executing without lock', ['key' => $key]);
        } catch (\BadMethodCallException) {
            Log::warning('[MediaManager] cache store does not support locks, executing without lock', ['key' => $key]);
        }

        $operation();
    }
}
