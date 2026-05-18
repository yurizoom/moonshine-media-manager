<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class MediaNavigator
{
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
            return [
                'download' => $routeCallback('download', compact('file')),
                'path' => $file,
                'preview' => $this->getFilePreview($file),
                'type' => $this->detectFileType($file),
                'isDir' => false,
                'size' => $this->getFilesize($file),
                'link' => $routeCallback('download', compact('file')),
                'url' => $this->storage->url($file),
                'time' => $this->getFileChangeTime($file),
            ];
        });
    }

    public function formatDirectories(array $dirs, callable $routeCallback): Collection
    {
        return collect($dirs)->map(function ($dir) use ($routeCallback) {
            $path = '/'.trim($dir, '/');

            return [
                'download' => '',
                'path' => $dir,
                'preview' => '',
                'isDir' => true,
                'size' => '',
                'link' => $routeCallback('index', ['path' => $path, 'view' => $this->defaultView]),
                'url' => $this->storage->url($dir),
                'time' => $this->getFileChangeTime($dir),
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

    private function detectFileType(string $file): bool|string
    {
        $extension = File::extension($file);

        foreach ($this->fileTypes as $type => $regex) {
            if (preg_match("/^($regex)$/i", $extension) !== 0) {
                return $type;
            }
        }

        return false;
    }

    private function getFilesize(string $file): string
    {
        try {
            return MediaFormatter::formatBytes($this->storage->size($file));
        } catch (\Throwable) {
            return '—';
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

    private function getIndexPath(string $path): string
    {
        return route('moonshine.media.manager.index', [
            'path' => $path,
            'view' => $this->defaultView,
        ]);
    }
}