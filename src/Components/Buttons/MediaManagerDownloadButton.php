<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components\Buttons;

use MoonShine\ActionButtons\ActionButton;

/**
 * @method static static make(string $path)
 */
final class MediaManagerDownloadButton extends ActionButton
{
    public function __construct(private readonly string $path)
    {
        parent::__construct(__(''), $path);

        $this->icon('heroicons.outline.cloud-arrow-down')->success()->blank()->showInLine();
    }

    public function viewLabel(bool $condition): static
    {
        $this->setLabel($condition ? 'Download' : '');

        return $this;
    }
}
