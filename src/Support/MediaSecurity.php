<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Support;

use YuriZoom\MoonShineMediaManager\Exceptions\MediaManagerException;

class MediaSecurity
{
    private const BLOCKED_PATHS = [
        'framework',
        'logs',
    ];

    public static function assertNotBlockedPath(string $path): void
    {
        $firstSegment = ltrim($path, '/');
        $firstSegment = explode('/', $firstSegment)[0] ?? '';

        foreach (self::BLOCKED_PATHS as $blocked) {
            if (strtolower($firstSegment) === $blocked) {
                throw new MediaManagerException(
                    __('moonshine-media-manager::media-manager.error.path_not_allowed')
                );
            }
        }
    }
}