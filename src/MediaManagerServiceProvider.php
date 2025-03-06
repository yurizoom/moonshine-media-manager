<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager;

use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Contracts\MenuManager\MenuManagerContract;
use MoonShine\MenuManager\MenuItem;
use YuriZoom\MoonShineMediaManager\Components\Buttons\MediaManagerPreview;
use YuriZoom\MoonShineMediaManager\Pages\MediaManagerPage;

class MediaManagerServiceProvider extends ServiceProvider
{
    public function boot(CoreContract $core, MenuManagerContract $menu): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'moonshine-media-manager');
        $this->loadRoutesFrom(__DIR__ . '/../routes/media_manager.php');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'moonshine-media-manager');
        $this->mergeConfigFrom(__DIR__ . '/../config/media-manager.php', 'moonshine.media_manager');
        $this->loadViewComponentsAs('moonshine-media-manager', [
            'preview' => MediaManagerPreview::class,
        ]);

        $core
            ->pages([
                MediaManagerPage::class,
            ]);

        if (config('moonshine.media_manager.auto_menu')) {
            $menu->add([
                MenuItem::make(
                    __('Media manager'),
                    MediaManagerPage::class,
                ),
            ]);
        }
    }
}
