<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Support;

use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\Js;

final class MediaAssets
{
    public static function get(): array
    {
        return [
            Css::make(self::versioned('/vendor/media-manager/media-manager.css')),
            Js::make(self::versioned('/vendor/media-manager/media-manager.js')),
        ];
    }

    /** Append ?v={filemtime} so the browser refetches the file after every republish. */
    private static function versioned(string $relativePath): string
    {
        $absolutePath = public_path(ltrim($relativePath, '/'));
        $mtime = is_file($absolutePath) ? @filemtime($absolutePath) : false;

        return $relativePath.($mtime ? '?v='.$mtime : '');
    }
}
