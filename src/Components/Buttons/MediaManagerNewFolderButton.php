<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components\Buttons;

use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Fields\Hidden;
use MoonShine\UI\Fields\Text;

/**
 * @method static static make()
 */
final class MediaManagerNewFolderButton extends ActionButton
{
    public function __construct()
    {
        parent::__construct(__('moonshine-media-manager::media-manager.new_folder'), route('moonshine.media.manager.new.folder'));

        $this->inModal(
            __('New folder'),
            fn(mixed $data): string => (string)FormBuilder::make(
                $this->getUrl($data),
            )
                ->fields([
                    Hidden::make('dir'),
                    Text::make('Name'),
                ])
                ->fill([
                    'dir' => moonshineRequest()->get('path', '/'),
                ])
                ->submit(__('Submit')),
        )
            ->icon('folder')
            ->secondary()
            ->showInLine();
    }
}
