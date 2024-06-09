<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components\Buttons;

use MoonShine\Components\MoonShineComponent;
use YuriZoom\MoonShineMediaManager\MediaManager;

/**
 * @method static static make(string $url)
 */
final class MediaManagerUrlButton extends MoonShineComponent
{
    protected string $label = '';

    public function __construct(private readonly string $url)
    {
        //
    }

    public function getView(): string
    {
        return 'moonshine-media-manager::buttons.url';
    }

    protected function viewData(): array
    {
        return [
            'label' => $this->label,
            'url' => $this->url,
            'class' => $this->attributes()->get('class'),
        ];
    }

    public function viewLabel(bool $condition): static
    {
        $this->label = $condition ? 'Url' : '';

        return $this;
    }
}
