<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Support;

use YuriZoom\MoonShineMediaManager\Exceptions\MediaManagerException;

class MediaSecurity
{
    /**
     * Fallback used when the configuration value is absent or not a list.
     *
     * @var list<string>
     */
    private const DEFAULT_BLOCKED_PATHS = [
        'framework',
        'logs',
    ];

    /**
     * Assert the path does not start with a blocked top-level directory.
     *
     * @throws MediaManagerException when the first path segment is blocked
     */
    public static function assertNotBlockedPath(string $path): void
    {
        $firstSegment = ltrim($path, '/');
        $firstSegment = explode('/', $firstSegment)[0] ?? '';

        foreach (self::blockedPaths() as $blocked) {
            if (strtolower($firstSegment) === strtolower($blocked)) {
                throw new MediaManagerException(
                    __('moonshine-media-manager::media-manager.error.path_not_allowed')
                );
            }
        }
    }

    /**
     * @return list<string>
     */
    private static function blockedPaths(): array
    {
        $configured = config('moonshine.media_manager.blocked_paths', self::DEFAULT_BLOCKED_PATHS);

        if (! is_array($configured)) {
            return self::DEFAULT_BLOCKED_PATHS;
        }

        return array_values(array_filter($configured, 'is_string'));
    }
}