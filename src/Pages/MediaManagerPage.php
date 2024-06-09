<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Pages;

use MoonShine\Attributes\Icon;
use MoonShine\Components\Breadcrumbs;
use MoonShine\Decorations\Block;
use MoonShine\Decorations\Column;
use MoonShine\Decorations\Divider;
use MoonShine\Decorations\Grid;
use MoonShine\Pages\Page;
use Symfony\Component\Routing\Attribute\Route;
use YuriZoom\MoonShineMediaManager\Components\Buttons\MediaManagerNewFolderButton;
use YuriZoom\MoonShineMediaManager\Components\Buttons\MediaManagerRefreshButton;
use YuriZoom\MoonShineMediaManager\Components\Buttons\MediaManagerUploadButton;
use YuriZoom\MoonShineMediaManager\Components\Buttons\MediaManagerViewButton;
use YuriZoom\MoonShineMediaManager\Components\MediaManagerQuickJump;
use YuriZoom\MoonShineMediaManager\Components\MediaManagerView;
use YuriZoom\MoonShineMediaManager\MediaManager;

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
        $path = moonshineRequest()->get('path', '/');
        $view = moonshineRequest()->get('view', config('moonshine.media_manager.default_view'));

        $manager = new MediaManager($path ?? '/');

        return [
            Block::make([
                Grid::make([
                    Column::make([
                        MediaManagerRefreshButton::make(),
                        MediaManagerUploadButton::make(),
                        MediaManagerNewFolderButton::make(),
                        MediaManagerViewButton::make('table'),
                        MediaManagerViewButton::make('list'),
                    ])
                        ->columnSpan(8)
                        ->customAttributes(['class' => 'flex gap-2']),
                    Column::make([
                        MediaManagerQuickJump::make($view),
                    ])
                        ->columnSpan(4)
                ]),
            ]),
            Divider::make(),
            Block::make([
                Breadcrumbs::make($manager->navigation()),
                MediaManagerView::make($view, $manager->ls()),
            ]),

        ];
    }
}
