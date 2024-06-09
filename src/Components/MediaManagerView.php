<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components;

use MoonShine\Components\MoonShineComponent;

/**
 * @method static static make(string $view, array $list)
 */
final class MediaManagerView extends MoonShineComponent
{
    public function __construct(protected string $view, protected readonly array $list)
    {
        //
    }

    public function getView(): string
    {
        return match ($this->view) {
            'table' => 'moonshine-media-manager::table',
            'list' => 'moonshine-media-manager::list',
        };
    }

    protected function viewData(): array
    {
        return [
            'items' => array_map(fn($item) => MediaManagerItem::make($this->view, $item), $this->list)
        ];
    }
}
