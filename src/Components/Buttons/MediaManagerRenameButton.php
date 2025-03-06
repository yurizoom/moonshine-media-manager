<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components\Buttons;

use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Fields\Hidden;
use MoonShine\UI\Fields\Text;

/**
 * @method static static make(mixed $item = null)
 */
final class MediaManagerRenameButton extends ActionButton
{
    public function __construct(?DataWrapperContract $item = null)
    {
        parent::__construct('', route('moonshine.media.manager.move'), $item);

        $this->inModal(
            __('moonshine-media-manager::media-manager.rename'),
            fn(mixed $data): string => (string)FormBuilder::make(
                $this->getUrl($data),
            )
                ->fields([
                    Hidden::make('Path', 'path'),
                    Text::make('New'),
                ])
                ->fill([
                    'path' => $data,
                    'new' => $data
                ]),
        )
            ->primary()
            ->icon('pencil')
            ->showInLine();
    }

    public function viewLabel(bool $condition): static
    {
        $this->setLabel($condition ? 'Rename & Move' : '');

        return $this;
    }
}
