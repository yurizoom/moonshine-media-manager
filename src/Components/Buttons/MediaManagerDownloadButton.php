<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components\Buttons;

use MoonShine\UI\Components\ActionButton;

/**
 * @method static static make(string $path)
 */
final class MediaManagerDownloadButton extends ActionButton
{
    public function __construct(readonly string $path)
    {
        parent::__construct(__(''), $path);

        $this->icon('cloud-arrow-down')->success()->blank()->showInLine();
    }

    public function viewLabel(bool $condition): MediaManagerDownloadButton
    {
        $this->setLabel($condition ? 'Download' : '');

        return $this;
    }
}
