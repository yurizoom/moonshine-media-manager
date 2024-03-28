<?php

declare(strict_types=1);

namespace MoonShine\MediaManager\Pages;

use MoonShine\Attributes\Icon;
use MoonShine\MediaManager\Components\MediaManagerComponent;
use MoonShine\Pages\Page;
use Symfony\Component\Routing\Attribute\Route;

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
