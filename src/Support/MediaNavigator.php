<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class MediaNavigator
{
    protected array $fileTypes = [
        'image' => 'png|jpe?g|gif|bmp|svg|ico|webp|avif|heic|heif',
        'word' => 'doc|docx|odt|rtf',
        'excel' => 'xls|xlsx|ods|csv',
        'ppt' => 'ppt|pptx|odp',
        'pdf' => 'pdf',
        'archive' => 'zip|rar|7z|tar|gz|bz2|tgz',
        'text' => 'txt|log|md',
        'audio' => 'mp3|wav|flac|ogg|aac|m4a|opus',
        'video' => 'mp4|mkv|avi|mov|wmv|flv|webm|m4v|mpg|mpeg',
        'code' => 'json|xml|yaml|yml|php|js|ts|html|css|scss|vue|jsx|tsx|py|go|rs|java|c|cpp|h|rb|sh',
        'font' => 'woff2?|ttf|otf|eot',
    ];

    public function __construct(
        private readonly string $currentPath,
        private readonly object $storage,
        private readonly string $indexUrl,
        private readonly string $defaultView = 'table',
    ) {
    }

    public function formatFiles(array $files, callable $routeCallback): Collection
    {
        return collect($files)->map(function ($file) use ($routeCallback) {
            $timeRaw = $this->getRawFileChangeTime($file);

            return [
                'download' => $routeCallback('download', compact('file')),
                'path' => $file,
                'preview' => $this->getFilePreview($file),
                'type' => $this->detectFileType($file),
                'isDir' => false,
                'size' => $this->getFilesize($file),
                'sizeBytes' => $this->getRawFilesize($file),
                'link' => $routeCallback('download', compact('file')),
                'url' => $this->cacheBustUrl($this->storage->url($file), $timeRaw),
                'time' => $this->getFileChangeTime($file),
                'timeRaw' => $timeRaw,
            ];
        });
    }

    public function formatDirectories(array $dirs, callable $routeCallback): Collection
    {
        return collect($dirs)->map(function ($dir) use ($routeCallback) {
            $path = '/'.trim($dir, '/');
            $timeRaw = $this->getRawFileChangeTime($dir);

            return [
                'download' => '',
                'path' => $dir,
                'preview' => '',
                'isDir' => true,
                'size' => '',
                'sizeBytes' => $this->getRawFilesize($dir),
                'link' => $routeCallback('index', ['path' => $path, 'view' => $this->defaultView]),
                'url' => $this->cacheBustUrl($this->storage->url($dir), $timeRaw),
                'time' => $this->getFileChangeTime($dir),
                'timeRaw' => $timeRaw,
            ];
        });
    }

    public function navigation(): array
    {
        $folders = array_filter(explode('/', $this->currentPath));

        $path = '';
        $navigation = [$this->getIndexPath('') => __('moonshine-media-manager::media-manager.home')];

        foreach ($folders as $folder) {
            $path = rtrim($path, '/').'/'.$folder;
            $navigation[$this->getIndexPath($path)] = $folder;
        }

        return $navigation;
    }

    private function getFilePreview(string $file): string
    {
        if ($this->detectFileType($file) !== 'image') {
            return '';
        }

        return '<img src="'.e($this->storage->url($file)).'" alt="Attachment"/>';
    }

    private function detectFileType(string $file): string
    {
        $extension = File::extension($file);

        foreach ($this->fileTypes as $type => $regex) {
            if (preg_match("/^($regex)$/i", $extension) !== 0) {
                return $type;
            }
        }

        return 'file';
    }

    private function getFilesize(string $file): string
    {
        try {
            return MediaFormatter::formatBytes($this->storage->size($file));
        } catch (\Throwable) {
            return '—';
        }
    }

    private function getRawFilesize(string $file): int
    {
        try {
            return $this->storage->size($file);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function getFileChangeTime(string $file): string
    {
        try {
            return MediaFormatter::formatTimestamp($this->storage->lastModified($file));
        } catch (\Throwable) {
            return '—';
        }
    }

    private function getRawFileChangeTime(string $file): int
    {
        try {
            return $this->storage->lastModified($file);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function getIndexPath(string $path): string
    {
        return route('moonshine.media.manager.index', [
            'path' => $path,
            'view' => $this->defaultView,
        ]);
    }

    private function cacheBustUrl(string $url, int $version): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'v='.$version;
    }
}