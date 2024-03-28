<?php

declare(strict_types=1);

namespace MoonShine\MediaManager\Components;

use MoonShine\Components\MoonShineComponent;
use MoonShine\MediaManager\MediaManager;

/**
 * @method static static make()
 */
final class MediaManagerComponent extends MoonShineComponent
{
    public function __construct()
    {
        //
    }

    public function getView(): string
    {
        return 'moonshine-media-manager::'.moonshineRequest()->get('view', 'table');
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
