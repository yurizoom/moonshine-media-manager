<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Helpers;

use Illuminate\Support\Str;
use YuriZoom\MoonShineMediaManager\Enums\MediaManagerView as MediaManagerViewEnums;
use YuriZoom\MoonShineMediaManager\Exceptions\MediaManagerException;

class URLGenerator
{
    /**
     * Resolve the display view from the request, falling back to the
     * configured default and finally to the table view.
     */
    public static function getView(): MediaManagerViewEnums
    {
        return MediaManagerViewEnums::tryFrom(
            moonshineRequest()->get('view', config('moonshine.media_manager.default_view'))
        )
            ?? MediaManagerViewEnums::tryFrom(config('moonshine.media_manager.default_view'))
            ?? MediaManagerViewEnums::TABLE;
    }

    /**
     * Normalize a user-supplied storage path: unify separators, drop
     * traversal (…) and current-dir segments, return an absolute-style path.
     */
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

    /**
     * Normalize a file/folder name: transliterate unicode, strip control and
     * special characters, collapse separators, and reject names carrying
     * dangerous extensions in any segment (e.g. "shell.php.jpg").
     *
     * @throws MediaManagerException when a dangerous extension is detected
     */
    public static function sanitizeFileName(string $name): string
    {
        $name = basename($name);

        // Transliterate unicode (Cyrillic and friends) before filtering, so
        // «Отчёт.jpg» becomes «Otchet.jpg» instead of being stripped to «jpg».
        $name = Str::ascii($name);

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