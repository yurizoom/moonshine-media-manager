<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components\Buttons;

use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Support\Enums\HttpMethod;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Fields\Hidden;

/**
 * @method static static make(mixed $item = null)
 */
final class MediaManagerDeleteButton extends ActionButton
{
    public function __construct(?DataWrapperContract $item = null)
    {
        parent::__construct('', route('moonshine.media.manager.delete'), $item);

        $this->withConfirm(
            method: HttpMethod::DELETE,
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
            ->icon('trash')
            ->showInLine();
    }

    public function viewLabel(bool $condition): MediaManagerDeleteButton
    {
        $this->setLabel($condition ? 'Delete' : '');

        return $this;
    }
}
