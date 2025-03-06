<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components\Buttons;

use MoonShine\UI\Components\MoonShineComponent;

/**
 * @method static static make(string $url)
 */
final class MediaManagerUrlButton extends MoonShineComponent
{
    protected string $label = '';

    public function __construct(protected string $url)
    {
        parent::__construct();
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
            'class' => $this->getAttributes()->get('class'),
        ];
    }

    public function viewLabel(bool $condition): static
    {
        $this->label = $condition ? 'Url' : '';

        return $this;
    }
}
