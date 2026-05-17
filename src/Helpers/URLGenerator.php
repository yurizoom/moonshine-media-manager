<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use YuriZoom\MoonShineMediaManager\Enums\MediaManagerView as MediaManagerViewEnums;

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

    public static function sanitizeFileName(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[^a-zA-Z0-9._\-\s]/', '', $name);
        $name = preg_replace('/\s+/', '-', $name);

        return $name ?: 'file';
    }
}
