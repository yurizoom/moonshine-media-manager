<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components\Buttons;

use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\FormBuilder;
use MoonShine\Decorations\Heading;
use MoonShine\Fields\Hidden;

/**
 * @method static static make(mixed $item = null)
 */
final class MediaManagerDeleteButton extends ActionButton
{
    public function __construct(mixed $item = null)
    {
        parent::__construct('', route('moonshine.media.manager.delete'), $item);

        $this->withConfirm(
            method: 'DELETE',
            formBuilder: fn(FormBuilder $formBuilder, $path) => $formBuilder
                ->fields([
                    Heading::make(__('moonshine-media-manager::media-manager.confirm_message')),
                    Hidden::make('', 'files[]')
                ])
                ->fill([
                    'files[]' => $path
                ])
        )
            ->error()
            ->icon('heroicons.outline.trash')
            ->showInLine();
    }

    public function viewLabel(bool $condition): static
    {
        $this->setLabel($condition ? 'Delete' : '');

        return $this;
    }
}
