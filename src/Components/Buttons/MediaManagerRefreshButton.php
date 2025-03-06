<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components\Buttons;

use MoonShine\UI\Components\ActionButton;

/**
 * @method static static make()
 */
final class MediaManagerRefreshButton extends ActionButton
{
    public function __construct()
    {
        parent::__construct('', '');

        $this->icon('arrow-path')->warning()->showInLine();
    }
}
