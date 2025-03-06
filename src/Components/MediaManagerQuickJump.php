<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components;

use MoonShine\UI\Components\MoonShineComponent;
use YuriZoom\MoonShineMediaManager\Enums\MediaManagerView as MediaManagerViewEnums;

/**
 * @method static static make(MediaManagerViewEnums $viewType)
 */
final class MediaManagerQuickJump extends MoonShineComponent
{
    public function __construct(protected MediaManagerViewEnums $viewType)
    {
        parent::__construct();
    }

    public function getView(): string
    {
        return 'moonshine-media-manager::quick_jump';
    }

    protected function viewData(): array
    {
        return [
            'path' => moonshineRequest()->get('path', '/'),
            'view' => $this->viewType->value,
        ];
    }
}
