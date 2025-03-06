<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components;

use MoonShine\UI\Components\MoonShineComponent;
use YuriZoom\MoonShineMediaManager\Enums\MediaManagerView as MediaManagerViewEnums;

/**
 * @method static static make(string $view, array $list)
 */
final class MediaManagerView extends MoonShineComponent
{
    public function __construct(
        protected MediaManagerViewEnums $viewType = MediaManagerViewEnums::TABLE,
        protected array $list = [],
    ) {
        parent::__construct();
    }

    public function getView(): string
    {
        return match ($this->viewType) {
            MediaManagerViewEnums::LIST => 'moonshine-media-manager::list',
            default => 'moonshine-media-manager::table',
        };
    }

    protected function viewData(): array
    {
        return [
            'items' => array_map(fn($item) => MediaManagerItem::make($this->viewType, $item), $this->list)
        ];
    }
}
