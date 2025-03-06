<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components\Buttons;

use MoonShine\UI\Components\ActionButton;
use YuriZoom\MoonShineMediaManager\Enums\MediaManagerView as MediaManagerViewEnums;
use YuriZoom\MoonShineMediaManager\Helpers\URLGenerator;

/**
 * @method static static make(MediaManagerViewEnums $view)
 */
final class MediaManagerViewButton extends ActionButton
{
    public function __construct(MediaManagerViewEnums $view)
    {
        parent::__construct(__(''), URLGenerator::query(url()->current(), ['path' => moonshineRequest()->get('path', '/'), 'view' => $view]));

        $this->icon(match($view) {
            MediaManagerViewEnums::LIST => 'squares-2x2',
            default => 'list-bullet',
        })->showInLine();
    }
}
