<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Pages;

use MoonShine\Attributes\Icon;
use MoonShine\Pages\Page;
use Symfony\Component\Routing\Attribute\Route;
use YuriZoom\MoonShineMediaManager\Components\MediaManagerComponent;

#[Icon('heroicons.outline.film')]
#[Route('media')]
class MediaManagerPage extends Page
{
    public function title(): string
    {
        return __('Media manager');
    }

    public function breadcrumbs(): array
    {
        return [
            '#' => $this->title(),
        ];
    }

    public function components(): array
    {
        return [MediaManagerComponent::make()];
    }
}
