<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components;

use MoonShine\Components\MoonShineComponent;
use YuriZoom\MoonShineMediaManager\Components\Buttons\MediaManagerDeleteButton;
use YuriZoom\MoonShineMediaManager\Components\Buttons\MediaManagerDownloadButton;
use YuriZoom\MoonShineMediaManager\Components\Buttons\MediaManagerRenameButton;
use YuriZoom\MoonShineMediaManager\Components\Buttons\MediaManagerUrlButton;

/**
 * @method static static make(string $view, array $item)
 */
final class MediaManagerItem extends MoonShineComponent
{
    public function __construct(protected string $view, protected readonly array $items)
    {
    }

    public function getView(): string
    {
        return match ($this->view) {
            'table' => 'moonshine-media-manager::table_row',
            'list' => 'moonshine-media-manager::list_item',
        };
    }

    protected function viewData(): array
    {
        return [
            'items' => $this->items,
            'actions' => [
                MediaManagerDownloadButton::make($this->items['download'])->canSee(fn() => !$this->items['isDir'])->viewLabel($this->view == 'list'),
                MediaManagerUrlButton::make($this->items['url'])->viewLabel($this->view == 'list'),
                MediaManagerRenameButton::make($this->items['path'])->viewLabel($this->view == 'list'),
                MediaManagerDeleteButton::make($this->items['path'])->viewLabel($this->view == 'list'),
            ]
        ];
    }
}
