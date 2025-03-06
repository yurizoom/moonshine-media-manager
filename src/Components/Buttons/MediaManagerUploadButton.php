<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components\Buttons;

use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Fields\File;
use MoonShine\UI\Fields\Hidden;

/**
 * @method static static make()
 */
final class MediaManagerUploadButton extends ActionButton
{
    public function __construct()
    {
        parent::__construct(__('moonshine-media-manager::media-manager.upload'), route('moonshine.media.manager.upload'));

        $this->success()
            ->inModal(
                __('Upload'),
                fn(mixed $data): FormBuilder => FormBuilder::make(
                    $this->getUrl($data),
                )
                    ->fields([
                        Hidden::make('dir'),
                        File::make(column: 'files')->multiple()->required(),
                    ])
                    ->fill([
                        'dir' => moonshineRequest()->get('path', '/'),
                    ])
                    ->submit(__('Submit')),
            )
            ->icon('cloud-arrow-up')
            ->showInLine();
    }
}
