<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use MoonShine\Support\Enums\ToastType;
use Symfony\Component\HttpFoundation\StreamedResponse;
use YuriZoom\MoonShineMediaManager\Events\MediaManagerFileDeleted;
use YuriZoom\MoonShineMediaManager\Events\MediaManagerFileUploaded;
use YuriZoom\MoonShineMediaManager\Helpers\URLGenerator;
use YuriZoom\MoonShineMediaManager\Pages\MediaManagerPage;

class MediaManager
{
    protected string $path = '/';

    protected Filesystem $storage;

    /** @var string[] */
    protected array $allowed = [];

    /**
     * Paths that should never be accessible through the media manager,
     * even if the disk root is broad (e.g. `local` disk = storage/app/).
     *
     * @var string[]
     */
    protected const BLOCKED_PATHS = [
        'framework',
        'logs',
    ];

    /**
     * Mapping of allowed file extensions to their expected MIME types.
     * Used to verify that the actual file content matches the claimed extension.
     *
     * @var array<string, string[]>
     */
    protected const EXTENSION_MIME_MAP = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'avif' => ['image/avif'],
        'bmp' => ['image/bmp'],
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'ppt' => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        'rar' => ['application/x-rar-compressed'],
        'tar' => ['application/x-tar'],
        'gz' => ['application/gzip', 'application/x-gzip'],
        'txt' => ['text/plain'],
        'csv' => ['text/csv', 'text/plain'],
        'mp3' => ['audio/mpeg'],
        'wav' => ['audio/wav'],
        'ogg' => ['audio/ogg'],
        'mp4' => ['video/mp4'],
        'avi' => ['video/x-msvideo'],
        'mov' => ['video/quicktime'],
        'mkv' => ['video/x-matroska'],
    ];

    protected array $fileTypes = [
        'image' => 'png|jpg|jpeg|tmp|gif',
        'word' => 'doc|docx',
        'ppt' => 'ppt|pptx',
        'pdf' => 'pdf',
        'zip' => 'zip|tar\.gz|rar|rpm',
        'txt' => 'txt|pac|log|md',
        'audio' => 'mp3|wav|flac|3pg|aa|aac|ape|au|m4a|mpc|ogg',
        'video' => 'mkv|rmvb|flv|mp4|avi|wmv|rm|asf|mpeg',
    ];

    public function __construct(string $path = '/')
    {
        $this->path = URLGenerator::sanitizePath($path);

        $this->assertNotBlockedPath($this->path);

        if (! empty(config('moonshine.media_manager.allowed_ext'))) {
            $this->allowed = explode(',', config('moonshine.media_manager.allowed_ext'));
        }

        $this->initStorage();
    }

    private function initStorage(): void
    {
        $disk = config('moonshine.media_manager.disk');

        $this->storage = Storage::disk($disk);

        if (! $this->storage->getAdapter() instanceof LocalFilesystemAdapter) {
            toast(__('moonshine-media-manager::media-manager.error.only_local_storage'), ToastType::ERROR);
        }
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

        return $this->formatDirectories($directories)
            ->merge($this->formatFiles($files))
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
            $this->assertNotBlockedPath($safePath);

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
        $this->assertNotBlockedPath($safeNew);

        return $this->storage->move($this->path, $safeNew);
    }

    /**
     * @param  UploadedFile[]  $files
     */
    public function upload(array $files = []): bool
    {
        $maxFileSize = config('moonshine.media_manager.max_file_size', 10 * 1024 * 1024);

        foreach ($files as $file) {
            $this->validateUploadedFile($file, $maxFileSize);

            $safeName = URLGenerator::sanitizeFileName($file->getClientOriginalName());
            $path = rtrim($this->path, '/').'/'.$safeName;
            $this->storage->putFileAs($this->path, $file, $safeName);
            if (class_exists(MediaManagerFileUploaded::class)) {
                MediaManagerFileUploaded::dispatch($path, $this->getDisk());
            }
        }

        return true;
    }

    /**
     * Validate an uploaded file for extension, MIME type, and size.
     *
     * @throws \RuntimeException
     */
    protected function validateUploadedFile(UploadedFile $file, int $maxFileSize): void
    {
        // Use guessExtension() which reads actual file content (magic bytes),
        // NOT getClientOriginalExtension() which is purely client-supplied.
        $realExtension = strtolower($file->guessExtension() ?: '');
        $clientExtension = strtolower($file->getClientOriginalExtension());

        // Check against allowed extensions using the real (guessed) extension
        if ($this->allowed) {
            if (! in_array($realExtension, $this->allowed) && ! in_array($clientExtension, $this->allowed)) {
                throw new \RuntimeException(
                    __('moonshine-media-manager::media-manager.error.file_extension_not_allowed', ['ext' => $file->getClientOriginalExtension()])
                );
            }
        }

        // MIME type cross-check: verify actual MIME matches expected for the extension
        $mimeType = $file->getMimeType();

        if ($realExtension && isset(self::EXTENSION_MIME_MAP[$realExtension])) {
            $expectedMimes = self::EXTENSION_MIME_MAP[$realExtension];

            if (! in_array($mimeType, $expectedMimes, true)) {
                throw new \RuntimeException(
                    __('moonshine-media-manager::media-manager.error.mime_type_mismatch', [
                        'ext' => $realExtension,
                        'mime' => $mimeType,
                    ])
                );
            }
        }

        // File size check
        if ($file->getSize() > $maxFileSize) {
            throw new \RuntimeException(
                __('moonshine-media-manager::media-manager.error.file_too_large', [
                    'max' => $this->formatBytes($maxFileSize),
                ])
            );
        }
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

    public function formatFiles($files = []): Collection
    {
        $files = array_map(function ($file) {
            return [
                'download' => route('moonshine.media.manager.download', compact('file')),
                'path' => $file,
                'preview' => $this->getFilePreview($file),
                'type' => $this->detectFileType($file),
                'isDir' => false,
                'size' => $this->getFilesize($file),
                'link' => route('moonshine.media.manager.download', compact('file')),
                'url' => $this->storage->url($file),
                'time' => $this->getFileChangeTime($file),
            ];
        }, $files);

        return collect($files);
    }

    public function formatDirectories($dirs = []): Collection
    {
        $url = $this->indexUrl(['path' => '__path__', 'view' => request('view')]);

        $dirs = array_map(function ($dir) use ($url) {
            return [
                'download' => '',
                'path' => $dir,
                'preview' => '',
                'isDir' => true,
                'size' => '',
                'link' => $this->indexUrl(['path' => '/'.trim($dir, '/'), 'view' => request('view')]),
                'url' => $this->storage->url($dir),
                'time' => $this->getFileChangeTime($dir),
            ];
        }, $dirs);

        return collect($dirs);
    }

    public function navigation(): array
    {
        $folders = explode('/', $this->path);

        $folders = array_filter($folders);

        $view = URLGenerator::getView();

        $path = '';

        $navigation = [$this->indexUrl(['view' => $view->value]) => __('moonshine-media-manager::media-manager.home')];

        foreach ($folders as $folder) {
            $path = rtrim($path, '/').'/'.$folder;

            $navigation[$this->indexUrl(['path' => $path, 'view' => $view->value])] = $folder;
        }

        return $navigation;
    }

    public function getFilePreview(string $file): string
    {
        if ($this->detectFileType($file) !== 'image') {
            return '';
        }

        return '<img src="'.e($this->storage->url($file)).'" alt="Attachment"/>';
    }

    protected function detectFileType(string $file): bool|string
    {
        $extension = File::extension($file);

        foreach ($this->fileTypes as $type => $regex) {
            if (preg_match("/^($regex)$/i", $extension) !== 0) {
                return $type;
            }
        }

        return false;
    }

    public function getFilesize(string $file): string
    {
        try {
            $bytes = $this->storage->size($file);
        } catch (\Throwable) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function getFileChangeTime(string $file): string
    {
        try {
            $time = $this->storage->lastModified($file);
        } catch (\Throwable) {
            return '—';
        }

        return date('Y-m-d H:i:s', $time);
    }

    protected function indexUrl(array $params = []): string
    {
        return app(MediaManagerPage::class)->getRoute($params);
    }

    /**
     * Prevent access to sensitive paths that should never be exposed
     * through the media manager, regardless of disk configuration.
     *
     * @throws \RuntimeException
     */
    protected function assertNotBlockedPath(string $path): void
    {
        $firstSegment = ltrim($path, '/');
        $firstSegment = explode('/', $firstSegment)[0] ?? '';

        foreach (self::BLOCKED_PATHS as $blocked) {
            if (strtolower($firstSegment) === $blocked) {
                throw new \RuntimeException(
                    __('moonshine-media-manager::media-manager.error.path_not_allowed')
                );
            }
        }
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
