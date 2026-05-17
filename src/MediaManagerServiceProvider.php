<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Contracts\MenuManager\MenuManagerContract;
use MoonShine\MenuManager\MenuItem;
use YuriZoom\MoonShineMediaManager\Contracts\MediaManagerRegistryInterface;
use YuriZoom\MoonShineMediaManager\Pages\MediaManagerPage;
use YuriZoom\MoonShineMediaManager\Support\MediaManagerRegistry;

class MediaManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            MediaManagerRegistryInterface::class,
            MediaManagerRegistry::class,
        );
    }

    public function boot(CoreContract $core, MenuManagerContract $menu): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'moonshine-media-manager');
        $this->loadRoutesFrom(__DIR__.'/../routes/media_manager.php');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'moonshine-media-manager');
        $this->mergeConfigFrom(__DIR__.'/../config/media-manager.php', 'moonshine.media_manager');

        $this->publishes([
            __DIR__.'/../dist/media-manager.js' => public_path('vendor/media-manager/media-manager.js'),
            __DIR__.'/../dist/media-manager.css' => public_path('vendor/media-manager/media-manager.css'),
        ], 'media-manager-assets');

        $this->registerViewComposer();

        $core->pages([
            MediaManagerPage::class,
        ]);

        if (config('moonshine.media_manager.auto_menu')) {
            $menu->add([
                MenuItem::make(MediaManagerPage::class),
            ]);
        }
    }

    private function registerViewComposer(): void
    {
        $views = [
            'moonshine-media-manager::manager',
            'moonshine-media-manager::components.media-manager-offcanvas',
            'moonshine-media-manager::partials.browser-toolbar',
            'moonshine-media-manager::partials.browser-table',
            'moonshine-media-manager::partials.browser-list',
        ];

        View::composer($views, function (\Illuminate\View\View $view): void {
            $registry = $this->app->make(MediaManagerRegistryInterface::class);

            $view->with('mmFileActions', $registry->getFileActions());
            $view->with('mmToolbarActions', $registry->getToolbarActions());
        });
    }
}
