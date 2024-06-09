<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components\Buttons;

use MoonShine\ActionButtons\ActionButton;

/**
 * @method static static make()
 */
final class MediaManagerRefreshButton extends ActionButton
{
    public function __construct()
    {
        parent::__construct(__(''), '');

        $this->icon('heroicons.outline.arrow-path')->warning()->showInLine();
    }
}
