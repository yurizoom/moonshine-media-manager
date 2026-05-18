<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components;

use MoonShine\UI\Components\MoonShineComponent;
use YuriZoom\MoonShineMediaManager\MediaManager;
use YuriZoom\MoonShineMediaManager\Support\MediaAssets;

final class MediaManagerOffCanvas extends MoonShineComponent
{
    protected string $view = 'moonshine-media-manager::components.media-manager-offcanvas';

    protected function viewData(): array
    {
        $manager = new MediaManager('/');

        return [
            'urls' => $manager->urls(),
        ];
    }

    protected function assets(): array
    {
        return MediaAssets::get();
    }
}
