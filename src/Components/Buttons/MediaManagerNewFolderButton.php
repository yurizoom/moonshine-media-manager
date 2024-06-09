<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components\Buttons;

use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\FormBuilder;
use MoonShine\Fields\Hidden;
use MoonShine\Fields\Text;

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
                $this->url($data),
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
            ->icon('heroicons.outline.folder')
            ->secondary()
            ->showInLine();
    }
}
