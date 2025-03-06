<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components;

use MoonShine\Core\TypeCasts\MixedDataWrapper;
use MoonShine\UI\Components\MoonShineComponent;
use YuriZoom\MoonShineMediaManager\Components\Buttons\MediaManagerDeleteButton;
use YuriZoom\MoonShineMediaManager\Components\Buttons\MediaManagerDownloadButton;
use YuriZoom\MoonShineMediaManager\Components\Buttons\MediaManagerRenameButton;
use YuriZoom\MoonShineMediaManager\Components\Buttons\MediaManagerUrlButton;
use YuriZoom\MoonShineMediaManager\Enums\MediaManagerView as MediaManagerViewEnums;

/**
 * @method static static make(MediaManagerViewEnums $viewType, array $items)
 */
final class MediaManagerItem extends MoonShineComponent
{
    public function __construct(protected MediaManagerViewEnums $viewType = MediaManagerViewEnums::TABLE, protected array $items = [])
    {
        parent::__construct();
    }

    public function getView(): string
    {
        return match ($this->viewType) {
            MediaManagerViewEnums::LIST => 'moonshine-media-manager::list_item',
            default => 'moonshine-media-manager::table_row',
        };
    }

    protected function viewData(): array
    {
        return [
            'items' => $this->items,
            'actions' => [
                MediaManagerDownloadButton::make($this->items['download'])->canSee(fn() => !$this->items['isDir'])->viewLabel($this->viewType == MediaManagerViewEnums::LIST),
                MediaManagerUrlButton::make($this->items['url'])->viewLabel($this->viewType == MediaManagerViewEnums::LIST),
                MediaManagerRenameButton::make(new MixedDataWrapper($this->items['path']))->viewLabel($this->viewType == MediaManagerViewEnums::LIST),
                MediaManagerDeleteButton::make(new MixedDataWrapper($this->items['path']))->viewLabel($this->viewType == MediaManagerViewEnums::LIST),
            ]
        ];
    }
}
