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
            Css::make('/vendor/media-manager/media-manager.css'),
            Js::make('/vendor/media-manager/media-manager.js'),
        ];
    }
}