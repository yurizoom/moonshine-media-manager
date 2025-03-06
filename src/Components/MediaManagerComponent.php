<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components;

use MoonShine\UI\Components\MoonShineComponent;
use YuriZoom\MoonShineMediaManager\MediaManager;

/**
 * @method static static make()
 */
final class MediaManagerComponent extends MoonShineComponent
{
    public function getView(): string
    {
        return 'moonshine-media-manager::' . moonshineRequest()->get('view', config('moonshine.media_manager.default_view'));
    }

    protected function viewData(): array
    {
        $path = moonshineRequest()->get('path', '/');

        $manager = new MediaManager($path);

        return [
            'list' => $manager->ls(),
            'nav' => $manager->navigation(),
            'url' => $manager->urls(),
        ];
    }
}
