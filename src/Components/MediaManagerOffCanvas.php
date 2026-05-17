<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components;

use MoonShine\UI\Components\MoonShineComponent;
use YuriZoom\MoonShineMediaManager\MediaManager;

final class MediaManagerOffCanvas extends MoonShineComponent
{
    public function getView(): string
    {
        return 'moonshine-media-manager::components.media-manager-offcanvas';
    }

    protected function viewData(): array
    {
        $manager = new MediaManager('/');

        return [
            'urls' => $manager->urls(),
        ];
    }
}
