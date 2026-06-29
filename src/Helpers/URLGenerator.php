<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use YuriZoom\MoonShineMediaManager\Enums\MediaManagerView as MediaManagerViewEnums;
use YuriZoom\MoonShineMediaManager\Exceptions\MediaManagerException;

class URLGenerator
{
    public static function query($path, $query = [], $extra = [], $secure = null): string
    {
        [$path, $existingQueryString] = self::extractQueryString($path);

        parse_str(Str::after($existingQueryString, '?'), $existingQueryArray);

        return rtrim(
            url()->to(
                $path.'?'.Arr::query(
                    array_merge($existingQueryArray, $query)
                ),
                $extra,
                $secure
            ),
            '?'
        );
    }

    protected static function extractQueryString($path): array
    {
        if (($queryPosition = strpos($path, '?')) !== false) {
            return [
                substr($path, 0, $queryPosition),
                substr($path, $queryPosition),
            ];
        }

        return [$path, ''];
    }

    public static function getView(): MediaManagerViewEnums
    {
        return MediaManagerViewEnums::tryFrom(
            moonshineRequest()->get('view', config('moonshine.media_manager.default_view'))
        )
            ?? MediaManagerViewEnums::tryFrom(config('moonshine.media_manager.default_view'))
            ?? MediaManagerViewEnums::TABLE;
    }

    public static function sanitizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $segments = explode('/', $path);
        $safe = [];

        foreach ($segments as $segment) {
            if ($segment === '..' || $segment === '.') {
                continue;
            }

            if ($segment !== '') {
                $safe[] = $segment;
            }
        }

        return '/' . implode('/', $safe);
    }

    /**
     * Dangerous extensions that should never appear in any segment of the filename,
     * even before the final extension (e.g. "shell.php.jpg").
     *
     * @var string[]
     */
    private const DANGEROUS_EXTENSIONS = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'pht', 'phar',
        'htaccess', 'htpasswd',
    ];

    public static function sanitizeFileName(string $name): string
    {
        $name = basename($name);

        // Strip leading dots to prevent hidden/config files (.htaccess, .env, etc.)
        $name = ltrim($name, '.');

        // Remove everything except alphanumeric, dots, underscores, hyphens, spaces
        $name = preg_replace('/[^a-zA-Z0-9._\-\s]/', '', $name);

        // Collapse whitespace to single hyphens
        $name = preg_replace('/\s+/', '-', $name);

        // Collapse consecutive dots to a single dot
        $name = preg_replace('/\.{2,}/', '.', $name);

        // Block dangerous extensions anywhere in the filename
        $segments = explode('.', strtolower($name));
        foreach ($segments as $segment) {
            if (in_array($segment, self::DANGEROUS_EXTENSIONS, true)) {
                throw new MediaManagerException(
                    __('moonshine-media-manager::media-manager.error.file_extension_not_allowed', ['ext' => $segment])
                );
            }
        }

        return $name ?: 'file';
    }
}