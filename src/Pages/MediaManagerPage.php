<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Pages;

use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\Layout\Box;
use Symfony\Component\Routing\Attribute\Route;
use YuriZoom\MoonShineMediaManager\MediaManager;

#[Route('media')]
class MediaManagerPage extends Page
{
    public function getTitle(): string
    {
        return __('Media manager');
    }

    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle(),
        ];
    }

    public function components(): array
    {
        $manager = new MediaManager('/');

        return [
            Box::make([
                FlexibleRender::make(
                    view('moonshine-media-manager::manager', [
                        'urls' => $manager->urls(),
                    ])->render(),
                ),
            ]),
        ];
    }
}
