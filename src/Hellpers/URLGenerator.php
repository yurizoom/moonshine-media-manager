<?php

namespace YuriZoom\MoonShineMediaManager\Hellpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class URLGenerator
{
    public static function query($path, $query = [], $extra = [], $secure = null)
    {
        [$path, $existingQueryString] = self::extractQueryString($path);

        parse_str(Str::after($existingQueryString, '?'), $existingQueryArray);

        return rtrim(url()->to($path.'?'.Arr::query(
                array_merge($existingQueryArray, $query)
            ), $extra, $secure), '?');
    }

    protected static function extractQueryString($path)
    {
        if (($queryPosition = strpos($path, '?')) !== false) {
            return [
                substr($path, 0, $queryPosition),
                substr($path, $queryPosition),
            ];
        }

        return [$path, ''];
    }
}