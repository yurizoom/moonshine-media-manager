<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components\Buttons;

use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\FormBuilder;
use MoonShine\Fields\Hidden;
use MoonShine\Fields\Text;

/**
 * @method static static make(mixed $item = null)
 */
final class MediaManagerRenameButton extends ActionButton
{
    public function __construct(mixed $item = null)
    {
        parent::__construct('', route('moonshine.media.manager.move'), $item);

        $this->inModal(
            __('Rename / Move'),
            fn(mixed $data): string => (string)FormBuilder::make(
                $this->url($data),
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
            ->icon('heroicons.outline.pencil')
            ->showInLine();
    }

    public function viewLabel(bool $condition): static
    {
        $this->setLabel($condition ? 'Rename & Move' : '');

        return $this;
    }
}
