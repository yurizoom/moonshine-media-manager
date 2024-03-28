<?php

declare(strict_types=1);

namespace MoonShine\MediaManager;

use Illuminate\Support\ServiceProvider;
use MoonShine\MediaManager\Pages\MediaManagerPage;
use MoonShine\Menu\MenuItem;
use MoonShine\MoonShine;

class MediaManagerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'moonshine-media-manager');
        $this->loadRoutesFrom(__DIR__.'/../routes/media_manager.php');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'moonshine-media-manager');
        $this->mergeConfigFrom(__DIR__.'/../config/media-manager.php', 'moonshine.media_manager');

        moonshine()
            ->pages([
                new MediaManagerPage(),
            ])
            ->when(
                config('moonshine.composer_viewer.auto_menu'),
                fn (MoonShine $moonshine) => $moonshine->
                vendorsMenu([
                    MenuItem::make(
                        static fn () => __('Media manager'),
                        new MediaManagerPage(),
                    ),
                ])
            );
    }
}
