<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components;

use MoonShine\UI\Components\MoonShineComponent;
use YuriZoom\MoonShineMediaManager\MediaManager;
use YuriZoom\MoonShineMediaManager\Support\MediaAssets;

final class MediaManagerOffCanvas extends MoonShineComponent
{
    protected string $view = 'moonshine-media-manager::components.media-manager-offcanvas';

    /** @var array<string, string>|null Routes only — constant per process; the path entry is refreshed by the first loadFiles response. */
    private static ?array $urlsCache = null;

    protected function viewData(): array
    {
        self::$urlsCache ??= (new MediaManager('/'))->urls();

        return [
            'urls' => self::$urlsCache,
        ];
    }

    protected function assets(): array
    {
        return MediaAssets::get();
    }
}
