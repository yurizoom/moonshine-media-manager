<?php

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
}