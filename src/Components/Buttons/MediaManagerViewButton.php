<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components\Buttons;

use MoonShine\ActionButtons\ActionButton;
use YuriZoom\MoonShineMediaManager\Hellpers\URLGenerator;

/**
 * @method static static make(string $view)
 */
final class MediaManagerViewButton extends ActionButton
{
    public function __construct(string $view)
    {
        parent::__construct(__(''), URLGenerator::query(url()->current(), ['path' => moonshineRequest()->get('path', '/'), 'view' => $view]));

        $this->icon(match($view) {
            'table' => 'heroicons.outline.list-bullet',
            'list' => 'heroicons.outline.squares-2x2',
        })->showInLine();
    }
}
