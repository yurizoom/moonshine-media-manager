<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use MoonShine\MoonShineUI;
use Symfony\Component\HttpFoundation\StreamedResponse;
use YuriZoom\MoonShineMediaManager\Pages\MediaManagerPage;

/**
 * Class MediaManager.
 */
class MediaManager
{
    /**
     * @var string
     */
    protected string $path = '/';

    /**
     * @var Filesystem
     */
    protected Filesystem $storage;

    /**
     * List of allowed extensions.
     *
     * @var string[]
     */
    protected array $allowed = [];

    /**
     * @var array
     */
    protected array $fileTypes = [
        'image' => 'png|jpg|jpeg|tmp|gif',
        'word' => 'doc|docx',
        'ppt' => 'ppt|pptx',
        'pdf' => 'pdf',
        'code' => 'php|js|java|python|ruby|go|c|cpp|sql|m|h|json|html|aspx',
        'zip' => 'zip|tar\.gz|rar|rpm',
        'txt' => 'txt|pac|log|md',
        'audio' => 'mp3|wav|flac|3pg|aa|aac|ape|au|m4a|mpc|ogg',
        'video' => 'mkv|rmvb|flv|mp4|avi|wmv|rm|asf|mpeg',
    ];

    /**
     * MediaManager constructor.
     *
     * @param string $path
     */
    public function __construct(string $path = '/')
    {
        $this->path = $path;

        if (!empty(config('moonshine.media_manager.allowed_ext'))) {
            $this->allowed = explode(',', config('moonshine.media_manager.allowed_ext'));
        }

        $this->initStorage();
    }

    private function initStorage(): void
    {
        $disk = config('moonshine.media_manager.disk');

        $this->storage = Storage::disk($disk);

        if (!$this->storage->getAdapter() instanceof LocalFilesystemAdapter) {
            MoonShineUI::toast(__('moonshine-media-manager::media-manager.error.only_local_storage'), "error");
        }
    }

    public function ls(): array
    {
        if (!$this->exists()) {
            MoonShineUI::toast(__('moonshine-media-manager::media-manager.error.file_not_exists', ['path' => $this->path]), "error");
            return [];
        }

        $files = $this->storage->files($this->path);

        $directories = $this->storage->directories($this->path);

        return $this->formatDirectories($directories)
            ->merge($this->formatFiles($files))
            ->sort(function ($item) {
                return $item['path'];
            })->all();
    }

    public function download(): StreamedResponse
    {
        return $this->storage->download($this->path);
    }

    public function delete($path): bool
    {
        $paths = is_array($path) ? $path : func_get_args();

        foreach ($paths as $path) {
            if ($this->storage->fileExists($path)) {
                $this->storage->delete($path);
            } else {
                $this->storage->deleteDirectory($path);
            }
        }

        return true;
    }

    public function move($new): bool
    {
        return $this->storage->move($this->path, $new);
    }

    /**
     * @param UploadedFile[] $files
     * @return mixed
     */
    public function upload(array $files = []): mixed
    {
        foreach ($files as $file) {
            if ($this->allowed && !in_array($file->getClientOriginalExtension(), $this->allowed)) {
                MoonShineUI::toast(__('moonshine-media-manager::media-manager.error.file_extension_not_allowed', ['ext' => $file->getClientOriginalExtension()]), "error");
            }

            $this->storage->putFileAs($this->path, $file, $file->getClientOriginalName());
        }

        return true;
    }

    public function newFolder($name): bool
    {
        $path = rtrim($this->path, '/') . '/' . trim($name, '/');

        return $this->storage->makeDirectory($path);
    }

    public function exists(): bool
    {
        return $this->storage->exists($this->path);
    }

    /**
     * @return array
     */
    public function urls(): array
    {
        return [
            'path' => $this->path,
            'index' => $this->indexUrl(),
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
                'icon' => '',
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

        $preview = "<a href=\"$url\"><span class=\"file-icon text-aqua\"><i class=\"fa fa-folder\"></i></span></a>";

        $dirs = array_map(function ($dir) use ($preview) {
            return [
                'download' => '',
                'icon' => '',
                'path' => $dir,
                'preview' => str_replace('__path__', $dir, $preview),
                'isDir' => true,
                'size' => '',
                'link' => $this->indexUrl(['path' => '/' . trim($dir, '/'), 'view' => request('view')]),
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

        $path = '';

        $navigation = [$this->indexUrl() => __('moonshine-media-manager::media-manager.home')];

        foreach ($folders as $folder) {
            $path = rtrim($path, '/') . '/' . $folder;

            $navigation[$this->indexUrl(['path' => $path])] = $folder;
        }

        return $navigation;
    }

    public function getFilePreview($file): string
    {
        return ($this->detectFileType($file) == 'image')
            ? '<img src="' . $this->storage->url($file) . '" alt="Attachment"/>'
            : '';
    }

    protected function detectFileType($file): bool|string
    {
        $extension = File::extension($file);

        foreach ($this->fileTypes as $type => $regex) {
            if (preg_match("/^($regex)$/i", $extension) !== 0) {
                return $type;
            }
        }

        return false;
    }

    public function getFilesize($file): string
    {
        $bytes = $this->storage->size($file);

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFileChangeTime($file): string
    {
        $time = $this->storage->lastModified($file);

        return date('Y-m-d H:i:s', $time);
    }

    protected function indexUrl(array $params = []): string
    {
        return MediaManagerPage::make()->route($params);
    }
}
