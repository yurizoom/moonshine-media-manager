<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Pages;

use MoonShine\Laravel\Components\Fragment;
use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\Breadcrumbs;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Divider;
use Symfony\Component\Routing\Attribute\Route;
use YuriZoom\MoonShineMediaManager\Components\Buttons\MediaManagerNewFolderButton;
use YuriZoom\MoonShineMediaManager\Components\Buttons\MediaManagerRefreshButton;
use YuriZoom\MoonShineMediaManager\Components\Buttons\MediaManagerUploadButton;
use YuriZoom\MoonShineMediaManager\Components\Buttons\MediaManagerViewButton;
use YuriZoom\MoonShineMediaManager\Components\MediaManagerQuickJump;
use YuriZoom\MoonShineMediaManager\Components\MediaManagerView;
use YuriZoom\MoonShineMediaManager\Enums\MediaManagerView as MediaManagerViewEnums;
use YuriZoom\MoonShineMediaManager\Helpers\URLGenerator;
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
        $path = moonshineRequest()->get('path', '/');
        $view = URLGenerator::getView();

        $manager = new MediaManager($path ?? '/');

        return [
            Box::make([
                Fragment::make([
                    MediaManagerRefreshButton::make(),
                    MediaManagerUploadButton::make(),
                    MediaManagerNewFolderButton::make(),
                    MediaManagerViewButton::make(MediaManagerViewEnums::TABLE),
                    MediaManagerViewButton::make(MediaManagerViewEnums::LIST),
                ]),
                Fragment::make([
                    MediaManagerQuickJump::make($view),
                ])->style('margin-top: 0')
            ])->class('flex items-center justify-between'),
            Divider::make(),
            Box::make([
                Breadcrumbs::make($manager->navigation()),
                MediaManagerView::make($view, $manager->ls()),
            ]),

        ];
    }
}
