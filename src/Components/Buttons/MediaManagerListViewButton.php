<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components\Buttons;

use MoonShine\Components\MoonShineComponent;
use YuriZoom\MoonShineMediaManager\MediaManager;

/**
 * @method static static make()
 */
final class MediaManagerListViewButton extends MoonShineComponent
{
    public function __construct()
    {
        //
    }

    public function getView(): string
    {
        return 'moonshine-media-manager::buttons.list_view';
    }

    protected function viewData(): array
    {
        return [
            'path' => moonshineRequest()->get('path', '/')
        ];
    }
}
