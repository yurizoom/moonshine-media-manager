<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components;

use MoonShine\Components\MoonShineComponent;

/**
 * @method static static make(string $view)
 */
final class MediaManagerQuickJump extends MoonShineComponent
{
    public function __construct(protected string $view)
    {
        //
    }

    public function getView(): string
    {
        return 'moonshine-media-manager::quick_jump';
    }

    protected function viewData(): array
    {
        return [
            'path' => moonshineRequest()->get('path', '/'),
            'view' => $this->view,
        ];
    }
}
