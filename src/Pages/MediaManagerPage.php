<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Pages;

use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\Layout\Box;
use YuriZoom\MoonShineMediaManager\MediaManager;
use YuriZoom\MoonShineMediaManager\Support\MediaAssets;

final class MediaManagerPage extends Page
{
    public function getTitle(): string
    {
        return __('moonshine-media-manager::media-manager.title');
    }

    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle(),
        ];
    }

    public function components(): iterable
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

    protected function assets(): array
    {
        return MediaAssets::get();
    }
}
