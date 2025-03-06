<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components\Buttons;

use MoonShine\UI\Components\MoonShineComponent;

/**
 * @method static static make()
 */
final class MediaManagerPreview extends MoonShineComponent
{
    public function getView(): string
    {
        return 'moonshine-media-manager::preview';
    }

    protected function viewData(): array
    {
        return [];
    }
}
